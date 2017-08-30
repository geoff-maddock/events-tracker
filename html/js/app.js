// App - some basic app functions for interactions
var App = (function()
{
    var init = function()
    {
        this.setupControls();
        this.setupLoadingModal();
        console.log('App init complete');
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