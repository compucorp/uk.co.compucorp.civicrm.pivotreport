{literal}
<script type="text/javascript">
  CRM.$(function ($) {
    CRM.$('#reportSelectorBtn').on( "click", function() {
      var url = CRM.url('civicrm/' + CRM.$('#CRMData').val().toLocaleLowerCase() + '-report', {});
      window.location.href = url;
    });
  });
</script>
{/literal}

{if !$cacheBuilt}
  <div class="messages error">
    This report works using a cache built with data in CiviCRM, but the cache
    has not been built yet. If you want to load the information into the report,
    click on the 'Build Cache' button to start working.
  </div>
{/if}

<form id="whichDataType" method="post">
  Select which CiviCRM data do you want to use?
  <select name="CRMData" id="CRMData">
    {html_options options=$options_array selected=$CRMDataType}
  </select>
  <input id="reportSelectorBtn" type="button" value="Go"/>
  <input class="build-cache-button hidden" type="button" value="{if !$cacheBuilt}Build Cache{else}Rebuild Cache{/if}">
</form>