function confirmDangerousAction (el) {
  let $el = $(el);

  if (!$el.is('a')) {
    $el = $el.closest('a');
  }

  let confirmTitle = App.lang.confirm;
  if ($el.data('confirm-title')) {
    confirmTitle = $el.data('confirm-title');
  }

  let dangerMode = true;
  if ($el.hasClass('btn-success') || $el.hasClass('btn-outline-success')) {
    dangerMode = false;
  }

  let buttonText;
  if ($el.find('span').length > 0) {
    // jQuery trick to pull an item's text without inner HTML elements.
    // https://stackoverflow.com/questions/8624592/how-to-get-only-direct-text-without-tags-with-jquery-in-html
    buttonText = $el.find('span').first().text();
  } else {
    buttonText = $el.clone().children().remove().end().text();
  }

  return Swal.fire({
    title: confirmTitle,
    confirmButtonText: buttonText,
    confirmButtonColor: dangerMode ? '#e64942' : '#3085d6',
    showCancelButton: true,
    focusCancel: dangerMode
  });
}

$(function () {

  $('a.btn-danger,a.btn[data-confirm-title]').on('click', function (e) {
    e.preventDefault();

    const linkUrl = $(this).attr('href');
    confirmDangerousAction(e.target).then((result) => {
      if (result.value) {
        window.location.href = linkUrl;
      }
    });
    return false;
  });

});

