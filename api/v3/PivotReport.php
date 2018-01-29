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
      $entityInstance->getGroupInstance(CRM_PivotReport_BAO_PivotReportCache::SOURCE_REBUILDCACHE),
      array()
    );
  }

  CRM_PivotReport_BAO_PivotReportCache::updateBuildDatetime();

  return civicrm_api3_create_success(
    $result,
    $params
  );
}

/**
 * PivotReport.rebuildcachechunk API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_pivot_report_rebuildcachechunk($params) {
  $result = CRM_PivotReport_BAO_PivotReportCache::rebuildCacheChunk();

  return civicrm_api3_create_success(
    array($result),
    $params
  );
}

/**
 * PivotReport.rebuildcachepartial API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_pivot_report_rebuildcachepartial($params) {
  $entity = !empty($params['entity']) ? $params['entity'] : NULL;
  $offset = !empty($params['offset']) ? (int) $params['offset'] : 0;
  $multiValuesOffset = !empty($params['multiValuesOffset']) ? (int) $params['multiValuesOffset'] : 0;
  $index = !empty($params['index']) ? $params['index'] : NULL;
  $page = !empty($params['page']) ? (int) $params['page'] : 0;
  $pivotCount = !empty($params['pivotCount']) ? (int) $params['pivotCount'] : 0;

  $entityInstance = new CRM_PivotReport_Entity($entity);
  $result = $entityInstance->getDataInstance()->rebuildCachePartial(
    $entityInstance->getGroupInstance(CRM_PivotReport_BAO_PivotReportCache::SOURCE_UI),
    $params,
    $offset,
    $multiValuesOffset,
    $index,
    $page,
    $pivotCount
  );

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

/**
 * PivotReport.getsupportedentities API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_pivot_report_getsupportedentities($params) {
  return civicrm_api3_create_success(
    CRM_PivotReport_Entity::getSupportedEntities(),
    $params
  );
}

/**
 * PivotReport.getsupportedentitiescount API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_pivot_report_getsupportedentitiescount($params) {
  $entities = CRM_PivotReport_Entity::getSupportedEntities();
  $result = array();

  foreach ($entities as $entity) {
    $entityInstance = new CRM_PivotReport_Entity($entity);
    $result[$entity] = (int) $entityInstance->getDataInstance()->getCount();
  }

  return civicrm_api3_create_success(
    array($result),
    $params
  );
}

/**
 * PivotReport.gettotalcount API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_pivot_report_gettotalcount($params) {
  $entities = CRM_PivotReport_Entity::getSupportedEntities();
  $totalCount = 0;

  foreach ($entities as $entity) {
    $entityInstance = new CRM_PivotReport_Entity($entity);
    $totalCount += (int) $entityInstance->getDataInstance()->getCount();
  }

  return civicrm_api3_create_success(
    $totalCount,
    $params
  );
}

/**
 * PivotReport.getcount API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_pivot_report_getcount($params) {
  $entity = !empty($params['entity']) ? $params['entity'] : NULL;

  $entityInstance = new CRM_PivotReport_Entity($entity);

  return civicrm_api3_create_success(
    (int) $entityInstance->getDataInstance()->getCount(),
    $params
  );
}

/**
 * PivotReport.getentitycount API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_pivot_report_getentitycount($params) {
  $entity = !empty($params['entity']) ? $params['entity'] : NULL;

  $entityInstance = new CRM_PivotReport_Entity($entity);

  return civicrm_api3_create_success(
    (int) $entityInstance->getDataInstance()->getEntityCount($entityInstance->getGroupInstance()),
    $params
  );
}

/**
 * PivotReport.getpivotcount API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_pivot_report_getpivotcount($params) {
  $entity = !empty($params['entity']) ? $params['entity'] : NULL;

  $entityInstance = new CRM_PivotReport_Entity($entity);

  return civicrm_api3_create_success(
    (int) $entityInstance->getDataInstance()->getPivotCount($entityInstance->getGroupInstance()),
    $params
  );
}

/**
 * PivotReport.getbuilddatetime API
 * Returns last cache build date. If 'format' value equals '1' then output
 * value is formatted with default CRM date format.
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_pivot_report_getbuilddatetime($params) {
  $result = CRM_PivotReport_BAO_PivotReportCache::getBuildDatetime();

  if (!empty($params['format']) && (int) $params['format'] === 1) {
    $result = CRM_Utils_Date::customFormat($result);
  }

  return civicrm_api3_create_success(
    $result,
    $params
  );
}

/**
 * PivotReport.updatebuilddatetime API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_pivot_report_updatebuilddatetime($params) {
  return civicrm_api3_create_success(
    CRM_PivotReport_BAO_PivotReportCache::updateBuildDatetime(),
    $params
  );
}
