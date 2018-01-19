CRM.PivotReport = CRM.PivotReport || {};

CRM.PivotReport.Dates = (function($) {

  /**
   * Initialize dates manipulator class.
   *
   * @constructor
   */
  function Dates(config) {
    this.crmConfig = config;
  };

  /**
   * Checks if given date is between provided start and end dates.
   *
   * @param string checkDateValue
   * @param string startDateValue
   * @param string endDateValue
   *
   * @returns {boolean}
   *   True if checkDateValue is between given start and end dates.
   */
  Dates.prototype.dateInRange = function (checkDateValue, startDateValue, endDateValue) {

    var startDate = new Date(startDateValue);
    var startTime = startDate.getTime();

    var endDate = new Date(endDateValue);
    var endTime = endDate.getTime();

    var checkDate = new Date(checkDateValue);
    var checkTime = checkDate.getTime();

    var inRange = false;

    if (startDateValue != '' && endDateValue != '') {
      if (checkTime >= startTime && checkTime <= endTime) {
        inRange = true;
      }
    } else if (startDateValue != '') {
      if (checkTime >= startTime) {
        inRange = true;
      }
    } else if (endDateValue != '') {
      if (checkTime <= endTime) {
        inRange = true;
      }
    } else {
      inRange = true;
    }

    return inRange;
  };

  /**
   * Calculates start and end dates for given relative date range and unit.
   *
   * @param string relativeTerm
   * @param string unit
   *
   * @returns {{startDate: Date, endDate: Date}}
   *   Object with calculated start and end dates.
   */
  Dates.prototype.getRelativeStartAndEndDates = function (relativeTerm, unit) {
    var dates = {};

    switch (unit) {
      case 'year':
        dates = this.calculateRelativeYearDates(relativeTerm);
        break;

      case 'fiscal_year':
        dates = this.calculateRelativeFiscalYearDates(relativeTerm);
        break;
      case 'quarter':
        dates = this.calculateRelativeQuarterDates(relativeTerm);
        break;
      case 'month':
        dates = this.calculateRelativeMonthDates(relativeTerm);
        break;
      case 'week':
        dates = this.calculateRelativeWeekDates(relativeTerm);
        break;
      case 'day':
        dates = this.calculateRelativeDayDates(relativeTerm);
        break;
    }

    return dates;
  };

  /**
   * Calculates start and end dates for given day-relative interval.
   *
   * @param relativeTerm
   *   Relative interval to calculate start and end dates.
   *
   * @returns {{startDate: Date, endDate: Date}}
   *   Object with calculated start and end dates.
   */
  Dates.prototype.calculateRelativeDayDates = function (relativeTerm) {
    var today = new Date();
    var startDate = new moment({
      year: today.getFullYear(),
      month: today.getMonth(),
      day: today.getDate()
    });

    var endDate = new moment({
      year: today.getFullYear(),
      month: today.getMonth(),
      day: today.getDate()
    });

    switch (relativeTerm) {

      case 'this':
        startDate.startOf('day');
        endDate.endOf('day');
        break;

      case 'previous':
        startDate.subtract(1, 'day');
        endDate.subtract(1, 'day');

        startDate.startOf('day');
        endDate.endOf('day');
        break;

      case 'starting':
        startDate.add(1, 'day');
        endDate.add(1, 'day');

        startDate.startOf('day');
        endDate.endOf('day');
        break;
    }

    return {
      'startDate': startDate.toDate(),
      'endDate': endDate.toDate()
    };
  };


  /**
   * Calculates start and end dates for given week-relative interval.
   *
   * @param relativeTerm
   *   Relative interval to calculate start and end dates.
   *
   * @returns {{startDate: Date, endDate: Date}}
   *   Object with calculated start and end dates.
   */
  Dates.prototype.calculateRelativeWeekDates = function (relativeTerm) {
    var today = new Date();
    var startDate = new moment({
      year: today.getFullYear(),
      month: today.getMonth(),
      day: today.getDate()
    });

    var endDate = new moment({
      year: today.getFullYear(),
      month: today.getMonth(),
      day: today.getDate()
    });

    switch (relativeTerm) {

      case 'this':
        startDate.startOf('week');
        endDate.endOf('week');
        break;

      case 'previous':
        startDate.subtract(1, 'week');
        endDate.subtract(1, 'week');

        startDate.startOf('week');
        endDate.endOf('week');
        break;

      case 'ending':
        startDate.subtract(7, 'days');
        break;

      case 'next':
        startDate.add(1, 'week');
        endDate.add(1, 'week');

        startDate.startOf('week');
        endDate.endOf('week');
        break;
    }

    return {
      'startDate': startDate.toDate(),
      'endDate': endDate.toDate()
    };
  };

  /**
   * Calculates start and end dates for given month-relative interval.
   *
   * @param relativeTerm
   *   Relative interval to calculate start and end dates.
   *
   * @returns {{startDate: Date, endDate: Date}}
   *   Object with calculated start and end dates.
   */
  Dates.prototype.calculateRelativeMonthDates = function (relativeTerm) {
    var today = new Date();
    var startDate = new moment({
      year: today.getFullYear(),
      month: today.getMonth(),
      day: today.getDate()
    });

    var endDate = new moment({
      year: today.getFullYear(),
      month: today.getMonth(),
      day: today.getDate()
    });

    switch (relativeTerm) {

      case 'this':
        startDate.startOf('month');
        endDate.endOf('month');
        break;

      case 'previous':
        startDate.subtract(1, 'month');
        endDate.month(startDate.month());

        startDate.startOf('month');
        endDate.endOf('month');
        break;

      case 'ending':
        startDate.subtract(30, 'days');
        break;

      case 'ending_2':
        startDate.subtract(60, 'days');
        break;

      case 'next':
        startDate.add(1, 'month');
        endDate.month(startDate.month());

        startDate.startOf('month');
        endDate.endOf('month');
        break;
    }

    return {
      'startDate': startDate.toDate(),
      'endDate': endDate.toDate()
    };
  };

  /**
   * Calculates start and end dates for given year-relative interval.
   *
   * @param relativeTerm
   *   Relative interval to calculate start and end dates.
   *
   * @returns {{startDate: Date, endDate: Date}}
   *   Object with calculated start and end dates.
   */
  Dates.prototype.calculateRelativeYearDates = function (relativeTerm) {
    var today = new Date();
    var startDate = new moment({
      year: today.getFullYear(),
      month: today.getMonth(),
      day: today.getDate()
    });

    var endDate = new moment({
      year: today.getFullYear(),
      month: today.getMonth(),
      day: today.getDate()
    });

    switch (relativeTerm) {

      case 'this':
        startDate.startOf('year');
        endDate.endOf('year');
        break;

      case 'previous':
        startDate.subtract(1, 'years');
        startDate.startOf('year');

        endDate.subtract(1, 'years');
        endDate.endOf('year');
        break;

      case 'ending':
        startDate.subtract(1, 'years');
        break;

      case 'ending_2':
        startDate.subtract(2, 'years');
        break;

      case 'ending_3':
        startDate.subtract(3, 'years');
        break;

      case 'next':
        startDate.add(1, 'years');
        startDate.startOf('year');

        endDate.add(1, 'years');
        endDate.endOf('year');
        break;
    }

    return {
      'startDate': startDate.toDate(),
      'endDate': endDate.toDate()
    };
  };

  /**
   * Calculates start and end dates of quarter-relative interval.
   *
   * @param relativeTerm
   *   Relative interval used to calculate start and end dates.
   *
   * @returns {{startDate: Date, endDate: Date}}
   *   Object with calculated start and end dates.
   */
  Dates.prototype.calculateRelativeQuarterDates = function (relativeTerm) {
    var today = new Date();
    var startDate = new moment({
      year: today.getFullYear(),
      month: today.getMonth(),
      day: today.getDate()
    });

    var endDate = new moment({
      year: today.getFullYear(),
      month: today.getMonth(),
      day: today.getDate()
    });

    switch (relativeTerm) {

      case 'this':
        startDate.startOf('quarter');
        endDate.endOf('quarter');
        break;

      case 'previous':
        startDate.subtract(1, 'quarters');
        startDate.startOf('quarter');

        endDate.subtract(1, 'quarters');
        endDate.endOf('quarter');
        break;

      case 'ending':
        startDate.subtract(90, 'days');
        break;

      case 'next':
        startDate.add(1, 'quarters');
        startDate.startOf('quarter');

        endDate.add(1, 'quarters');
        endDate.endOf('quarter');
        break;
    }

    return {
      'startDate': startDate.toDate(),
      'endDate': endDate.toDate()
    };
  };

  /**
   * Calculates start and end dates for given fiscal year-relative interval.
   *
   * @param relativeTerm
   *   Relative interval to calculate start and end dates.
   *
   * @returns {{startDate: Date, endDate: Date}}
   *   Object with calculated start and end dates.
   */
  Dates.prototype.calculateRelativeFiscalYearDates = function (relativeTerm) {

    var today = new Date();
    var startDate = new moment({
      year: today.getFullYear(),
      month: today.getMonth(),
      day: today.getDate()
    });

    var endDate = new moment({
      year: today.getFullYear(),
      month: today.getMonth(),
      day: today.getDate()
    });

    var fiscalBeginningDay = parseInt(this.crmConfig.fiscalYearStart.d);
    var fiscalBeginningMonth = parseInt(this.crmConfig.fiscalYearStart.M) - 1;

    if (relativeTerm == 'previous') {
      startDate.subtract(1, 'years');
    } else if (relativeTerm == 'next') {
      startDate.add(1, 'years');
    }

    if (startDate.month() < fiscalBeginningMonth) {
      startDate.subtract(1, 'years');
    }

    startDate.month(fiscalBeginningMonth);
    startDate.date(fiscalBeginningDay);

    endDate.year(startDate.year());
    endDate.month(fiscalBeginningMonth);
    endDate.date(fiscalBeginningDay);
    endDate.add(1, 'years');
    endDate.subtract(1, 'ms');

    return {
      'startDate': startDate.toDate(),
      'endDate': endDate.toDate()
    };
  };

  return Dates;
})(CRM.$);
