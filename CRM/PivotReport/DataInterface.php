<?php

interface CRM_PivotReport_DataInterface {

  /**
   * Returns an array containing formatted entity data and information
   * needed to make a call for more data.
   *
   * @param array $params
   * @param int $page
   *
   * @return array
   */
  public function get(array $params, $page = 0);

  /**
   * Rebuilds pivot report cache including header and data.
   *
   * @param array $params
   *
   * @return array
   */
  public function rebuildCache(array $params);

  /**
   * Rebuilds entity data cache using entity paginated results.
   *
   * @param \CRM_PivotCache_Group $cacheGroup
   * @param string $entityName
   * @param array $params
   * @param int $offset
   * @param int $multiValuesOffset
   * @param int $page
   *
   * @return int
   */
  public function rebuildData($cacheGroup, $entityName, array $params, $offset = 0, $multiValuesOffset = 0, $page = 0);

  /**
   * Rebuilds entity header cache.
   *
   * @param \CRM_PivotCache_Group $cacheGroup
   * @param array $header
   */
  public function rebuildHeader($cacheGroup, array $header);
}
