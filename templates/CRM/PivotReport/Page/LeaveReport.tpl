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

{literal}
<script type="text/javascript">
    CRM.$(function ($) {
        new CRM.PivotReport.PivotTable({
            'entityName': 'Leave',
            'cacheBuilt': {/literal}{$cacheBuilt|var_export:true}{literal},
            'filter': true,
            'filterField': 'Absence Date',
            'initialLoad': {
                'limit': 1000,
                'message': 'There are more than 1000 items, getting only items from last 30 days.',
                'getFilter': function() {
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
            },
            'derivedAttributes': {
                'Absence Start Month': $.pivotUtilities.derivers.dateFormat('Absence Start Date', '%y-%m'),
                'Absence End Month': $.pivotUtilities.derivers.dateFormat('Absence End Date', '%y-%m'),
                'Group By Month': $.pivotUtilities.derivers.dateFormat('Absence Date', '%y-%m'),
                'Absence Day of Week': $.pivotUtilities.derivers.dateFormat('Absence Date', '%w'),
                'Contract Start Date (Grouped by month)': $.pivotUtilities.derivers.dateFormat('Contract Start Date', '%y-%m'),
                'Contract End Date (Grouped by month)': $.pivotUtilities.derivers.dateFormat('Contract End Date', '%y-%m'),
                'Role Start Date (Grouped by month)': $.pivotUtilities.derivers.dateFormat('Role Start Date', '%y-%m'),
                'Role End Date (Grouped by month)': $.pivotUtilities.derivers.dateFormat('Role End Date', '%y-%m'),
                'Activity Duration In Days': function(row) {
                    if (row['Absence Calculation Unit'] === 'Days') {
                        return Math.abs(row['Absence Amount']).toFixed(2);
                    }

                    return '';
                },
                'Activity Duration In Hours': function(row) {
                    if (row['Absence Calculation Unit'] === 'Hours') {
                        return Math.abs(row['Absence Amount']).toFixed(2);
                    }

                    return '';
                },
                'Absence Amount Taken': function(row) {
                    if (!row['Is TOIL']) {
                        return Math.abs(row['Absence Amount']).toFixed(2);
                    }

                    return '';
                },
                'Absence Amount Accrued': function(row) {
                    if (row['Is TOIL']) {
                        return Math.abs(row['Absence Amount']).toFixed(2);
                    }

                    return '';
                },
                'Absence Absolute Amount': function(row) {
                    return Math.abs(row['Absence Amount']).toFixed(2);
                },
                'Absence Is Credit': function(row) {
                    if (row['Is TOIL']) {
                        return 'Yes';
                    }

                    return 'No';
                }
            },
            'hiddenAttributes': ['Is TOIL', 'Absence Amount', 'Absence Calculation Unit', 'Is TOIL']
        });
    });
</script>
{/literal}
