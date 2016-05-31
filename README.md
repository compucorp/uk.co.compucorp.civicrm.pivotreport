Activity Report extension
======

This extension provides a CiviCRM report page with Pivot Table containing Activity data.

Installation
------

Go to 
- Administer -> System Settings -> Manage Extensions (for CiviCRM < 4.7)
- Administer -> System Settings -> Extensions (for CiviCRM >= 4.7)

and install Activity Report (uk.co.compucorp.civicrm.activityreport) extension.

No additional steps is required.

Usage
------

After installing the new page is available from the top CiviCRM menu:
- Reports -> Activity Report

About Pivot Table
------

Pivot Table basic function is to enable data exploration and analysis by turning a data set into a summary table and then optionally adding a true 2-d drag'n'drop UI to allow a user to manipulate this summary table, turning it into a pivot table, very similar to the one found in older versions of Microsoft Excel with a bunch of extra developer-oriented features and some visualization effects. The summary table can be rendered as various kinds of charts, turning the pivot table into a pivot chart.

Usage sample presentation:

![image](http://nicolas.kruchten.com/pivottable/images/animation.gif)

Pivot Table User Interface
------

Pivot Table (PivotTable.js library) implements a pivot table drag'n'drop UI similar to that found in popular spreadsheet programs. You can drag attributes into/out of the row/column areas, and specify rendering, aggregation and filtering options. There is a [step-by-step tutorial](https://github.com/nicolaskruchten/pivottable/wiki/UI-Tutorial) in the wiki.

Activity Data
------

The Pivot Table contains every Activity field including any Custom Fields with various data types such as:
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

