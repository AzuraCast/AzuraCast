$(function() {

    $('a.btn-danger').on('click', function(e) {

        e.preventDefault();

        var linkUrl = $(this).attr('href');

        swal({
            title: 'Are you sure?',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes',
            showLoaderOnConfirm: true
        }, function() {
            window.location.href = linkUrl;
        });

        return false;

    });

});

