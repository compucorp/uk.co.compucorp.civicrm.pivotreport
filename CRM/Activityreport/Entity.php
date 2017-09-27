<?php

class CRM_Activityreport_Entity {

  /**
   * Entities supported by the extension.
   *
   * @var array
   */
  private static $supportedEntities = array(
    'Activity',
    'Contribution',
    'Membership',
  );

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
    return in_array($this->entityName, self::$supportedEntities);
  }

  /**
   * Returns supportedEntities property value.
   *
   * @return array
   */
  public static function getSupportedEntities() {
    return self::$supportedEntities;
  }

  /**
   * Returns an instance of CRM_PivotReport_AbstractData for entityName property
   * value.
   *
   * @return \CRM_PivotReport_AbstractData
   * @throws Exception
   */
  public function getDataInstance() {
    $className = 'CRM_PivotReport_Data' . $this->entityName;
    if (!class_exists($className)) {
      throw new Exception("Class '{$className}' does not exist. It should exist and extend CRM_PivotReport_AbstractData class.");
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
