$(function() {

    $('a.btn-danger').on('click', function(e) {

        e.preventDefault();

        const linkUrl = $(this).attr('href');

        let confirmTitle = 'Are you sure?';
        if ($(this).data('confirm-title')) {
            confirmTitle = $(this).data('confirm-title');
        }

        swal({
            title: confirmTitle,
            type: 'warning',
            buttons: [true, $(this).text()],
            dangerMode: true
        }).then((value) => {
            if (value) {
                window.location.href = linkUrl;
            }
        });

        return false;

    });

});

