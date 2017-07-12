<?php

/**
 * 
 */
class CRM_PivotCache_DataSet {

  /**
   * Next date in YYYY-MM-DD format to fetch with further request.
   *
   * @var string 
   */
  private $nextDate = NULL;

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

  /**
   * Gets next date value in YYYY-MM-DD format, needed for further data request.
   *
   * @return string
   */
  public function getNextDate() {
    return $this->nextDate;
  }

  /**
   * Sets next date value by given path.
   *
   * @param string $path
   */
  public function setNextDateByPath($path) {
    $this->nextDate = substr($path, 5, 10);
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
    $this->nextPage = (int) substr($path, 16);
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
