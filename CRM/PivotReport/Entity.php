<?php

class CRM_PivotReport_Entity {

  /**
   * Entities which may be supported by the extension.
   *
   * @var array
   */
  private static $entities = array(
    'Activity' => array(),
    'Case' => array(),
    'Contribution' => array(
      'components' => array(
        'CiviContribute',
      ),
    ),
    'Membership' => array(),
    'Prospect' => array(
      'extensions' => array(
        'uk.co.compucorp.civicrm.prospect',
      ),
      'entities' => array(
        'Contribution',
        'Pledge',
      ),
    ),
  );

  /**
   * Entities supported by the extension.
   *
   * @var array
   */
  private static $supportedEntities = array();

  /**
   * List of Components enabled in CiviCRM.
   *
   * @var array
   */
  private static $enabledComponents = NULL;

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
   * @param bool $checkSupport
   *
   * @throws Exception
   */
  public function __construct($entityName, $checkSupport = TRUE) {
    $this->entityName = $entityName;

    if ($checkSupport && !$this->isSupported()) {
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
        // Check all required components.
        if (!empty($value['components']) && !self::checkComponents($value['components'])) {
          continue;
        }

        // Check all required extensions.
        if (!empty($value['extensions']) && !self::checkExtensions($value['extensions'])) {
          continue;
        }

        // Check all required entities.
        if (!empty($value['entities']) && !self::checkApiEntities($value['entities'])) {
          continue;
        }

        // Check main Pivot Report entity itself.
        $entity = new self($key, FALSE);
        $dataInstance = $entity->getDataInstance();
        $apiEntityName = $dataInstance->getApiEntityName();
        if (!self::checkApiEntities(array($apiEntityName))) {
          continue;
        }

        self::$supportedEntities[] = $key;
      }
    }

    return self::$supportedEntities;
  }

  /**
   * Returns TRUE if all given components are present and enabled.
   * Otherwise returns FALSE.
   *
   * @param array $components
   *
   * @return boolean
   */
  private static function checkComponents(array $components) {
    $enabledComponents = self::getEnabledComponents();

    foreach ($components as $component) {
      if (!in_array($component, $enabledComponents)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Returns an array containing enabled CiviCRM components.
   *
   * @return array|NULL
   */
  private static function getEnabledComponents() {
    if (self::$enabledComponents == NULL) {
      $settings = civicrm_api3('Setting', 'get', array(
        'sequential' => 1,
        'return' => array('enable_components'),
      ));

      if (!empty($settings['values'][0]['enable_components'])) {
        self::$enabledComponents = $settings['values'][0]['enable_components'];
      } else {
        self::$enabledComponents = array();
      }
    }

    return self::$enabledComponents;
  }

  /**
   * Returns TRUE if all given extensions are present and enabled.
   * Otherwise returns FALSE.
   *
   * @param array $extensions
   *
   * @return boolean
   */
  private static function checkExtensions(array $extensions) {
    foreach ($extensions as $extension) {
      $isEnabled = CRM_Core_DAO::getFieldValue(
        'CRM_Core_DAO_Extension',
        $extension,
        'is_active',
        'full_name'
      );

      if (!$isEnabled) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Returns TRUE if all given entities are accessible via API 'get' call.
   * Otherwise returns FALSE.
   *
   * @param array $entities
   *
   * @return boolean
   */
  private static function checkApiEntities(array $entities) {
    foreach ($entities as $entity) {
      $result = TRUE;

      try {
        $response = civicrm_api3($entity, 'get');

        if (!empty($response['is_error']) && (int) $response['is_error']) {
          $result = FALSE;
        }
      } catch (Exception $e) {
        $result = FALSE;
      }

      if (!$result) {
        return FALSE;
      }
    }

    return TRUE;
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
   * @param int $source
   *
   * @return \CRM_PivotCache_AbstractGroup
   * @throws Exception
   */
  public function getGroupInstance($source = NULL) {
    $className = 'CRM_PivotCache_Group' . $this->entityName;
    if (!class_exists($className)) {
      throw new Exception("Class '{$className}' does not exist. It should exist and extend CRM_PivotCache_AbstractGroup class.");
    }

    return new $className(NULL, $source);
  }
}
