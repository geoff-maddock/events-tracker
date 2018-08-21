import jQuery from 'jquery';

const $ = jQuery;

const Visibility = {
    init(target) {
        $(target).find('[data-toggle="visibility"]').on('click', (event) => {
            console.log('toggle visibility');
            console.log(event.currentTarget);
            event.preventDefault();
            const defaults = {};
            const options = {
                target: $(event.currentTarget).data('target'),
            };
            const settings = $.extend(defaults, options);

            $(settings.target).toggleClass('hidden');
            // save into localStorage
            //localStorage.setItem($(settings.target).attr('id'), $(settings.target).attr('class'));

        });
    },

};

export default Visibility;