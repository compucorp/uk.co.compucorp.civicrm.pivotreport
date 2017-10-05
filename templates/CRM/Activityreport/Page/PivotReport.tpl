<h3>{$reportTitle}</h3>
{include file="CRM/Activityreport/Page/ReportSelector.tpl"}
{include file="CRM/Activityreport/Page/ReportConfig.tpl"}
<div id="pivot-report-preloader">
  Loading<span id="pivot-report-loading-count"></span>.
</div>
{include file="CRM/Activityreport/Page/`$CRMDataType`Report.tpl"}
<div id="pivot-report-table">
</div>
