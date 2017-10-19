<h3>{$reportTitle}</h3>
{include file="CRM/PivotReport/Page/ReportSelector.tpl"}
{include file="CRM/PivotReport/Page/ReportConfig.tpl"}
<div id="pivot-report-preloader">
  Loading<span id="pivot-report-loading-count"></span>.
</div>
{include file="CRM/PivotReport/Page/`$CRMDataType`Report.tpl"}
<div id="pivot-report-table">
</div>
