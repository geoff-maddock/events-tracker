(function($) {

    $.fn.autoSubmit = function() {

        this.on('change', function() {
            var form = this.closest('form');
            form.submit();
        });

        return this;
    }

}(jQuery));