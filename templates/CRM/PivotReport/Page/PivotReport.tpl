{if !$cacheBuilt}
  <div class="messages error">
    {ts}The report data has not yet been built. Please contact your admin. Or if you are an admin, please go to <a href="/civicrm/pivot-report-config">Pivot Report Config</a> to refresh the data.{/ts}
  </div>
{else}
  {include file="CRM/PivotReport/Page/ReportConfig.tpl"}
  {include file="CRM/PivotReport/Page/ReportPreloader.tpl"}
  {include file="CRM/PivotReport/Page/`$CRMDataType`Report.tpl"}
  <div id="pivot-report-table" class="hidden">
  </div>
{/if}
