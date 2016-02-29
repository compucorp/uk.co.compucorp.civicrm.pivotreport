<?php

/**
 * Provide static methods to retrieve and format an Activity data.
 */
class CRM_Activityreport_Data {
  protected static $fields = array();
  protected static $emptyRow = array();

  /**
   * Return an array containing formatted Activity data.
   * 
   * @return array
   */
  public static function get() {
    self::$fields = self::getActivityFields();
    self::$emptyRow = self::getEmptyRow();

    $activities = civicrm_api3('Activity', 'get', array(
      'sequential' => 1,
      'options' => array('sort' => 'id ASC', 'limit' => 0),
    ));

    return self::formatResult($activities['values']);
  }

  /**
   * Return a result of recursively parsed and formatted $data.
   * 
   * @param mixed   $data       data element
   * @param string  $dataKey    key of current $data item
   * @param bool    $root       says if we are at the top of the $data value
   * 
   * @return type
   */
  protected static function formatResult($data, $dataKey = null, $root = true) {
    $result = array();

    if (is_array($data)) {
      if (!$root) {
        $result = self::$emptyRow;
      }
      foreach ($data as $key => $value) {
        $dataKey = $key;
        if (!empty(self::$fields[$key]['title'])) {
          $key = self::$fields[$key]['title'];
        }
        $result[$key] = self::formatResult($value, $dataKey, false);
      }
    } else {
      return self::formatValue($dataKey, $data);
    }

    return $result;
  }

  /**
   * Return $value formatted by available Option Values for the $key Field.
   * If there is no Option Values for the field, then return $value itself
   * with HTML tags stripped.
   * 
   * @param string $key
   * @param string $value
   * 
   * @return string
   */
  protected static function formatValue($key, $value) {
    if (!empty(self::$fields[$key]['optionValues'])) {
      return self::$fields[$key]['optionValues'][$value];
    }
    return strip_tags($value);
  }

  protected static function getEmptyRow() {
    $result = array();

    foreach (self::$fields as $key => $value) {
      if (!empty($value['title'])) {
        $key = $value['title'];
      }
      $result[$key] = '';
    }

    return $result;
  }

  /**
   * Return an array containing all Fields of Activity entity,
   * keyed by their API keys and extended with available fields Option Values.
   * 
   * @return array
   */
  protected static function getActivityFields() {
    $fields = CRM_Activity_DAO_Activity::fields();
    $keys = CRM_Activity_DAO_Activity::fieldKeys();
    $result = array();

    foreach ($fields as $key => $value) {
      if (!empty($keys[$value['name']])) {
        $key = $value['name'];
      }
      $result[$key] = $value;
      $result[$key]['optionValues'] = self::getOptionValues($value);
    }

    return $result;
  }

  /**
   * Return available Option Values of specified $field array.
   * If there is no available Option Values for the field, then return null.
   * 
   * @param array $field
   * 
   * @return array
   */
  protected static function getOptionValues($field) {
    if (empty($field['pseudoconstant']['optionGroupName'])) {
      return null;
    }
    $result = civicrm_api3('Activity', 'getoptions', array(
      'field' => $field['name'],
    ));
    return $result['values'];
  }
}
