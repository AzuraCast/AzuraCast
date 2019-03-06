function styleForm(form, translations) {

    var lang = $.extend({}, {
        "placeholder": "Select...",
        "no_results": "No results found!",
        "advanced": "Advanced"
    }, translations);

    var $form = $(form);

    $(window).on('beforeunload', function() {
        return false;
    });

    $form.on('submit', function() {
        $(window).off('beforeunload');
    });

    $form.find('fieldset').addClass('form-group');

    $form.find('input:not(input[type=button],input[type=submit],input[type=reset],input[type=radio],input[type=checkbox]),textarea,select').addClass('form-control');

    $form.find('select').wrap('<div class="select" />').chosen({
        width: "100%",
        placeholder_text_single: lang.placeholder,
        placeholder_text_multiple: lang.placeholder,
        no_results_text: lang.no_results
    });

    autosize($form.find('textarea'));

    $form.find('input[type=radio]').each(function() {
        $(this).addClass('custom-control-input');
        $(this).closest('.form-field').addClass('mt-3');
        $(this).next('label').addClass('custom-control-label').addBack().wrapAll('<div class="custom-control custom-radio" />');
    });
    $form.find('input[type=checkbox]').each(function() {
        $(this).addClass('custom-control-input');
        $(this).closest('.form-field').addClass('mt-3');

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
        .prepend('<span class="text-info">'+lang.advanced+'</span> ');

    $form.find('input[type=button],input[type=submit],input[type=reset]').addClass('btn m-t-10');

    // Scroll to errors.
    var error_fields = $form.find('.has-error:visible');
    if (error_fields.length > 0) {
        $([document.documentElement, document.body]).animate({
            scrollTop: error_fields.first().offset().top - $('#header').outerHeight() - 15
        }, 1000);
    }

}
