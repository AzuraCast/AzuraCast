$(function() {

    $('form.form').each(function() {
        var $form = $(this);

        $form.addClass('fa-form-engine fa-form');

        $form.find('.form-group > label').addClass('control-label');

        $form.find('input:not(input[type=button],input[type=submit],input[type=reset],input[type=radio],input[type=checkbox]),textarea,select').addClass('form-control');
        $form.find('select').wrap('<div class="select" />');

        autosize($form.find('textarea'));

        $form.find('input[type=radio]').each(function() {
            $(this).closest('.form-field').addClass('radio-group');
            $(this).next('label').addBack().wrapAll('<div class="radio m-b-15" />');
        });
        $form.find('input[type=checkbox]').each(function() {
            $(this).closest('.form-field').addClass('checkbox-group');
            $(this).next('label').addBack().wrapAll('<div class="checkbox m-b-15" />');
        });

        $form.find('input[type=checkbox],input[type=radio]').after('<i class="input-helper"></i>');

        $form.find('input[type=checkbox].inline').removeClass('inline').closest('div.checkbox').addClass('checkbox-inline');
        $form.find('input[type=radio].inline').removeClass('inline').closest('div.radio').addClass('radio-inline');

        $form.find('div.checkbox:not(.checkbox-inline)').addClass('m-b-15');
        $form.find('div.radio:not(.radio-inline)').addClass('m-b-15');

        $form.find('.help-block.form-error').parent().addClass('has-error');
        $form.find('.help-block.form-success').parent().addClass('has-success');
        $form.find('.help-block.form-warning').parent().addClass('has-warning');

        $form.find('input[type=button],input[type=submit],input[type=reset]').addClass('btn m-t-10');
    });

});