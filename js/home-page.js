(function ($, Drupal) {
  Drupal.behaviors.laPillsHomePage = {
    attach: function (context, settings) {
      if (context !== window.document) return;

      $('.home-page-actions > button', context).on('click', function() {
        window.location.href = $(this).data('url');
      });
    }
  };
})(jQuery, Drupal);
