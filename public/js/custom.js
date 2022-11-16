// App - some basic app functions for interactions
var App = (function()
{
    var init = function()
    {
        this.initTooltip();
        this.setupConfirm();
        this.setupDeleteConfirm();
        this.setupControls();
        this.setupLoadingModal();
        this.setupAjaxAction('body');
        $('.auto-submit').autoSubmit();
        this.setupNameToSlug();
    };

    var initTooltip = function () {
        $('[data-toggle="tooltip"]').tooltip();
    };

    var setupDeleteConfirm = function() {
        $('button.delete').on('click', function (e) {
            var form = $(this).parents('form');
            var type = $(this).data('type');
            e.preventDefault();
            console.log('set up delete confirm');
            console.log('form: ' + form);
            Swal.fire({
                    title: "Are you sure?",
                    text: "You will not be able to recover this " + type + "!",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Yes, delete it!",
                    preConfirm: function() {
                        return new Promise(function(resolve) {
                            setTimeout(function() {
                                resolve()
                            }, 2000)
                        })
                    }
                }).then(result => {
                if (result.value) {
                    // handle Confirm button click
                    // result.value will contain `true` or the input value
                    console.log('set up delete confirm');
                    console.log('form: ' + form);
                    form.submit();
                } else {
                    // handle dismissals
                    // result.dismiss can be 'cancel', 'overlay', 'esc' or 'timer'
                    console.log('cancelled confirm')
                }
            });
        });
    };

    var setupConfirm = function() {
        // confirm clicking on links
        $('a.confirm').on('click', function (e) {
            var link = $(this).attr('href');
            e.preventDefault();
            var form = null;
            var type = $(this).data('type');
            console.log('a setupConfirm function called')
            Swal.fire({
                    title: "Are you sure?",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Confirm",
                    preConfirm: function() {
                        return new Promise(function(resolve) {
                            setTimeout(function() {
                                resolve()
                            }, 1000)
                        })
                    }
                }).then(result => {
                    if (form !== null) {
                        // form is not null, so submit
                        console.log('form is not null')
                        form.submit();
                    } else if (result.value) {
                        // handle Confirm button click
                        window.location.href = link;
                    } else {
                        // handle dismissals
                        // result.dismiss can be 'cancel', 'overlay', 'esc' or 'timer'
                        console.log('cancelled confirm')
                    }
            });
        });
        // confirm clicking on buttons
        $('button.confirm').on('click', function (e) {
            var link = $(this).attr('href');
            e.preventDefault();
            var form = $(this).parents('form');
            var type = $(this).data('type');
            console.log('button setupConfirm function called')
            Swal.fire({
                    title: "Are you sure?",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Confirm",
                    preConfirm: function() {
                        return new Promise(function(resolve) {
                            setTimeout(function() {
                                resolve()
                            }, 1000)
                        })
                    }
                }).then(result => {
                    if (form !== null) {
                        // form is not null, so submit
                        console.log('form is not null')
                        form.submit();
                    } else if (result.value) {
                        // handle Confirm button click
                        window.location.href = link;
                    } else {
                        // handle dismissals
                        // result.dismiss can be 'cancel', 'overlay', 'esc' or 'timer'
                        console.log('cancelled confirm')
                    }
            });
        });
    };

    var setupControls = function(target)
    {
        if(typeof target === 'undefined' || !target)
        {
            var target = 'body';
        }

        // select2
        $(target + ' .select2').each(function() {
            var $this = $(this);
            $this.select2({
                placeholder: $this.data('placeholder'),
                tags: $this.data('tags'),
                allowClear: true,
                width: '100%',
            });
        });

        // enable tooltips
        $(target).tooltip({
            selector: '.tip',
            container: 'body',
            html: true,
            delay: { show: 500 }
        });

    };

    // ajax submit follow
    var setupAjaxAction = function(init) {
        $(init).on('click', 'a.ajax-action', function(e) {
            e.preventDefault();
            let target = $(this).data("target");
            $.ajax({
                url : $(this).attr('href'),
            }).done(function (data) {
                // fire a flash message
                $(target).replaceWith(data.Success);
                Swal.fire({
                        title: "Success",
                        text: data.Message,
                        type: "success",
                        timer: 2000,
                    });
                console.log('Updated target ' +target);
            }).fail(function () {
                console.log('No events could be loaded')
            });
        });
    };

    let setupLoadingModal = function()
    {
        $('#content').on('click', '.loading-modal', function(e) {
            e.preventDefault();
            var href = $(this).attr('href');
            var msg = $(this).data('loading-modal');
            Framework.showLoadingModal(msg);
            window.location.href = href;
        });
    };

    let showLoadingModal = function(message)
    {
        $('#loading-modal .modal-body p').html('<div class="modal-loading"><div class="modal-loading-spinner"><i class="fa fa-spinner fa-spin fa-3x fa-fw"></i></div><div class="modal-loading-message">' + message +'</div></div>');
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
    var setupNameToSlug = function() {
        $('body').on('keyup', '#name', function(e) {
            var content = kebabCase(e.target.value)
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
        setupLoadingModal: setupLoadingModal,
        showLoadingModal: showLoadingModal,
        setupNameToSlug: setupNameToSlug,
    };
})();

// js module for the home page
var Home = (function()
{
    var init = function()
    {
        this.loadDays();
        this.setupPagination();
        this.setupAddEvents();
        this.setupLoadScroll();

        window.addEventListener('popstate', function(event) {
            console.log('popstate fired');
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
    var loadDays = function() {
        $('body section.day').each(function(e) {

            var url = $(this).attr('href');
            var num = $(this).attr('data-num');
            getDayEvents(url, num);
        });
    };

    // when a pagination link is clicked, load the results of the url
    var setupPagination = function() {
        $('body').on('click', '.pagination a', function(e) {
            e.preventDefault();
            var url = $(this).attr('href');
            getEvents(url);
            // window.history.pushState("", "", url);
            console.log('url: ' + url)
            history.pushState(null, null, window.location.pathname);
        });
    };

    // when the add events link is clicked, append the events to the bottom
    var setupAddEvents = function() {
        console.log('setup add events');
        $('body').on('click', '#add-event', function(e) {
            e.preventDefault();
            console.log('add event');
            var url = $(this).attr('href');
            var target = '.home';

            addEvents(url, target);

            console.log('url: ' + url)
            history.pushState(null, null, window.location.pathname);
        });
    };

    // set up javascript that fires when the page scrolls
    var setupLoadScroll = function() {
        console.log('setup load scroll');

        var scrollTimeout;
        var throttle = 300;

        $(window).on('scroll', function(e) {
            if (!scrollTimeout) {
                if($(window).scrollTop() == $(document).height() - $(window).height()) {

                        scrollTimeout = setTimeout(function () {

                                var url = $('#add-event').attr('href');
                                var target = '.home';
                    
                                addEvents(url, target);
                    
                                history.pushState(null, null, window.location.pathname);
                                scrollTimeout = null;
                            }, throttle);
                }
            }
        });
    };

    // load a day's events
    var getDayEvents = function(url, num) {
        $.ajax({
            url : url
        }).done(function (data) {
            // load results into the applicable position
            $('#day-position-'+num).html(data);
        }).fail(function () {
            console.log('No events could be loaded')
        });
    };

    // load a whole block of events
    var getEvents = function getEvents(url) {
        $.ajax({
            url : url
        }).done(function (data) {
            $('#4days').html(data);
        }).fail(function () {
            console.log('No events could be loaded.');
        });
    };

    // load a whole block of events and append
    var addEvents = function addEvents(url, target) {
        console.log('add events: '+url)
        $.ajax({
            url : url
        }).done(function (data) {
            $('.next-events').parent().remove();
            $(target).last().after(data);
            console.log('last targetted '+target);
        }).fail(function () {
            console.log('No events could be loaded.');
        });
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
$(function()
{
    App.init();
});
