<?php

/**
 * @inheritdoc
 */
class CRM_PivotCache_GroupActivity extends CRM_PivotCache_AbstractGroup {

  public function __construct($name = NULL) {
    parent::__construct('Activity');
  }

  /**
   * @inheritdoc
   */
  public function query($page, array $params) {
    $cache = new CRM_Core_DAO_Cache();

    $cache->group_name = $this->getName();

    if (!empty($params['start_date'])) {
      $whereStartDate = CRM_Core_DAO::createSQLFilter(
        'path',
        array(
          '>=' => $this->getPath(substr($params['start_date'], 0, 10), $page),
        ),
        'String'
      );

      $cache->whereAdd($whereStartDate);
    }

    if (!empty($params['end_date'])) {
      $whereEndDate = CRM_Core_DAO::createSQLFilter(
        'path',
        array(
          '<=' => $this->getPath(substr($params['end_date'], 0, 10), 999999),
        ),
        'String'
      );

      $cache->whereAdd($whereEndDate);
    }

    $cache->whereAdd("path <> 'header'");

    $cache->orderBy('path ASC');

    $cache->find();

    return $cache;
  }
}
