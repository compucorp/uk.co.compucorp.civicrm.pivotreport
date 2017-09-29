{literal}
<script type="text/javascript">
    CRM.$(function ($) {
      new CRM.PivotReport.PivotTable({
        'entityName': 'Membership',
        'derivedAttributes': {
          'Date-wise New Members': $.pivotUtilities.derivers.dateFormat("Member Since", "%d"),
          'Day-wise New Members': $.pivotUtilities.derivers.dateFormat("Member Since", "%w"),
          'Month-wise New Members': $.pivotUtilities.derivers.dateFormat("Member Since", "%n"),
          'Year-wise New Members': $.pivotUtilities.derivers.dateFormat("Member Since", "%y"),
        }
      });
    });
</script>
{/literal}
