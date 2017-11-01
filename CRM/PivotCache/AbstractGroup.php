<?php

use CRM_PivotData_DataPage as DataPage;

/**
 * @inheritdoc
 */
abstract class CRM_PivotCache_AbstractGroup implements CRM_PivotCache_GroupInterface {

  /**
   * Name of cache group.
   *
   * @var string 
   */
  protected $name = NULL;

  public function __construct($name = NULL) {
    $this->name = 'pivotreport.' . $name;
  }

  /**
   * Returns instance of data class for given entity.
   *
   * @param string $entity
   *   Name of entity
   *
   * @return \CRM_PivotData_AbstractData
   */
  public static function getInstance($entity) {

    $className = 'CRM_PivotCache_Group' . $entity;
    $dataInstance = new $className();

    return $dataInstance;
  }

  /**
   * Gets cache group name.
   *
   * @return string
   */
  protected function getName() {
    return $this->name;
  }

  /**
   * @inheritdoc
   */
  public function clear() {
    CRM_PivotReport_BAO_PivotReportCache::deleteGroup($this->getName());
  }

  /**
   * @inheritdoc
   */
  public function getHeader() {
    return json_decode(CRM_PivotReport_BAO_PivotReportCache::getItem($this->getName(), 'header'));
  }

  /**
   * @inheritdoc
   */
  public function cacheHeader(array $rows) {
    $jsonHeader = json_encode($this->sortHeader($rows));
    CRM_PivotReport_BAO_PivotReportCache::setItem($jsonHeader, $this->getName(), 'header');
  }

  /**
   * Prepares an array containing data header with fields labels.
   *
   * @return array
   */
  private function sortHeader(array $header) {
    ksort($header);

    return array_keys($header);
  }

  /**
   * @inheritdoc
   */
  public function getPacket($data) {
    return json_decode(unserialize($data));
  }

  /**
   * @inheritdoc
   */
  public function cachePage(DataPage $page) {
    $count = count($page->getData());

    $jsonData = json_encode($page->getData());
    CRM_PivotReport_BAO_PivotReportCache::setItem($jsonData, $this->getName(), $this->getPath($page->getIndex(), $page->getPage()));

    return $count;
  }

  /**
   * Gets a cache path string by specified index and page.
   *
   * @param string $index
   * @param int $page
   *
   * @return string
   */
  protected function getPath($index, $page = NULL) {
    return 'data_' . $index . '_' . str_pad($page, 6, '0', STR_PAD_LEFT);
  }


  /**
   * Checks if cache for the entity is built.
   *
   * @return bool
   *   True if there is data in cache for the entity, false otherwise
   */
  public function isCacheBuilt() {
    $cache = new CRM_PivotReport_DAO_PivotReportCache();

    $cache->group_name = $this->getName();
    $cache->whereAdd("path = 'header'");
    $cache->whereAdd("group_name = '{$this->getName()}'");
    $cache->orderBy('path ASC');
    $cache->find();

    if ($cache->N > 0) {
      return true;
    }

    return false;
  }

}
