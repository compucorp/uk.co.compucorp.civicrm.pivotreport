<h3>Activity Pivot Table</h3>

<div id="reportPivotTable">
  <div id="activity-report-preloader">
    Loading <span id="activity-report-loading-count">0</span> of <span id="activity-report-loading-total">0</span> Activities.
  </div>
  <div id="activity-report-debug-container">
    <div id="activity-report-loader-messages"></div>
  </div>
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

        function loadData(offset, limit, total, multiValuesOffset, multiValuesTotal) {
          CRM.$('span#activity-report-loading-count').text(offset);
          var localLimit = limit;
          if (multiValuesOffset > 0 && multiValuesTotal > 0) {
            localLimit = limit - (multiValuesTotal - multiValuesOffset);
          }
          if (multiValuesTotal - multiValuesOffset > limit) {
            localLimit = 1;
          }
          CRM.api3('ActivityReport', 'get', {
            "sequential": 1,
            "offset": offset,
            "limit": localLimit,
            "multiValuesOffset": multiValuesOffset
          }).done(function(result) {
            if (result['values'][0]['info'].messages.length) {
              for (var i in result['values'][0]['info'].messages) {
                CRM.$('div#activity-report-loader-messages').append(result['values'][0]['info'].messages[i] + '<br>');
              }
            }
            data = data.concat(processData(result['values'][0].data));
            var nextOffset = parseInt(result['values'][0].info.nextOffset, 10);
            if (nextOffset > total) {
              initPivotTable(data);
            } else {
              var multiValuesOffset = parseInt(result['values'][0]['info'].multiValuesOffset, 10);
              var multiValuesTotal = parseInt(result['values'][0]['info'].multiValuesTotal, 10);
              loadData(nextOffset, limit, total, multiValuesOffset, multiValuesTotal);
            }
          });
        }

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

        CRM.api3('Activity', 'getcount', {
          "sequential": 1,
          "is_current_revision": 1,
          "is_deleted": 0,
        }).done(function(result) {
          var total = parseInt(result.result, 10);
          CRM.$('span#activity-report-loading-total').text(total);
          loadData(0, limit, total, 0, 0);
        });

        function initPivotTable(data) {
          jQuery("#reportPivotTable").pivotUI(data, {
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
                          width: parseInt(jQuery('#reportPivotTable').width() * 0.78, 10)
                      }
                  },
              },
              derivedAttributes: {
                  "Activity Date": jQuery.pivotUtilities.derivers.dateFormat("Activity Date Time", "%y-%m-%d"),
                  "Activity Start Date Months": jQuery.pivotUtilities.derivers.dateFormat("Activity Date Time", "%y-%m"),
                  "Activity is a test": function(row) {
                      if (parseInt(row["Activity is a test"], 10) === 0) {
                          return "No";
                      }
                      return "Yes";
                  },
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
