(function ($) {
  Drupal.behaviors.timerActiveInactive = {
    attach: function (context, drupalSettings) {
      $(document).ready(function () {
        $(context).find('input[type="checkbox"].timer-active-inactive').once('timer-active-inactive').each(function (e) {
          var $this = $(this);

          var settings = {
            url: drupalSettings.path.baseUrl + 'timers/ajax/' + $this.data('id') + '/active-inactive',
            element: this,
            event: 'change'
          };

          Drupal.ajax(settings);
        });
      });
    }
  };
})(jQuery);
