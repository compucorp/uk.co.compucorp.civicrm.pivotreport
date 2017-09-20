<?php

require_once 'CRM/Core/Page.php';

class CRM_Activityreport_Page_ActivityReport extends CRM_Core_Page {
  function run() {
    CRM_Utils_System::setTitle(ts('ActivityReport'));

    parent::run();
  }
}
