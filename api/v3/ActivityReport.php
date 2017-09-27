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
  $keyValueFrom = !empty($params['keyvalue_from']) ? $params['keyvalue_from'] : null;
  $keyValueTo = !empty($params['keyvalue_to']) ? $params['keyvalue_to'] : null;
  $page = !empty($params['page']) ? (int)$params['page'] : 0;
  $entity = !empty($params['entity']) ? $params['entity'] : 'Activity';

  switch ($entity) {
    case 'Activity':
      $dataInstance = new CRM_PivotReport_DataActivity();
      $cacheGroupInstance = new CRM_PivotCache_GroupActivity();
      break;

    case 'Contribution':
      $dataInstance = new CRM_PivotReport_DataContribution();
      $cacheGroupInstance = new CRM_PivotCache_GroupContribution();
      break;

    case 'Membership':
      $dataInstance = new CRM_PivotReport_DataMembership();
      $cacheGroupInstance = new CRM_PivotCache_GroupMembership();
      break;
  }

  return civicrm_api3_create_success(
    $dataInstance->get(
      $cacheGroupInstance,
      array(
        'keyvalue_from' => $keyValueFrom,
        'keyvalue_to' => $keyValueTo,
      ),
      $page
    ),
    $params
  );
}

/**
 * ActivityReport.getheader API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_activity_report_getheader($params) {
  $entity = !empty($params['entity']) ? $params['entity'] : 'Activity';

  switch ($entity) {
    case 'Activity':
      $cacheGroupInstance = new CRM_PivotCache_GroupActivity();
      break;

    case 'Contribution':
      $cacheGroupInstance = new CRM_PivotCache_GroupContribution();
      break;

    case 'Membership':
      $cacheGroupInstance = new CRM_PivotCache_GroupMembership();
      break;
  }

  return civicrm_api3_create_success($cacheGroupInstance->getHeader(), $params);
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

  $dataInstance = new CRM_PivotReport_DataActivity();
  $cacheGroupInstance = new CRM_PivotCache_GroupActivity();
  $activities = $dataInstance->rebuildCache(
    $cacheGroupInstance,
    array(
      'start_date' => $startDate,
      'end_date' => $endDate,
    )
  );

  $dataInstance = new CRM_PivotReport_DataContribution();
  $cacheGroupInstance = new CRM_PivotCache_GroupContribution();
  $contributions = $dataInstance->rebuildCache(
    $cacheGroupInstance,
    array(
    )
  );

  $dataInstance = new CRM_PivotReport_DataMembership();
  $cacheGroupInstance = new CRM_PivotCache_GroupMembership();
  $memberships = $dataInstance->rebuildCache(
    $cacheGroupInstance,
    array(
    )
  );

  return civicrm_api3_create_success(
    array(
      'activities' => $activities,
      'contributions' => $contributions,
      'memberships' => $memberships
    ),
    $params
  );
}
