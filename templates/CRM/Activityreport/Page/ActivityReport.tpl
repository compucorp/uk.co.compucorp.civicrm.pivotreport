<h3>Activity Pivot Table</h3>

<div id="activity-report-preloader">
  Loading <span id="activity-report-loading-count">0</span> of <span id="activity-report-loading-total">0</span> Activities.
</div>
<div id="activity-report-filters" class="hidden">
  <form>
    <select name="activity_year">
      {foreach from=$years key=k item=year}
          <option value="{$k}">{$year}</option>
      {/foreach}
    </select>
    <select name="activity_month">
      {foreach from=$months key=k item=month}
          <option value="{$k}">{$month}</option>
      {/foreach}
    </select>
    <input class="apply-filters-button" type="button" value="Apply filters">
    <input class="load-all-data-button hidden" type="button" value="Load all data">
  </form>
</div>
<div id="activity-report-pivot-table">
</div>

{literal}
<script type="text/javascript">
    // Handle jQuery prop() method if it's not supported.
    (function($){
        if (typeof $.fn.prop !== 'function')
        $.fn.prop = function(name, value){
            if (typeof value === 'undefined') {
                return this.attr(name);
            } else {
                return this.attr(name, value);
            }
        };
    })(jQuery);

    CRM.$(function () {
        var data = [];
        var limit = 1000;
        var total = 0;

        /**
         * Reset data array and init empty Pivot Table.
         */
        function resetData() {
          data = [];
          initPivotTable([]);
        }

        /**
         * Load a pack of Activities data. If there is more data to load
         * (depending on the total value and the response) then we run
         * the function recursively.
         *
         * @param int offset
         *   Offset to start with (initially should be 0)
         * @param int limit
         *   Limit of data to load with one call
         * @param int total
         *   Helper parameter telling us if we need to keep loading the data
         * @param int multiValuesOffset
         *   In case we are in the middle of a multivalues activity,
         *   we know the combination to start with another call.
         * @param int multiValuesTotal
         *   In case we are in the middle of a multivalues activity,
         *   we know the total number of multivalues combinations for
         *   this particular Activity
         * @param string startYearMonth
         *   Date in YYYY-MM format to tell API the year and month of
         *   Activities we want to pick.
         */
        function loadData(offset, limit, total, multiValuesOffset, multiValuesTotal, startYearMonth) {
          CRM.$('span#activity-report-loading-count').text(offset);
          var localLimit = limit;

          if (multiValuesOffset > 0 && multiValuesTotal > 0) {
            localLimit = limit - (multiValuesTotal - multiValuesOffset);
          }
          if (multiValuesTotal - multiValuesOffset > limit) {
            localLimit = 1;
          }
          if (offset + localLimit > total) {
            localLimit = total - offset;
          }

          CRM.api3('ActivityReport', 'get', {
            "sequential": 1,
            "offset": offset,
            "limit": localLimit,
            "multiValuesOffset": multiValuesOffset,
            "startYearMonth": startYearMonth
          }).done(function(result) {
            data = data.concat(processData(result['values'][0].data));
            var nextOffset = parseInt(result['values'][0].info.nextOffset, 10);

            if (nextOffset > total) {
              loadComplete(data);

              CRM.alert(total + ' Activities loaded.', '', 'info');
            } else {
              var multiValuesOffset = parseInt(result['values'][0]['info'].multiValuesOffset, 10);
              var multiValuesTotal = parseInt(result['values'][0]['info'].multiValuesTotal, 10);

              loadData(nextOffset, limit, total, multiValuesOffset, multiValuesTotal, startYearMonth);
            }
          });
        }

        /**
         * Hide preloader, show filters and init Pivot Table.
         *
         * @param array data
         */
        function loadComplete(data) {
          CRM.$('#activity-report-preloader').addClass('hidden');
          CRM.$('#activity-report-filters').removeClass('hidden');

          initPivotTable(data);
          data = [];
        }

        /**
         * Format incoming data (combine header with fields values)
         * to be compatible with Pivot library.
         *
         * @param array data
         * @returns array
         */
        function processData(data) {
          var result = [];
          var i, j;
          var header = data[0];

          delete data[0];

          for (i in data) {
            var row = {};
            for (j in data[i]) {
              row[header[j]] = data[i][j];
            }
            result.push(row);
          }

          return result;
        }

        var activityReportForm = CRM.$('#activity-report-filters form');
        var activityReportYear = CRM.$('select[name="activity_year"]', activityReportForm);
        var activityReportMonth = CRM.$('select[name="activity_month"]', activityReportForm);

        CRM.$('input[type="button"].apply-filters-button', activityReportForm).click(function(e) {
          if (!activityReportYear.val().length || !activityReportMonth.val().length) {
            CRM.alert('Please select valid year and month.');
            return false;
          }

          CRM.$('#activity-report-preloader').removeClass('hidden');
          CRM.$('#activity-report-filters').addClass('hidden');

          loadDataByYearMonthFilter(activityReportYear.val(), activityReportMonth.val());
        });

        CRM.$('input[type="button"].load-all-data-button', activityReportForm).click(function(e) {
          CRM.confirm({ message: 'This operation may take some time to load all data for big data sets. Do you really want to load all Activities data?' }).on('crmConfirm:yes', function() {
            loadAllData();
          });
        });

        // Initially we check total number of Activities and then start
        // data fetching.
        CRM.api3('Activity', 'getcount', {
          "sequential": 1,
          "is_current_revision": 1,
          "is_deleted": 0,
          "is_test": 0,
        }).done(function(result) {
          total = parseInt(result.result, 10);

          if (total > 5000) {
            CRM.alert('There is more than 5000 Activities, getting only Activities from last 30 days.', '', 'info');

            CRM.$('input[type="button"].load-all-data-button', activityReportForm).removeClass('hidden');
            var dateFilterValue = new Date();
            dateFilterValue.setDate(dateFilterValue.getDate() - 30);

            loadDataByDateFilter(dateFilterValue.toISOString().substring(0, 10));
          } else {
            loadAllData();
          }
        });

        /**
         * Run data loading by specified date.
         *
         * @param string dateFilterValue
         */
        function loadDataByDateFilter(dateFilterValue) {
          resetData();

          CRM.$("#activity-report-pivot-table").html('');

          CRM.api3('Activity', 'getcount', {
            "sequential": 1,
            "is_current_revision": 1,
            "is_deleted": 0,
            "is_test": 0,
            "activity_date_time": {">=": dateFilterValue}
          }).done(function(result) {
            var totalFiltered = parseInt(result.result, 10);

            if (!totalFiltered) {
              CRM.$('#activity-report-preloader').addClass('hidden');
              CRM.$('#activity-report-filters').removeClass('hidden');

              CRM.alert('There is no Activities starting from ' + dateFilterValue + ' date.');
            } else {
              CRM.$('span#activity-report-loading-total').text(totalFiltered);

              loadData(0, limit, totalFiltered, 0, 0);
            }
          });
        }

        /**
         * Run data loading by specified year and month.
         *
         * @param string activityYear
         * @param string activityMonth
         */
        function loadDataByYearMonthFilter(activityYear, activityMonth) {
          resetData();

          CRM.$("#activity-report-pivot-table").html('');

          var startDate = new Date(activityYear, activityMonth - 1);
          var startMonthString = startDate.getMonth() + 1;

          if (startMonthString < 10) {
            startMonthString = '0' + startMonthString;
          }
          var startDateString = startDate.getFullYear() + '-' + startMonthString;

          var endDate = startDate;
          endDate.setMonth(endDate.getMonth() + 1);
          endDate.setDate(endDate.getDate() - 1);
          var endMonthString = endDate.getMonth() + 1;

          if (endMonthString < 10) {
            endMonthString = '0' + endMonthString;
          }

          var endDateString = endDate.getFullYear() + '-' + endMonthString + '-' + endDate.getDate() + ' 23:59:59';

          CRM.api3('Activity', 'getcount', {
            "sequential": 1,
            "is_current_revision": 1,
            "is_deleted": 0,
            "is_test": 0,
            "activity_date_time": {
              "BETWEEN":[
                startDateString,
                endDateString
              ]
            }
          }).done(function(result) {
            var totalFiltered = parseInt(result.result, 10);

            if (!totalFiltered) {
              CRM.$('#activity-report-preloader').addClass('hidden');
              CRM.$('#activity-report-filters').removeClass('hidden');

              CRM.alert('There is no Activities starting in ' + startDateString + ' date.');
            } else {
              CRM.$('span#activity-report-loading-total').text(totalFiltered);

              loadData(0, limit, totalFiltered, 0, 0, startDateString);
            }
          });
        }

        /**
         * Run all data loading.
         */
        function loadAllData() {
          resetData();

          CRM.$("#activity-report-pivot-table").html('');
          CRM.$('#activity-report-preloader').removeClass('hidden');
          CRM.$('#activity-report-filters').addClass('hidden');
          CRM.$('span#activity-report-loading-total').text(total);

          loadData(0, limit, total, 0, 0);
        }

        /*
         * Init Pivot Table with given data.
         *
         * @param array data
         */
        function initPivotTable(data) {
          jQuery("#activity-report-pivot-table").pivotUI(data, {
              rendererName: "Table",
              renderers: CRM.$.extend(
                  jQuery.pivotUtilities.renderers, 
                  jQuery.pivotUtilities.c3_renderers,
                  jQuery.pivotUtilities.export_renderers
              ),
              vals: ["Total"],
              rows: [],
              cols: [],
              aggregatorName: "Count",
              unusedAttrsVertical: false,
              rendererOptions: {
                  c3: {
                      size: {
                          width: parseInt(jQuery('#activity-report-pivot-table').width() * 0.78, 10)
                      }
                  },
              },
              derivedAttributes: {
                  "Activity Date": jQuery.pivotUtilities.derivers.dateFormat("Activity Date Time", "%y-%m-%d"),
                  "Activity Start Date Months": jQuery.pivotUtilities.derivers.dateFormat("Activity Date Time", "%y-%m"),
                  "Activity Expire Date": function(row) {
                    if (!row["Expire Date"]) {
                      return "";
                    }
                    var expireDateParts = row["Expire Date"].split(/(\d{1,2})\/(\d{1,2})\/(\d{4})/);
                    return expireDateParts[3] + "-" + expireDateParts[1] + "-" + expireDateParts[2];
                  }
              },
              hiddenAttributes: ["Test", "Expire Date"]
          }, false);
        }
    });
</script>
{/literal}
