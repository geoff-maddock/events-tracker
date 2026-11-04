/* global SC */
/**
 * Pop-out audio player (#2366).
 *
 * The queue comes from /player/queue with this window's own query string
 * (source=upcoming|radar|event|series|entity plus filters[...]), one page at a
 * time: the next page loads when the listener scrolls to the end of the list,
 * or plays past the last artist. Each artist's playable sources are fetched
 * lazily from its sources_url.
 *
 * Playback uses the SoundCloud and Bandcamp players' own controls. SoundCloud
 * items are watched through the Widget API so the queue moves on to the next
 * artist when a track finishes; Bandcamp's embed has no API, so those items
 * move on with next/previous.
 *
 * Other windows talk to this one over BroadcastChannel('arcane-player'):
 *   {type: 'ping'}                         -> replies {type: 'pong'}
 *   {type: 'load' | 'enqueue' | 'play-now', query: 'source=entity&slug=foo'}
 */
(function () {
    'use strict';

    var root = document.getElementById('player');
    if (!root) {
        return;
    }

    var STORAGE_KEY = 'arcane-player:v2';
    // start fetching the next page when this close to the end of the queue
    var PREFETCH_DISTANCE = 2;

    var el = {
        source: document.getElementById('player-source'),
        entity: document.getElementById('player-entity'),
        track: document.getElementById('player-track'),
        event: document.querySelector('#player-event a'),
        star: document.getElementById('player-star'),
        frame: document.getElementById('player-frame'),
        prev: document.getElementById('player-prev'),
        next: document.getElementById('player-next'),
        status: document.getElementById('player-status'),
        queue: document.getElementById('player-queue'),
        queueScroller: document.getElementById('player-queue').closest('section'),
        empty: document.getElementById('player-empty'),
        more: document.getElementById('player-more'),
        moreSentinel: document.getElementById('player-more-sentinel'),
        template: document.getElementById('player-queue-item'),
    };

    var config = {
        queueUrl: root.dataset.queueUrl,
        signedIn: root.dataset.signedIn === '1',
        loginUrl: root.dataset.loginUrl,
        followUrl: root.dataset.followUrl,
        unfollowUrl: root.dataset.unfollowUrl,
        csrf: (document.querySelector('meta[name="csrf-token"]') || {}).content || '',
    };

    var state = {
        query: currentQuery(),
        items: [],
        index: -1,
        // offset of the queue's next page, or null when there's nothing more
        nextOffset: null,
        loadingMore: null,
        widget: null,
        mountToken: 0,
    };

    // ---- helpers -------------------------------------------------------

    function currentQuery() {
        return window.location.search.replace(/^\?/, '');
    }

    function formatDate(iso) {
        if (!iso) {
            return '';
        }
        var date = new Date(iso);
        if (isNaN(date.getTime())) {
            return '';
        }
        return date.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
    }

    // "Sat, Nov 14 · Event name", or "Played Sat, Nov 1 · ..." for a past show
    function describeEvent(event) {
        var date = formatDate(event.start_at);
        if (date && event.past) {
            date = 'Played ' + date;
        }
        return [date, event.name].filter(Boolean).join(' · ');
    }

    function setStatus(message) {
        el.status.textContent = message || '';
    }

    function describeSource(query) {
        var params = new URLSearchParams(query);
        var source = params.get('source') || 'upcoming';
        var labels = { radar: 'Your radar', event: 'Event lineup', series: 'Series', entity: 'Artist' };
        if (labels[source]) {
            return labels[source];
        }
        var filters = [];
        params.forEach(function (value, key) {
            var match = key.match(/^filters\[([^\]]+)\](?:\[([^\]]+)\])?/);
            if (!match || value === '') {
                return;
            }
            if (match[1] === 'start_at' && (match[2] === 'start' || match[2] === 'end')) {
                filters.push((match[2] === 'start' ? 'from ' : 'until ') + value);
            } else {
                filters.push(match[1].replace(/_/g, ' ') + ': ' + value);
            }
        });
        // a filtered queue mirrors its list, so it can include past shows
        return filters.length ? 'Shows · ' + filters.join(', ') : 'Upcoming shows';
    }

    function fetchJson(url) {
        return fetch(url, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        }).then(function (response) {
            if (!response.ok) {
                var error = new Error('HTTP ' + response.status);
                error.status = response.status;
                throw error;
            }
            return response.json();
        });
    }

    function withOffset(query, offset) {
        var params = new URLSearchParams(query);
        params.delete('offset');
        if (offset) {
            params.set('offset', String(offset));
        }
        var string = params.toString();
        return string ? '?' + string : '';
    }

    // ---- persistence ---------------------------------------------------

    function save() {
        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify({
                query: state.query,
                index: state.index,
                nextOffset: state.nextOffset,
                // sources are re-fetched (and served from the server cache) on reload
                items: state.items.map(function (item) {
                    return { entity: item.entity, event: item.event, providers: item.providers, sources_url: item.sources_url };
                }),
            }));
        } catch {
            // storage unavailable: the player still works, it just won't resume
        }
    }

    function restore() {
        try {
            var saved = JSON.parse(window.localStorage.getItem(STORAGE_KEY) || 'null');
            if (saved && saved.query === state.query && Array.isArray(saved.items) && saved.items.length) {
                return saved;
            }
        } catch {
            // ignore unreadable state
        }
        return null;
    }

    // ---- queue ---------------------------------------------------------

    function prepare(item) {
        item.sources = null;
        item.state = 'idle';
        return item;
    }

    // one page of a queue: {items, nextOffset}, or null on failure
    function loadQueue(query, offset) {
        if (!offset) {
            setStatus('Building your queue…');
        }
        return fetchJson(config.queueUrl + withOffset(query, offset))
            .then(function (data) {
                if (!offset) {
                    setStatus('');
                }
                return {
                    items: (data.items || []).map(prepare),
                    nextOffset: typeof data.next_offset === 'number' ? data.next_offset : null,
                };
            })
            .catch(function (error) {
                if (error.status === 401) {
                    setStatus('Sign in to play your radar.');
                } else if (error.status === 404) {
                    setStatus('That list could not be found.');
                } else {
                    setStatus('Could not load the queue. Try again in a moment.');
                }
                return null;
            });
    }

    // add items whose artist isn't queued yet; returns how many were added
    function appendItems(items) {
        var known = {};
        state.items.forEach(function (item) {
            known[item.entity.id] = true;
        });
        var added = 0;
        items.forEach(function (item) {
            if (!known[item.entity.id]) {
                known[item.entity.id] = true;
                state.items.push(item);
                added++;
            }
        });
        return added;
    }

    function replaceQueue(query, autoplay) {
        state.query = query;
        el.source.textContent = describeSource(query);
        try {
            window.history.replaceState(null, '', window.location.pathname + (query ? '?' + query : ''));
        } catch {
            // history unavailable
        }
        return loadQueue(query, 0).then(function (page) {
            if (page === null) {
                return;
            }
            state.items = page.items;
            state.nextOffset = page.nextOffset;
            state.loadingMore = null;
            state.index = -1;
            renderQueue();
            if (state.items.length) {
                playIndex(0, 1, autoplay);
            } else {
                clearNowPlaying();
            }
            save();
        });
    }

    // the next page of the current queue; resolves to how many artists it added
    function loadMore() {
        if (state.nextOffset === null) {
            return Promise.resolve(0);
        }
        if (state.loadingMore) {
            return state.loadingMore;
        }
        var query = state.query;
        el.more.hidden = false;
        state.loadingMore = loadQueue(query, state.nextOffset).then(function (page) {
            el.more.hidden = true;
            state.loadingMore = null;
            if (page === null || query !== state.query) {
                return 0;
            }
            state.nextOffset = page.nextOffset;
            var added = appendItems(page.items);
            renderQueue();
            save();
            // a page of artists already queued (e.g. added from another page): keep going
            if (added === 0 && state.nextOffset !== null) {
                return loadMore();
            }
            return added;
        });
        return state.loadingMore;
    }

    function addToQueue(query, playNow) {
        return loadQueue(query, 0).then(function (page) {
            if (page === null) {
                return;
            }
            var firstIndex = null;
            page.items.forEach(function (item) {
                var existing = state.items.findIndex(function (queued) {
                    return queued.entity.id === item.entity.id;
                });
                if (firstIndex === null && existing !== -1) {
                    firstIndex = existing;
                }
            });
            var before = state.items.length;
            var added = appendItems(page.items);
            if (firstIndex === null && added > 0) {
                firstIndex = before;
            }
            renderQueue();
            save();
            if (firstIndex === null) {
                setStatus('Nothing playable to add.');
            } else if (playNow || state.index < 0) {
                playIndex(firstIndex, 1, true);
            } else if (added === 0) {
                setStatus('Already in the queue.');
            } else {
                setStatus(added === 1 ? 'Added to the queue.' : 'Added ' + added + ' artists to the queue.');
            }
        });
    }

    function renderQueue() {
        el.queue.textContent = '';
        el.empty.hidden = state.items.length > 0;

        state.items.forEach(function (item, i) {
            var node = el.template.content.cloneNode(true);
            var button = node.querySelector('.player-queue-item');
            button.dataset.index = String(i);
            button.dataset.state = item.state;
            button.setAttribute('aria-current', i === state.index ? 'true' : 'false');
            node.querySelector('.player-queue-number').textContent = String(i + 1);
            node.querySelector('.player-queue-name').textContent = item.entity.name;
            node.querySelector('.player-queue-event').textContent = item.event ? describeEvent(item.event) : '';
            node.querySelector('.player-queue-providers').textContent = (item.providers || []).map(function (provider) {
                return provider === 'soundcloud' ? 'SoundCloud' : 'Bandcamp';
            }).join(' / ');
            el.queue.appendChild(node);
        });
    }

    function refreshQueueItem(i) {
        var button = el.queue.querySelector('.player-queue-item[data-index="' + i + '"]');
        if (button && state.items[i]) {
            button.dataset.state = state.items[i].state;
        }
        el.queue.querySelectorAll('.player-queue-item').forEach(function (node) {
            var current = node.dataset.index === String(state.index);
            node.setAttribute('aria-current', current ? 'true' : 'false');
            if (current && typeof node.scrollIntoView === 'function') {
                node.scrollIntoView({ block: 'nearest' });
            }
        });
    }

    function ensureSources(item, i) {
        if (item.sources !== null) {
            return Promise.resolve(item.sources);
        }
        item.state = 'loading';
        refreshQueueItem(i);
        return fetchJson(item.sources_url)
            .then(function (data) {
                item.sources = Array.isArray(data.sources) ? data.sources : [];
            })
            .catch(function () {
                item.sources = [];
            })
            .then(function () {
                item.state = item.sources.length ? 'ready' : 'unavailable';
                refreshQueueItem(i);
                return item.sources;
            });
    }

    // ---- playback ------------------------------------------------------

    function playIndex(i, direction, autoplay) {
        var step = direction || 1;
        var skipped = 0;

        function attempt(target) {
            if (target >= state.items.length && step > 0 && state.nextOffset !== null) {
                // past the end of what's loaded: fetch the next page, then carry on
                loadMore().then(function (added) {
                    if (added > 0) {
                        attempt(target);
                    } else {
                        setStatus('End of the queue.');
                    }
                });
                return;
            }
            if (target < 0 || target >= state.items.length || skipped > state.items.length) {
                setStatus(step > 0 ? 'End of the queue.' : 'Start of the queue.');
                return;
            }
            var item = state.items[target];
            state.index = target;
            refreshQueueItem(target);
            showItem(item);
            save();

            if (step > 0 && target >= state.items.length - PREFETCH_DISTANCE) {
                loadMore();
            }

            ensureSources(item, target).then(function (sources) {
                if (state.index !== target) {
                    return; // the user moved on while this was loading
                }
                if (!sources.length) {
                    skipped++;
                    setStatus('No playable tracks for ' + item.entity.name + ', skipping.');
                    attempt(target + step);
                    return;
                }
                setStatus('');
                mount(item, sources[0], autoplay);
                prefetch(target + step);
            });
        }

        attempt(i);
    }

    function prefetch(i) {
        if (state.items[i]) {
            ensureSources(state.items[i], i);
        }
    }

    function showItem(item) {
        el.entity.textContent = item.entity.name;
        el.entity.href = item.entity.url;
        el.track.textContent = '';
        if (item.event) {
            el.event.textContent = describeEvent(item.event);
            el.event.href = item.event.url;
            el.event.parentElement.hidden = false;
        } else {
            el.event.parentElement.hidden = true;
        }
        updateStar(item);
        document.title = item.entity.name + ' | Player';
    }

    function clearNowPlaying() {
        teardown();
        el.entity.textContent = 'Nothing to play';
        el.entity.removeAttribute('href');
        el.track.textContent = '';
        el.event.parentElement.hidden = true;
    }

    function teardown() {
        if (state.widget && typeof SC !== 'undefined') {
            try {
                state.widget.unbind(SC.Widget.Events.FINISH);
            } catch {
                // widget already gone
            }
        }
        state.widget = null;
        setPlaying(false);
        el.frame.textContent = '';
    }

    function soundcloudSrc(src, autoplay) {
        var url;
        try {
            url = new URL(src);
        } catch {
            return src;
        }
        url.searchParams.set('auto_play', autoplay ? 'true' : 'false');
        url.searchParams.set('visual', 'false');
        url.searchParams.set('show_comments', 'false');
        url.searchParams.set('hide_related', 'true');
        url.searchParams.set('show_teaser', 'false');
        return url.toString();
    }

    function mount(item, source, autoplay) {
        teardown();
        var token = ++state.mountToken;
        var iframe = document.createElement('iframe');
        iframe.setAttribute('allow', 'autoplay; encrypted-media');
        iframe.setAttribute('loading', 'eager');

        el.track.textContent = source.title || '';

        if (source.provider === 'soundcloud') {
            iframe.title = 'SoundCloud player: ' + item.entity.name;
            iframe.height = '166';
            iframe.src = soundcloudSrc(source.src, autoplay);
            el.frame.appendChild(iframe);
            bindSoundcloud(iframe, item, token);
        } else {
            iframe.title = 'Bandcamp player: ' + item.entity.name;
            iframe.height = '120';
            iframe.src = source.src;
            el.frame.appendChild(iframe);
        }

        updateMediaSession(item, source);
    }

    function bindSoundcloud(iframe, item, token) {
        if (typeof SC === 'undefined') {
            return; // widget API blocked: the embed still plays, it just won't auto-advance
        }
        var widget = SC.Widget(iframe);
        state.widget = widget;
        var events = SC.Widget.Events;

        widget.bind(events.READY, function () {
            if (token !== state.mountToken) {
                return;
            }
            widget.getCurrentSound(function (sound) {
                if (sound && sound.title && token === state.mountToken) {
                    el.track.textContent = sound.title;
                    updateMediaSession(item, { title: sound.title, artwork: sound.artwork_url });
                }
            });
        });
        widget.bind(events.PLAY, function () {
            if (token === state.mountToken) {
                setPlaying(true);
            }
        });
        widget.bind(events.PAUSE, function () {
            if (token === state.mountToken) {
                setPlaying(false);
            }
        });
        // one track per artist, then on to the next artist in the queue
        widget.bind(events.FINISH, function () {
            if (token === state.mountToken) {
                playIndex(state.index + 1, 1, true);
            }
        });
    }

    function setPlaying(playing) {
        if ('mediaSession' in navigator) {
            navigator.mediaSession.playbackState = playing ? 'playing' : 'paused';
        }
    }

    // space and OS media keys; SoundCloud only, Bandcamp's embed can't be driven
    function togglePlay() {
        if (state.widget) {
            state.widget.toggle();
        }
    }

    function next() {
        playIndex(state.index + 1, 1, true);
    }

    function prev() {
        playIndex(state.index - 1, -1, true);
    }

    function updateMediaSession(item, source) {
        if (!('mediaSession' in navigator) || typeof window.MediaMetadata === 'undefined') {
            return;
        }
        var metadata = { title: source.title || item.entity.name, artist: item.entity.name, album: item.event ? item.event.name : '' };
        if (source.artwork) {
            metadata.artwork = [{ src: source.artwork }];
        }
        navigator.mediaSession.metadata = new window.MediaMetadata(metadata);
    }

    // ---- follow (star) -------------------------------------------------

    function updateStar(item) {
        var followed = !!(item && item.entity.followed);
        el.star.setAttribute('aria-pressed', followed ? 'true' : 'false');
        el.star.querySelector('i').className = followed ? 'bi bi-star-fill' : 'bi bi-star';
        var label = !config.signedIn
            ? 'Sign in to follow this artist'
            : (followed ? 'Unfollow ' : 'Follow ') + (item ? item.entity.name : 'this artist');
        el.star.setAttribute('aria-label', label);
        el.star.title = label;
    }

    function toggleFollow() {
        var item = state.items[state.index];
        if (!item) {
            return;
        }
        if (!config.signedIn) {
            openInMainWindow(config.loginUrl);
            return;
        }
        var following = item.entity.followed;
        var url = (following ? config.unfollowUrl : config.followUrl).replace('__ID__', encodeURIComponent(item.entity.id));
        el.star.disabled = true;
        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': config.csrf,
            },
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            item.entity.followed = !following;
            save();
            updateStar(item);
            setStatus((following ? 'Unfollowed ' : 'Following ') + item.entity.name + '.');
        }).catch(function () {
            setStatus('Could not update follow. Try again.');
        }).then(function () {
            el.star.disabled = false;
        });
    }

    // ---- links open in the main site window ------------------------------

    function openInMainWindow(href) {
        var opener = window.opener;
        if (opener && !opener.closed) {
            try {
                opener.location.href = href;
                opener.focus();
                return;
            } catch {
                // cross-origin or blocked: fall through to a new tab
            }
        }
        window.open(href, '_blank', 'noopener');
    }

    document.addEventListener('click', function (event) {
        var link = event.target.closest('[data-opener-link]');
        if (link && link.getAttribute('href') && link.getAttribute('href') !== '#') {
            event.preventDefault();
            openInMainWindow(link.href);
        }
    });

    // ---- wiring --------------------------------------------------------

    el.queue.addEventListener('click', function (event) {
        var button = event.target.closest('.player-queue-item');
        if (button) {
            playIndex(parseInt(button.dataset.index, 10), 1, true);
        }
    });
    el.prev.addEventListener('click', prev);
    el.next.addEventListener('click', next);
    el.star.addEventListener('click', toggleFollow);

    // scrolling to the bottom of the list loads the next page
    if ('IntersectionObserver' in window) {
        new IntersectionObserver(function (entries) {
            if (entries.some(function (entry) { return entry.isIntersecting; }) && state.items.length) {
                loadMore();
            }
        }, { root: el.queueScroller, rootMargin: '0px 0px 120px 0px' }).observe(el.moreSentinel);
    } else {
        el.queueScroller.addEventListener('scroll', function () {
            var scroller = el.queueScroller;
            if (scroller.scrollTop + scroller.clientHeight >= scroller.scrollHeight - 120) {
                loadMore();
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        var tag = (event.target.tagName || '').toLowerCase();
        if (event.altKey || event.ctrlKey || event.metaKey || tag === 'input' || tag === 'textarea' || tag === 'select') {
            return;
        }
        // let Enter/Space activate a focused button normally
        if (event.key === ' ' && tag !== 'button') {
            event.preventDefault();
            togglePlay();
        } else if (event.key === 'ArrowRight') {
            event.preventDefault();
            next();
        } else if (event.key === 'ArrowLeft') {
            event.preventDefault();
            prev();
        }
    });

    if ('mediaSession' in navigator) {
        try {
            navigator.mediaSession.setActionHandler('nexttrack', next);
            navigator.mediaSession.setActionHandler('previoustrack', prev);
            navigator.mediaSession.setActionHandler('play', togglePlay);
            navigator.mediaSession.setActionHandler('pause', togglePlay);
        } catch {
            // action not supported
        }
    }

    if ('BroadcastChannel' in window) {
        var channel = new BroadcastChannel('arcane-player');
        channel.addEventListener('message', function (event) {
            var message = event.data || {};
            var query = typeof message.query === 'string' ? message.query.replace(/^\?/, '') : '';
            if (message.type === 'ping') {
                channel.postMessage({ type: 'pong' });
            } else if (message.type === 'load') {
                replaceQueue(query, true);
                window.focus();
            } else if (message.type === 'enqueue') {
                addToQueue(query, false);
            } else if (message.type === 'play-now') {
                addToQueue(query, true);
                window.focus();
            }
        });
    }

    // ---- boot ----------------------------------------------------------

    el.source.textContent = describeSource(state.query);
    updateStar(null);

    var saved = restore();
    if (saved) {
        state.items = saved.items.map(prepare);
        state.nextOffset = typeof saved.nextOffset === 'number' ? saved.nextOffset : null;
        renderQueue();
        // resume where the listener was, but don't start audio unprompted
        playIndex(Math.min(Math.max(saved.index, 0), state.items.length - 1), 1, false);
    } else {
        replaceQueue(state.query, false);
    }
})();
