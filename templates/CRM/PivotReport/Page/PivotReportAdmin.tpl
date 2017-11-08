<div id="pivot-report-admin">
  <input class="build-cache-button" type="button" value="{ts}Refresh All Pivot Reports{/ts}">

  {include file="CRM/PivotReport/Page/ReportPreloader.tpl"}

  <div class="build-date-time">{ts}Last refresh{/ts}: <span>{if $buildDateTime}{$buildDateTime|crmDate}{else}{ts}Never{/ts}{/if}</span></div>
</div>

{literal}
<script type="text/javascript">
    CRM.$(function ($) {
      new CRM.PivotReport.Admin();
    });
</script>
{/literal}
