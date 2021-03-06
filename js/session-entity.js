(function ($, Drupal) {
  Drupal.behaviors.laPillsSessionEntity = {
    attach: function (context, settings) {
      if (context !== window.document) return;

      if (settings.laPillsSessionEntity.canUpdate !== true) return;

      function updateQuestionnaireCounts(url, title) {
        $.ajax({
          method: 'GET',
          url: Drupal.url(url),
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
              }).css('color', ($element.css('color') === 'rgb(255, 255, 255)') ? '#000' : '#fff').css('background-color', $element.css('color')));

              if ($.fn.tooltip) {
                $element.find('span.answers-count')
                  .data('toggle', 'tooltip')
                  .tooltip();
              }
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
