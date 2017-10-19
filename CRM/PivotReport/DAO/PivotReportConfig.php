<?php

require_once 'CRM/Core/DAO.php';
require_once 'CRM/Utils/Type.php';

class CRM_PivotReport_DAO_PivotReportConfig extends CRM_Core_DAO {
  /**
   * static instance to hold the table name
   *
   * @var string
   */
  static $_tableName = 'civicrm_pivotreport_config';
  /**
   * static value to see if we should log any modifications to
   * this table in the civicrm_log table
   *
   * @var boolean
   */
  static $_log = true;

  /**
   * Unique ID
   *
   * @var int unsigned
   */
  public $id;

  /**
   * Entity name
   *
   * @var string
   */
  public $entity;

  /**
   * Configuration label
   *
   * @var string
   */
  public $label;

  /**
   * Configuration JSON data
   *
   * @var string
   */
  public $json_config;

  /**
   * class constructor
   *
   * @return civicrm_pivotreportconfig
   */
  function __construct() {
    $this->__table = 'civicrm_pivotreport_config';
    parent::__construct();
  }

  /**
   * Returns foreign keys and entity references
   *
   * @return array
   *   [CRM_Core_Reference_Interface]
   */
  static function getReferenceColumns() {
    if (!isset(Civi::$statics[__CLASS__]['links'])) {
      Civi::$statics[__CLASS__]['links'] = static ::createReferenceColumns(__CLASS__);
      CRM_Core_DAO_AllCoreTables::invoke(__CLASS__, 'links_callback', Civi::$statics[__CLASS__]['links']);
    }

    return Civi::$statics[__CLASS__]['links'];
  }

  /**
   * Returns all the column names of this table
   *
   * @return array
   */
  static function &fields() {
    if (!isset(Civi::$statics[__CLASS__]['fields'])) {
      Civi::$statics[__CLASS__]['fields'] = array(
        'id' => array(
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'description' => 'Unique ID',
          'required' => true,
        ),
        'entity' => array(
          'name' => 'entity',
          'type' => CRM_Utils_Type::T_STRING,
          'description' => 'Entity name',
          'required' => true,
        ),
        'label' => array(
          'name' => 'label',
          'type' => CRM_Utils_Type::T_STRING,
          'description' => 'Configuration label',
          'required' => true,
        ),
        'json_config' => array(
          'name' => 'json_config',
          'type' => CRM_Utils_Type::T_TEXT,
          'description' => 'Configuration JSON data',
          'required' => true,
        ),
      );

      CRM_Core_DAO_AllCoreTables::invoke(__CLASS__, 'fields_callback', Civi::$statics[__CLASS__]['fields']);
    }

    return Civi::$statics[__CLASS__]['fields'];
  }

  /**
   * Return a mapping from field-name to the corresponding key (as used in fields()).
   *
   * @return array
   *   Array(string $name => string $uniqueName).
   */
  static function &fieldKeys() {
    if (!isset(Civi::$statics[__CLASS__]['fieldKeys'])) {
      Civi::$statics[__CLASS__]['fieldKeys'] = array_flip(CRM_Utils_Array::collect('name', self::fields()));
    }

    return Civi::$statics[__CLASS__]['fieldKeys'];
  }

  /**
   * Returns the names of this table
   *
   * @return string
   */
  static function getTableName() {
    return self::$_tableName;
  }

  /**
   * Returns if this table needs to be logged
   *
   * @return boolean
   */
  function getLog() {
    return self::$_log;
  }

  /**
   * Returns the list of fields that can be imported
   *
   * @param bool $prefix
   *
   * @return array
   */
  static function &import($prefix = false) {
    $r = CRM_Core_DAO_AllCoreTables::getImports(__CLASS__, 'pivotreport_config', $prefix, array());
    return $r;
  }

  /**
   * Returns the list of fields that can be exported
   *
   * @param bool $prefix
   *
   * @return array
   */
  static function &export($prefix = false) {
    $r = CRM_Core_DAO_AllCoreTables::getExports(__CLASS__, 'pivotreport_config', $prefix, array());
    return $r;
  }
}
