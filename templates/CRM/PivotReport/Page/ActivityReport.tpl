<div id="pivot-report-filters" class="hidden">
  <form>
    <fieldset>
      <legend>Working Dataset Filter</legend>
      <p>
        The total number of activities exceeds 50,000. Only last 30 days loaded.
        Use this form to change the date range for which data needs to be
        visualized.
      </p>
      <label for="keyvalue_from">Load Activities From:</label>
      <input type="text" name="keyvalue_from" value="">
      <label for="keyvalue_to">To:</label>
      <input type="text" name="keyvalue_to" value="">
      <input class="apply-filters-button" type="button" value="Apply filters">
      <input class="load-all-data-button hidden" type="button" value="Load all data">
    </fieldset>
  </form>
</div>

{literal}
<script type="text/javascript">
    CRM.$(function ($) {
      new CRM.PivotReport.PivotTable({
        'entityName': 'Activity',
        'cacheBuilt': {/literal}{$cacheBuilt|var_export:true}{literal},
        'filter': true,
        'filterField': 'Activity Date',
        'initialLoad': {
          'limit': 50000,
          'message': 'There are more than 50000 items, getting only items from last 30 days.',
          'getFilter': function() {
            var startDateFilterValue = new Date();
            var endDateFilterValue = new Date();
            startDateFilterValue.setDate(startDateFilterValue.getDate() - 30);

            return new CRM.PivotReport.Filter(startDateFilterValue.toISOString().substring(0, 10), endDateFilterValue.toISOString().substring(0, 10));
          }
        },
        'getCountParams': function(startDate, endDate) {
          var params = {
            'sequential': 1,
            'is_current_revision': 1,
            'is_deleted': 0,
            'is_test': 0
          };

          var apiFilter = null;

          if (startDate && endDate) {
            apiFilter = {'BETWEEN': [startDate, endDate]};
          }
          else if (startDate && !endDate) {
            apiFilter = {'>=': startDate};
          }
          else if (!startDate && endDate) {
            apiFilter = {'<=': endDate};
          }

          if (apiFilter) {
            params.activity_date_time = apiFilter;
          }

          return params;
        },
        'initFilterForm': function(keyValueFromField, keyValueToField) {
          keyValueFromField.crmDatepicker({
            time: false
          });
          keyValueToField.crmDatepicker({
            time: false
          });
        },
        'derivedAttributes': {
          'Activity Date': $.pivotUtilities.derivers.dateFormat('Activity Date Time', '%y-%m-%d'),
          'Activity Expire Date': function(row) {
            if (!row['Expire Date']) {
              return '';
            }
            var expireDateParts = row['Expire Date'].split(/(\d{1,2})\/(\d{1,2})\/(\d{4})/);
            return expireDateParts[3] + '-' + expireDateParts[1] + '-' + expireDateParts[2];
          }
        },
        'hiddenAttributes': ['Test', 'Expire Date']
      });
    });
</script>
{/literal}
