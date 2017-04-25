<?php

/**
 * Provide static methods to retrieve and format an Activity data.
 */
class CRM_Activityreport_Data {
  const ROWS_TO_RETURN = 1000;
  private static $fields = array();
  private static $emptyRow = array();
  private static $multiValues = array();
  private static $formattedValues = array();
  private static $customizedValues = array();

  /**
   * Return an array containing formatted Activity data.
   *
   * @param int $offset
   *   Offset for API call
   * @param int $limit
   *   Limit for API call
   * @param int $multiValuesOffset
   *   Multivalues offset
   * @param string $startDate
   *   "Date from" value to filter Activities by their date
   * @param string $endDate
   *   "Date to" value to filter Activities by their date
   *
   * @return array
   */
  public static function get($offset = 0, $limit = 0, $multiValuesOffset = 0, $startDate = null, $endDate = null) {
    self::$fields = self::getActivityFields();
    self::$emptyRow = self::getEmptyRow();
    self::$multiValues = array();

    $params = array(
      'sequential' => 1,
      'is_current_revision' => 1,
      'is_deleted' => 0,
      'is_test' => 0,
      'return' => implode(',', array_keys(self::$fields)),
      'options' => array(
        'sort' => 'activity_date_time DESC',
        'offset' => $offset,
        'limit' => $limit,
      ),
    );

    $activityDateFilter = self::getAPIDateFilter($startDate, $endDate);
    if (!empty($activityDateFilter)) {
      $params['activity_date_time'] = $activityDateFilter;
    }

    $activities = civicrm_api3('Activity', 'get', $params);

    $formattedActivities = self::formatResult($activities['values']);
    $result = self::splitMultiValues($formattedActivities, $offset, $multiValuesOffset);

    return $result;
  }

  /**
   * Return an array containing API date filter conditions basing on specified
   * dates.
   *
   * @param string $startDate
   * @param string $endDate
   *
   * @return array|NULL
   */
  private static function getAPIDateFilter($startDate, $endDate) {
    $apiFilter = null;

    if (!empty($startDate) && !empty($endDate)) {
      $apiFilter = array('BETWEEN' => array($startDate, $endDate));
    }
    else if (!empty($startDate) && empty($endDate)) {
      $apiFilter = array('>=' => $startDate);
    }
    else if (empty($startDate) && !empty($endDate)) {
      $apiFilter = array('<=' => $endDate);
    }

    return $apiFilter;
  }

  /**
   * Return an array containing $data rows and each row containing multiple values
   * of at least one field is populated into separate row for each field's
   * multiple value.
   *
   * @param array $data
   *   Array containing a set of Activities
   * @param int $totalOffset
   *   Activity absolute offset we start with
   * @param int $multiValuesOffset
   *   Multi Values offset
   *
   * @return array
   */
  private static function splitMultiValues(array $data, $totalOffset, $multiValuesOffset) {
    $result = array();
    $i = 0;

    foreach ($data as $key => $row) {
      $multiValuesRows = array();
      if (!empty(self::$multiValues[$key])) {
        $multiValuesFields = array_combine(self::$multiValues[$key], array_fill(0, count(self::$multiValues[$key]), 0));

        $multiValuesRows = self::populateMultiValuesRow($row, $multiValuesFields, $multiValuesOffset, self::ROWS_TO_RETURN - $i);

        $result = array_merge($result, $multiValuesRows['data']);
        $multiValuesOffset = 0;
      } else {
        $result[] = $row;
      }
      $i = count($result);

      if ($i === self::ROWS_TO_RETURN) {
        break;
      }
      $totalOffset++;
    }

    $header = self::getHeader();
    $output = array(array_keys($header));
    foreach ($result as $row) {
      $output[] = array_values($row);
    }

    return array(
      array(
        'info' => array(
          'nextOffset' => !empty($multiValuesRows['info']['multiValuesOffset']) ? $totalOffset : $totalOffset + 1,
          'multiValuesOffset' => !empty($multiValuesRows['info']['multiValuesOffset']) ? $multiValuesRows['info']['multiValuesOffset'] : 0,
          'multiValuesTotal' => !empty($multiValuesRows['info']['multiValuesTotal']) ? $multiValuesRows['info']['multiValuesTotal'] : 0,
        ),
        'data' => $output,
      ),
    );
  }

  /**
   * Prepare an array containing data header with fields labels.
   *
   * @return array
   */
  private static function getHeader() {
    $header = array_merge(self::$emptyRow, array(
      'Activity Date' => null,
      'Activity Start Date Months' => null,
      'Activity Expire Date' => null,
    ));

    ksort($header);

    return $header;
  }

  /**
   * Return an array containing set of rows which are built basing on given $row
   * and $fields array with indexes of multi values of the $row.
   *
   * @param array $row
   *   A single Activity row
   * @param array $fields
   *   Array containing Activity multi value fields as keys and integer
   *   indexes as values
   * @param int $offset
   *   Combination offset to start from
   * @param int $limit
   *   How many records can we generate?
   *
   * @return array
   */
  private static function populateMultiValuesRow(array $row, array $fields, $offset, $limit) {
    $data = array();
    $info = array(
      'multiValuesTotal' => self::getTotalCombinations($row, $fields),
      'multiValuesOffset' => 0,
    );
    $found = true;
    $i = 0;

    while ($found) {
      if ($i >= $offset) {
        $rowResult = array();
        foreach ($fields as $key => $index) {
          $rowResult[$key] = $row[$key][$index];
        }
        $data[] = array_merge($row, $rowResult);
      }
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
      $i++;
      if (($i - $offset === $limit) && $found) {
        $info['multiValuesOffset'] = $i;
        break;
      }
    }

    return array(
      'info' => $info,
      'data' => $data,
    );
  }

  /**
   * Get number of multivalues combinations for given Activity row.
   *
   * @param array $row
   *   Activity row
   * @param array $fields
   *   Array containing all Activity fields
   *
   * @return int
   */
  private static function getTotalCombinations(array $row, array $fields) {
    $combinations = 1;

    foreach ($fields as $key => $value) {
      if (!empty($row[$key]) && is_array($row[$key])) {
        $combinations *= count($row[$key]);
      }
    }

    return $combinations;
  }

  /**
   * Return a result of recursively parsed and formatted $data.
   *
   * @param mixed $data
   *   Data element
   * @param string $dataKey
   *   Key of current $data item
   * @param int $level
   *   How deep we are relative to the root of our data
   *
   * @return type
   */
  private static function formatResult($data, $dataKey = null, $level = 0) {
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
        $dataKey = $key;
        if (!empty(self::$fields[$key]['title'])) {
          $key = self::$fields[$key]['title'];
        }
        $result[$key] = self::formatResult($value, $dataKey, $level + 1);
        if ($level === 1 && is_array($result[$key])) {
          self::$multiValues[$baseKey][] = $key;
        }
      }
      if ($level === 1) {
          $result = array_merge($result, array(
            'Activity Date' => null,
            'Activity Start Date Months' => null,
            'Activity Expire Date' => null,
          ));
          ksort($result);
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
   * @param string $key
   *   Field name
   * @param string $value
   *   Field value
   * @param int $level
   *   Recursion level
   *
   * @return string
   */
  private static function formatValue($key, $value, $level = 0) {
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
    if (!empty(self::$formattedValues[$key][$value])) {
      return self::$formattedValues[$key][$value];
    }
    if (!empty(self::$fields[$key]['customField'])) {
      switch (self::$fields[$key]['customField']['data_type']) {
        case 'File':
          $result = CRM_Utils_System::formatWikiURL($value['fileURL'] . ' ' . $value['fileName']);
          self::$formattedValues[$key][$value] = $result;
          return $result;
        break;
        // For few field types we can use 'formatCustomValues()' core method.
        case 'Date':
        case 'Boolean':
        case 'Link':
        case 'StateProvince':
        case 'Country':
          $data = array('data' => $value);
          CRM_Utils_System::url();
          $result = CRM_Core_BAO_CustomGroup::formatCustomValues($data, self::$fields[$key]['customField']);
          self::$formattedValues[$key][$value] = $result;
          return $result;
        break;
        // Anyway, 'formatCustomValues()' core method doesn't handle some types
        // such as 'CheckBox' (looks like they aren't implemented there) so
        // we deal with them automatically by custom handling of 'optionValues' array.
      }
    }

    if (!empty(self::$fields[$key]['optionValues'])) {
      $result = self::$fields[$key]['optionValues'][$value];
      self::$formattedValues[$key][$value] = $result;
      return $result;
    }
    $result = strip_tags(self::customizeValue($key, $value));
    self::$formattedValues[$key][$value] = $result;
    return $result;
  }

  /**
   * Additional function for customizing Activity value by its key
   * (if it's needed). For example: we want to return Campaign's title
   * instead of ID.
   *
   * @param string $key
   *   Field key
   * @param string $value
   *   Field value
   *
   * @return string
   */
  private static function customizeValue($key, $value) {
    if (!empty(self::$customizedValues[$key][$value])) {
      return self::$customizedValues[$key][$value];
    }
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
    self::$customizedValues[$key][$value] = $result;
    return $result;
  }

  private static function getEmptyRow() {
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
  private static function getActivityFields() {
    $unsetFields = array(
      'is_current_revision',
      'activity_is_deleted',
      'weight',
      'source_contact_id',
      'phone_id',
      'relationship_id',
      'source_record_id',
      'activity_is_test',
      'is_test',
      'parent_id',
      'original_id',
      'activity_details',
    );
    // Get standard Fields of Activity entity.
    $fields = CRM_Activity_DAO_Activity::fields();

    foreach ($unsetFields as $unsetField) {
      unset($fields[$unsetField]);
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
      'WHERE g.extends = \'Activity\' AND g.is_active = 1 AND f.is_active = 1 AND f.html_type NOT IN (\'TextArea\', \'RichTextEditor\') AND (f.data_type <> \'String\' OR (f.data_type = \'String\' AND f.html_type <> \'Text\')) '
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
   *   Field key
   *
   * @return array
   */
  private static function getOptionValues($field) {
    if (empty($field['pseudoconstant']['optionGroupName'])) {
      return null;
    }
    $result = civicrm_api3('Activity', 'getoptions', array(
      'field' => $field['name'],
    ));
    return $result['values'];
  }
}
