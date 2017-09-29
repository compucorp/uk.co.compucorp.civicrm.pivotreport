CRM.PivotReport = CRM.PivotReport || {};

CRM.PivotReport.Filter = (function($) {

  /**
   * Initializes Pivot Filter.
   *
   * @param {string} from
   * @param {string} to
   */
  function Filter(from, to) {
    this.keyvalue_from = typeof from !== 'undefined' ? from : null;
    this.keyvalue_to = typeof to !== 'undefined' ? to : null;
  }

  /**
   * Gets 'keyvalue_from' property value.
   *
   * @returns {string}
   */
  Filter.prototype.getFrom = function() {
    return this.keyvalue_from;
  }

  /**
   * Sets 'keyvalue_from' property value.
   *
   * @param {string} value
   */
  Filter.prototype.setFrom = function(value) {
    this.keyvalue_from = value;
  }

  /**
   * Gets 'keyvalue_to' property value.
   *
   * @returns {string}
   */
  Filter.prototype.getTo = function() {
    return this.keyvalue_to;
  }

  /**
   * Sets 'keyvalue_to' property value.
   *
   * @param {string} value
   */
  Filter.prototype.setTo = function(value) {
    this.keyvalue_to = value;
  }

  return Filter;
})(CRM.$);
