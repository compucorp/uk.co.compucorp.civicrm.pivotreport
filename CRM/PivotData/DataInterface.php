<?php

use CRM_PivotCache_AbstractGroup as AbstractGroup;

/**
 * Contains API entry point to get the data and to rebuild existing cached DataSets.
 * Also contains all logic to parse Entity data into output format used for
 * cache the data.
 */
interface CRM_PivotData_DataInterface {

  /**
   * Returns an array containing formatted entity data and information
   * needed to make a call for more data.
   *
   * @param \CRM_PivotCache_AbstractGroup $cacheGroup
   * @param array $params
   * @param int $page
   *
   * @return array
   */
  public function get(AbstractGroup $cacheGroup, array $params, $page = 0);

  /**
   * Rebuilds pivot report cache including header and data.
   *
   * @param \CRM_PivotCache_AbstractGroup $cacheGroup
   * @param array $params
   *
   * @return array
   */
  public function rebuildCache(AbstractGroup $cacheGroup, array $params);

  /**
   * Rebuilds pivot report cache partially including header and data.
   *
   * @param \CRM_PivotCache_AbstractGroup $cacheGroup
   * @param array $params
   * @param int $offset
   * @param int $multiValuesOffset
   * @param string $index
   * @param int $page
   * @param int $pivotCount
   *
   * @return array
   */
  public function rebuildCachePartial(AbstractGroup $cacheGroup, array $params, $offset, $multiValuesOffset, $index, $page, $pivotCount);

  /**
   * Rebuilds entity data cache using entity paginated results.
   *
   * @param \CRM_PivotCache_AbstractGroup $cacheGroup
   * @param array $params
   * @param int $offset
   * @param int $multiValuesOffset
   * @param int $page
   *
   * @return int
   */
  public function rebuildData(AbstractGroup $cacheGroup, array $params, $offset = 0, $multiValuesOffset = 0, $page = 0);

  /**
   * Rebuilds entity header cache.
   *
   * @param \CRM_PivotCache_AbstractGroup $cacheGroup
   * @param array $header
   */
  public function rebuildHeader(AbstractGroup $cacheGroup, array $header);

  /**
   * Returns list of fields of Date data type for the entity.
   *
   * @return array
   */
  public function getDateFields();
}
