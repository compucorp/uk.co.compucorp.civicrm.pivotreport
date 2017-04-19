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
  $offset = !empty($params['offset']) ? (int)$params['offset'] : 0;
  $limit = !empty($params['limit']) ? (int)$params['limit'] : 1000;

  $multiValuesOffset = !empty($params['multiValuesOffset']) ? (int)$params['multiValuesOffset'] : 0;
  $startYearMonth = !empty($params['startYearMonth']) ? $params['startYearMonth'] : null;

  return civicrm_api3_create_success(CRM_Activityreport_Data::get($offset, $limit, $multiValuesOffset, $startYearMonth), $params);
}
