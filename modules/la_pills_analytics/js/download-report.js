(function ($, Drupal) {
    Drupal.behaviors.laPillsAnalyticsDownloadReport = {
      attach: function (context, settings) {
        if (context !== window.document) return;

        setTimeout(function() {
          if ($('a#download-report-url', context).length) {
            window.open($('a#download-report-url', context).attr('href'), '_blank');
          }
        }, 2000);
      }
    };
  })(jQuery, Drupal);
  