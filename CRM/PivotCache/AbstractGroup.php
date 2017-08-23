<?php

/**
 * Manages 'pivotreport' cache group.
 */
abstract class CRM_PivotCache_AbstractGroup implements CRM_PivotCache_GroupInterface {

  /**
   * Name of cache group.
   *
   * @var string 
   */
  private $name = NULL;

  public function __construct($name) {
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
   * Deletes cache group.
   */
  public function clear() {
    CRM_Core_BAO_Cache::deleteGroup($this->getName());
  }

  /**
   * Gets header row from cache.
   *
   * @return arryay
   */
  public function getHeader() {
    return json_decode(CRM_Core_BAO_Cache::getItem($this->getName(), 'header'));
  }

  /**
   * Caches a header row.
   *
   * @param array $rows
   */
  public function cacheHeader(array $rows) {
    CRM_Core_BAO_Cache::setItem(json_encode($this->sortHeader($rows)), $this->getName(), 'header');
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
   * Gets an array of serialized data packet.
   *
   * @param string $data
   *
   * @return array
   */
  public function getPacket($data) {
    return json_decode(unserialize($data));
  }

  /**
   * Puts a data packet into cache table with specific index and page number.
   * Returns count of packet items.
   *
   * @param array $packet
   * @param string $index
   * @param int $page
   *
   * @return int
   */
  public function cachePacket(array $packet, $index, $page = NULL) {
    if (empty($packet)) {
      return 0;
    }

    $count = count($packet);

    CRM_Core_BAO_Cache::setItem(json_encode($packet), $this->getName(), $this->getPath($index, $page));

    unset($packet);

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
  private function getPath($index, $page = NULL) {
    return 'data_' . $index . '_' . str_pad($page, 6, '0', STR_PAD_LEFT);
  }
}
