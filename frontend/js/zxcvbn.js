$(document).ready(function () {

  $('input[type=password].strength').on('keyup', function (e) {

    let currentPassword = $(this).val(),
      result = zxcvbn(currentPassword),
      score = result.score;

    let group = $(this).closest('.form-group');
    if (!group.length) {
      group = $(this).closest('div');
    }

    let explanation = group.find('.form-text.password-explanation');
    if (!explanation.length) {
      explanation = $('<small class="form-text password-explanation" />');
      $(this).after(explanation);

      explanation = group.find('.form-text.password-explanation');
    }

    let explanationText = '';
    let explanationMeter = $('<meter class="password-strength" min="0" max="4" low="2" high="3" optimum="4" />')
      .val(score);

    if (currentPassword === '') {
      explanationText = App.lang.pw_blank;
    } else if (result.feedback.warning) {
      explanationText = result.feedback.warning;
    } else if (result.feedback.suggestions.length) {
      explanationText = result.feedback.suggestions[0];
    } else {
      explanationText = App.lang.pw_good;
    }

    explanation.html('')
      .append(explanationMeter)
      .append('&nbsp;' + explanationText);

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
