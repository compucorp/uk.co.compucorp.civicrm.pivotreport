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

  /**
   * Source of cache group.
   *
   * @var int
   */
  protected $source = NULL;

  public function __construct($name, $source) {
    $this->name = 'pivotreport.' . $name;
    $this->source = $source;
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
  public function getName() {
    return $this->name;
  }

  /**
   * Gets cache group source.
   *
   * @return string
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * @inheritdoc
   */
  public function clear() {
    CRM_PivotReport_BAO_PivotReportCache::deleteGroup($this->getName(), NULL, $this->source);
  }

  /**
   * @inheritdoc
   */
  public function getHeader() {
    return json_decode($this->getCacheValue('header'));
  }

  /**
   * @inheritdoc
   */
  public function cacheHeader(array $rows) {
    $jsonHeader = json_encode($this->sortHeader($rows));
    $this->setCacheValue('header', $jsonHeader);
  }

  /**
   * @inheritdoc
   */
  public function setCacheValue($key, $value) {
    CRM_PivotReport_BAO_PivotReportCache::setItem($value, $this->getName(), $key, $this->source);
  }

  /**
   * @inheritdoc
   */
  public function getCacheValue($key) {
    return CRM_PivotReport_BAO_PivotReportCache::getItem($this->getName(), $key);
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
    $this->setCacheValue($this->getPath($page->getIndex(), $page->getPage()), $jsonData);

    return $count;
  }

  /**
   * @inheritdoc
   */
  public function query($page, array $params) {
    $cache = new CRM_PivotReport_DAO_PivotReportCache();

    $cache->group_name = $this->getName();

    $this->customizeQuery($cache, $page, $params);

    $cache->whereAdd("path NOT IN ('header', 'entityCount', 'pivotCount') AND is_active = 1");

    $cache->orderBy('path ASC');

    $cache->find();

    return $cache;
  }

  /**
   * Allows to modify Pivot Report Cache DAO object before executing the query.
   *
   * @param \CRM_PivotReport_DAO_PivotReportCache $queryObject
   * @param int $page
   * @param array $params
   */
  protected function customizeQuery(CRM_PivotReport_DAO_PivotReportCache $queryObject, $page, array $params) {
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

    $cache->whereAdd("path = 'pivotCount'");
    $cache->whereAdd("group_name = '{$this->getName()}'");
    $cache->whereAdd("is_active = 1");
    $cache->find();

    if ($cache->N > 0) {
      return true;
    }

    return false;
  }
}
