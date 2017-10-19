<?php

use CRM_PivotData_DataPage as DataPage;

/**
 * Provides an interface for managing 'pivotreport' cache group.
 * The Cache Group reflects CiviCRM cache data limited to 'pivotreport' group.
 */
interface CRM_PivotCache_GroupInterface {

  /**
   * Deletes cache group.
   */
  public function clear();

  /**
   * Gets header row from cache.
   *
   * @return arryay
   */
  public function getHeader();

  /**
   * Caches a header row.
   *
   * @param array $rows
   */
  public function cacheHeader(array $rows);

  /**
   * Gets an array of serialized data packet.
   *
   * @param string $data
   *
   * @return array
   */
  public function getPacket($data);

  /**
   * Puts a data page into cache table.
   * Returns count of packet items.
   *
   * @param \CRM_PivotData_DataPage $page
   *
   * @return int
   */
  public function cachePage(DataPage $page);

  /**
   * Gets DAO resource of cached data for specified criteria.
   *
   * @param int $page
   * @param array $params
   *
   * @return \CRM_Core_DAO
   */
  public function query($page, array $params);
}
