<?php

/**
 * Stores a single data page.
 *
 * Each data page contains three properties: data (set of pivot rows),
 * index (it may be any string identifier of starting row, such as date value)
 * and page number.
 */
class CRM_PivotData_DataPage {

  /**
   * Page data.
   *
   * @var array
   */
  private $data = array();

  /**
   * Page index.
   *
   * @var string
   */
  private $index = NULL;

  /**
   * Page number.
   *
   * @var int
   */
  private $page = 0;

  /**
   * Next offset
   *
   * @var int
   */
  private $nextOffset = 0;

  /**
   * Next multiValues offset
   *
   * @var int
   */
  private $nextMultiValuesOffset = 0;

  public function __construct($data, $index, $page, $nextOffset, $nextMultiValuesOffset) {
    $this->data = $data;
    $this->index = $index;
    $this->page = $page;
    $this->nextOffset = $nextOffset;
    $this->nextMultiValuesOffset = $nextMultiValuesOffset;
  }

  /**
   * Gets page data.
   *
   * @return array
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Gets page index.
   *
   * @return string
   */
  public function getIndex() {
    return $this->index;
  }

  /**
   * Gets page number.
   *
   * @return int
   */
  public function getPage() {
    return $this->page;
  }

  /**
   * Gets next offset.
   *
   * @return int
   */
  public function getNextOffset() {
    return $this->nextOffset;
  }

  /**
   * Gets next multiValues offset.
   *
   * @return int
   */
  public function getNextMultiValuesOffset() {
    return $this->nextMultiValuesOffset;
  }
}
