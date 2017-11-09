CRM.PivotReport = CRM.PivotReport || {};

CRM.PivotReport.Config = (function($) {

  /**
   * Initializes Pivot Config.
   *
   * @param {object} PivotTable
   */
  function Config(PivotTable) {
    this.PivotTable = PivotTable;
    this.pivotConfig = {};
    this.container = $('#pivot-report-config');

    this.initUI();
  };

  /**
   * Sets Pivot Config object value.
   *
   * @param {object} pivotConfig
   */
  Config.prototype.setPivotConfig = function(pivotConfig) {
    this.pivotConfig = pivotConfig;
  };

  /**
   * Gets Pivot Config object value.
   *
   * @returns {object}
   */
  Config.prototype.getPivotConfig = function() {
    return this.pivotConfig;
  };

  /**
   * Handles both Save and SaveNew actions server requests.
   *
   * @param {int} configId
   * @param {string} configLabel
   */
  Config.prototype.configSaveProcess = function(configId, configLabel) {
    var that = this;

    CRM.api3('PivotReportConfig', 'create', {
      'id': configId,
      'entity': that.PivotTable.getEntityName(),
      'label': configLabel,
      'json_config': JSON.stringify(that.getPivotConfig())
    }).done(function(result) {
      if (result.is_error) {
        CRM.alert(result.message, 'Error saving Report configuration', 'error');
        return;
      }

      if (!configId) {
        $('.report-config-select', that.container).append('<option value="' + result.id + '">' + result.values[result.id].label + '</option>');
        var emptyOption = $('.report-config-select option[value=""]', this.container).text();
        $('.report-config-select option[value=""]', that.container).remove();

        // Sort options by their labels alphabetically.
        $('.report-config-select', that.container).append($(".report-config-select option").remove().sort(function(a, b) {
          var at = $(a).text().toLocaleLowerCase(), bt = $(b).text().toLocaleLowerCase();
          return (at > bt) ? 1 : ((at < bt) ? -1 : 0);
        }));

        $('.report-config-select', that.container).prepend('<option value="">' + emptyOption + '</option>');
        $('.report-config-select', that.container).val(result.id);

        that.showConfigRelatedButtons();
      }

      CRM.alert('Report configuration has been saved', 'Success', 'success');
    });
  };

  /**
   * Returns an ID of currently active Report configuration.
   *
   * @returns {int}
   */
  Config.prototype.getReportConfigurationId = function() {
    return $('.report-config-select', this.container).val();
  };

  /**
   * Applies given Pivot Table configuration.
   *
   * @param {object} config
   */
  Config.prototype.applyConfig = function(config) {
    var that = this;

    config['onRefresh'] = function (config) {
      return that.PivotTable.pivotTableOnRefresh(config);
    }

    this.PivotTable.applyConfig(config);
  };

  /**
   * Gets Report configuration by currently selected configId
   * and apply it to the Pivot Table instance.
   */
  Config.prototype.configGet = function() {
    var that = this;
    var configId = this.getReportConfigurationId();
    if (!configId) {
      this.hideConfigRelatedButtons();
      return false;
    }

    this.showConfigRelatedButtons();

    CRM.api3('PivotReportConfig', 'getsingle', {
      'id': configId
    }).done(function(result) {
      if (result.is_error) {
        CRM.alert(result.error_message, 'Error loading Pivot Report configuration', 'error');
        return;
      }

      that.applyConfig(JSON.parse(result.json_config));
      CRM.alert('Pivot Report configuration applied.', '', 'info');
    });
  };

  /**
   * Saves Report configuration with currently selected configId.
   *
   * @param {string} message
   */
  Config.prototype.configSave = function(message) {
    var that = this;
    var configId = this.getReportConfigurationId();
    if (!configId) {
      CRM.alert('Please choose configuration to update.', 'No configuration selected', 'error');
      return false;
    }

    if (typeof message === 'undefined') {
      message = 'Are you sure you want to save this configuration changes?';
    }

    CRM.confirm({
      'message': message
    }).on('crmConfirm:yes', function() {
      that.configSaveProcess(configId);
    })
  };

  /**
   * Saves new Report configuration basing on currently set configuration.
   */
  Config.prototype.configSaveNew = function() {
    var that = this;

    swal({
      title: 'New Report configuration',
      text: 'Configuration name:',
      type: 'input',
      showCancelButton: true,
      closeOnConfirm: true,
      inputPlaceholder: 'New configuration label'
    }, function (inputValue) {
      if (inputValue === false) {
        return false;
      }

      if (inputValue === '') {
        swal.showInputError('Configuration name cannot be empty.');
        return false;
      }

      that.configSaveProcess(0, inputValue);
    });
  }

  /**
   * Deletes currently active configuration.
   */
  Config.prototype.configDelete = function() {
    var that = this;

    var configId = this.getReportConfigurationId();
    if (!configId) {
      CRM.alert('Please choose configuration to delete.', 'No configuration selected', 'error');
      return false;
    }

    var configId = this.getReportConfigurationId();

    CRM.confirm({
      'message': 'Are you sure you want to delete this configuration?'
    }).on('crmConfirm:yes', function() {
      CRM.api3('PivotReportConfig', 'delete', {
        'id': configId
      }).done(function(result) {
        if (result.is_error) {
          CRM.alert(result.message, 'Error deleting Report configuration', 'error');
          return;
        }

        $('.report-config-select option[value=' + configId + ']', this.container).remove();
        CRM.alert('Report configuration has been deleted', 'Success', 'info');

        that.hideConfigRelatedButtons();
      });
    });
  };

  /**
   * Shows configuration related buttons.
   */
  Config.prototype.showConfigRelatedButtons = function() {
    $('.report-config-save-btn', this.container).removeClass('hidden');
    $('.report-config-delete-btn', this.container).removeClass('hidden');
  }

  /**
   * Hides configuration related buttons.
   */
  Config.prototype.hideConfigRelatedButtons = function() {
    $('.report-config-save-btn', this.container).addClass('hidden');
    $('.report-config-delete-btn', this.container).addClass('hidden');
  }

  /**
   * Handles UI events.
   */
  Config.prototype.initUI = function() {
    var that = this;

    $('form', this.container).on('submit', function(e) {
      e.preventDefault();
      return null;
    });

    $('.report-config-select', this.container).bind('change', function(e) {
      that.configGet();
    });
    $('.report-config-save-btn', this.container).bind('click', function(e) {
      that.configSave();
    });
    $('.report-config-save-new-btn', this.container).bind('click', function(e) {
      that.configSaveNew();
    });
    $('.report-config-delete-btn', this.container).bind('click', function(e) {
      that.configDelete();
    });
  };

  return Config;
})(CRM.$);
