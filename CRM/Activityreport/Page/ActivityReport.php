<?php

require_once 'CRM/Core/Page.php';

class CRM_Activityreport_Page_ActivityReport extends CRM_Core_Page {

  function run() {
    CRM_Utils_System::setTitle(ts('Activity Report'));

    $options_array = array(
      'Activity' => 'Activity',
      'Contribution' => 'Contribution',
      'Membership' => 'Membership'
    );
    $this->assign('options_array', $options_array);
    $this->assign('CRMDataType', 'Activity');
    $this->assign('cacheBuilt', $this->isCacheBuilt());

    parent::run();
  }

  /**
   * Checks if cache for the entity is built.
   *
   * @return bool
   *   True if the cache is already built, false otherwise
   */
  private function isCacheBuilt() {
    $cacheGroup = new CRM_PivotCache_GroupActivity();
    return $cacheGroup->isCacheBuilt();
  }

}
