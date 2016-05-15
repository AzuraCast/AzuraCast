function notify(message, type){
    $.growl({
        message: message
    },{
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

$(function() {
    // Apply proper styling to all scripted forms.
    $('form.fa-form').each(function() {
        var form = $(this);

        form.find('input:not(input[type=button],input[type=submit],input[type=reset],input[type=radio],input[type=checkbox]),textarea,select').addClass('form-control');
        form.find('select').wrap('<div class="select" />');
        autosize(form.find('textarea'));

        form.find('input[type=checkbox],input[type=radio]').after('<i class="input-helper"></i>');
        form.find('div.checkbox:not(.checkbox-inline)').addClass('m-b-15');
        form.find('div.radio:not(.radio-inline)').addClass('m-b-15');

        form.find('.help-block.form-error').parent().addClass('has-error');
        form.find('.help-block.form-success').parent().addClass('has-success');
        form.find('.help-block.form-warning').parent().addClass('has-warning');

        form.find('input[type=button],input[type=submit],input[type=reset]').addClass('btn m-t-10');
    });
});