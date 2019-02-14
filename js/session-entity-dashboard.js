(function ($, Drupal) {
  Drupal.behaviors.laPillsSessionEntityDashboard = {
    attach: function (context, settings) {
      if (context !== window.document) return;

      var questions = $('.questionnaire .question', context);
      questions.each(function(index, element) {
        var $element = $(element);
        var type = $element.data('question-type');
        var graphid = 'graph-' + $element.data('uuid');

        if (type === 'multi-choice') {
          $('<div>', {
            id: graphid,
            class: 'graph'
          }).appendTo($element).ready(function() {
            var chart = c3.generate({
              bindto: '#' + graphid,
              data: {
                columns: [
                  ['data1', 30],
                  ['data2', 50]
                ],
                type: 'pie'
              }
            });
          });
        } else if (type === 'checkboxes') {
          $('<div>', {
            id: graphid,
            class: 'graph'
          }).appendTo($element).ready(function() {
            var chart = c3.generate({
              bindto: '#' + graphid,
              data: {
                columns: [
                  ['data1', 30],
                  ['data2', 50]
                ],
                type: 'bar'
              }
            });
          });
        } else if (type === 'scale') {
          $('<div>', {
            id: graphid,
            class: 'graph'
          }).appendTo($element).ready(function() {
            var chart = c3.generate({
              bindto: '#' + graphid,
              data: {
                columns: [
                  ['data1', 130, 100, 140, 200, 150, 50],
                ],
                type: 'area-step'
              },
              legend: {
                show: false
              }
            });
          });
        } else {
          console.warn('Unhandled graph type:', type);
        }
      });
    }
  };
})(jQuery, Drupal);
