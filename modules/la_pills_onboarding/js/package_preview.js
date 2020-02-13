(function ($) {
  Drupal.behaviors.laPillsOnboardingPackagePreview = {
    attach: function (context, settings) {
      $('#edit-preview').once().on('click', function(e) {
        e.preventDefault();

        var selected = $('select[name="user_package"]').val();

        if (!settings.laPillsUserPackageUrls.hasOwnProperty(selected)) {
          return;
        }

        // XXX This will not show attached entities unless permission is added
        // XXX First call will bring three different commands instead of one
        Drupal.ajax({
          progress: { type: 'throbber' },
          dialogType: 'modal',
          dialog: undefined,
          dialogRenderer: undefined,
          url: settings.laPillsUserPackageUrls[selected],
          base: 'edit-preview',
          event: 'click',
        }).execute();
      });
    }
  };
})(jQuery);
