<?php

require_once 'pivotreport.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function pivotreport_civicrm_config(&$config) {
  _pivotreport_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function pivotreport_civicrm_install() {
  _pivotreport_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function pivotreport_civicrm_enable() {
  _pivotreport_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_pageRun
 */
function pivotreport_civicrm_pageRun($page) {
  if (get_class($page) === 'CRM_PivotReport_Page_PivotReportAdmin') {
    CRM_Core_Resources::singleton()
      ->addScriptFile('uk.co.compucorp.civicrm.pivotreport', 'packages/bootstrap/bootstrap.min.js', 1, 'html-header')
      ->addScriptFile('uk.co.compucorp.civicrm.pivotreport', 'js/PivotReport.Admin.js', 2, 'html-header')
      ->addScriptFile('uk.co.compucorp.civicrm.pivotreport', 'js/PivotReport.Preloader.js', 3, 'html-header');
    CRM_Core_Resources::singleton()
      ->addStyleFile('uk.co.compucorp.civicrm.pivotreport', 'packages/bootstrap/bootstrap.min.css', 1)
      ->addStyleFile('uk.co.compucorp.civicrm.pivotreport', 'css/style_admin.css', 2);
  }

  if (get_class($page) === 'CRM_PivotReport_Page_PivotReport') {
    CRM_Core_Resources::singleton()
      ->addScriptFile('uk.co.compucorp.civicrm.pivotreport', 'packages/d3/d3.min.js', 1, 'html-header')
      ->addScriptFile('uk.co.compucorp.civicrm.pivotreport', 'packages/c3/c3.min.js', 1, 'html-header')
      ->addScriptFile('uk.co.compucorp.civicrm.pivotreport', 'packages/pivottable/pivot.min.js', 1, 'html-header')
      ->addScriptFile('uk.co.compucorp.civicrm.pivotreport', 'packages/pivottable/c3_renderers.min.js', 2, 'html-header')
      ->addScriptFile('uk.co.compucorp.civicrm.pivotreport', 'packages/moment.js/moment.min.js', 2, 'html-header')
      ->addScriptFile('uk.co.compucorp.civicrm.pivotreport', 'packages/bootstrap/bootstrap.min.js', 3, 'html-header')
      ->addScriptFile('uk.co.compucorp.civicrm.pivotreport', 'packages/bootstrap-sweetalert/sweetalert.min.js', 4, 'html-header')
      ->addScriptFile('uk.co.compucorp.civicrm.pivotreport', 'js/PivotReport.Preloader.js', 5, 'html-header')
      ->addScriptFile('uk.co.compucorp.civicrm.pivotreport', 'js/PivotReport.Filter.js', 5, 'html-header')
      ->addScriptFile('uk.co.compucorp.civicrm.pivotreport', 'js/PivotReport.Dates.js', 6, 'html-header')
      ->addScriptFile('uk.co.compucorp.civicrm.pivotreport', 'js/PivotReport.Config.js', 7, 'html-header')
      ->addScriptFile('uk.co.compucorp.civicrm.pivotreport', 'js/FileSaver.js', 8, 'html-header')
      ->addScriptFile('uk.co.compucorp.civicrm.pivotreport', 'js/PivotReport.Export.js', 9, 'html-header')
      ->addScriptFile('uk.co.compucorp.civicrm.pivotreport', 'js/PivotReport.PivotTable.js', 10, 'html-header');
    CRM_Core_Resources::singleton()
      ->addStyleFile('uk.co.compucorp.civicrm.pivotreport', 'packages/c3/c3.min.css', 1)
      ->addStyleFile('uk.co.compucorp.civicrm.pivotreport', 'packages/pivottable/pivot.min.css', 2)
      ->addStyleFile('uk.co.compucorp.civicrm.pivotreport', 'packages/bootstrap/bootstrap.min.css', 3)
      ->addStyleFile('uk.co.compucorp.civicrm.pivotreport', 'packages/bootstrap-sweetalert/sweetalert.min.css', 4)
      ->addStyleFile('uk.co.compucorp.civicrm.pivotreport', 'css/style.css', 5);
  }
}

/**
 * Implementation of hook_civicrm_permission
 *
 * @param array $permissions
 * @return void
 */
function pivotreport_civicrm_permission(&$permissions) {
  $prefix = ts('CiviCRM Reports') . ': '; // name of extension or module
  $permissions['access CiviCRM pivot table reports'] = [
    'label' => $prefix . ts('access CiviCRM pivot table reports'),
  ];
  $permissions['Admin Pivot Report'] = [
    'label' => $prefix . ts('Admin Pivot Report'),
  ];
}
