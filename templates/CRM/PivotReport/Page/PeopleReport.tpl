<div id="pivot-report-filters" class="hidden">
  <form>
    <fieldset>
      <legend>Working Dataset Filter</legend>
      <p>
        The total number of items exceeds 1000. Only last 30 days loaded.
        Use this form to change the date range for which data needs to be
        visualized.
      </p>
      <label for="keyvalue_from">Load Contacts From:</label>
      <input type="text" name="keyvalue_from" value="">
      <label for="keyvalue_to">To:</label>
      <input type="text" name="keyvalue_to" value="">
      <input class="apply-filters-button" type="button" value="Apply filters">
      <input class="load-all-data-button hidden" type="button" value="Load all data">
    </fieldset>
  </form>
</div>
<div id="pivot-report-custom-filter-form" class="hidden">
  <form>
    <label>Headcount on date:</label><br />
    <input name="headCountOnDate" class="crm-ui-datepicker" />
    <hr />
    <button class"btn btn-primary" type="submit">
      Filter
    </button>
  </form>
</div>
{literal}
<script type="text/javascript">
  CRM.$(function ($) {
    new CRM.PivotReport.PivotTable({
      'entityName': 'People',
      'cacheBuilt': {/literal}{$cacheBuilt|var_export:true}{literal},
      'filter': true,
      'filterField': 'Contract Start Date',
      'customFilter': customFilter,
      'initialLoad': {
        'limit': 1000,
        'message': 'There are more than 1000 items, getting only items from last 30 days.',
        'getFilter': function () {
          var startDateFilterValue = new Date();
          var endDateFilterValue = new Date();
          startDateFilterValue.setDate(startDateFilterValue.getDate() - 30);

          return new CRM.PivotReport.Filter(startDateFilterValue.toISOString().substring(0, 10), endDateFilterValue.toISOString().substring(0, 10));
        }
      },
      'initFilterForm': function(keyValueFromField, keyValueToField) {
        keyValueFromField.crmDatepicker({
          time: false
        });
        keyValueToField.crmDatepicker({
          time: false
        });
      }
    });

    /**
     * Returns true when the people record has a contract and a job role that
     * fall between the custom filter dates selected by the user.
     *
     * @param {Object} record - the people record.
     * @return {Boolean}
     */
    function customFilter (record) {
      var endsAfterDate, hasValidStartDates, startsBeforeDate;
      var date = moment(this.customFilterValues.headCountOnDate);
      var contract = {
        start: moment(record['Contract Start Date']),
        end: moment(record['Contract End Date'])
      };
      var role = {
        start: moment(record['Role Start Date']),
        end: moment(record['Role End Date'])
      };

      hasValidStartDates = contract.start.isValid() && role.start.isValid();
      startsBeforeDate = contract.start.isSameOrBefore(date) && role.start.isSameOrBefore(date);
      endsAfterDate = (!contract.end.isValid() || date.isBetween(contract.start, contract.end, null, '[]'))
        && (!role.end.isValid() || date.isBetween(role.start, role.end, null, '[]'));

      return hasValidStartDates && startsBeforeDate && endsAfterDate;
    }
  });
</script>
{/literal}
