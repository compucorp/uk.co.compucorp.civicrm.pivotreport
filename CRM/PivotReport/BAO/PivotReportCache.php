<?php

use CRM_PivotReport_ExtensionUtil as E;

class CRM_PivotReport_BAO_PivotReportCache extends CRM_PivotReport_DAO_PivotReportCache {

  /**
   * Cache built using API rebuildcache action.
   */
  const SOURCE_REBUILDCACHE = 1;

  /**
   * Cache built using API rebuildcachechunk action.
   */
  const SOURCE_REBUILDCACHECHUNK = 2;

  /**
   * Cache built using Pivot Report Config UI.
   */
  const SOURCE_UI = 3;

  /**
   * @var array ($cacheKey => $cacheValue)
   */
  static $_cache = NULL;

  /**
   * Caches single chunk of Pivot Entity based on previously saved chunk
   * status data.
   * Returns an array containing Entity, Offset and MultiValuesOffset values
   * and time taken to complete in microseconds or NULL if there is active
   * lock present (which means that previous run didn't finish yet).
   *
   * @return string
   */
  public static function rebuildCacheChunk() {
    if (self::isLocked()) {
      return NULL;
    }

    $time = microtime(true);
    self::setIsLocked(TRUE);

    $status = new CRM_PivotCache_PivotReportChunkStatus();

    $entityInstance = new CRM_PivotReport_Entity($status->getEntity());
    $result = $entityInstance->getDataInstance()->rebuildCachePartial(
      $entityInstance->getGroupInstance(self::SOURCE_REBUILDCACHECHUNK),
      array(),
      $status->getOffset(),
      $status->getMultiValuesOffset(),
      $status->getIndex(),
      $status->getPage(),
      $status->getPivotCount()
    );

    if (!$result['count']) {
      $status->setupNextEntity();
    } else {
      $status->setOffset($result['offset']);
      $status->setMultiValuesOffset($result['multiValuesOffset']);
      $status->setPage($result['page']);
      $status->setIndex($result['index']);
      $status->setPivotCount($status->getPivotCount() + $result['count']);
    }

    $status->update();

    $cacheBuilt = FALSE;
    if (!$status->getEntity()) {
      self::updateBuildDatetime();
      $cacheBuilt = TRUE;
    }

    self::setIsLocked(FALSE);

    return array(
      'entity' => $status->getEntity(),
      'offset' => $status->getOffset(),
      'multiValuesOffset' => $status->getMultiValuesOffset(),
      'time' => microtime(true) - $time,
      'cacheBuilt' => $cacheBuilt,
    );
  }

  /**
   * Retrieve an item from the DB cache.
   *
   * @param string $group
   *   (required) The group name of the item.
   * @param string $path
   *   (required) The path under which this item is stored.
   *
   * @return object
   *   The data if present in cache, else null
   */
  public static function &getItem($group, $path) {
    if (self::$_cache === NULL) {
      self::$_cache = array();
    }

    $argString = "CRM_CT_{$group}_{$path}";
    if (!array_key_exists($argString, self::$_cache)) {
      $cache = CRM_Utils_Cache::singleton();
      self::$_cache[$argString] = $cache->get($argString);
      if (!self::$_cache[$argString]) {
        $table = self::getTableName();
        $where = self::whereCache($group, $path);
        $rawData = CRM_Core_DAO::singleValueQuery("SELECT data FROM $table WHERE $where");
        $data = $rawData ? unserialize($rawData) : NULL;

        self::$_cache[$argString] = $data;
        $cache->set($argString, self::$_cache[$argString]);
      }
    }

    return self::$_cache[$argString];
  }

  /**
   * Store an item in the DB cache.
   *
   * @param object $data
   *   (required) A reference to the data that will be serialized and stored.
   * @param string $group
   *   (required) The group name of the item.
   * @param string $path
   *   (required) The path under which this item is stored.
   * @param int $source
   *   (optional) Source of the cache item.
   */
  public static function setItem(&$data, $group, $path, $source = NULL) {
    if (self::$_cache === NULL) {
      self::$_cache = array();
    }

    $lock = Civi::lockManager()->acquire("cache.{$group}_{$path}");
    if (!$lock->isAcquired()) {
      CRM_Core_Error::fatal();
    }

    $table = self::getTableName();
    $where = self::whereCache($group, $path, $source);
    $dataExists = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM $table WHERE {$where} AND is_active = 0");
    $now = date('Y-m-d H:i:s');
    $dataSerialized = serialize($data);

    if ($dataExists) {
      $sql = "UPDATE $table SET data = %1, created_date = %2 WHERE {$where}";
      $args = array(
        1 => array($dataSerialized, 'String'),
        2 => array($now, 'String'),
      );
      $dao = CRM_Core_DAO::executeQuery($sql, $args, TRUE, NULL, FALSE, FALSE);
    }
    else {
      $insert = CRM_Utils_SQL_Insert::into($table)
        ->row(array(
          'group_name' => $group,
          'path' => $path,
          'data' => $dataSerialized,
          'created_date' => $now,
          'is_active' => 0,
          'source' => $source,
        ));
      $dao = CRM_Core_DAO::executeQuery($insert->toSQL(), array(), TRUE, NULL, FALSE, FALSE);
    }

    $lock->release();

    $dao->free();

    // cache coherency - refresh or remove dependent caches
    $argString = "CRM_CT_{$group}_{$path}";
    $cache = CRM_Utils_Cache::singleton();
    $data = unserialize($dataSerialized);
    self::$_cache[$argString] = $data;
    $cache->set($argString, $data);

  }

  /**
   * Delete all the cache elements that belong to a group OR delete the entire
   * cache if group is not specified.
   *
   * @param string $group
   *   The group name of the entries to be deleted.
   * @param string $path
   *   Path of the item that needs to be deleted.
   * @param int $source
   *   Source of the cache group.
   */
  public static function deleteGroup($group, $path = NULL, $source = NULL) {
    $table = self::getTableName();
    $where = self::whereCache($group, $path, $source);
    CRM_Core_DAO::executeQuery("DELETE FROM $table WHERE $where AND is_active = 0");
  }

  /**
   * Compose a SQL WHERE clause for the cache.
   *
   * @param string $group
   * @param string|NULL $path
   *   Filter by path. If NULL, then return all paths.
   * @param int $source
   *   Source of the cache group.
   *
   * @return string
   */
  protected static function whereCache($group, $path = NULL, $source = NULL) {
    $clauses = array();

    $clauses[] = 'group_name = "' . CRM_Core_DAO::escapeString($group) . '"';

    if ($path) {
      $clauses[] = ('path = "' . CRM_Core_DAO::escapeString($path) . '"');
    }

    if ($source) {
      $clauses[] = 'source = ' . CRM_Core_DAO::escapeString($source);
    }

    return $clauses ? implode(' AND ', $clauses) : '(1)';
  }

  /**
   * Deletes all active cache of Pivot Report entities data.
   *
   * @param string $group
   */
  public static function deleteActiveCache($group = NULL) {
    $table = self::getTableName();
    $where = 'group_name <> "admin" AND is_active = 1';

    if ($group) {
      $where .= ' AND group_name = "' . CRM_Core_DAO::escapeString($group) . '"';
    }

    CRM_Core_DAO::executeQuery("DELETE FROM $table WHERE $where");
  }

  /**
   * Sets 'is_active' values to 1 for all cache rows.
   *
   * @param \CRM_PivotCache_AbstractGroup $group
   */
  public static function activateCache($group) {
    $table = self::getTableName();
    $where = 'group_name = "' . CRM_Core_DAO::escapeString($group->getName()) . '" AND source = ' . CRM_Core_DAO::escapeString($group->getSource());

    CRM_Core_DAO::executeQuery("UPDATE $table SET is_active = 1 WHERE $where");
  }

  /**
   * Gets is_locked cache value.
   *
   * @return bool
   */
  public static function isLocked() {
    return self::getItem('admin', 'is_locked');
  }

  /**
   * Updates is_locked cache value with given value.
   *
   * @param bool $value
   */
  public static function setIsLocked($value) {
    self::setItem($value, 'admin', 'is_locked');
  }

  /**
   * Gets chunk_status cache values.
   *
   * @return array|NULL
   */
  public static function getChunkStatus() {
    return self::getItem('admin', 'chunk_status');
  }

  /**
   * Sets chunk_status cache values.
   *
   * @param array $values
   */
  public static function setChunkStatus(array $values) {
    self::setItem($values, 'admin', 'chunk_status');
  }

  /**
   * Gets build_datetime cache value.
   *
   * @return string
   */
  public static function getBuildDatetime() {
    return self::getItem('admin', 'build_datetime');
  }

  /**
   * Updates build_datetime cache value with current date.
   */
  public static function updateBuildDatetime() {
    $date = date('Y-m-d H:i:s');
    self::setItem($date, 'admin', 'build_datetime');
  }
}
