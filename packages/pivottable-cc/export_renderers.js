(function() {
  var callWithJQuery;

  callWithJQuery = function(pivotModule) {
    if (typeof exports === "object" && typeof module === "object") {
      return pivotModule(require("jquery"));
    } else if (typeof define === "function" && define.amd) {
      return define(["jquery"], pivotModule);
    } else {
      return pivotModule(jQuery);
    }
  };

  callWithJQuery(function($) {

    /**
     * Returns an array containing a pivot table result.
     *
     * @param {object} pivotData
     * @param {object} opts
     * @returns {Array}
     */
    function getPivotResult(pivotData, opts) {
      var defaults = {
        localeStrings: {}
      };
      opts = $.extend(defaults, opts);

      var row = getResultHeader(pivotData.rowAttrs, getColKeys(pivotData), pivotData.aggregatorName);

      return getResultArray(pivotData, row, getRowKeys(pivotData), getColKeys(pivotData));
    }

    /**
     * Returns a row containing column names.
     *
     * @param {Array} rowAttrs
     * @param {Array} colKeys
     * @param {string} aggregatorName
     * @returns {Array}
     */
    function getResultHeader(rowAttrs, colKeys, aggregatorName) {
      var row = [];
      var rowAttr = null;
      var colKey = null;
      var i;

      for (i = 0; i < rowAttrs.length; i++) {
        rowAttr = rowAttrs[i];
        row.push(rowAttr);
      }

      if (colKeys.length === 1 && colKeys[0].length === 0) {
        row.push(aggregatorName);
      } else {
        for (i = 0; i < colKeys.length; i++) {
          colKey = colKeys[j];
          row.push(colKey.join("-"));
        }
      }

      return row;
    }

    /**
     * Returns an array containing pivot table result rows.
     *
     * @param {object} pivotData
     * @param {Array} row
     * @param {Array} rowKeys
     * @param {Array} colKeys
     * @returns {Array}
     */
    function getResultArray(pivotData, row, rowKeys, colKeys) {
      var result = [];
      var rowKeysIndex = null;

      result.push(row);

      for (rowKeysIndex = 0; rowKeysIndex < rowKeys.length; rowKeysIndex++) {
        result.push(
          buildPivotColData(
            pivotData,
            buildPivotRowData(
              [],
              rowKeys[rowKeysIndex]
            ),
            rowKeys[rowKeysIndex],
            colKeys
          )
        );
      }

      return result;
    }

    /**
     * Returns an array containing pivot table row keys.
     *
     * @param {object} pivotData
     * @returns {Array}
     */
    function getRowKeys(pivotData) {
      var rowKeys = pivotData.getRowKeys();

      if (rowKeys.length === 0) {
        rowKeys.push([]);
      }

      return rowKeys;
    }

    /**
     * Returns an array containing pivot table column keys.
     *
     * @param {object} pivotData
     * @returns {Array}
     */
    function getColKeys(pivotData) {
      var colKeys = pivotData.getColKeys();

      if (colKeys.length === 0) {
        colKeys.push([]);
      }

      return colKeys;
    }

    /**
     * Returns a row merged with specified row key array.
     *
     * @param {Array} row
     * @param {Array} rowKey
     * @returns {Array}
     */
    function buildPivotRowData(row, rowKey) {
      var i;

      for (i = 0; i < rowKey.length; i++) {
        row.push(rowKey[i]);
      }

      return row;
    }

    /**
     * Returns a row merged with specified column keys array.
     *
     * @param {object} pivotData
     * @param {Array} row
     * @param {Array} rowKey
     * @param {Array} colKeys
     * @returns {Array}
     */
    function buildPivotColData(pivotData, row, rowKey, colKeys) {
      var colKey = null;
      var aggregator = null;
      var i;

      for (i = 0; i < colKeys.length; i++) {
        colKey = colKeys[i];
        aggregator = pivotData.getAggregator(rowKey, colKey);

        if (aggregator.value() !== null) {
          row.push(aggregator.value());
        } else {
          row.push("");
        }
      }

      return row;
    }

    /**
     * Returns a string containing a set of result rows and columns
     * joined by specified separator.
     *
     * @param {Array} result
     * @param {string} separator
     * @returns {String}
     */
    function getResultContent(result, separator) {
      var content = "";
      var i = result.length;
      var row = null;
      var n;

      for (n = 0; n < i; n++) {
        row = result[n];
        content += row.join(separator) + "\n";
      }

      return content;
    }

    /**
     * Returns current date in YYYYMMDD_HHII format.
     *
     * @returns {String}
     */
    function getCurrentTimestamp() {
      var now = new Date();
      var month = now.getMonth() + 1;
      var day = now.getDate();
      var date = [now.getFullYear(), ('0' + month).substring(month.length), ('0' + day).substring(day.length)];
      var time = [now.getHours(), now.getMinutes()];

      return date.join('') + '_' + time.join('');
    }

    /**
     * Extends pivot renderers list.
     */
    return $.pivotUtilities.export_renderers = {
      /**
       * Adds "TSV Export" renderer to pivot renderers.
       */
      "TSV Export": function(pivotData, opts) {
        var content = getResultContent(getPivotResult(pivotData, opts), "\t");

        return $('<a id="download" href="data:text/tsv,' + encodeURIComponent(content) + '"> Download as a TSV File </a>').click(function() {
          $('#download').attr('download', 'pivot_report_' + getCurrentTimestamp() + '.tsv');
        });
     },

      /**
       * Adds "CSV Export" renderer to pivot renderers.
       */
    "CSV Export": function(pivotData, opts) {
        var content = getResultContent(getPivotResult(pivotData, opts), ",");

        return $('<a id="download" href="data:text/csv,' + encodeURIComponent(content) + '"> Download as a CSV File </a>').click(function() {
          $('#download').attr('download', 'pivot_report_' + getCurrentTimestamp() + '.csv');
        });
      }
    };
  });
}).call(this);

//# sourceMappingURL=export_renderers.js.map
