<?php

class CRM_PivotReport_Entity {

  /**
   * Entities which may be supported by the extension.
   *
   * @var array
   */
  private static $entities = array(
    'Activity' => TRUE,
    'Case' => TRUE,
    'Contribution' => TRUE,
    'Membership' => TRUE,
    'Prospect' => 'uk.co.compucorp.civicrm.prospect',
  );

  /**
   * Entities supported by the extension.
   *
   * @var array
   */
  private static $supportedEntities = array();

  /**
   * Entity name.
   *
   * @var string
   */
  private $entityName = NULL;

  /**
   * Creates an instance of the class related to specified Entity name.
   *
   * @param string $entityName
   *
   * @throws Exception
   */
  public function __construct($entityName) {
    $this->entityName = $entityName;

    if (!$this->isSupported()) {
      throw new Exception("Entity '{$entityName}' is not supported by Pivot Report extension.");
    }
  }

  /**
   * Returns TRUE if entityName property value is one of supported Entities.
   * Otherwise returns FALSE.
   *
   * @return bool
   */
  private function isSupported() {
    return in_array($this->entityName, self::getSupportedEntities());
  }

  /**
   * Returns supportedEntities property value.
   *
   * @return array
   */
  public static function getSupportedEntities() {
    if (empty(self::$supportedEntities)) {
      foreach (self::$entities as $key => $value) {
        if ($value === TRUE) {
          self::$supportedEntities[] = $key;
        } else {
          $isEnabled = CRM_Core_DAO::getFieldValue(
            'CRM_Core_DAO_Extension',
            $value,
            'is_active',
            'full_name'
          );

          if ($isEnabled) {
            self::$supportedEntities[] = $key;
          }
        }
      }
    }

    return self::$supportedEntities;
  }

  /**
   * Returns an instance of CRM_PivotData_AbstractData for entityName property
   * value.
   *
   * @return \CRM_PivotData_AbstractData
   * @throws Exception
   */
  public function getDataInstance() {
    $className = 'CRM_PivotData_Data' . $this->entityName;

    if (!class_exists($className)) {
      throw new Exception("Class '{$className}' does not exist. It should exist and extend CRM_PivotData_AbstractData class.");
    }

    return new $className();
  }

  /**
   * Returns an instance of CRM_PivotCache_AbstractGroup for entityName property
   * value.
   *
   * @return \CRM_PivotCache_AbstractGroup
   * @throws Exception
   */
  public function getGroupInstance() {
    $className = 'CRM_PivotCache_Group' . $this->entityName;
    if (!class_exists($className)) {
      throw new Exception("Class '{$className}' does not exist. It should exist and extend CRM_PivotCache_AbstractGroup class.");
    }

    return new $className();
  }
}
