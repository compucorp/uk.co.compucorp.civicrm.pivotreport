<h3>Activity Pivot Table</h3>

<div id="activity-report-preloader">
  Loading<span id="activity-report-loading-count"></span>.
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
        var header = [];
        var data = [];
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
         * @param string startDate
         *   "Date from" value to filter Activities by their date
         * @param string endDate
         *   "Date to" value to filter Activities by their date
         * @param int page
         *   Page offset to start with (initially should be 0)
         */
        function loadData(startDate, endDate, page) {
          CRM.$('span#activity-report-loading-count').append('.');

          CRM.api3('ActivityReport', 'get', {
            "sequential": 1,
            "start_date": startDate,
            "end_date": endDate,
            "page": page
          }).done(function(result) {
            data = data.concat(processData(result['values'][0].data));
            var nextDate = result['values'][0].nextDate;
            var nextPage = result['values'][0].nextPage;

            if (nextDate === '') {
              loadComplete(data);
            } else {
              loadData(nextDate, endDate, nextPage);
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

        // Initially we load header and check total number of Activities
        // and then start data fetching.
        CRM.api3('ActivityReport', 'getheader', {
        }).done(function(result) {
          header = result.values;

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

              loadData(startDate, endDate, 0);
            }
          });
        }

        /**
         * Return an array containing API date filter conditions basing on specified
         * dates.
         *
         * @param string startDate
         * @param string endDate
         * @return object
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

          loadData(null, null, 0);
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