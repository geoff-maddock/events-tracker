import jQuery from 'jquery';

const $ = jQuery;

const Visibility = {
    init(target) {
        console.log('Visibility.init');

        // find all the collapsible items
        const items = $(target).find('.collapsible');

        $.each(items, function(key, value) {
            console.log('value: '+ value.id);

            // check the localstorage
            let stored = localStorage.getItem('#' + value.id);
            console.log('stored: ' + stored)
            
            if (stored !== null) {
                // if there is a stored state, then the item is closed
                $('#' + value.id).removeClass('show');
                $('#' + value.id).addClass('hide');
                console.log('starts closed');
            }
        });

        // on click - change the stored visibility state
        $(target).find('.toggler').on('click', (event) => { 
            console.log(event.target);
            let target = event.target.getAttribute("data-bs-target")
            console.log('click target: ' + target);

            let state = '';
            // if the stored value is not close, then set to close
            if (localStorage.getItem(target) !== 'closed') {
                localStorage.setItem(target, 'closed');
                state = 'closed';
            } else {
                // if the stored value was closed, then remove it
                localStorage.removeItem(target);
            }

            console.log('set: ' + target + ' to ' + state);
        });

    },

};

export default Visibility;