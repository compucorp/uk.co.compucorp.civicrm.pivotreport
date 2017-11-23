CRM.PivotReport = CRM.PivotReport || {};

CRM.PivotReport.Admin = (function($) {

  /**
   * Initializes Pivot Admin.
   */
  function Admin() {
    this.container = $('#pivot-report-admin');
    this.totalCount = 0;
    this.loadedCount = {};
    this.cachedPivotCount = {};
    this.Preloader = new CRM.PivotReport.Preloader();
    this.Preloader.hide();

    this.initUI();
  };

  /**
   * Initializes of cache building.
   */
  Admin.prototype.buildCache = function() {
    var that = this;

    CRM.api3('PivotReport', 'gettotalcount', {
      'sequential': 1
    }).done(function(result) {
      that.totalCount = result.values;
      that.cacheAllEntities();
    });
  }

  /**
   * Handles supported entities caching.
   */
  Admin.prototype.cacheAllEntities = function() {
    var that = this;

    CRM.api3('PivotReport', 'getsupportedentitiescount', {
      'sequential': 1
    }).done(function(result) {
      that.Preloader.reset();
      that.Preloader.show();

      for (var i in result.values[0]) {
        that.loadedCount[i] = 0;
        that.cachedPivotCount[i] = 0;
        that.cacheEntity(i, 0, 0, null, 0, result.values[0][i]);
      }
    });
  }

  /**
   * Builds cache for specified entity.
   *
   * @param {string} entity
   * @param {int} offset
   * @param {int} multiValuesOffset
   * @param {string} index
   * @param {int} page
   * @param {int} entityCount
   */
  Admin.prototype.cacheEntity = function(entity, offset, multiValuesOffset, index, page, entityCount) {
    var that = this;

    CRM.api3('PivotReport', 'rebuildcachepartial', {
        entity: entity,
        offset: offset,
        multiValuesOffset: multiValuesOffset,
        index: index,
        page: page,
        pivotCount: that.cachedPivotCount[entity]
    }).done(function(result) {
      that.loadedCount[entity] = offset;
      that.cachedPivotCount[entity] += result.values.count;

      var progressValue = parseInt((that.getTotalLoadedCount() / that.totalCount) * 100, 10);
      that.Preloader.setValue(progressValue);

      if (parseInt(result.values.count, 10) === 0) {
        that.loadedCount[entity] = entityCount;

        if (that.getTotalLoadedCount() === that.totalCount) {
          that.Preloader.hide();
          that.updateBuildDateTime();

          $('input.build-cache-button', that.container).prop('disabled', false);
          $('div.in-progress', that.container).addClass('hidden');

          CRM.alert('All Pivot Reports have been refreshed.', null, 'success');
        }

        return;
      }

      that.cacheEntity(entity, result.values.offset, result.values.multiValuesOffset, result.values.index, result.values.page, entityCount);
    });
  }

  /**
   * Returns total count of already cached items.
   *
   * @returns {int}
   */
  Admin.prototype.getTotalLoadedCount = function() {
    var result = 0;

    for (var i in this.loadedCount) {
      result += this.loadedCount[i];
    }

    return result;
  }

  /**
   * Updates cache build datetime.
   */
  Admin.prototype.updateBuildDateTime = function() {
    var that = this;

    CRM.api3('PivotReport', 'updatebuilddatetime').done(function() {
      CRM.api3('PivotReport', 'getbuilddatetime', {
         'sequential': 1,
         'format': 1
      }).done(function(result) {
        $('div.build-date-time > span', that.container).text(result.values);
      });
    });
  }

  /**
   * Handles UI events.
   */
  Admin.prototype.initUI = function() {
    var that = this;

    $('input[type="button"].build-cache-button').click(function(e) {
      CRM.confirm({message: 'This operation may take some time to build the cache. Do you really want to build the cache for all supported entities?' })
      .on('crmConfirm:yes', function() {
        $('input.build-cache-button', that.container).prop('disabled', true);
        $('div.in-progress', that.container).removeClass('hidden');

        that.buildCache();
      });
    });
  };

  return Admin;
})(CRM.$);
