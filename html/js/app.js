// App - some basic app functions for interactions
var App = (function()
{
    var init = function()
    {
        this.initTooltip();
        this.setupConfirm();
        this.setupControls();
        this.setupLoadingModal();

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
        if(!target)
        {
            var target = 'body';
        }

        // select2
        $(target + ' .select2').select2({
                placeholder: $(this).data('placeholder'),
                tags: $(this).data('tags'),
                allowClear: true,
            });

        // enable tooltips
        $(target).tooltip({
            selector: '.tip',
            container: 'body',
            html: true,
            delay: { show: 500 }
        });

    };

    var setupLoadingModal = function()
    {
        $('#content').on('click', '.loading-modal', function(e) {
            e.preventDefault();
            var href = $(this).attr('href');
            var msg = $(this).data('loading-modal');
            Framework.showLoadingModal(msg);
            window.location.href = href;
        });
    };

    var showLoadingModal = function(message)
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
        setupLoadingModal: setupLoadingModal,
        showLoadingModal: showLoadingModal,

    };
})();

// init app module on document load
$(function()
{
    App.init();
});

// FlashMessage module
var FlashMessage = (function()
{
    var fadeout = function(id)
    {
        $(id).delay(4000).fadeOut(200);
    };

    var show = function(type, message, fadeout)
    {
        var i = Math.floor(Math.random()*1000000);
        // remove success messages when adding a new one to prevent stacking
        if(type == 'success')
        {
            $('.alert-'+type).remove();
        }
        $('#flash').append('<div class="alert alert-'+type+' align-center" style="display:none;" id="flash-'+i+'"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>'+message+'</div>');
        $('#flash-'+i).fadeIn(200);
        if(fadeout && fadeout === true) {
            this.fadeout('#flash-'+i);
        }
    };

    var showError = function(message, fadeout)
    {
        Util.loadStop();
        this.show('danger', message, fadeout);
    };

    var showSuccess = function(message, fadeout)
    {
        this.show('success', message, fadeout);
    };

    var showWarning = function(message, fadeout)
    {
        this.show('warning', message, fadeout);
    };

    var showInfo = function(message, fadeout)
    {
        this.show('info', message, fadeout);
    };

    return {
        fadeout: fadeout,
        show: show,
        showError: showError,
        showSuccess: showSuccess,
        showWarning: showWarning,
        showInfo: showInfo
    }
})();
