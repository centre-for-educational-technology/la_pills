(function ($) {
  Drupal.behaviors.counter = {
    attach: function (context, settings) {
      $(document).ready(function () {
        $(context).find('.timer-session-button').once('timer-session').each(function (e) {
          var $this = $(this);

          if ($this.hasClass('la-pills-active-timer')) {
            $this.countimer({ initHours : 0, initMinutes : 0, initSeconds: 0});
          } else {
            $this.countimer({ initHours : 0, initMinutes : 0, initSeconds: 0});
            $this.countimer('stop');
          }
        });
      });
    }
  }
})(jQuery);