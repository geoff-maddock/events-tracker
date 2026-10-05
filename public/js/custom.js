// App - some basic app functions for interactions
var App = (function () {
    var init = function () {
        this.initTooltip();
        this.setupConfirm();
        this.setupDeleteConfirm();
        this.setupControls();
        this.setupLoadingModal();
        this.setupAjaxAction('body');
        this.setupPostLinks();
        $('.auto-submit').autoSubmit();
        this.setupNameToSlug();
        this.loadEmbeds();
    };


    // load embeded audio code with caching support
    var loadEmbeds = function () {
        $('body div.playlist-id').each(function (e) {
            // Hold onto the element itself rather than looking it back up by id:
            // a page can carry more than one placeholder with the same id (e.g. an
            // entity page whose event card and Audio section both key off the same
            // record), and $('#id') would resolve every loader to the first match.
            var $container = $(this);
            var url = $container.attr('data-url');
            var slug = $container.attr('data-slug');
            var resourceType = $container.attr('data-resource-type') || 'events';
            var endpoint = $container.attr('data-endpoint') || 'minimal-embeds';

            // Use EmbedLoader with caching if available and slug is provided
            if (typeof EmbedLoader !== 'undefined' && slug) {
                EmbedLoader.load(resourceType, slug, {
                    endpoint: endpoint,
                    onSuccess: function (embeds) {
                        if (embeds && embeds.length > 0) {
                            // Cards render at most one player. The server caps this too,
                            // but localStorage entries cached before that cap can still
                            // hold several, so trim here as well.
                            var list = endpoint === 'embeds' ? embeds : embeds.slice(0, 1);
                            var html = list.map(function (embed) {
                                return endpoint === 'embeds'
                                    ? '<div class="rounded-md overflow-hidden">' + embed + '</div>'
                                    : embed;
                            }).join('');
                            $container.html(html);
                            $container.removeClass('playlist-id hidden');
                        }
                    },
                    onError: function () {
                    }
                });
            } else if (url) {
                // Fallback to original AJAX behavior for backward compatibility
                $.ajax({
                    url: url
                }).done(function (data) {
                    // load results into the applicable position if data exists
                    if (data.Success && data.Success.trim() !== '') {
                        $container.html(data.Success);
                        $container.removeClass('playlist-id hidden');
                    }
                }).fail(function () {
                });
            }
        });
    };

    var initTooltip = function () {
        // Only initialize tooltips if Bootstrap tooltip function exists
        if (typeof $.fn.tooltip === 'function') {
            $('[data-toggle="tooltip"]').tooltip();
        }
    };

    var setupDeleteConfirm = function () {
        $('button.delete').on('click', function (e) {
            var form = $(this).parents('form');
            var type = $(this).data('type');
            e.preventDefault();
            Swal.fire({
                title: "Are you sure?",
                text: "You will not be able to recover this " + type + "!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, delete it!",
                preConfirm: function () {
                    return new Promise(function (resolve) {
                        setTimeout(function () {
                            resolve()
                        }, 2000)
                    })
                }
            }).then(result => {
                if (result.value) {
                    // handle Confirm button click
                    // result.value will contain `true` or the input value
                    form.submit();
                } else {
                    // handle dismissals
                    // result.dismiss can be 'cancel', 'overlay', 'esc' or 'timer'
                }
            });
        });
    };

    var setupConfirm = function () {
        // confirm clicking on links
        $('a.confirm').on('click', function (e) {
            var link = $(this).attr('href');
            var method = $(this).data('method');
            e.preventDefault();
            var form = null;
            var type = $(this).data('type');
            Swal.fire({
                title: "Are you sure?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Confirm",
                preConfirm: function () {
                    return new Promise(function (resolve) {
                        setTimeout(function () {
                            resolve()
                        }, 1000)
                    })
                }
            }).then(result => {
                if (form !== null) {
                    // form is not null, so submit
                    form.submit();
                } else if (result.value) {
                    // handle Confirm button click; state-changing links are POSTed (#2166)
                    if (method) {
                        postTo(link);
                    } else {
                        window.location.href = link;
                    }
                } else {
                    // handle dismissals
                    // result.dismiss can be 'cancel', 'overlay', 'esc' or 'timer'
                }
            });
        });
        // confirm clicking on buttons
        $('button.confirm').on('click', function (e) {
            var link = $(this).attr('href');
            e.preventDefault();
            var form = $(this).parents('form');
            var type = $(this).data('type');
            Swal.fire({
                title: "Are you sure?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Confirm",
                preConfirm: function () {
                    return new Promise(function (resolve) {
                        setTimeout(function () {
                            resolve()
                        }, 1000)
                    })
                }
            }).then(result => {
                if (form !== null) {
                    // form is not null, so submit
                    form.submit();
                } else if (result.value) {
                    // handle Confirm button click
                    window.location.href = link;
                } else {
                    // handle dismissals
                    // result.dismiss can be 'cancel', 'overlay', 'esc' or 'timer'
                }
            });
        });
    };

    var select2Loading = null;

    var loadSelect2 = function () {
        select2Loading = select2Loading || $.ajax({
            url: 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.full.min.js',
            dataType: 'script',
            cache: true
        });
        return select2Loading;
    };

    var setupSelect2 = function ($elements) {
        $elements.each(function () {
            var $this = $(this);
            $this.select2({
                placeholder: $this.data('placeholder'),
                tags: $this.data('tags'),
                allowClear: true,
                width: '100%',
                theme: $this.data('theme') || 'tailwind',
            });
        });
    };

    var setupControls = function (target) {
        if (typeof target === 'undefined' || !target) {
            var target = 'body';
        }

        // select2; the layout only includes it on pages that declare select2.include,
        // so fetch it here if this page has a .select2 field without it (#2175)
        var $select2 = $(target + ' .select2');
        if ($select2.length && !$.fn.select2) {
            loadSelect2().then(function () { setupSelect2($select2); });
        } else {
            setupSelect2($select2);
        }

        // enable tooltips (only if Bootstrap tooltip function exists)
        if (typeof $.fn.tooltip === 'function') {
            $(target).tooltip({
                selector: '.tip',
                container: 'body',
                html: true,
                delay: { show: 500 }
            });
        }

    };

    // ajax submit follow
    var setupAjaxAction = function (init) {
        $(init).on('click', 'a.ajax-action', function (e) {
            e.preventDefault();
            let target = $(this).data("target");
            $.ajax({
                url: $(this).attr('href'),
                // follow/attend change state, so they are POST routes with a CSRF token (#2166)
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken() },
            }).done(function (data) {
                // fire a flash message
                $(target).replaceWith(data.Success);
                Swal.fire({
                    title: "Success",
                    text: data.Message,
                    type: "success",
                    timer: 2000,
                });
            }).fail(function () {
            });
        });
    };

    var csrfToken = function () {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    };

    // submit a POST to url as a regular form, carrying the CSRF token
    var postTo = function (url) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        form.style.display = 'none';
        var token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_token';
        token.value = csrfToken();
        form.appendChild(token);
        document.body.appendChild(form);
        form.submit();
    };

    // <a href="..." data-method="post"> links (follow, attend, like, lock, admin user
    // actions) keep their markup but submit as POST, since those routes change state (#2166).
    // .confirm and .ajax-action links have their own handlers above.
    var setupPostLinks = function () {
        $(document).on('click', 'a[data-method]', function (e) {
            if ($(this).is('.confirm, .ajax-action')) {
                return;
            }
            e.preventDefault();
            postTo($(this).attr('href'));
        });
    };

    let setupLoadingModal = function () {
        $('#content').on('click', '.loading-modal', function (e) {
            e.preventDefault();
            var href = $(this).attr('href');
            var msg = $(this).data('loading-modal');
            Framework.showLoadingModal(msg);
            window.location.href = href;
        });
    };

    let showLoadingModal = function (message) {
        $('#loading-modal .modal-body p').html('<div class="modal-loading"><div class="modal-loading-spinner"><i class="fa fa-spinner fa-spin fa-3x fa-fw"></i></div><div class="modal-loading-message">' + message + '</div></div>');
        $('#loading-modal').modal({
            backdrop: 'static',
            keyboard: 'false'
        });
    };

    const kebabCase = str => str.match(/[A-Z]{2,}(?=[A-Z][a-z0-9]*|\b)|[A-Z]?[a-z0-9]*|[A-Z]|[0-9]+/g)
        .filter(Boolean)
        .map(x => x.toLowerCase())
        .join('-')

    // when a value is typed in a name input, update the slug input with the kebab-case-name
    var setupNameToSlug = function () {
        $('body').on('keyup', '#name', function (e) {
            var content = kebabCase(e.target.value)
            // if content starts with a number, prepend a letter
            if (content.match(/^[0-9]/)) {
                content = 'a-' + content;
            }

            $('input#slug').val(content);
        })
    }

    return {
        init: init,
        initTooltip: initTooltip,
        setupConfirm: setupConfirm,
        setupDeleteConfirm: setupDeleteConfirm,
        setupControls: setupControls,
        setupAjaxAction: setupAjaxAction,
        setupPostLinks: setupPostLinks,
        postTo: postTo,
        setupLoadingModal: setupLoadingModal,
        showLoadingModal: showLoadingModal,
        setupNameToSlug: setupNameToSlug,
        loadEmbeds: loadEmbeds,
    };
})();

// js module for the home page
var Home = (function () {
    var init = function () {
        this.loadDays();
        this.setupPagination();
        this.setupAddEvents();
        this.setupLoadScroll();

        window.addEventListener('popstate', function (event) {
            // The popstate event is fired each time when the current history entry changes.

            var r = true;

            if (r == true) {
                // Call Back button programmatically as per user confirmation.
                history.back();
                // Uncomment below line to redirect to the previous page instead.
                // window.location = document.referrer // Note: IE11 is not supporting this.
            } else {
                // Stay on the current page.
                history.pushState(null, null, window.location.pathname);
            }

            history.pushState(null, null, window.location.pathname);

        }, false);
    };

    // check the day sections and load via ajax
    var loadDays = function () {
        $('body section.day').each(function (e) {
            var url = $(this).attr('href');
            var num = $(this).attr('data-num');
            getDayEvents(url, num);
        });
    };

    // when a pagination link is clicked, load the results of the url
    var setupPagination = function () {
        $('body').on('click', '.pagination a', function (e) {
            e.preventDefault();
            var url = $(this).attr('href');
            getEvents(url);
            // window.history.pushState("", "", url);
            history.pushState(null, null, window.location.pathname);
        });
    };

    // when the add events link is clicked, append the events to the bottom
    var setupAddEvents = function () {
        $('body').on('click', '#add-event', function (e) {
            e.preventDefault();
            var url = $(this).attr('href');
            var target = '.home';

            $('#add-event').attr("href", "");
            $('#add-event').html("Loading...");
            addEvents(url, target);

            history.pushState(null, null, window.location.pathname);
        });
    };

    // set up javascript that fires when the page scrolls
    var setupLoadScroll = function () {
        var scrollTimeout;
        var throttle = 300;

        $(window).on('scroll', function (e) {
            if (!scrollTimeout) {
                if ($(window).scrollTop() == $(document).height() - $(window).height()) {

                    scrollTimeout = setTimeout(function () {

                        var url = $('#add-event').attr('href');
                        var target = '.home';

                        // log this event

                        // change the next events content
                        $('#add-event').attr("href", "");
                        $('#add-event').html("Loading...");

                        addEvents(url, target);

                        history.pushState(null, null, window.location.pathname);
                        scrollTimeout = null;
                    }, throttle);
                }
            }
        });
    };

    // load a day's events
    var getDayEvents = function (url, num) {
        // maybe add a wait here?
        if (url !== undefined) {
            $.ajax({
                url: url
            }).done(function (data) {
                // load results into the applicable position
                $('#day-position-' + num).html(data);
                // TODO determine if we need to do this re-load, or ONLY after the days have been added?
                App.loadEmbeds();
            }).fail(function () {
            });
        }
    };

    // load a whole block of events
    var getEvents = function getEvents(url) {
        $.ajax({
            url: url
        }).done(function (data) {
            $('#4days').html(data);
        }).fail(function () {
        });
    };

    // load a whole block of events and append
    var addEvents = function addEvents(url, target) {
        if (url !== undefined) {
            $.ajax({
                url: url
            }).done(function (data) {
                $('.next-events').parent().remove();
                $(target).last().after(data);
                App.loadEmbeds();
            }).fail(function () {
            });
        }
    };

    return {
        init: init,
        loadDays: loadDays,
        setupPagination: setupPagination,
        getDayEvents: getDayEvents,
        getEvents: getEvents,
        setupAddEvents: setupAddEvents,
        setupLoadScroll: setupLoadScroll,
        addEvents: addEvents
    };
})();

// init app module on document load
$(function () {
    App.init();
});
