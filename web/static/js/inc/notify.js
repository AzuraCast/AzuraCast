function notify(message, type, minimal_layout) {

    var growlSettings = {
        type: type,
        allow_dismiss: true,
        label: 'Cancel',
        className: 'btn-xs btn-inverse align-right',
        placement: {
            from: 'top',
            align: 'right'
        },
        delay: 10000,
        z_index: 8,
        animate: {
            enter: 'animated fadeIn',
            exit: 'animated fadeOut'
        },
        offset: {
            x: 20,
            y: 85
        }
    };

    if (minimal_layout) {
        growlSettings.placement.from = 'top';
        growlSettings.placement.align = 'center';
        growlSettings.offset.y = 20;
    }

    $.notify({ message: message }, growlSettings);

}
