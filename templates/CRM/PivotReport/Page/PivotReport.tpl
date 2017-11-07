{if !$cacheBuilt}
  <div class="messages error">
    This report works using a cache built with data in CiviCRM, but the cache
    has not been built yet. If you want to load the information into the report,
    click on the 'Build Cache' button to start working.
  </div>
{else}
  {include file="CRM/PivotReport/Page/ReportConfig.tpl"}
  {include file="CRM/PivotReport/Page/ReportPreloader.tpl"}
  {include file="CRM/PivotReport/Page/`$CRMDataType`Report.tpl"}
  <div id="pivot-report-table" class="hidden">
  </div>
{/if}
