<h3>Activity Pivot Table</h3>

<div id="reportPivotTable"></div>

{literal}
<script type="text/javascript">
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
                "Activity Date Months": jQuery.pivotUtilities.derivers.dateFormat("Activity Date", "%y-%m")
            }
        }, false);
    });
</script>
{/literal}
