CRM.PivotReport = CRM.PivotReport || {};

CRM.PivotReport.Preloader = (function($) {

  /**
   * Preloader constructor.
   */
  function Preloader() {
    this.container = CRM.$('#pivot-report-preloader');
    this.title = CRM.$('.pivot-report-loading-title', this.container);
    this.progressBar = CRM.$('.progress > .progress-bar', this.container);
    this.progressValue = CRM.$('.progress > .progress-value', this.container);
  }

  /**
   * Hides Pivot Report preloader.
   */
  Preloader.prototype.hide = function() {
    this.container.hide();
  }

  /**
   * Shows Pivot Report preloader.
   */
  Preloader.prototype.show = function() {
    this.container.show();
  }

  /**
   * Sets Pivot Report preloader title.
   *
   * @param {string} title
   */
  Preloader.prototype.setTitle = function(title) {
    this.title.text(title);
  }

  /**
   * Resets Pivot Report preloader.
   */
  Preloader.prototype.reset = function() {
    this.setValue(0);
  }

  /**
   * Sets Pivot Report preloader value.
   *
   * @param {integer} value
   */
  Preloader.prototype.setValue = function(value) {
    var progressValue = value + '%';

    this.progressBar.css('width', progressValue);
    this.progressValue.text(progressValue);
  }

  return Preloader;
})(CRM.$);
