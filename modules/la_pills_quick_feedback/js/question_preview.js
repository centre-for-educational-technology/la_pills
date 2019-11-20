(function ($) {
  Drupal.behaviors.questionPreviewFormDisableSubmission = {
    attach: function (context, drupalSettings) {
      $(document).ready(function () {
        $(context).find('form.question-preview-form').once('question-preview-form-disable-submission').each(function (e) {
          var $this = $(this);

          $this.on('submit', function(e) {
            e.preventDefault();
          });
        });
      });
    }
  };
})(jQuery);
