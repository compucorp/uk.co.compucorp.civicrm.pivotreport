<?php

use CRM_PivotCache_AbstractGroup as AbstractGroup;

/**
 * Provides an interface for managing Pivot Report DataSet.
 * DataSet means single CiviCRM cache row (which may contain a set of Entity
 * rows grouped by cache key - 'index' and 'page' values).
 */
interface CRM_PivotCache_DataSetInterface {

  /**
   * Gets CRM_PivotCache_DataSet object of cached data for specified criteria.
   *
   * @param \CRM_PivotCache_AbstractGroup $cacheGroup
   * @param int $page
   * @param int $limit
   * @param array $params
   *
   * @return \CRM_PivotCache_DataSet
   */
  public function get(AbstractGroup $cacheGroup, $page, $limit, array $params);

  /**
   * Gets next index needed for further data request.
   *
   * @return string
   */
  public function getNextIndex();

  /**
   * Sets next index by given path.
   *
   * @param string $path
   */
  public function setNextIndexByPath($path);

  /**
   * Gets next page value, needed for further data request.
   *
   * @return int
   */
  public function getNextPage();

  /**
   * Sets next page value by given path.
   *
   * @param string $path
   */
  public function setNextPageByPath($path);

  /**
   * Gets data array of current data set.
   *
   * @return array
   */
  public function getData();

  /**
   * Adds given data to current data set's data array.
   *
   * @param array $data
   */
  public function addData($data);

  /**
   * Gets data count of current data set's data array.
   *
   * @return int
   */
  public function getCount();
}
