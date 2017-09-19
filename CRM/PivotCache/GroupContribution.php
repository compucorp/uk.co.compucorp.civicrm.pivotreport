<?php

/**
 * @inheritdoc
 */
class CRM_PivotCache_GroupContribution extends CRM_PivotCache_AbstractGroup {

  public function __construct($name = NULL) {
    parent::__construct('Contribution');
  }

  /**
   * @inheritdoc
   */
  public function query($page, array $params) {
    $cache = new CRM_Core_DAO_Cache();

    $cache->group_name = $this->getName();

    $cache->whereAdd("path <> 'header'");

    $cache->orderBy('path ASC');

    $cache->find();

    return $cache;
  }
}
