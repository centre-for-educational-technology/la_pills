(function ($) {
  Drupal.behaviors.activityLoggingBeforeUnload = {
    attach: function (context, settings) {
      if (context !== window.document) return;

      $(document).ready(function () {
        $(window).on('beforeunload', function(e) {
          var count = $(context).find('.timer-session-button.la-pills-active-timer').length;

          if (count) {
            return Drupal.t('Are you sure you want to navigate away from this page with activity logging still being active? Please note tat any logging will stay active until stopped or session being closed!');
          }

          return;
        });
      });
    }
  };
})(jQuery);
