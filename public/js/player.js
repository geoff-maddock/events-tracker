/* global SC */
/**
 * Pop-out audio player (#2366).
 *
 * The queue comes from /player/queue with this window's own query string
 * (source=upcoming|radar|event|series|entity plus filters[...]); each artist's
 * playable sources are fetched lazily from its sources_url.
 *
 * SoundCloud items are driven through the Widget API (play/pause, seek,
 * auto-advance when a track finishes). Bandcamp's embed has no API, so those
 * items show Bandcamp's own controls and the queue only moves on prev/next.
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

    var STORAGE_KEY = 'arcane-player:v1';
    var SEEK_STEP_MS = 15000;

    var el = {
        source: document.getElementById('player-source'),
        entity: document.getElementById('player-entity'),
        track: document.getElementById('player-track'),
        event: document.querySelector('#player-event a'),
        star: document.getElementById('player-star'),
        frame: document.getElementById('player-frame'),
        bandcampNote: document.getElementById('player-bandcamp-note'),
        seekRow: document.getElementById('player-seek-row'),
        seek: document.getElementById('player-seek'),
        timeCurrent: document.getElementById('player-time-current'),
        timeTotal: document.getElementById('player-time-total'),
        prev: document.getElementById('player-prev'),
        next: document.getElementById('player-next'),
        back: document.getElementById('player-back'),
        forward: document.getElementById('player-forward'),
        toggle: document.getElementById('player-toggle'),
        status: document.getElementById('player-status'),
        queue: document.getElementById('player-queue'),
        empty: document.getElementById('player-empty'),
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
        widget: null,
        provider: null,
        playing: false,
        duration: 0,
        seeking: false,
        mountToken: 0,
    };

    // ---- helpers -------------------------------------------------------

    function currentQuery() {
        return window.location.search.replace(/^\?/, '');
    }

    function formatTime(ms) {
        var total = Math.max(0, Math.floor((ms || 0) / 1000));
        var minutes = Math.floor(total / 60);
        var seconds = total % 60;
        return minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
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
            var match = key.match(/^filters\[([^\]]+)\]/);
            if (match && value !== '') {
                filters.push(match[1].replace(/_/g, ' ') + ': ' + value);
            }
        });
        return 'Upcoming shows' + (filters.length ? ' · ' + filters.join(', ') : '');
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

    // ---- persistence ---------------------------------------------------

    function save() {
        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify({
                query: state.query,
                index: state.index,
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

    function loadQueue(query) {
        setStatus('Building your queue…');
        return fetchJson(config.queueUrl + (query ? '?' + query : ''))
            .then(function (data) {
                return (data.items || []).map(prepare);
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

    function replaceQueue(query, autoplay) {
        state.query = query;
        el.source.textContent = describeSource(query);
        try {
            window.history.replaceState(null, '', window.location.pathname + (query ? '?' + query : ''));
        } catch {
            // history unavailable
        }
        return loadQueue(query).then(function (items) {
            if (items === null) {
                return;
            }
            state.items = items;
            state.index = -1;
            renderQueue();
            if (items.length) {
                setStatus('');
                playIndex(0, 1, autoplay);
            } else {
                clearNowPlaying();
                setStatus('');
            }
            save();
        });
    }

    function addToQueue(query, playNow) {
        return loadQueue(query).then(function (items) {
            if (items === null) {
                return;
            }
            var known = {};
            state.items.forEach(function (item, i) {
                known[item.entity.id] = i;
            });
            var firstIndex = null;
            items.forEach(function (item) {
                if (known[item.entity.id] !== undefined) {
                    if (firstIndex === null) {
                        firstIndex = known[item.entity.id];
                    }
                    return;
                }
                state.items.push(item);
                known[item.entity.id] = state.items.length - 1;
                if (firstIndex === null) {
                    firstIndex = state.items.length - 1;
                }
            });
            renderQueue();
            save();
            if (firstIndex === null) {
                setStatus('Nothing playable to add.');
            } else if (playNow || state.index < 0) {
                playIndex(firstIndex, 1, true);
            } else {
                setStatus('Added to the queue.');
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
            node.querySelector('.player-queue-event').textContent = item.event
                ? [formatDate(item.event.start_at), item.event.name].filter(Boolean).join(' · ')
                : '';
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
        var attempts = 0;

        function attempt(target) {
            if (target < 0 || target >= state.items.length || attempts >= state.items.length) {
                setStatus(direction > 0 ? 'End of the queue.' : 'Start of the queue.');
                return;
            }
            attempts++;
            var item = state.items[target];
            state.index = target;
            refreshQueueItem(target);
            showItem(item);
            save();

            ensureSources(item, target).then(function (sources) {
                if (state.index !== target) {
                    return; // the user moved on while this was loading
                }
                if (!sources.length) {
                    setStatus('No playable tracks for ' + item.entity.name + ', skipping.');
                    attempt(target + (direction || 1));
                    return;
                }
                setStatus('');
                mount(item, sources[0], autoplay);
                prefetch(target + (direction || 1));
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
            el.event.textContent = [formatDate(item.event.start_at), item.event.name].filter(Boolean).join(' · ');
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
                state.widget.unbind(SC.Widget.Events.PLAY_PROGRESS);
                state.widget.unbind(SC.Widget.Events.FINISH);
            } catch {
                // widget already gone
            }
        }
        state.widget = null;
        state.provider = null;
        state.duration = 0;
        setPlaying(false);
        el.frame.textContent = '';
        el.seek.value = '0';
        el.timeCurrent.textContent = '0:00';
        el.timeTotal.textContent = '0:00';
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
        state.provider = source.provider;

        el.track.textContent = source.title || '';

        if (source.provider === 'soundcloud') {
            iframe.title = 'SoundCloud player: ' + item.entity.name;
            iframe.height = '166';
            iframe.src = soundcloudSrc(source.src, autoplay);
            el.frame.appendChild(iframe);
            setControlsFor('soundcloud');
            bindSoundcloud(iframe, item, token);
        } else {
            iframe.title = 'Bandcamp player: ' + item.entity.name;
            iframe.height = '120';
            iframe.src = source.src;
            el.frame.appendChild(iframe);
            setControlsFor('bandcamp');
        }

        updateMediaSession(item, source);
    }

    function setControlsFor(provider) {
        var controllable = provider === 'soundcloud' && typeof SC !== 'undefined';
        el.bandcampNote.hidden = provider !== 'bandcamp';
        el.seekRow.hidden = provider === 'bandcamp';
        [el.toggle, el.back, el.forward].forEach(function (button) {
            button.disabled = !controllable;
            button.setAttribute('aria-disabled', controllable ? 'false' : 'true');
        });
        el.seek.disabled = !controllable;
    }

    function bindSoundcloud(iframe, item, token) {
        if (typeof SC === 'undefined') {
            setStatus('SoundCloud controls are unavailable; use the player above.');
            return;
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
            widget.getDuration(function (duration) {
                state.duration = duration || 0;
                el.timeTotal.textContent = formatTime(state.duration);
            });
        });
        widget.bind(events.PLAY, function () {
            if (token === state.mountToken) {
                setPlaying(true);
                widget.getDuration(function (duration) {
                    state.duration = duration || state.duration;
                    el.timeTotal.textContent = formatTime(state.duration);
                });
            }
        });
        widget.bind(events.PAUSE, function () {
            if (token === state.mountToken) {
                setPlaying(false);
            }
        });
        widget.bind(events.PLAY_PROGRESS, function (progress) {
            if (token !== state.mountToken || state.seeking) {
                return;
            }
            el.timeCurrent.textContent = formatTime(progress.currentPosition);
            el.seek.value = String(Math.round((progress.relativePosition || 0) * 1000));
        });
        // one track per artist, then on to the next artist in the queue
        widget.bind(events.FINISH, function () {
            if (token === state.mountToken) {
                playIndex(state.index + 1, 1, true);
            }
        });
    }

    function setPlaying(playing) {
        state.playing = playing;
        el.toggle.setAttribute('aria-label', playing ? 'Pause' : 'Play');
        el.toggle.querySelector('i').className = playing ? 'bi bi-pause-fill' : 'bi bi-play-fill';
        if ('mediaSession' in navigator) {
            navigator.mediaSession.playbackState = playing ? 'playing' : 'paused';
        }
    }

    function togglePlay() {
        if (state.widget) {
            state.widget.toggle();
        }
    }

    function seekBy(delta) {
        if (!state.widget) {
            return;
        }
        state.widget.getPosition(function (position) {
            var target = Math.max(0, position + delta);
            if (state.duration) {
                target = Math.min(target, state.duration - 500);
            }
            state.widget.seekTo(target);
        });
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
            // the same entity can't appear twice, but keep saved state in step
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
    el.toggle.addEventListener('click', togglePlay);
    el.back.addEventListener('click', function () {
        seekBy(-SEEK_STEP_MS);
    });
    el.forward.addEventListener('click', function () {
        seekBy(SEEK_STEP_MS);
    });
    el.star.addEventListener('click', toggleFollow);

    el.seek.addEventListener('input', function () {
        state.seeking = true;
        el.timeCurrent.textContent = formatTime((parseInt(el.seek.value, 10) / 1000) * state.duration);
    });
    el.seek.addEventListener('change', function () {
        state.seeking = false;
        if (state.widget && state.duration) {
            state.widget.seekTo((parseInt(el.seek.value, 10) / 1000) * state.duration);
        }
    });

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
        } else if (event.key === 'j' || event.key === 'J') {
            seekBy(-SEEK_STEP_MS);
        } else if (event.key === 'l' || event.key === 'L') {
            seekBy(SEEK_STEP_MS);
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
        renderQueue();
        // resume where the listener was, but don't start audio unprompted
        playIndex(Math.min(Math.max(saved.index, 0), state.items.length - 1), 1, false);
    } else {
        replaceQueue(state.query, false);
    }
})();
