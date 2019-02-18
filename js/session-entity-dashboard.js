(function ($, Drupal) {
  Drupal.behaviors.laPillsSessionEntityDashboard = {
    attach: function (context, settings) {
      if (context !== window.document) return;

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
