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

    this.header = [];
    this.data = [];
    this.total = 0;
    this.pivotReportForm = null;
    this.pivotReportKeyValueFrom = null;
    this.pivotReportKeyValueTo = null;
    this.dateFields = null;

    this.initFilterForm();
    this.initUI();
    this.initPivotDataLoading();
  };

  /**
   * Initializes date filters for each field of Date data type.
   */
  PivotTable.prototype.initDateFilters = function () {
    var that = this;

    if (!Array.isArray(this.dateFields)) {

      CRM.api3('ActivityReport', 'getdatefields', {entity: this.config.entityName}).done(function(result) {
        that.dateFields = result.values;
        that.initDateFilters();
      });

      return;
    }

    $('div.pvtFilterBox').each(function () {
      var container = $(this);

      $(this).children().each(function () {

        if ($(this).prop("tagName") == 'H4') {
          var fieldName = $(this).text().replace(/[ ()0-9]/g, '');

          if ($.inArray($(this).text().replace(/[()0-9]/g, ''), that.dateFields)) {
            $(this).after('' +
              '<div class="inner_date_filters">' +
              ' <form>' +
              '   <input type="text" id="fld_' + fieldName + '_start" name="fld_' + fieldName + '_start" class="inner_date" value=""> - ' +
              '   <input type="text" id="fld_' + fieldName + '_end" name="fld_' + fieldName + '_end" class="inner_date" value="">' +
              ' </form>' +
              '</div>'
            );

            $('.pvtFilter', container).each(function () {
              $(this).addClass(fieldName);
            });
          }
        }
      });
    });

    $('.inner_date').each(function () {

      $(this).change(function () {
        var fieldInfo = $(this).attr('name').split('_');

        var startDateValue = $('#fld_' + fieldInfo[1] + '_start').val();
        var startDate = new Date(startDateValue);

        var endDateValue = $('#fld_' + fieldInfo[1] + '_end').val();
        var endDate = new Date(endDateValue);

        $('input.' + fieldInfo[1]).each(function () {
          var checkDate = new Date($('span.value', $(this).parent()).text());

          if (startDateValue != '' && endDateValue != '') {
            if (checkDate.getTime() > startDate.getTime() && checkDate.getTime() < endDate.getTime()) {
              $(this).prop('checked', true);
            } else {
              $(this).prop('checked', false);
            }
          } else if (startDateValue != '') {
            if (checkDate.getTime() > startDate.getTime()) {
              $(this).prop('checked', true);
            } else {
              $(this).prop('checked', false);
            }
          } else if (endDateValue != '') {
            if (checkDate.getTime() < endDate.getTime()) {
              $(this).prop('checked', true);
            } else {
              $(this).prop('checked', false);
            }
          }
        });
      });

      $(this).crmDatepicker({
        time: false
      });
    });

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
        CRM.api3('ActivityReport', 'rebuildcache', {entity: that.config.entityName}).done(function(result) {
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

    CRM.api3('ActivityReport', 'getheader', {
      'entity': that.config.entityName
    }).done(function(result) {
      that.header = result.values;

      CRM.api3(that.config.entityName, 'getcount', that.config.getCountParams()).done(function(result) {
        that.total = parseInt(result.result, 10);

        if (that.config.initialLoad.limit && that.total > that.config.initialLoad.limit) {
          CRM.alert(that.config.initialLoad.message, '', 'info');

          $('input[type="button"].load-all-data-button', this.pivotReportForm).removeClass('hidden');
          var filter = that.config.initialLoad.getFilter();

          that.loadDataByFilter(filter.getFrom(), filter.getTo());
        } else {
          that.loadAllData();
        }
      });
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

    CRM.api3('ActivityReport', 'get', params).done(function(result) {
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
    this.data = [];
  };

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

    $("#pivot-report-table").html('');

    CRM.api3(this.config.entityName, 'getcount', this.config.getCountParams(filterValueFrom, filterValueTo)).done(function(result) {
      var totalFiltered = parseInt(result.result, 10);

      if (!totalFiltered) {
        $('#pivot-report-preloader').addClass('hidden');

        if (this.config.filter) {
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

    $("#pivot-report-table").html('');
    $('#pivot-report-preloader').removeClass('hidden');

    this.loadData({
      "keyvalue_from": null,
      "keyvalue_to": null,
      "page": 0
    });
  };

  /*
   * Inits Pivot Table with given data.
   *
   * @param {array} data
   */
  PivotTable.prototype.initPivotTable = function(data) {
    var that = this;

    $("#pivot-report-table").pivotUI(data, {
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
        unusedAttrsVertical: false,
        rendererOptions: {
            c3: {
                size: {
                    width: parseInt($('#pivot-report-table').width() * 0.78, 10)
                }
            },
        },
        derivedAttributes: that.config.derivedAttributes,
        hiddenAttributes: that.config.hiddenAttributes,
    }, false);

    this.initDateFilters();
  };

  return PivotTable;
})(CRM.$);
