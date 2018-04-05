// App - some basic app functions for interactions
var App = (function()
{
    var init = function()
    {
        this.initTooltip();
        this.setupConfirm();
        this.setupControls();
        this.setupLoadingModal();
        this.setupAjaxAction('body');
        $('.auto-submit').autoSubmit();
    };

    var initTooltip = function () {
        $('[data-toggle="tooltip"]').tooltip();
    };

    var setupConfirm = function() {
        $('button.delete').on('click', function (e) {
            e.preventDefault();
            var form = $(this).parents('form');
            var type = $(this).data('type');
            swal({
                    title: "Are you sure?",
                    text: "You will not be able to recover this " + type + "!",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Yes, delete it!",
                    closeOnConfirm: true
                },
                function (isConfirm) {
                    if (isConfirm) {
                        form.submit();
                    }
                    ;
                    //
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
        $(target + ' .select2').select2({
                placeholder: $(this).data('placeholder'),
                tags: $(this).data('tags'),
                allowClear: true,
            });

        // console.log('Set up select to, applied to select2 class.');
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
                swal({
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


    return {
        init: init,
        initTooltip: initTooltip,
        setupConfirm: setupConfirm,
        setupControls: setupControls,
        setupAjaxAction: setupAjaxAction,
        setupLoadingModal: setupLoadingModal,
        showLoadingModal: showLoadingModal,

    };
})();

// js module for the home page
var Home = (function()
{
    var init = function()
    {
        this.loadDays();
        this.setupPagination();
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
            window.history.pushState("", "", url);
        });
    };


    // load a day's events
    var getDayEvents = function(url, num) {
        $.ajax({
            url : url
        }).done(function (data) {
            $('#day-'+num).html(data);
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

    return {
        init: init,
        loadDays: loadDays,
        setupPagination: setupPagination,
        getDayEvents: getDayEvents,
        getEvents: getEvents
    };
})();

// init app module on document load
$(function()
{
    App.init();
});