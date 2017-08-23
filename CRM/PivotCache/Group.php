<?php

/**
 * Manages 'pivotreport' cache group.
 */
class CRM_PivotCache_Group extends CRM_PivotCache_AbstractGroup {

  /**
   * Gets DAO resource of cached data for specified criteria.
   *
   * @param int $page
   * @param array $params
   *
   * @return \CRM_Core_DAO
   */
  public function query($page, array $params) {
    $cache = new CRM_Core_DAO_Cache();

    $cache->group_name = $this->getName();

    if (!empty($params['start_date'])) {
      $whereStartDate = CRM_Core_DAO::createSQLFilter(
        'path',
        array(
          '>=' => 'data_' . substr($params['start_date'], 0, 10) . '_' . str_pad($page, 6, '0', STR_PAD_LEFT),
        ),
        'String'
      );

      $cache->whereAdd($whereStartDate);
    }

    if (!empty($params['end_date'])) {
      $whereEndDate = CRM_Core_DAO::createSQLFilter(
        'path',
        array(
          '<=' => 'data_' . substr($params['end_date'], 0, 10) . '_999999',
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
