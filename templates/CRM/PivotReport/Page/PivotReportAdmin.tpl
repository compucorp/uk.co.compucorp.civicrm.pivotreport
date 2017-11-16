<div id="pivot-report-admin">
  <div class="rebuild-pivot-report-cache">
    <input class="build-cache-button" type="button" value="{ts}Refresh All Pivot Reports{/ts}">
    <div class="in-progress hidden">
      <i class="fa fa-spinner fa-spin"></i>
      <span>{ts}Building report cache. This might take a few minutes.{/ts}</span>
    </div>

    {include file="CRM/PivotReport/Page/ReportPreloader.tpl"}

    <div class="build-date-time">{ts}Last refresh{/ts}: <span>{if $buildDateTime}{$buildDateTime|crmDate}{else}{ts}Never{/ts}{/if}</span></div>
  </div>
</div>

{literal}
<script type="text/javascript">
    CRM.$(function ($) {
      new CRM.PivotReport.Admin();
    });
</script>
{/literal}
