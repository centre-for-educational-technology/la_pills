(function ($, Drupal) {
  Drupal.behaviors.laPillsSessionEntity = {
    attach: function (context, settings) {
      if (context !== window.document) return;

      if (settings.laPillsSessionEntity.canUpdate !== true) return;

      function updateQuestionnaireCounts(url, title) {
        $.ajax({
          method: 'GET',
          url: Drupal.url('api/v1/session_entity/1/questionnaire_count'),
          cache: false,
          dataType: 'json'
        })
        .done(function(data) {
          if (data && $.isArray(data) && data.length > 0) {
            $.each(data, function(index, value) {
              var $element = $('[data-questionnaire-uuid="' + value.uuid + '"]');
              $element.find('span.answers-count').remove();
              $element
              .append($('<span>', {
                text: value.count,
                class: 'answers-count',
                title: title
              }).css('color', '#fff').css('background-color', $element.css('color')));
            });
          }
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
          console.error(jqXHR, textStatus, errorThrown);
        });
      }

      var url = 'api/v1/session_entity/' + settings.laPillsSessionEntity.id + '/questionnaire_count';
      var title = Drupal.t('Number of unique submissions');

      updateQuestionnaireCounts(url, title);

      setInterval(function() {
        updateQuestionnaireCounts(url, title);
      }, 10000);
    }
  };
})(jQuery, Drupal);
