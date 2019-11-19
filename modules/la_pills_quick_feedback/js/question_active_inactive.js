(function ($) {
  Drupal.behaviors.questionActiveInactive = {
    attach: function (context, drupalSettings) {
      $(document).ready(function () {
        $(context).find('input[type="checkbox"].question-active-inactive').once('question-active-inactive').each(function (e) {
          var $this = $(this);

          var settings = {
            url: drupalSettings.path.baseUrl + 'quick-feedback/ajax/' + $this.data('id') + '/active-inactive',
            element: this,
            event: 'change'
          };

          Drupal.ajax(settings);
        });
      });
    }
  }
})(jQuery);
