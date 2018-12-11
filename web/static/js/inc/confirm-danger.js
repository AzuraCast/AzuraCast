function confirmDangerousAction(el) {
    const linkUrl = $(el).attr('href');

    let confirmTitle = 'Are you sure?';
    if ($(el).data('confirm-title')) {
        confirmTitle = $(el).data('confirm-title');
    }

    swal({
        title: confirmTitle,
        type: 'warning',
        buttons: [true, $(el).text()],
        dangerMode: true
    }).then((value) => {
        if (value) {
            window.location.href = linkUrl;
        }
    });
}

$(function() {

    $('a.btn-danger').on('click', function(e) {
        e.preventDefault();
        confirmDangerousAction(e.target);
        return false;
    });

});

