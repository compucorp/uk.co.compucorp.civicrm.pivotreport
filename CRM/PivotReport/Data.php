<?php

/**
 * Provides a functionality to prepare entity data for Pivot Table.
 */
class CRM_PivotReport_Data extends CRM_PivotReport_AbstractData {

  public function __construct($name = NULL) {
    $this->name = 'Activity';
  }

  /**
   * Rebuilds pivot report cache including header and data.
   *
   * @param array $params
   *
   * @return array
   */
  public function rebuildCache(array $params) {
    $this->fields = $this->getFields();
    $this->emptyRow = $this->getEmptyRow();
    $this->multiValues = array();

    $time = microtime(true);

    $cacheGroup = new CRM_PivotCache_Group($this->name);

    $cacheGroup->clear();

    $count = $this->rebuildData($cacheGroup, $this->name, $params);

    $this->rebuildHeader($cacheGroup, array_merge($this->emptyRow, array(
      'Activity Date' => null,
      'Activity Start Date Months' => null,
      'Activity Expire Date' => null,
    )));

    return array(
      array(
        'rows' => $count,
        'time' => (microtime(true) - $time),
      )
    );
  }

  /**
   * Returns an array containing API parameters for Activity 'get' call.
   *
   * @param array $inputParams
   *
   * @return array
   */
  protected function getEntityApiParams(array $inputParams) {
    $params = array(
      'sequential' => 1,
      'is_current_revision' => 1,
      'is_deleted' => 0,
      'is_test' => 0,
      'return' => implode(',', array_keys($this->fields)),
      'options' => array(
        'sort' => 'activity_date_time ASC',
        'limit' => self::ROWS_API_LIMIT,
      ),
    );

    $startDate = !empty($inputParams['start_date']) ? $inputParams['start_date'] : NULL;
    $endDate = !empty($inputParams['end_date']) ? $inputParams['end_date'] : NULL;

    $activityDateFilter = $this->getAPIDateFilter($startDate, $endDate);
    if (!empty($activityDateFilter)) {
      $params['activity_date_time'] = $activityDateFilter;
    }

    return $params;
  }

  /**
   * Returns an array containing API date filter conditions basing on specified
   * dates.
   *
   * @param string $startDate
   * @param string $endDate
   *
   * @return array|NULL
   */
  private function getAPIDateFilter($startDate, $endDate) {
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
   * Returns an array containing $data rows and each row containing multiple values
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
  protected function splitMultiValues(array $data, $totalOffset, $multiValuesOffset) {
    $result = array();
    $date = NULL;
    $i = 0;

    foreach ($data as $key => $row) {
      $activityDate = substr($row['Activity Date Time'], 0, 10);

      if (!$date) {
        $date = $activityDate;
      }

      if ($date !== $activityDate) {
        $totalOffset--;
        break;
      }

      $multiValuesRows = null;
      if (!empty($this->multiValues[$key])) {
        $multiValuesFields = array_combine($this->multiValues[$key], array_fill(0, count($this->multiValues[$key]), 0));

        $multiValuesRows = $this->populateMultiValuesRow($row, $multiValuesFields, $multiValuesOffset, self::ROWS_MULTIVALUES_LIMIT - $i);

        $result = array_merge($result, $multiValuesRows['data']);
        $multiValuesOffset = 0;
      } else {
        $result[] = array_values($row);
      }
      $i = count($result);

      if ($i === self::ROWS_MULTIVALUES_LIMIT) {
        break;
      }

      unset($this->multiValues[$key]);

      $totalOffset++;
    }

    return array(
      'info' => array(
        'index' => $date,
        'nextOffset' => !empty($multiValuesRows['info']['multiValuesOffset']) ? $totalOffset : $totalOffset + 1,
        'multiValuesOffset' => !empty($multiValuesRows['info']['multiValuesOffset']) ? $multiValuesRows['info']['multiValuesOffset'] : 0,
        'multiValuesTotal' => !empty($multiValuesRows['info']['multiValuesTotal']) ? $multiValuesRows['info']['multiValuesTotal'] : 0,
      ),
      'data' => $result,
    );
  }

  /**
   * Returns a result of recursively parsed and formatted $data.
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
  protected function formatResult($data, $dataKey = null, $level = 0) {
    $result = array();

    if ($level < 2) {
      if ($level === 1) {
        $result = $this->emptyRow;
      }
      $baseKey = $dataKey;
      foreach ($data as $key => $value) {
        if (empty($this->fields[$key]) && $level) {
          continue;
        }
        $dataKey = $key;
        if (!empty($this->fields[$key]['title'])) {
          $key = $this->fields[$key]['title'];
        }
        $result[$key] = $this->formatResult($value, $dataKey, $level + 1);
        if ($level === 1 && is_array($result[$key])) {
          $this->multiValues[$baseKey][] = $key;
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
      return $this->formatValue($dataKey, $data);
    }

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
  protected function customizeValue($key, $value) {
    if (!empty($this->customizedValues[$key][$value])) {
      return $this->customizedValues[$key][$value];
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

    $this->customizedValues[$key][$value] = $result;

    return $result;
  }

  /**
   * Returns an array containing all Fields and Custom Fields of Activity entity,
   * keyed by their API keys and extended with available fields Option Values.
   *
   * @return array
   */
  protected function getFields() {
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
        $fields['activity_type_id']['title'] = ts('Activity Type');
    }
    if (!empty($fields['activity_date_time'])) {
        $fields['activity_date_time']['title'] = ts('Activity Date Time');
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
      $result[$key]['optionValues'] = $this->getOptionValues($value);
    }

    return $result;
  }

  /**
   * Returns available Option Values of specified $field array.
   * If there is no available Option Values for the field, then return null.
   *
   * @param array $field
   *   Field key
   *
   * @return array
   */
  private function getOptionValues($field) {
    if (empty($field['pseudoconstant']['optionGroupName'])) {
      return null;
    }

    $result = civicrm_api3('Activity', 'getoptions', array(
      'field' => $field['name'],
    ));

    return $result['values'];
  }

  /**
   * Gets total number of Activities.
   *
   * @param array $params
   *
   * @return int
   */
  protected function getCount(array $params) {
    $apiParams = [
      'is_current_revision' => 1,
      'is_deleted' => 0,
      'is_test' => 0,
    ];

    $startDate = !empty($params['start_date']) ? $params['start_date'] : NULL;
    $endDate = !empty($params['end_date']) ? $params['end_date'] : NULL;

    $activityDateFilter = $this->getAPIDateFilter($startDate, $endDate);

    if (!empty($activityDateFilter)) {
      $apiParams['activity_date_time'] = $activityDateFilter;
    }

    return civicrm_api3('Activity', 'getcount', $apiParams);
  }
}
