<?php

/**
 * ActivityReport.get API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_activity_report_get_spec(&$spec) {
}

/**
 * ActivityReport.get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_activity_report_get($params) {
  $startDate = !empty($params['start_date']) ? $params['start_date'] : null;
  $endDate = !empty($params['end_date']) ? $params['end_date'] : null;
  $page = !empty($params['page']) ? (int)$params['page'] : 0;

  return civicrm_api3_create_success(CRM_Activityreport_Data::get($startDate, $endDate, $page), $params);
}

/**
 * ActivityReport.getheader API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_activity_report_getheader($params) {
  $cacheGroup = new CRM_PivotCache_Group('activity');

  return civicrm_api3_create_success($cacheGroup->getHeader(), $params);
}

/**
 * ActivityReport.rebuildcache API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_activity_report_rebuildcache($params) {
  $startDate = !empty($params['start_date']) ? $params['start_date'] : null;
  $endDate = !empty($params['end_date']) ? $params['end_date'] : null;

  return civicrm_api3_create_success(CRM_Activityreport_Data::rebuildCache($startDate, $endDate), $params);
}
