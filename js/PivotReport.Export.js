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
    var content = this.getContent(this.data, this.config, separator);
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
  Export.prototype.getContent = function (data, config, separator) {
    var pivotResult = this.getPivotResult(data, config);

    var content = "";
    var i = pivotResult.length;
    var row = null;
    var n;

    for (n = 0; n < i; n++) {
      row = pivotResult[n];
      content += row.join(separator) + "\n";
    }

    return content;
  }

  /**
   * Builds a matrix with the data of the pivot report, given it's current
   * configuration.
   *
   * @param object data
   *   Object holding the pivot report's data
   * @param object config
   *   Object with the current configuration of the pivot report
   *
   * @returns {Array}
   *   Matrix holding the pivot report's data in the current configuration
   */
  Export.prototype.getPivotResult = function (data, config) {
    var header = this.getResultHeader(data.rowAttrs, this.getColKeys(data), data.aggregatorName);
    return this.getResultArray(data, header, this.getRowKeys(data), this.getColKeys(data));
  }

  /**
   * Returns the keys for rows.
   *
   * @param object data
   *   Object holding the pivot report's data
   *
   * @returns {array}
   *   List of keys for rows
   */
  Export.prototype.getRowKeys = function (data) {
    var rowKeys = data.getRowKeys();

    if (rowKeys.length === 0) {
      rowKeys.push([]);
    }

    return rowKeys;
  }

  /**
   * Builds a matrix with the report's data, given a header and the report's row
   * and column keys.
   *
   * @param object pivotData
   *   Object holding the pivot report's data
   * @param Array header
   *   Array with the report's first row
   * @param Array rowKeys
   *   Array with the report's row keys
   * @param Array colKeys
   *   Array with the report's column keys
   *
   * @returns {Array}
   *   Matrix holding the pivot report's data with the given row and column keys
   */
  Export.prototype.getResultArray = function (pivotData, header, rowKeys, colKeys) {
    var result = [];
    result.push(header);

    for (var rowKeysIndex = 0; rowKeysIndex < rowKeys.length; rowKeysIndex++) {
      result.push(
        this.buildPivotColData(
          pivotData,
          this.buildPivotRowData(
            [],
            rowKeys[rowKeysIndex]
          ),
          rowKeys[rowKeysIndex],
          colKeys,
          rowKeysIndex
        )
      );
    }

    result.push(this.getTotals());

    return result;
  }

  /**
   * Builds Totals row.
   *
   * @returns {Array}
   */
  Export.prototype.getTotals = function() {
    var $tr = $('table.pvtTable tbody tr:last');
    var colSpan = $('th.pvtTotalLabel', $tr).attr('colspan');
    var label = $('th.pvtTotalLabel', $tr).text();
    var total = $('td.pvtGrandTotal', $tr).text();
    var row = new Array(colSpan - 1);

    row.push('"' + label + '"');
    row.push(total);

    return row;
  }

  /**
   * Pushes into  row the given row keys.
   *
   * @param Array row
   *   Row being built
   * @param Array rowKey
   *   Keys for the row
   *
   * @returns {Array}
   */
  Export.prototype.buildPivotRowData = function (row, rowKey) {
    var i;
    var value;

    for (i = 0; i < rowKey.length; i++) {
      value = rowKey[i];

      if (isNaN(rowKey[i])) {
        value = '"' + rowKey[i] + '"';
      }
      row.push(value);
    }

    return row;
  }

  /**
   * Adds the aggregated values for the given row for each dolumn in the report.
   *
   * @param pivotData
   *   Object holding the pivot report's data
   * @param row
   *   Current row being built
   * @param rowKey
   *   Keys for the row
   * @param colKeys
   *   Keys for each column
   * @param index
   *   Row index
   *
   * @returns {Array}
   *   Complete row for the report
   */
  Export.prototype.buildPivotColData = function (pivotData, row, rowKey, colKeys, index) {
    var aggregatorValue = null;
    var i;
    var $tr = $('table.pvtTable tbody tr')[index];

    for (i = 0; i < colKeys.length; i++) {
      aggregatorValue = $('td.rowTotal', $tr).text();
      if (aggregatorValue !== null) {
        row.push(aggregatorValue);
      } else {
        row.push("");
      }
    }

    return row;
  }

  /**
   * Builds the first row for the report.
   *
   * @param Array rowAttrs
   *   First columns of the report, basically the fields added to rows
   * @param Array colKeys
   *   Keys for each column
   * @param string aggregatorName
   *   Name of the aggregator being used
   *
   * @returns {Array}
   *   First row of the report
   */
  Export.prototype.getResultHeader = function (rowAttrs, colKeys, aggregatorName) {
    var row = [];
    var i;

    for (i = 0; i < rowAttrs.length; i++) {
      row.push('"' + rowAttrs[i] + '"');
    }

    if (colKeys.length === 1 && colKeys[0].length === 0) {
      row.push('"' + aggregatorName + '"');
    } else {
      for (i = 0; i < colKeys.length; i++) {
        row.push('"' + colKeys[i].join(' - ') + '"');
      }
    }

    return row;
  }

  /**
   * Returns list of column keys for the report.
   *
   * @param data
   *   Object holding the pivot report's data
   * @returns {Array}
   */
  Export.prototype.getColKeys = function (data) {
    var colKeys = data.getColKeys();

    if (colKeys.length === 0) {
      colKeys.push([]);
    }

    return colKeys;
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
