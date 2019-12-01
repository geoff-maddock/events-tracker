(function($) {

    $.fn.autoSubmit = function(options) {

        var defaults = {};
        var settings = $.extend($.fn.autoSubmit.defaults, options);

        this.on('change', function() {
            console.log('auto submit on change');
            var form = this.closest('form');
            form.submit();
        });

        return this;
    }

}(jQuery));