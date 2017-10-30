<h3>{$reportTitle}</h3>
{include file="CRM/PivotReport/Page/ReportSelector.tpl"}
{include file="CRM/PivotReport/Page/ReportConfig.tpl"}
{include file="CRM/PivotReport/Page/ReportPreloader.tpl"}
{include file="CRM/PivotReport/Page/`$CRMDataType`Report.tpl"}
<div id="pivot-report-table" class="hidden">
</div>
