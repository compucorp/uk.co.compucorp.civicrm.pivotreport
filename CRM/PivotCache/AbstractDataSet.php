<?php

use CRM_PivotCache_AbstractGroup as AbstractGroup;

/**
 * @inheritdoc
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
  private $data = array();

  public function __construct($name) {
    $this->name = $name;
  }

  /**
   * @inheritdoc
   */
  public function get(AbstractGroup $cacheGroup, $page, $limit, array $params) {
    $break = FALSE;

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
   * @inheritdoc
   */
  public function getNextIndex() {
    return $this->nextIndex;
  }

  /**
   * @inheritdoc
   */
  public function setNextIndexByPath($path) {
    list(, $index) = explode('_', $path);

    $this->nextIndex = $index;
  }

  /**
   * @inheritdoc
   */
  public function getNextPage() {
    return $this->nextPage;
  }

  /**
   * @inheritdoc
   */
  public function setNextPageByPath($path) {
    list(, , $page) = explode('_', $path);

    $this->nextPage = (int) $page;
  }

  /**
   * @inheritdoc
   */
  public function getData() {
    return $this->data;
  }

  /**
   * @inheritdoc
   */
  public function addData($data) {
    $this->data = array_merge($this->data, $data);
  }

  /**
   * @inheritdoc
   */
  public function getCount() {
    return count($this->data);
  }
}
