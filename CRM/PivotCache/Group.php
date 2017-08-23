<?php

/**
 * Manages 'pivotreport' cache group.
 */
class CRM_PivotCache_Group {

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
  private function getName() {
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
   * Gets CRM_PivotCache_DataSet object of cached data for specified criteria.
   *
   * @param string $startDate
   * @param string $endDate
   * @param int $page
   * @param int $limit
   *
   * @return \CRM_PivotCache_DataSet
   */
  public function getDataSet($startDate, $endDate, $page, $limit) {
    $break = FALSE;
    $dataSet = new CRM_PivotCache_DataSet();
    $cache = $this->getDbDataResource($startDate, $endDate, $page);

    while ($cache->fetch()) {
      if ($break) {
        $dataSet->setNextDateByPath($cache->path);
        $dataSet->setNextPageByPath($cache->path);

        break;
      }

      $dataSet->addData($this->getPacket($cache->data));

      if ($dataSet->getCount() >= $limit) {
        $break = TRUE;
      }
    }

    return $dataSet;
  }

  /**
   * Gets DAO resource of cached data for specified criteria.
   *
   * @param string $startDate
   * @param string $endDate
   * @param int $page
   *
   * @return \CRM_Core_DAO
   */
  private function getDbDataResource($startDate, $endDate, $page) {
    $cache = new CRM_Core_DAO_Cache();

    $cache->group_name = $this->getName();

    if (!empty($startDate)) {
      $whereStartDate = CRM_Core_DAO::createSQLFilter(
        'path',
        array(
          '>=' => 'data_' . substr($startDate, 0, 10) . '_' . str_pad($page, 6, '0', STR_PAD_LEFT),
        ),
        'String'
      );

      $cache->whereAdd($whereStartDate);
    }

    if (!empty($endDate)) {
      $whereEndDate = CRM_Core_DAO::createSQLFilter(
        'path',
        array(
          '<=' => 'data_' . substr($endDate, 0, 10) . '_999999',
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

  /**
   * Gets an array of serialized data packet.
   *
   * @param string $data
   * @return array
   */
  private function getPacket($data) {
    return json_decode(unserialize($data));
  }

  /**
   * Puts a data packet into cache table with specific date and page number.
   * Returns count of packet items.
   *
   * @param array $packet
   * @param string $date
   * @param int $page
   *
   * @return int
   */
  public function cachePacket(array $packet, $date, $page = NULL) {
    if (empty($packet)) {
      return 0;
    }

    $count = count($packet);

    CRM_Core_BAO_Cache::setItem(json_encode($packet), $this->getName(), $this->getPathByDateAndPage($date, $page));

    unset($packet);

    return $count;
  }

  /**
   * Gets a cache path string by specified date and page.
   *
   * @param string $date
   * @param int $page
   *
   * @return string
   */
  private function getPathByDateAndPage($date, $page = NULL) {
    return 'data_' . $date . '_' . str_pad($page, 6, '0', STR_PAD_LEFT);
  }
}
