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

  // jQuery trick to pull an item's text without inner HTML elements.
  // https://stackoverflow.com/questions/8624592/how-to-get-only-direct-text-without-tags-with-jquery-in-html
  let buttonText = $el.clone().children().remove().end().text();

  return swal({
    title: confirmTitle,
    buttons: [true, buttonText],
    dangerMode: dangerMode
  });
}

$(function () {

  $('a.btn-danger,a.btn[data-confirm-title]').on('click', function (e) {
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

