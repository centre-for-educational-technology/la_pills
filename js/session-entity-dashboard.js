(function ($, Drupal) {
  Drupal.behaviors.laPillsSessionEntityDashboard = {
    attach: function (context, settings) {
      if (context !== window.document) return;

      /**
       * Rounds the value in case of float to certain digits or returns it as is
       * in caae of integer.
       * @param  {number} value  Numerical value
       * @param  {int}    digits Number of digits to appear after decimal point
       * @return {number}        Rounded float or integer value
       */
      function numberToIntegerOrFixed(value, digits) {
        return Number.isInteger(value) ? value : Number(value).toFixed(digits);
      }

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
        var meansData = {};

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
            var tmp = [];
            $.each(data.counts, function(key, value) {
              tmp.push(key * value);
            });
            var totalSum = tmp.reduce(function(total, value) {
              return total + value;
            });
            var totalCount = Object.values(data.counts).reduce(function(total, count) {
              return total + count;
            });

            meansData[data.title] = (totalCount > 0) ? totalSum / totalCount : 0;

            var graphData = {
              bindto: '#' + graphid,
              data: {
                columns: [
                  [Drupal.t('Kokku')].concat(Object.values(data.counts)),
                ],
                type: 'area-step'
              },
              legend: {
                show: false
              },
              axis: {
                x: {
                  type: 'category',
                  categories: data.options
                },
                y: {
                  tick: {}
                }
              },
              grid: {
                x: {}
              }
            };

            if (totalCount > 0) {
              graphData.grid.x.lines = [
                {
                  value: meansData[data.title] - d3.min(data.options),
                  text: Drupal.t('Mean value: @value', {
                    '@value': numberToIntegerOrFixed(meansData[data.title], 1)
                  }),
                  position: 'middle',
                  class: 'mean'
                }
              ];
            }

            if (d3.max(Object.values(data.counts)) < 10) {
              graphData.axis.y.tick.values = [];
              for (var i = 0; i<=d3.max(Object.values(data.counts)); i++) {
                graphData.axis.y.tick.values.push(i);
              }
            }

            $('<div>', {
              id: graphid,
              class: 'graph scale'
            }).appendTo($element).ready(function() {
              var chart = c3.generate(graphData);
            });
          } else {
            console.warn('Unhandled graph type:', data.type);
          }
        });

        if (Object.keys(meansData).length > 0) {
          var $meansElement = $('<div>', {
            class: 'questionnaire-means well',
            id: 'questinnaire-' + questionnaireUuid + '-means'
          });
          var graphId = 'questionnaire-scale-mean-' + questionnaireUuid;
          var columns = [];
          $.each(meansData, function(key, value) {
            columns.push([key, value]);
          });

          $('<h3>', {
            text: Drupal.t('Mean values of questionnaire scale questions')
          }).appendTo($meansElement);

          $('<div>', {
            id: graphId,
            class: 'graph graph-scale-mean'
          }).css('height', 150 + (25 * columns.length)).appendTo($meansElement).ready(function() {
            var chart = c3.generate({
              bindto: '#' + graphId,
              data: {
                columns: columns,
                type: 'bar'
              },
              legend: {
                show: true
              },
              axis: {
                rotated: true,
                x: {
                  show: false
                }
              },
              bar: {
                space: 0.25
              },
              tooltip: {
                grouped: false,
                format: {
                  value: function(value, ratio, id, index) {
                    return numberToIntegerOrFixed(value, 1);
                  }
                }
              }
            });
          });

          $meansElement.appendTo($('#questionnaire-' + questionnaireUuid, context));
        }
      });
    }
  };
})(jQuery, Drupal);
