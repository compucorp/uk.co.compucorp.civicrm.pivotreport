CRM.PivotReport = CRM.PivotReport || {};

/**
 * Class to Handle Pivot Report export to CSV and TSV formats.
 */
CRM.PivotReport.Export = (function($) {

  /**
   * Initializes Pivot Report Exporter.
   *
   * @param object data
   *   Object holding the data for the pivot report
   * @param object config
   *   Object with the report's current configuration
   *
   * @constructor
   */
  function Export(data, config) {
    this.data = data;
    this.config = config;
  }

  /**
   * Generates export file for the given export type and sends it as a download.
   *
   * @param string type
   *   String representing one of the supported export types.
   */
  Export.prototype.export = function (type) {
    var separator = this.getSeparatorFromType(type);
    var content = this.getContent(separator);
    this.sendContent(content, type);
  }

  /**
   * Sends given content as a file of given type.
   *
   * @param string content
   *   Content of file to be sent.
   * @param string type
   *   Type of the file to be sent.
   */
  Export.prototype.sendContent = function (content, type) {
    var that = this;

    window.saveAs(new Blob([content], {type: 'text/' + type.toLowerCase()})   , 'pivot_report_' + that.getCurrentTimestamp() + '.' + type.toLowerCase());
  }

  /**
   * Builds the export file by traversing the given pivot result and joining
   * fields with the given separator, adding each row on a different line.
   *
   * @param object data
   *   Object holding the pivot report's data
   * @param object config
   *   Object with the current configuration of the pivot report
   * @param string separator
   *   String to be used to separate each field within a row
   *
   * @returns {string}
   *   Content of the export file
   */
  Export.prototype.getContent = function (separator) {
    var pivotResult = this.getPivotResult();
    var content = "";
    var i = pivotResult.length;
    var row = null;
    var n;

    for (n = 0; n < i; n++) {
      row = this.wrapRowValues(pivotResult[n]);
      content += row.join(separator) + "\n";
    }

    return content;
  }

  /**
   * Returns array containing values wrapped with '"'.
   *
   * @param {Array} row
   *
   * @returns {Array}
   */
  Export.prototype.wrapRowValues = function(row) {
    var result = [];

    for (var i = 0; i < row.length; i++) {
      result[i] = '"' + row[i] + '"';
    }

    return result;
  }

  /**
   * Builds a matrix with the data of the pivot report basing on current
   * Pivot Report table structure.
   *
   * @returns {Array}
   *   Matrix holding the pivot report's data in the current configuration
   */
  Export.prototype.getPivotResult = function () {
    var result = [];
    var $table = $('div#pivot-report-table table.pvtTable');

    result = result.concat(this.getTableResult($($table)));

    return result;
  }

  /**
   * Returns array with Pivot Table data created from Table report.
   *
   * @param Pivot Report jQuery table $container
   *
   * @returns {Array}
   */
  Export.prototype.getTableResult = function($container) {
    var result = [];
    var rowInfo = [];
    var $rows = $('tr', $container).toArray();

    for (var index = 0; index < $rows.length; index++) {
      var value = $rows[index];
      var $row = $('th, td', value).toArray();

      if (!rowInfo.length) {
        for (var rowIndex = 0; rowIndex < $row.length; rowIndex++) {
          var cellInfo = this.getCellInfo($row[rowIndex]);
          var cellArray = new Array(cellInfo.colspan);

          for (var i = 0; i < cellArray.length; i++) {
            cellArray[i] = $.extend(true, {}, cellInfo);
          }

          rowInfo = rowInfo.concat(cellArray);
        }
      }

      var resultRow = [];

      for (var i = 0; i < rowInfo.length; i++) {
        var newCell = false;

        if (!rowInfo[i].rowspan) {
          rowInfo[i] = this.getCellInfo($row.shift());
          newCell = true;
        }

        resultRow.push(rowInfo[i].value);

        if (index === 0) {
          rowInfo[i].value = '';
        }
        rowInfo[i].rowspan--;

        if (newCell) {
          if (rowInfo[i].colspan > 1) {
            var colspanValue = rowInfo[i].value;
            if (index === $rows.length - 1) {
              colspanValue = '';
            }

            var cellArray = new Array(rowInfo[i].colspan - 1).fill(colspanValue);
            resultRow = resultRow.concat(cellArray);
            i += rowInfo[i].colspan - 1;
          }
        }
      }

      result.push(resultRow);
    }

    return result;
  }

  /**
   * Returns object with colspan, rowspan and value of specified Pivot Report
   * table cell.
   *
   * @param {type} $cell
   *
   * @returns {PivotReport.ExportExport.Export.prototype.getCellInfo.cellInfo}
   */
  Export.prototype.getCellInfo = function($cell) {
    var colspan = +$($cell).attr('colspan');
    var rowspan = +$($cell).attr('rowspan');

    colspan = !isNaN(colspan) ? colspan : 1;
    rowspan = !isNaN(rowspan) ? rowspan : 1;

    var cellInfo = {
      'colspan': colspan,
      'rowspan': rowspan,
      'value': $($cell).text()
    };

    return cellInfo;
  }

  /**
   * Returns separator to be used on the export, given it's type.
   *
   * @param string type
   *   Type of the report o be built.
   *
   * @returns {string}
   */
  Export.prototype.getSeparatorFromType = function (type) {
    var separator;

    switch (type) {
      case 'TSV':
        separator = "\t";
        break;

      case 'CSV':
        separator = ',';
        break;

      default:
        separator = ';'
        break;
    }

    return separator;
  }

  /**
   * Returns current date in YYYYMMDD_HHII format.
   *
   * @returns {String}
   */
  Export.prototype.getCurrentTimestamp = function () {
    var now = new Date();
    var month = now.getMonth() + 1;
    var day = now.getDate();
    var date = [now.getFullYear(), ('0' + month).substring(month.length), ('0' + day).substring(day.length)];
    var time = [now.getHours(), now.getMinutes()];

    return date.join('') + '_' + time.join('');
  }

  return Export;
})(CRM.$);
