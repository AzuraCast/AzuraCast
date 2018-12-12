function confirmDangerousAction(el) {
    let confirmTitle = 'Are you sure?';
    if ($(el).data('confirm-title')) {
        confirmTitle = $(el).data('confirm-title');
    }

    return swal({
        title: confirmTitle,
        type: 'warning',
        buttons: [true, $(el).text()],
        dangerMode: true
    });
}

$(function() {

    $('a.btn-danger').on('click', function(e) {
        e.preventDefault();

        const linkUrl = $(this).attr('href');
        confirmDangerousAction(e.target).then((value) => {
            if (value) {
                window.location.href = linkUrl;
            }
        });
        return false;
    });

});

