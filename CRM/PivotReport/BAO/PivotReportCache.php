<?php
use CRM_PivotReport_ExtensionUtil as E;

class CRM_PivotReport_BAO_PivotReportCache extends CRM_PivotReport_DAO_PivotReportCache {

  /**
   * @var array ($cacheKey => $cacheValue)
   */
  static $_cache = NULL;

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
   */
  public static function setItem(&$data, $group, $path) {
    if (self::$_cache === NULL) {
      self::$_cache = array();
    }

    $lock = Civi::lockManager()->acquire("cache.{$group}_{$path}");
    if (!$lock->isAcquired()) {
      CRM_Core_Error::fatal();
    }

    $table = self::getTableName();
    $where = self::whereCache($group, $path);
    $dataExists = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM $table WHERE {$where}");
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
   */
  public static function deleteGroup($group = NULL, $path = NULL) {
    $table = self::getTableName();
    $where = self::whereCache($group, $path);
    CRM_Core_DAO::executeQuery("DELETE FROM $table WHERE $where");
  }

  /**
   * Compose a SQL WHERE clause for the cache.
   *
   * @param string $group
   * @param string|NULL $path
   *   Filter by path. If NULL, then return all paths.
   *
   * @return string
   */
  protected static function whereCache($group, $path) {
    $clauses = array();
    $clauses[] = ('group_name = "' . CRM_Core_DAO::escapeString($group) . '"');

    if ($path) {
      $clauses[] = ('path = "' . CRM_Core_DAO::escapeString($path) . '"');
    }

    return $clauses ? implode(' AND ', $clauses) : '(1)';
  }

}
