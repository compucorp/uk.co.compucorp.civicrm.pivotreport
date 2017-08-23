<?php

/**
 * Manages pivot report data set.
 */
abstract class CRM_PivotCache_AbstractDataSet implements CRM_PivotCache_DataSetInterface {
  /**
   * Name of data set.
   *
   * @var string
   */
  private $name;

  /**
   * Next index to fetch with further request.
   *
   * @var string 
   */
  private $nextIndex = NULL;

  /**
   * Next page to fetch with further request.
   *
   * @var int 
   */
  private $nextPage = NULL;

  /**
   * An array of data for current data set.
   *
   * @var array 
   */
  private $data = [];

  public function __construct($name) {
    $this->name = $name;
  }

  /**
   * Gets CRM_PivotCache_DataSet object of cached data for specified criteria.
   *
   * @param int $page
   * @param int $limit
   * @param array $params
   *
   * @return \CRM_PivotCache_DataSet
   */
  public function get($page, $limit, array $params) {
    $break = FALSE;
    $cacheGroup = new CRM_PivotCache_Group($this->name);

    $cache = $cacheGroup->query($page, $params);

    while ($cache->fetch()) {
      if ($break) {
        $this->setNextIndexByPath($cache->path);
        $this->setNextPageByPath($cache->path);

        break;
      }

      $this->addData($cacheGroup->getPacket($cache->data));

      if ($this->getCount() >= $limit) {
        $break = TRUE;
      }
    }

    return $this;
  }

  /**
   * Gets next index needed for further data request.
   *
   * @return string
   */
  public function getNextIndex() {
    return $this->nextIndex;
  }

  /**
   * Sets next index by given path.
   *
   * @param string $path
   */
  public function setNextIndexByPath($path) {
    list(, $index) = explode('_', $path);

    $this->nextIndex = $index;
  }

  /**
   * Gets next page value, needed for further data request.
   *
   * @return int
   */
  public function getNextPage() {
    return $this->nextPage;
  }

  /**
   * Sets next page value by given path.
   *
   * @param string $path
   */
  public function setNextPageByPath($path) {
    list(, , $page) = explode('_', $path);

    $this->nextPage = (int) $page;
  }

  /**
   * Gets data array of current data set.
   *
   * @return array
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Adds given data to current data set's data array.
   *
   * @param array $data
   */
  public function addData($data) {
    $this->data = array_merge($this->data, $data);
  }

  /**
   * Gets data count of current data set's data array.
   *
   * @return int
   */
  public function getCount() {
    return count($this->data);
  }
}
