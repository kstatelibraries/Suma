'use strict';

angular.module('sumaAnalysis')
  .factory('processCalendarData', function ($q, $rootScope) {
    function sortData(response) {
      return _.sortBy(
        _.map(response, function (count, date) {
          return {
            date: date,
            count: count.count
          };
        }),
        function (obj) {
          return obj.date;
        }
      );
    }

    function processData(response) {
      var avg,
          data = {},
          sum;

      sum = sortData(response.periodSum);

      avg = sortData(response.periodAvg);

      data.options = [
        {title: 'Daily Avg', val: 'avg', data: avg},
        {title: 'Daily Sum', val: 'sum', data: sum}
      ];

      data.data = data.options[1];

      // Timezone abbreviation for displayed datetimes (passed through from the
      // server response when present; absent in tests/mocks so output is
      // unchanged there).
      if (response.tzAbbrev) {
        data.tzAbbrev = response.tzAbbrev;
      }

      return data;
    }

    return {
      get: function (response) {
        var dfd = $q.defer();

        dfd.resolve(processData(response));

        return dfd.promise;
      }
    };
  });
