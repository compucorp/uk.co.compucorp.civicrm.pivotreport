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
    this.pivotReportForm = null;
    this.pivotReportKeyValueFrom = null;
    this.pivotReportKeyValueTo = null;
    this.dateFields = null;
    this.relativeFilters = null;
    this.crmConfig = null;
    this.PivotConfig = new CRM.PivotReport.Config(this);

    this.initFilterForm();
    this.initUI();
    this.initPivotDataLoading();
  };

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
          fieldName = $(this).text().replace(/[ ()0-9]/g, '');

          if ($.inArray($(this).text().replace(/[()0-9]/g, ''), that.dateFields) >= 0) {
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
          }
        }
      });

      $(':button', container).each(function () {
        if ($(this).text() == 'Select All') {
          $(this).addClass(fieldName + '_batchSelector');
          $(this).off('click');
          $(this).on('click', function () {
            $('#fld_' + fieldName + '_start').change();
            $('input.inner_date.fld_' + fieldName + '_start.hasDatepicker').val($('#fld_' + fieldName + '_start').val());
          });
        }
      });
    });

    $('.inner_date').each(function () {

      $(this).change(function () {
        var fieldInfo = $(this).attr('name').split('_');
        var startDateValue = $('#fld_' + fieldInfo[1] + '_start').val();
        var endDateValue = $('#fld_' + fieldInfo[1] + '_end').val();

        $('input.' + fieldInfo[1]).each(function () {
          var checkDateValue = $('span.value', $(this).parent()).text();
          var checked = false;
          var dateChecker = new CRM.PivotReport.Dates(that.crmConfig);

          if (dateChecker.dateInRange(checkDateValue, startDateValue, endDateValue)) {
            checked = true;
          }

          if (checked == true && !$(this).is(':checked')) {
            $(this).click();
          } else if (checked == false && $(this).is(':checked')) {
            $(this).click();
          }

          if (checked === true) {
            $(this).parent().parent().show();
          } else {
            $(this).parent().parent().hide();
          }

        });
      });

      $(this).crmDatepicker({
        time: false
      });
    });

  };

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
      $('#pivot-report-preloader').removeClass('hidden');
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
   * Handles UI events.
   */
  PivotTable.prototype.initUI = function() {
    var that = this;

    $('input[type="button"].build-cache-button').click(function(e) {
      CRM.confirm({message: 'This operation may take some time to build the cache. Do you really want to build the cache for ' + that.config.entityName + ' data?' })
      .on('crmConfirm:yes', function() {
        CRM.api3('PivotReport', 'rebuildcache', {entity: that.config.entityName}).done(function(result) {
          that.initPivotDataLoading();
        });
      });
    });
  }

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
  PivotTable.prototype.loadData = function(loadParams) {
    var that = this;

    CRM.$('span#pivot-report-loading-count').append('.');

    var params = loadParams;
    params.sequential = 1;
    params.entity = this.config.entityName;

    CRM.api3('PivotReport', 'get', params).done(function(result) {
      that.data = that.data.concat(that.processData(result['values'][0].data));
      var nextKeyValue = result['values'][0].nextKeyValue;
      var nextPage = result['values'][0].nextPage;

      if (nextKeyValue === '') {
        that.loadComplete(that.data);
      } else {
        that.loadData({
          "keyvalue_from": nextKeyValue,
          "keyvalue_to": params.keyvalue_to,
          "page": nextPage
        });
      }
    });
  };

  /**
   * Hides preloader, show filters and init Pivot Table.
   *
   * @param {array} data
   */
  PivotTable.prototype.loadComplete = function(data) {
    $('#pivot-report-preloader').addClass('hidden');

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
   * Formats incoming data (combine header with fields values)
   * to be compatible with Pivot library.
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

    CRM.api3(this.config.entityName, 'getcount', this.config.getCountParams(filterValueFrom, filterValueTo)).done(function(result) {
      var totalFiltered = parseInt(result.result, 10);

      if (!totalFiltered) {
        $('#pivot-report-preloader').addClass('hidden');

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
        });
      }
    });
  };

  /**
   * Runs all data loading.
   */
  PivotTable.prototype.loadAllData = function() {
    this.resetData();

    if (this.config.filter) {
      this.pivotReportKeyValueFrom.val(null).trigger('change');
      $('#pivot-report-filters').addClass('hidden');
    }

    this.pivotTableContainer.html('');
    $('#pivot-report-preloader').removeClass('hidden');

    this.loadData({
      "keyvalue_from": null,
      "keyvalue_to": null,
      "page": 0
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
        $.pivotUtilities.c3_renderers,
        $.pivotUtilities.export_renderers
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
