function notify (message, type, options) {

  if (typeof options === 'boolean') {
    options = {
      minimal: options
    }
  } else if (typeof options === 'undefined') {
    options = {}
  }

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
  }

  if ($('body').hasClass('page-minimal')) {
    growlSettings.placement.from = 'top';
    growlSettings.placement.align = 'center';
    growlSettings.offset.y = 20;
  }

  $.notify({ message: message }, $.extend({}, growlSettings, options));
}
