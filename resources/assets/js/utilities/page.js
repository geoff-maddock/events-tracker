import jQuery from 'jquery';

const $ = jQuery;

const Page = {
    loadView(url = '') {
        if (url === '') {
            window.location.href = $(window.location).attr('href');
        } else {
            window.location.href = url;
        }
    },

    refresh() {
        window.location.reload();
    },
};

export default Page;