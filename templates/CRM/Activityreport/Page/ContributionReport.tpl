{literal}
<script type="text/javascript">
    CRM.$(function ($) {
      new CRM.PivotReport.PivotTable({
        'entityName': 'Contribution',
        'derivedAttributes': {
          'Date-wise Reciepts': $.pivotUtilities.derivers.dateFormat("Date Received", "%d"),
          'Day-wise Reciepts': $.pivotUtilities.derivers.dateFormat("Date Received", "%w"),
          'Month-wise Reciepts': $.pivotUtilities.derivers.dateFormat("Date Received", "%n"),
          'Year-wise Reciepts': $.pivotUtilities.derivers.dateFormat("Date Received", "%y"),
        }
      });
    });
</script>
{/literal}