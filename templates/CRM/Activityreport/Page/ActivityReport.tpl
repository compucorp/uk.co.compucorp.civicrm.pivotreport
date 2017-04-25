<h3>Activity Pivot Table</h3>

<div id="activity-report-preloader">
  Loading <span id="activity-report-loading-count">0</span> of <span id="activity-report-loading-total">0</span> Activities.
</div>
<div id="activity-report-filters" class="hidden">
  <form>
    <label for="activity_start_date">Activity start date</label>
    <input type="text" name="activity_start_date" value="">
    <label for="activity_end_date">Activity end date</label>
    <input type="text" name="activity_end_date" value="">
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
         * @param string startDate
         *   "Date from" value to filter Activities by their date
         * @param string endDate
         *   "Date to" value to filter Activities by their date
         */
        function loadData(offset, limit, total, multiValuesOffset, multiValuesTotal, startDate, endDate) {
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
            "startDate": startDate,
            "endDate": endDate
          }).done(function(result) {
            data = data.concat(processData(result['values'][0].data));
            var nextOffset = parseInt(result['values'][0].info.nextOffset, 10);

            if (nextOffset > total) {
              loadComplete(data);

              CRM.alert(total + ' Activities loaded.', '', 'info');
            } else {
              var multiValuesOffset = parseInt(result['values'][0]['info'].multiValuesOffset, 10);
              var multiValuesTotal = parseInt(result['values'][0]['info'].multiValuesTotal, 10);

              loadData(nextOffset, limit, total, multiValuesOffset, multiValuesTotal, startDate, endDate);
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
        var activityReportStartDateInput = CRM.$('input[name="activity_start_date"]', activityReportForm);
        var activityReportEndDateInput = CRM.$('input[name="activity_end_date"]', activityReportForm);

        CRM.$('input[type="button"].apply-filters-button', activityReportForm).click(function(e) {
          var startDateFilterValue = activityReportStartDateInput.val();
          var endDateFilterValue = activityReportEndDateInput.val();

          CRM.$('#activity-report-preloader').removeClass('hidden');
          CRM.$('#activity-report-filters').addClass('hidden');

          loadDataByDateFilter(startDateFilterValue, endDateFilterValue);
        });

        CRM.$('input[type="button"].load-all-data-button', activityReportForm).click(function(e) {
          CRM.confirm({ message: 'This operation may take some time to load all data for big data sets. Do you really want to load all Activities data?' }).on('crmConfirm:yes', function() {
            loadAllData();
          });
        });

        activityReportStartDateInput.crmDatepicker({
          time: false
        });
        activityReportEndDateInput.crmDatepicker({
          time: false
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
            CRM.alert('There are more than 5000 Activities, getting only Activities from last 30 days.', '', 'info');

            CRM.$('input[type="button"].load-all-data-button', activityReportForm).removeClass('hidden');
            var startDateFilterValue = new Date();
            var endDateFilterValue = new Date();
            startDateFilterValue.setDate(startDateFilterValue.getDate() - 30);

            loadDataByDateFilter(startDateFilterValue.toISOString().substring(0, 10), endDateFilterValue.toISOString().substring(0, 10));
          } else {
            loadAllData();
          }
        });

        /**
         * Run data loading by specified start and end date values.
         *
         * @param string startDateFilterValue
         * @param string endDateFilterValue
         */
        function loadDataByDateFilter(startDateFilterValue, endDateFilterValue) {
          resetData();

          activityReportStartDateInput.val(startDateFilterValue).trigger('change');
          activityReportEndDateInput.val(endDateFilterValue).trigger('change');

          var startDate = startDateFilterValue;
          var endDate = endDateFilterValue;
          var params = {
            "sequential": 1,
            "is_current_revision": 1,
            "is_deleted": 0,
            "is_test": 0
          };

          if (startDate) {
            startDate +=  " 00:00:00";
          }
          if (endDate) {
            endDate += " 23:59:59";
          }

          var activityDateFilter = getAPIDateFilter(startDate, endDate);

          if (activityDateFilter) {
            params.activity_date_time = activityDateFilter;
          }

          CRM.$("#activity-report-pivot-table").html('');

          CRM.api3('Activity', 'getcount', params).done(function(result) {
            var totalFiltered = parseInt(result.result, 10);

            if (!totalFiltered) {
              CRM.$('#activity-report-preloader').addClass('hidden');
              CRM.$('#activity-report-filters').removeClass('hidden');

              CRM.alert('There is no Activities created between selected dates.');
            } else {
              CRM.$('span#activity-report-loading-total').text(totalFiltered);

              loadData(0, limit, totalFiltered, 0, 0, startDate, endDate);
            }
          });
        }

        /**
         * Return an array containing API date filter conditions basing on specified
         * dates.
         *
         * @param string $startDate
         * @param string $endDate
         * @return array|NULL
         */
        function getAPIDateFilter(startDate, endDate) {
          var apiFilter = null;

          if (startDate && endDate) {
            apiFilter = {"BETWEEN": [startDate, endDate]};
          }
          else if (startDate && !endDate) {
            apiFilter = {">=": startDate};
          }
          else if (!startDate && endDate) {
            apiFilter = {'<=': endDate};
          }

          return apiFilter;
        }

        /**
         * Run all data loading.
         */
        function loadAllData() {
          resetData();

          activityReportStartDateInput.val(null).trigger('change');

          CRM.$("#activity-report-pivot-table").html('');
          CRM.$('#activity-report-preloader').removeClass('hidden');
          CRM.$('#activity-report-filters').addClass('hidden');
          CRM.$('span#activity-report-loading-total').text(total);

          loadData(0, limit, total, 0, 0, null, null);
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