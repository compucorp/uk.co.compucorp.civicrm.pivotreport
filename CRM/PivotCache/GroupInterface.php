<?php

/**
 * Manages 'pivotreport' cache group.
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
   * Puts a data packet into cache table with specific index and page number.
   * Returns count of packet items.
   *
   * @param array $packet
   * @param string $index
   * @param int $page
   *
   * @return int
   */
  public function cachePacket(array $packet, $index, $page = NULL);

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
