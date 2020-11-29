$(function () {
  $('form.form').each(function () {
    styleForm(this);
  });
});

function styleForm (form) {
  var $form = $(form);

  // Prevent leaving the page if the form is "dirty" (has unsaved changes).
  if ($.fn.dirrty) {
    $form.dirrty();
  }

  $form.find('input:not(input[type=button],input[type=submit],input[type=reset],input[type=radio],input[type=checkbox]),textarea,select').addClass('form-control');

  if ($.fn.select2) {
    $form.find('select').select2({
      width: '100%',
      theme: 'bootstrap4',
      language: App.lang.locale_short
    });
  }

  autosize($form.find('textarea'));

  $form.find('input[type=radio]').each(function () {
    $(this).addClass('custom-control-input');
    $(this).closest('.form-field');
    $(this).next('label').addClass('custom-control-label').addBack().wrapAll('<div class="custom-control custom-radio" />');
  });
  $form.find('input[type=checkbox]').each(function () {
    $(this).addClass('custom-control-input');
    $(this).closest('.form-field');

    $(this).next('label')
      .addClass('custom-control-label')
      .addBack()
      .wrapAll('<div class="custom-control custom-checkbox" />');
  });

  $form.find('.help-block').addClass('form-text');
  $form.find('.help-block.form-error').parent().addClass('has-error');
  $form.find('.help-block.form-success').parent().addClass('has-success');
  $form.find('.help-block.form-warning').parent().addClass('has-warning');

  // noinspection JSAnnotator
  $form.find('label.advanced,fieldset.advanced legend')
    .prepend('<span class="text-info">' + App.lang.advanced + '</span> ');

  $form.find('input[type=button],input[type=submit],input[type=reset]').addClass('btn m-t-10');

  // Scroll to errors.
  var error_fields = $form.find('.has-error:visible');
  if (error_fields.length > 0) {
    $([document.documentElement, document.body]).animate({
      scrollTop: error_fields.first().offset().top - $('#header').outerHeight() - 15
    }, 1000);
  }
}

$('form button.file-upload').on('click', function (e) {
  let inputElement = $(this).siblings('input[type=file]')[0];

  $(inputElement).trigger('click');
});

$('form input[type=file]').change(function (e) {
  let fileNameElement = $(this).siblings('.file-name')[0];
  $(fileNameElement).text($(this).val().split('\\').pop());
});
