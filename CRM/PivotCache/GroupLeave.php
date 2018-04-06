<?php

/**
 * @inheritdoc
 */
class CRM_PivotCache_GroupLeave extends CRM_PivotCache_AbstractGroup {

  public function __construct($name = NULL, $source = NULL) {
    parent::__construct('Leave', $source);
  }

  /**
   * @inheritdoc
   */
  protected function customizeQuery(CRM_PivotReport_DAO_PivotReportCache $queryObject, $page, array $params) {
    if (!empty($params['keyvalue_from'])) {
      $whereStartDate = CRM_Core_DAO::createSQLFilter(
        'path',
        array(
          '>=' => $this->getPath(substr($params['keyvalue_from'], 0, 10), $page),
        ),
        'String'
      );

      $queryObject->whereAdd($whereStartDate);
    }

    if (!empty($params['keyvalue_to'])) {
      $whereEndDate = CRM_Core_DAO::createSQLFilter(
        'path',
        array(
          '<=' => $this->getPath(substr($params['keyvalue_to'], 0, 10), 999999),
        ),
        'String'
      );

      $queryObject->whereAdd($whereEndDate);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle($entityName) {
    return ts('Leave Reports');
  }
}
