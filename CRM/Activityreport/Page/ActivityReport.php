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

    parent::run();
  }

}
