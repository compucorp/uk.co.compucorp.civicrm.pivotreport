<?php

/**
 * PivotReport.get API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_pivot_report_get_spec(&$spec) {
}

/**
 * PivotReport.get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_pivot_report_get($params) {
  $entity = !empty($params['entity']) ? $params['entity'] : 'Activity';
  $entityInstance = new CRM_PivotReport_Entity($entity);

  $keyValueFrom = !empty($params['keyvalue_from']) ? $params['keyvalue_from'] : null;
  $keyValueTo = !empty($params['keyvalue_to']) ? $params['keyvalue_to'] : null;
  $page = !empty($params['page']) ? (int)$params['page'] : 0;

  return civicrm_api3_create_success(
    $entityInstance->getDataInstance()->get(
      $entityInstance->getGroupInstance(),
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
 * PivotReport.getheader API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_pivot_report_getheader($params) {
  $entity = !empty($params['entity']) ? $params['entity'] : 'Activity';
  $entityInstance = new CRM_PivotReport_Entity($entity);

  return civicrm_api3_create_success($entityInstance->getGroupInstance()->getHeader(), $params);
}

/**
 * PivotReport.rebuildcache API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_pivot_report_rebuildcache($params) {
  $result = array();

  if (!empty($params['entity'])) {
    $entities = array($params['entity']);
  } else {
    $entities = CRM_PivotReport_Entity::getSupportedEntities();
  }

  foreach ($entities as $entity) {
    $entityInstance = new CRM_PivotReport_Entity($entity);
    $result[$entity] = $entityInstance->getDataInstance()->rebuildCache(
      $entityInstance->getGroupInstance(),
      array()
    );
  }

  return civicrm_api3_create_success(
    $result,
    $params
  );
}

function civicrm_api3_pivot_report_getdatefields($params) {
  $entity = !empty($params['entity']) ? $params['entity'] : 'Activity';
  $entityInstance = new CRM_PivotReport_Entity($entity);

  return civicrm_api3_create_success(
    $entityInstance->getDataInstance()->getDateFields(),
    $params
  );
}
