(function ($, Drupal) {
  Drupal.behaviors.laPillsSessionEntityDashboard = {
    attach: function (context, settings) {
      if (context !== window.document) return;

      var hasTooltips = typeof($.fn.tooltip) != 'undefined';

      $('table.responses.has-answers', context)
        .addClass('show-more')
        .attr('title', Drupal.t('Click to show more or less'));
      $('table.responses.has-answers > thead').on('click', function() {
        $table = $(this).parents('table');

        $table.find('tbody').hide(0, function() {
          $table.toggleClass('show-more');
          $table.find('tbody').show('slow');
        });
      });

      if (hasTooltips) {
        $('table.responses.has-answers').tooltip({
          placement: 'top'
        });
      }

      $.each(settings.laPillsSessionEntityDashboardData, function(questionnaireUuid, questions) {
        $.each(questions, function(questionUuid, data) {
          var $element = $('#'+data.id, context);
          var graphid = 'graph-' + data.id;

          if (data.type === 'multi-choice') {
            $('<div>', {
              id: graphid,
              class: 'graph'
            }).appendTo($element).ready(function() {
              var chart = c3.generate({
                bindto: '#' + graphid,
                data: {
                  columns: data.options.map(function(option) {
                    return [option, data.counts[option]];
                  }),
                  type: 'pie'
                }
              });
            });
          } else if (data.type === 'checkboxes') {
            $('<div>', {
              id: graphid,
              class: 'graph'
            }).appendTo($element).ready(function() {
              var chart = c3.generate({
                bindto: '#' + graphid,
                data: {
                  columns: data.options.map(function(option) {
                    return [option, data.counts[option]];
                  }),
                  type: 'bar'
                }
              });
            });
          } else if (data.type === 'scale') {
            $('<div>', {
              id: graphid,
              class: 'graph'
            }).appendTo($element).ready(function() {
              var chart = c3.generate({
                bindto: '#' + graphid,
                data: {
                  columns: [
                    ['data'].concat(Object.values(data.counts)),
                  ],
                  types: {
                    data: 'area-step'
                  }
                },
                legend: {
                  show: false
                }
              });
            });
          } else {
            console.warn('Unhandled graph type:', data.type);
          }
        });
      });
    }
  };
})(jQuery, Drupal);
