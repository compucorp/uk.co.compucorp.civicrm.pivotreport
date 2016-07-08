<?php

/**
 * Provide static methods to retrieve and format an Activity data.
 */
class CRM_Activityreport_Data {
  protected static $fields = array();
  protected static $emptyRow = array();
  protected static $multiValues = array();

  /**
   * Return an array containing formatted Activity data.
   * 
   * @return array
   */
  public static function get() {
    self::$fields = self::getActivityFields();
    self::$emptyRow = self::getEmptyRow();
    self::$multiValues = array();

    $activities = civicrm_api3('Activity', 'get', array(
      'sequential' => 1,
      'is_current_revision' => 1,
      'is_deleted' => 0,
      'api.ActivityContact.get' => array(),
      'return' => implode(',', array_keys(self::$fields)),
      'options' => array('sort' => 'id ASC', 'limit' => 0),
    ));

    return self::splitMultiValues(self::formatResult($activities['values']));
  }

  /**
   * Return an array containing $data rows and each row containing multiple values
   * of at least one field is populated into separate row for each field's
   * multiple value.
   * 
   * @param array   $data       array containing a set of Activities
   * 
   * @return array
   */
  protected static function splitMultiValues(array $data) {
    $result = array();

    foreach ($data as $key => $row) {
      if (!empty(self::$multiValues[$key])) {
        $multiValuesFields = array_combine(self::$multiValues[$key], array_fill(0, count(self::$multiValues[$key]), 0));
        $result = array_merge($result, self::populateMultiValuesRow($row, $multiValuesFields));
      } else {
        $result[] = $row;
      }
    }

    return $result;
  }

  /**
   * Return an array containing set of rows which are built basing on given $row
   * and $fields array with indexes of multi values of the $row.
   * 
   * @param array   $row        a single Activity row
   * @param array   $fields     array containing Activity multi value fields
   *                            as keys and integer indexes as values
   * 
   * @return array
   */
  protected static function populateMultiValuesRow(array $row, array $fields) {
    $result = array();
    $found = true;

    while ($found) {
      $rowResult = array();
      foreach ($fields as $key => $index) {
        $rowResult[$key] = $row[$key][$index];
      }
      $result[] = array_merge($row, $rowResult);
      foreach ($fields as $key => $index) {
        $found = false;
        if ($index + 1 === count($row[$key])) {
          $fields[$key] = 0;
          continue;
        }
        $fields[$key]++;
        $found = true;
        break;
      }
    }

    return $result;
  }

  /**
   * Return a result of recursively parsed and formatted $data.
   * 
   * @param mixed   $data       data element
   * @param string  $dataKey    key of current $data item
   * @param int     $level      how deep we are relative to the root of our data
   * 
   * @return type
   */
  protected static function formatResult($data, $dataKey = null, $level = 0) {
    $result = array();

    if ($level < 2) {
      if ($level === 1) {
        $result = self::$emptyRow;
      }
      $baseKey = $dataKey;
      foreach ($data as $key => $value) {
        if (empty(self::$fields[$key]) && $level) {
          continue;
        }
        if ($level === 0 && empty($value['api.ActivityContact.get']['values'])) {
            continue;
        }
        $dataKey = $key;
        if (!empty(self::$fields[$key]['title'])) {
          $key = self::$fields[$key]['title'];
        }
        $result[$key] = self::formatResult($value, $dataKey, $level + 1);
        if ($level === 1 && is_array($result[$key])) {
          self::$multiValues[$baseKey][] = $key;
        }
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
   * If $value contains an array of values then the method works recursively
   * returning an array of formatted values.
   * 
   * @param string $key     field name
   * @param string $value   field value
   * @param int $level      recursion level
   * 
   * @return string
   */
  protected static function formatValue($key, $value, $level = 0) {
    if (empty($value) || $level > 1) {
      return '';
    }
    $dataType = !empty(self::$fields[$key]['customField']['data_type']) ? self::$fields[$key]['customField']['data_type'] : null;
    if (is_array($value) && $dataType !== 'File') {
      $valueArray = array();
      foreach ($value as $valueKey => $valueItem) {
        $valueArray[] = self::formatValue($key, $valueKey, $level + 1);
      }
      return $valueArray;
    }
    if (!empty(self::$fields[$key]['customField'])) {
      switch (self::$fields[$key]['customField']['data_type']) {
        case 'File':
          return CRM_Utils_System::formatWikiURL($value['fileURL'] . ' ' . $value['fileName']);
        break;
        // For few field types we can use 'formatCustomValues()' core method.
        case 'Date':
        case 'Boolean':
        case 'Link':
        case 'StateProvince':
        case 'Country':
          $data = array('data' => $value);
          CRM_Utils_System::url();
          return CRM_Core_BAO_CustomGroup::formatCustomValues($data, self::$fields[$key]['customField']);
        break;
        // Anyway, 'formatCustomValues()' core method doesn't handle some types
        // such as 'CheckBox' (looks like they aren't implemented there) so
        // we deal with them automatically by custom handling of 'optionValues' array.
      }
    }

    if (!empty(self::$fields[$key]['optionValues'])) {
      return self::$fields[$key]['optionValues'][$value];
    }
    return strip_tags(self::customizeValue($key, $value));
  }

  /**
   * Additional function for customizing Activity value by its key
   * (if it's needed). For example: we want to return Campaign's title
   * instead of ID.
   * 
   * @param string $key
   * @param string $value
   * @return string
   */
  protected static function customizeValue($key, $value) {
    $result = $value;
    switch ($key) {
      case 'campaign_id':
        if (!empty($value)) {
          $campaign = civicrm_api3('Campaign', 'getsingle', array(
            'sequential' => 1,
            'return' => "title",
            'id' => $value,
          ));
          if ($campaign['is_error']) {
            $result = '';
          } else {
            $result = $campaign['title'];
          }
        }
      break;
    }
    return $result;
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
   * Return an array containing all Fields and Custom Fields of Activity entity,
   * keyed by their API keys and extended with available fields Option Values.
   * 
   * @return array
   */
  protected static function getActivityFields() {
    // Get standard Fields of Activity entity.
    $fields = CRM_Activity_DAO_Activity::fields();
    unset($fields['is_current_revision']);
    unset($fields['activity_is_deleted']);
    if (!empty($fields['source_record_id'])) {
        $fields['source_record_id']['title'] = t('Source Record ID');
    }
    if (!empty($fields['activity_type_id'])) {
        $fields['activity_type_id']['title'] = t('Activity Type');
    }
    if (!empty($fields['activity_date_time'])) {
        $fields['activity_date_time']['title'] = t('Activity Date Time');
    }
    $keys = CRM_Activity_DAO_Activity::fieldKeys();
    $result = array();

    // Now get Custom Fields of Activity entity.
    $customFieldsResult = CRM_Core_DAO::executeQuery(
      'SELECT g.id AS group_id, f.id AS id, f.label AS label, f.data_type AS data_type, ' .
      'f.html_type AS html_type, f.date_format AS date_format, og.name AS option_group_name ' .
      'FROM `civicrm_custom_group` g ' .
      'LEFT JOIN `civicrm_custom_field` f ON f.custom_group_id = g.id ' .
      'LEFT JOIN `civicrm_option_group` og ON og.id = f.option_group_id ' .
      'WHERE g.extends = \'Activity\' AND g.is_active = 1 AND f.is_active = 1'
    );
    while ($customFieldsResult->fetch()) {
      $customField = new CRM_Core_BAO_CustomField();
      $customField->id = $customFieldsResult->id;
      $customField->find(true);
      $fields['custom_' . $customFieldsResult->id] = array(
        'name' => 'custom_' . $customFieldsResult->id,
        'title' => $customFieldsResult->label,
        'pseudoconstant' => array(
          'optionGroupName' => $customFieldsResult->option_group_name,
        ),
        'customField' => (array)$customField,
      );
    }

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
