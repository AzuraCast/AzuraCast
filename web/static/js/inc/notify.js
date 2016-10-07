$(window).load(function() {

    function notify(message, type) {
        $.growl({
            message: message
        }, {
            type: type,
            allow_dismiss: true,
            label: 'Cancel',
            className: 'btn-xs btn-inverse align-right',
            placement: {
                from: 'top',
                align: 'right'
            },
            delay: 10000,
            animate: {
                enter: 'animated fadeIn',
                exit: 'animated fadeOut'
            },
            offset: {
                x: 20,
                y: 85
            }
        });
    }

});