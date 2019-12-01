import jQuery from 'jquery';

const $ = jQuery;

const Confirm = {

    init() {
        $('button.delete').on('click', function(e){
            e.preventDefault();
            var form = $(this).parents('form');
            Swal.fire({
                    title: "Are you sure?",
                    text: "You will not be able to recover this group!",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Yes, delete it!",
                    closeOnConfirm: true
                },
                function(isConfirm){
                    if (isConfirm) {
                        form.submit();
                    }
                });
        });
    },
};

export default Confirm;

