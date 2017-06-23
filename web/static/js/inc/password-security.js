
$(document).ready(function () {

    // Show password strength meter.
    if (typeof zxcvbn === 'function') {

        $('input[type=password]').on('keyup', function(e) {

            var result  = zxcvbn($(this).val()),
                score   = result.score;

            var group = $(this).closest('.form-group');
            if (!group.length) {
                group = $(this).closest('div');
            }

            var explanation = group.find('.help-block.password-explanation');

            if (!explanation.length) {
                explanation = $('<small class="help-block password-explanation" />');

                var label = group.find('label');
                if (label.length) {
                    label.after(explanation);
                } else {
                    $(this).after(explanation);
                }

                explanation = group.find('.help-block.password-explanation');
            }

            if (result.feedback.warning) {
                explanation.text(result.feedback.warning).show();
            } else {
                explanation.hide();
            }

            group.removeClass('has-success has-warning has-error');

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

    }

});