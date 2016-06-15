<h3>Activity Pivot Table</h3>

<div id="reportPivotTable"></div>

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
        var data = {/literal}{$activityData}{literal};
        
        /*** PivotTable library initialization: ***/
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
    });
</script>
{/literal}
