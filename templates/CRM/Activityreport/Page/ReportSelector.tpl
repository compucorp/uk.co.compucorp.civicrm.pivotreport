{literal}
<script type="text/javascript">
  CRM.$(function ($) {
    CRM.$('#reportSelectorBtn').on( "click", function() {

      var url = '';

      switch (CRM.$('#CRMData').val()) {
        case 'Activity':
          url = CRM.url('civicrm/activity-report', {});
          break;

        case 'Contribution':
          url = CRM.url('civicrm/contribution-report', {});
          break;

        case 'Membership':
          url = CRM.url('civicrm/membership-report', {});
          break;
      }

      window.location.href = url;
    });
  });
</script>
{/literal}

{if !$cacheBuilt}
  <div class="messages error">
    This report works using a cache built with activities' data, but the cache
    has not been built yet. If you want to load the activity information into
    the report, click on the 'Build Cache' button to start working.
  </div>
{/if}

<form id="whichDataType" method="post">
  Select which CiviCRM data do you want to use? (<em>default: Contribution</em>)
  <select name="CRMData" id="CRMData">
    {html_options options=$options_array selected=$CRMDataType}
  </select>
  <input id="reportSelectorBtn" type="button" value="Go"/>
</form>
