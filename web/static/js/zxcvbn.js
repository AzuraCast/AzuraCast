$(document).ready(function() {

    $('input[type=password].strength').on('keyup', function(e) {

        var result = zxcvbn($(this).val()),
            score = result.score;

        var group = $(this).closest('.form-group');
        if (!group.length) {
            group = $(this).closest('div');
        }

        var explanation = group.find('.form-text.password-explanation');

        if (!explanation.length) {
            explanation = $('<small class="form-text password-explanation" />');

            var label = group.find('label');
            if (label.length) {
                label.after(explanation);
            } else {
                $(this).after(explanation);
            }

            explanation = group.find('.form-text.password-explanation');
        }

        if (result.feedback.warning) {
            explanation.text(result.feedback.warning).show();
        } else {
            explanation.hide();
        }

        group.removeClass('has-error has-success has-warning');

        switch (score) {
            case 0:
            case 1:
                group.addClass('has-error');
                break;

            case 2:
            case 3:
                group.addClass('has-warning');
                break;

            case 4:
                group.addClass('has-success');
                break;
        }

    });

});
