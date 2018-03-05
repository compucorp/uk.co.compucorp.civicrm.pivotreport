Pivot Report extension
======

This extension provides a CiviCRM report page with Pivot Table containing various CiviCRM Entities data.

Installation
------

Go to
- Administer -> System Settings -> Extensions (for CiviCRM >= 4.7)

and install Pivot Report (uk.co.compucorp.civicrm.pivotreport) extension.

No additional steps are required.

Supported entities
------
Pivot Report extensions supports following CiviCRM entities:
 - Activity
 - Case
 - Contribution
 - Membership
 - Prospect (if Prospect extension is installed)

Usage
------

After installing the new page is available from the top CiviCRM menu:
- Reports -> Pivot Report

About Pivot Table
------

Pivot Table basic function is to enable data exploration and analysis by turning a data set into a summary table and then optionally adding a true 2-d drag'n'drop UI to allow a user to manipulate this summary table, turning it into a pivot table, very similar to the one found in older versions of Microsoft Excel with a bunch of extra developer-oriented features and some visualization effects. The summary table can be rendered as various kinds of charts, turning the pivot table into a pivot chart.

Usage sample presentation:

![image](http://nicolas.kruchten.com/pivottable/images/animation.gif)

Pivot Table User Interface
------

Pivot Table (PivotTable.js library) implements a pivot table drag'n'drop UI similar to that found in popular spreadsheet programs. You can drag attributes into/out of the row/column areas, and specify rendering, aggregation and filtering options. There is a [step-by-step tutorial](https://github.com/nicolaskruchten/pivottable/wiki/UI-Tutorial) in the wiki.

Pivot Report Data
------

The Pivot Table contains entity fields including any custom fields with various data types such as:
- Alphanumeric
- Integer
- Number
- Money
- Note
- Date
- Yes or No
- State/Province
- Country
- File
- Link
- Contact Reference

If a Custom Field relates to Option Group then its value is automatically picked from relevant Option Value so Pivot Table shows human readable values / labels instead of relational ID.


Save and Load Report Configurations
------

If you spent some time creating a fairly lengthy report and you are likely to do this regularly, you can now save your configuration with pivot report!

Just click on "Save As New" button when you are done or "Save Report" button if you improved any existing configuration, by simply select the configuration from the dropdown list you will be able to reproduce any complicated report in no time.


CSV/ TSV Export
------

On the table view, users will be able to export the current report as CSV file or TSV file via the export buttons on the top of the report

Large Dateset Handling
------

We have also recently added an automated solution for handling dataset with size that is too large to be processed instantly.

A "Pivot Report Cache Build (chunk)" scheduled job is now available once the extension is installed. Each time the job is executed, it will build cache for all data records or a part of the data records if necessary. It might take a few executions to complete the cache building for your entire dataset depending on your dataset size but this will allow the entire cache building process to be handled in the background without bringing any performance impact to the normal usage of the system.


Also, with "CiviCRM Reports: Admin Pivot Report" permission, admins will be able to view the last cache refresh time via "Administer -> Pivot Report Configuration" and manually refresh the entire cache when needed.
