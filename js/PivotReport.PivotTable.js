CRM.PivotReport = CRM.PivotReport || {};

CRM.PivotReport.PivotTable = (function($) {

  /**
   * Initializes Pivot Table.
   *
   * @param {object} config
   */
  function PivotTable(config) {
    var defaults = {
      'entityName': null,
      'cacheBuilt': true,
      'filter': false,
      'initialLoad': {
        'limit': 0,
        'message': '',
        'getFilter': function() {
          return new CRM.PivotReport.Filter(null, null);
        }
      },
      'getCountParams': function(keyValueFrom, keyValueTo) {
        return {};
      },
      'initFilterForm': function(keyValueFromField, keyValueToField) {},
      'derivedAttributes': {},
      'hiddenAttributes': []
    };

    this.config = $.extend(true, {}, defaults, config);

    this.pivotTableContainer = $('#pivot-report-table');
    this.header = [];
    this.data = [];
    this.total = 0;
    this.pivotCount = 0;
    this.totalLoaded = 0;
    this.uniqueLoaded = [];
    this.pivotReportForm = null;
    this.pivotReportKeyValueFrom = null;
    this.pivotReportKeyValueTo = null;
    this.dateFields = null;
    this.relativeFilters = null;
    this.crmConfig = null;
    this.PivotConfig = new CRM.PivotReport.Config(this);
    this.Preloader = new CRM.PivotReport.Preloader();

    this.removePrintIcon();
    this.initFilterForm();
    this.initPivotDataLoading();
    this.checkCacheBuilt();
  };

  /**
   * Removes standard Print CiviCRM icon on Pivot Report pages.
   */
  PivotTable.prototype.removePrintIcon = function() {
    $('div#printer-friendly').remove();
  }

  PivotTable.prototype.checkCacheBuilt = function () {
    if (this.config.cacheBuilt) {
      $('#pivot-report-config, #pivot-report-filters, #pivot-report-table').removeClass('hidden');
    } else {
      $('#pivot-report-config, #pivot-report-filters, #pivot-report-table').addClass('hidden');
    }
  }

  /**
   * Initializes date filters for each field of Date data type.
   */
  PivotTable.prototype.initDateFilters = function () {
    var that = this;

    $('div.pvtFilterBox').each(function () {
      var container = $(this);
      var fieldName = '';

      $(this).children().each(function () {

        if ($(this).prop("tagName") == 'H4') {
          fieldName = $(this).text().replace(/[ ()0-9?]/g, '');

          if ($.inArray($(this).text().replace(/[()0-9?]/g, ''), that.dateFields) >= 0) {
            $(this).after('' +
              '<div id="inner_' + fieldName + '" class="inner_date_filters">' +
              ' <form>' +
              '   <input type="text" id="fld_' + fieldName + '_start" name="fld_' + fieldName + '_start" class="inner_date fld_' + fieldName + '_start" value=""> - ' +
              '   <input type="text" id="fld_' + fieldName + '_end" name="fld_' + fieldName + '_end" class="inner_date fld_' + fieldName + '_end" value="">' +
              ' </form>' +
              '</div>'
            );

            var selectContainer = $('<p>');
            var relativeSelect = $('<select>');
            relativeSelect.attr('name', 'sel_' + fieldName);
            relativeSelect.addClass('relativeFilter');
            relativeSelect.change(function () {
              that.changeFilterDates($(this));
            });

            relativeSelect.append($("<option>").attr('value', '').text('- Any -'));
            $(that.relativeFilters).each(function () {
              relativeSelect.append($("<option>").attr('value', this.value).text(this.label));
            });

            selectContainer.append(relativeSelect);
            $(this).after(selectContainer);

            $('.pvtFilter', container).addClass(fieldName);
            $('.pvtSearch', container).hide();

            $(':button', container).each(function () {
              if ($(this).text() == 'Select All') {
                $(this).addClass(fieldName + '_batchSelector');
                $(this).off('click');
                $(this).on('click', function () {
                  $('#fld_' + fieldName + '_start').change();
                  $('input.inner_date.fld_' + fieldName + '_start.hasDatepicker').val($('#fld_' + fieldName + '_start').val());
                });
              }

              if ($(this).text() == 'Apply' || $(this).text() == 'Cancel') {
                $(this).on('click', function () {
                  that.filterDateValues(fieldName, true);
                });
              }
            });
          }
        }
      });
    });

    $('.inner_date').each(function () {

      $(this).change(function () {
        var fieldInfo = $(this).attr('name').split('_');
        that.filterDateValues(fieldInfo[1]);
      });

      $(this).crmDatepicker({
        time: false
      });
    });

  };

  /**
   * Given a fieldname, uses start and end dates set on inputs to filter values.
   *
   * @param string fieldName
   *   Name of the field to be filtered
   * @param bool applyOnly
   *   Do we want to use filter values for apply only?
   *   If so, then we don't change checkboxes status but only show/hide
   *   checkboxes basing on date filter values.
   */
  PivotTable.prototype.filterDateValues = function (fieldName, applyOnly) {
    var that = this;
    var startDateValue = $('#fld_' + fieldName + '_start').val();
    var endDateValue = $('#fld_' + fieldName + '_end').val();

    $('input.' + fieldName).each(function () {
      var checkDateValue = $('span.value', $(this).parent()).text();
      var checked = false;
      var dateChecker = new CRM.PivotReport.Dates(that.crmConfig);

      if (dateChecker.dateInRange(checkDateValue, startDateValue, endDateValue)) {
        checked = true;
      }

      if (!applyOnly) {
       if (checked == true && !$(this).is(':checked')) {
          $(this).prop('checked', true).toggleClass('changed');
        } else if (checked == false && $(this).is(':checked')) {
          $(this).prop('checked', false).toggleClass('changed');
        }
      }

      if (checked === true) {
        $(this).parent().parent().show();
      } else {
        $(this).parent().parent().hide();
      }
    });
  }

  /**
   * Updates start and end dates according to selected relative date.
   *
   * @param object select
   *   jQuery object referencing the select combo that holds the relative date
   *   value.
   */
  PivotTable.prototype.changeFilterDates = function (select) {
    var fieldInfo = select.attr('name').split('_');
    var fieldName = fieldInfo[1];

    var startValue = '';
    var endValue = '';
    var displayStart = '';
    var displayEnd = '';

    if (select.val() !== '') {
      var relativeDateInfo = select.val().split('.');
      var unit = relativeDateInfo[1];
      var relativeTerm = relativeDateInfo[0];

      var dateCalculator = new CRM.PivotReport.Dates(this.crmConfig);
      var dates = dateCalculator.getRelativeStartAndEndDates(relativeTerm, unit);

      startValue = dates.startDate.toUTCString();
      endValue = dates.endDate.toUTCString();
      displayStart = CRM.utils.formatDate(dates.startDate, CRM.config.dateInputFormat);
      displayEnd = CRM.utils.formatDate(dates.endDate, CRM.config.dateInputFormat);

      $('#inner_' + fieldName).hide();
    } else {
      $('#inner_' + fieldName).show();
    }

    $('input.inner_date.fld_' + fieldName + '_start.hasDatepicker').val(displayStart);
    $('input.inner_date.fld_' + fieldName + '_end.hasDatepicker').val(displayEnd);
    $('#fld_' + fieldName + '_start').val(startValue);
    $('#fld_' + fieldName + '_end').val(endValue);
    $('#fld_' + fieldName + '_end').change();
  };

  /**
   * Gets entity name.
   */
  PivotTable.prototype.getEntityName = function() {
    return this.config.entityName;
  }

  /**
   * Initializes Pivot Report filter form.
   */
  PivotTable.prototype.initFilterForm = function() {
    if (!this.config.filter) {
      return;
    }

    var that = this;

    this.pivotReportForm = $('#pivot-report-filters form');
    this.pivotReportKeyValueFrom = $('input[name="keyvalue_from"]', this.pivotReportForm);
    this.pivotReportKeyValueTo = $('input[name="keyvalue_to"]', this.pivotReportForm);

    $('input[type="button"].apply-filters-button', this.pivotReportForm).click(function(e) {
      $('#pivot-report-filters').addClass('hidden');

      that.loadDataByFilter(that.pivotReportKeyValueFrom.val(), that.pivotReportKeyValueTo.val());
    });

    $('input[type="button"].load-all-data-button', this.pivotReportForm).click(function(e) {
      CRM.confirm({ message: 'This operation may take some time to load all data for big data sets. Do you really want to load all Activities data?' }).on('crmConfirm:yes', function() {
        that.loadAllData();
      });
    });

    this.config.initFilterForm(this.pivotReportKeyValueFrom, this.pivotReportKeyValueTo);
  };

  /**
   * Loads header, checks total number of items and then starts data fetching.
   */
  PivotTable.prototype.initPivotDataLoading = function() {
    var that = this;
    var apiCalls = {
      'getConfig': ['Setting', 'get', {
        'sequential': 1,
        'return': ['weekBegins', 'fiscalYearStart']
      }],
      'getHeader': ['PivotReport', 'getheader', {'entity': this.config.entityName}],
      'getCount': [this.config.entityName, 'getcount', that.config.getCountParams()],
      'getPivotCount': ['PivotReport', 'getpivotcount', {'entity': this.config.entityName}],
      'dateFields': ['PivotReport', 'getdatefields', {entity: this.config.entityName}],
      'relativeFilters': ['OptionValue', 'get', {
        'sequential': 1,
        'option_group_id': 'relative_date_filters'
      }],
    };

    CRM.api3(apiCalls).done(function (result) {
      that.dateFields = result.dateFields.values;
      that.relativeFilters = result.relativeFilters.values;
      that.header = result.getHeader.values;
      that.total = parseInt(result.getCount, 10);
      that.pivotCount = parseInt(result.getPivotCount.values, 10);
      that.crmConfig = result.getConfig.values[0];

      $.each(that.dateFields, function (i, value) {
        that.config.derivedAttributes[value + ' (' + ts('per month') + ')'] = $.pivotUtilities.derivers.dateFormat(value, '%y-%m')
      });

      if (that.config.initialLoad.limit && that.total > that.config.initialLoad.limit) {
        CRM.alert(that.config.initialLoad.message, '', 'info');

        $('input[type="button"].load-all-data-button', this.pivotReportForm).removeClass('hidden');
        $('#pivot-report-filters').show();
        var filter = that.config.initialLoad.getFilter();

        that.loadDataByFilter(filter.getFrom(), filter.getTo());
      } else {
        that.loadAllData();
      }
    });
  };

  /**
   * Resets data array and init empty Pivot Table.
   */
  PivotTable.prototype.resetData = function() {
    this.totalLoaded = 0;
    this.data = [];
    this.initPivotTable([]);
  };

  /**
   * Loads a pack of Pivot Report data. If there is more data to load
   * (depending on the total value and the response) then we run
   * the function recursively.
   *
   * @param {object} loadParams
   *   Object containing params for API 'get' request of Pivot Report data.
   */
  PivotTable.prototype.loadData = function(loadParams, totalCount) {
    var that = this;
    var params = loadParams;

    params.sequential = 1;
    params.entity = this.config.entityName;

    CRM.api3('PivotReport', 'get', params).done(function(result) {
      that.data = that.data.concat(that.processData(result['values'][0].data));
      var nextKeyValue = result['values'][0].nextKeyValue;
      var nextPage = result['values'][0].nextPage;
      var progressValue = parseInt((that.totalLoaded / totalCount) * 100, 10);

      that.Preloader.setValue(progressValue);

      if (nextKeyValue === '') {
        that.loadComplete(that.data);
      } else {
        that.loadData({
          "keyvalue_from": nextKeyValue,
          "keyvalue_to": params.keyvalue_to,
          "page": nextPage
        }, totalCount);
      }
    });
  };

  /**
   * Hides preloader, shows filters and init Pivot Table.
   *
   * @param {array} data
   */
  PivotTable.prototype.loadComplete = function(data) {
    this.Preloader.hide();

    if (this.config.filter) {
      $('#pivot-report-filters').removeClass('hidden');
    }

    this.initPivotTable(data);
  };

  /**
   * Applies specified config for current Pivot Table data.
   *
   * @param {array} data
   */
  PivotTable.prototype.applyConfig = function(config) {
    this.pivotTableContainer.pivotUI(this.data, config , true);
    this.postRender();
  };

  /**
   * Makes changes to the pivot report after it is rendered.
   */
  PivotTable.prototype.postRender = function() {
    this.initDateFilters();
    this.uxImprovements();
    this.setUpExportButtons();
  }

  /**
   * Returns current date in YYYYMMDD_HHII format.
   *
   * @returns {String}
   */
  PivotTable.prototype.getCurrentTimestamp = function () {
    var now = new Date();
    var month = now.getMonth() + 1;
    var day = now.getDate();
    var date = [now.getFullYear(), ('0' + month).substring(month.length), ('0' + day).substring(day.length)];
    var time = [now.getHours(), now.getMinutes()];

    return date.join('') + '_' + time.join('');
  }

  /**
   * Makes changes to improve UX on pivot report.
   */
  PivotTable.prototype.uxImprovements = function() {
    // Move Chart Type Selection Box
    $('#pivot-report-type').html('');
    $('#pivot-report-type').append($('select.pvtRenderer'));

    // Prepend Filter Icon to Field Labels
    $('li.ui-sortable-handle span.pvtAttr span.pvtTriangle').each(function() {
      $(this).prependTo($(this).parent().parent());
    });

    // Add Empty Help Message to Rows
    $('td.pvtAxisContainer.pvtRows').append(
      '<div id="rows_help_msg">Drag and drop a field here from the list on the left to add as a row heading in the report.</div>'
    );

    $('td.pvtAxisContainer.pvtRows').bind("DOMSubtreeModified",function(){
      if ($('td.pvtAxisContainer.pvtRows li.ui-sortable-handle').length < 1) {
        $('#rows_help_msg').show();
      } else {
        $('#rows_help_msg').hide();
      }
    });

    // Add empty Help Messaage to Columns
    $('td.pvtAxisContainer.pvtCols').append(
      '<div id="cols_help_msg">Drag and drop a field here from the list on the left to add as a column heading in the report.</div>'
    );

    $('td.pvtAxisContainer.pvtCols').bind("DOMSubtreeModified",function(){
      if ($('td.pvtAxisContainer.pvtCols li.ui-sortable-handle').length < 1) {
        $('#cols_help_msg').show();
      } else {
        $('#cols_help_msg').hide();
      }
    });
  }

  /**
   * Implements functionality to generate CSV and TSV export files and send their
   * content as a download.
   */
  PivotTable.prototype.setUpExportButtons = function () {
    var that = this;

    $('#exportCSV').unbind().click(function () {
      var data = new $.pivotUtilities.PivotData(that.data, that.PivotConfig.getPivotConfig());
      var config = that.PivotConfig.getPivotConfig();
      var downloader = new CRM.PivotReport.Export(data, config);
      downloader.export('CSV');
    });

    $('#exportTSV').unbind().click(function () {
      var data = new $.pivotUtilities.PivotData(that.data, that.PivotConfig.getPivotConfig());
      var config = that.PivotConfig.getPivotConfig();
      var downloader = new CRM.PivotReport.Export(data, config);
      downloader.export('TSV');
    });

  }

  /**
   * Formats incoming data (combine header with fields values)
   * to be compatible with Pivot library.
   * Updates totalLoaded with number of unique rows processed.
   *
   * @param {array} data
   *
   * @returns {array}
   */
  PivotTable.prototype.processData = function(data) {
    var that = this;
    var result = [];
    var i, j;

    for (i in data) {
      var row = {};
      for (j in data[i]) {
        row[that.header[j]] = data[i][j];
      }

      result.push(row);
    }

    this.totalLoaded += result.length;

    return result;
  };

  /**
   * Runs data loading by specified filter values.
   *
   * @param {string} filterValueFrom
   * @param {string} filterValueTo
   */
  PivotTable.prototype.loadDataByFilter = function(filterValueFrom, filterValueTo) {
    var that = this;

    this.resetData();

    if (this.config.filter) {
      this.pivotReportKeyValueFrom.val(filterValueFrom).trigger('change');
      this.pivotReportKeyValueTo.val(filterValueTo).trigger('change');
    }

    this.pivotTableContainer.html('');
    this.Preloader.reset();
    this.Preloader.setTitle('Loading filtered data');
    this.Preloader.show();

    CRM.api3(this.config.entityName, 'getcount', this.config.getCountParams(filterValueFrom, filterValueTo)).done(function(result) {
      var totalFiltered = parseInt(result.result, 10);

      if (!totalFiltered) {
        that.Preloader.hide();

        if (that.config.filter) {
          $('#pivot-report-filters').removeClass('hidden');
        }

        CRM.alert('There are no items matching specified filter.');
      } else {
        that.total = totalFiltered;

        that.loadData({
          'keyvalue_from': filterValueFrom,
          'keyvalue_to': filterValueTo,
          'page': 0
        }, totalFiltered);
      }
    });
  };

  /**
   * Runs all data loading.
   */
  PivotTable.prototype.loadAllData = function() {
    var that = this;

    this.resetData();

    if (this.config.filter) {
      this.pivotReportKeyValueFrom.val(null).trigger('change');
      $('#pivot-report-filters').addClass('hidden');
    }

    this.pivotTableContainer.html('');
    this.Preloader.reset();
    this.Preloader.setTitle('Loading data');
    this.Preloader.show();

    CRM.api3('PivotReport', 'getpivotcount', {'entity': this.config.entityName}).done(function(result) {
        var totalCount = parseInt(result.values, 10);

        that.loadData({
          "keyvalue_from": null,
          "keyvalue_to": null,
          "page": 0
        }, totalCount);
    });
  };

  /**
   * Handle Pivot Table refreshing.
   *
   * @param {JSON} config
   */
  PivotTable.prototype.pivotTableOnRefresh = function(config) {
    var configCopy = JSON.parse(JSON.stringify(config));

    //delete some values which are functions
    delete configCopy["aggregators"];
    delete configCopy["renderers"];

    //delete some bulky default values
    delete configCopy["rendererOptions"];
    delete configCopy["localeStrings"];

    this.PivotConfig.setPivotConfig(configCopy);
  }

  /*
   * Initializes Pivot Table with given data.
   *
   * @param {array} data
   */
  PivotTable.prototype.initPivotTable = function(data) {
    var that = this;
    this.data = data;

    var config = {
      rendererName: "Table",
      renderers: $.extend(
        $.pivotUtilities.renderers,
        $.pivotUtilities.c3_renderers
      ),
      vals: ["Total"],
      rows: [],
      cols: [],
      aggregatorName: "Count",
      unusedAttrsVertical: true,
      menuLimit: 5000,
      rendererOptions: {
        c3: {
          size: {
            width: parseInt(that.pivotTableContainer.width() * 0.6, 10)
          }
        },
      },
      derivedAttributes: that.config.derivedAttributes,
      hiddenAttributes: that.config.hiddenAttributes,
      onRefresh: function (config) {
        return that.pivotTableOnRefresh(config);
      }
    };

    this.applyConfig(config);
  };

  return PivotTable;
})(CRM.$);
