<?php

require_once 'CRM/Core/Page.php';

class CRM_PivotReport_Page_PivotReportAdmin extends CRM_Core_Page {
  function run() {
    CRM_Utils_System::setTitle(ts('Pivot Report Configuration'));

    $this->assign('buildDateTime', CRM_PivotReport_BAO_PivotReportCache::getBuildDatetime());

    parent::run();
  }
}
