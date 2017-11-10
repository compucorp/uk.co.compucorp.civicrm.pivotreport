<?php

/**
 * @inheritdoc
 */
class CRM_PivotCache_GroupMembership extends CRM_PivotCache_AbstractGroup {

  public function __construct($name = NULL) {
    parent::__construct('Membership');
  }

  /**
   * @inheritdoc
   */
  public function query($page, array $params) {
    $cache = new CRM_PivotReport_DAO_PivotReportCache();

    $cache->group_name = $this->getName();

    $cache->whereAdd("path <> 'header'");

    $cache->orderBy('path ASC');

    $cache->find();

    return $cache;
  }
}
