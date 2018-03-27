import jQuery from 'jquery';

const $ = jQuery;

const Visibility = {
    init(target) {
        $(target).find('[data-toggle="visibility"]').on('click', (event) => {
            event.preventDefault();
            const defaults = {};
            const options = {
                target: $(event.currentTarget).data('target'),
            };
            const settings = $.extend(defaults, options);

            $(settings.target).toggleClass('d-none');
        });
    },

};

export default Visibility;