{literal}
<script type="text/javascript">
    CRM.$(function ($) {
      new CRM.PivotReport.PivotTable({
        'entityName': 'Contribution',
        'cacheBuilt': {/literal}{$cacheBuilt|var_export:true}{literal},
      });
    });
</script>
{/literal}
