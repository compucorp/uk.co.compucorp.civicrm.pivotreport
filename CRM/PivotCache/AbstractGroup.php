<?php

use CRM_PivotReport_DataPage as DataPage;

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
    CRM_Core_BAO_Cache::deleteGroup($this->getName());
  }

  /**
   * @inheritdoc
   */
  public function getHeader() {
    return json_decode(CRM_Core_BAO_Cache::getItem($this->getName(), 'header'));
  }

  /**
   * @inheritdoc
   */
  public function cacheHeader(array $rows) {
    $jsonHeader = json_encode($this->sortHeader($rows));
    CRM_Core_BAO_Cache::setItem($jsonHeader, $this->getName(), 'header');
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
    CRM_Core_BAO_Cache::setItem($jsonData, $this->getName(), $this->getPath($page->getIndex(), $page->getPage()));

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
    $cache = new CRM_Core_DAO_Cache();

    $cache->group_name = $this->getName();
    $cache->whereAdd("path = 'header'");
    $cache->orderBy('path ASC');
    $cache->find();

    if ($cache->N > 0) {
      return true;
    }

    return false;
  }

}
