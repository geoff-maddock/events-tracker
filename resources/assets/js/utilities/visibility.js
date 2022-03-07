import jQuery from 'jquery';

const $ = jQuery;

const Visibility = {
    init(target) {
        console.log('Visibility.init');

        // find all the collapsible items
        const items = $(target).find('.collapsible');

        // check storage for any collapsible items to see if they are collapsed
        $.each(items, function(key, value) {

            // check the localstorage
            const stored = localStorage.getItem('#' + value.id);
            
            if (stored !== null) {
                // if there is a stored state, then the item is closed
                $('#' + value.id).removeClass('show');
                $('#' + value.id).addClass('hide');
            }
        });

        // when a toggler is clicked - change the stored visibility state of the data-bs-target
        $(target).find('.toggler').on('click', (event) => { 
            const target = event.target.getAttribute("data-bs-target")

            let state = '';
            // if the stored value is not close, then set to close
            if (localStorage.getItem(target) !== 'closed') {
                localStorage.setItem(target, 'closed');
                state = 'closed';
            } else {
                state = 'open';
                // if the stored value was closed, then remove it
                localStorage.removeItem(target);
            }

            console.log('set: ' + target + ' to ' + state);
        });

    },

};

export default Visibility;