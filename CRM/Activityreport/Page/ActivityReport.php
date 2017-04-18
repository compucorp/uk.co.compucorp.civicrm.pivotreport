<?php

require_once 'CRM/Core/Page.php';

class CRM_Activityreport_Page_ActivityReport extends CRM_Core_Page {
  private $thisYear = null;

  function run() {
    CRM_Utils_System::setTitle(ts('ActivityReport'));

    $this->thisYear = date('Y');

    $years = array('' => ts('- select year -'));
    $months = array('' => ts('- select month -'));
    $startYear = $this->getActivityStartYear();

    for ($i = $this->thisYear; $i >= $startYear; $i--) {
      $years[$i] = $i;
    }

    for ($i = 1; $i < 13; $i++) {
      $months[$i] = date('F', mktime(0, 0, 0, $i));
    }

    $this->assign('years', $years);
    $this->assign('months', $months);

    parent::run();
  }

  /**
   * Get the oldest Activity and return its year. If there is no Activity found
   * then return current year.
   *
   * @return string
   */
  private function getActivityStartYear() {
    $row = CRM_Core_DAO::executeQuery(
      'SELECT YEAR(activity_date_time) AS start_year FROM civicrm_activity ORDER BY activity_date_time ASC LIMIT 1'
    );
    $row->fetch();

    $result = !empty($row->start_year) ? $row->start_year : $this->thisYear;

    return $result;
  }
}
