<?php

/**
 * Provides a functionality to prepare Activity entity data for Pivot Table.
 */
class CRM_PivotData_DataActivity extends CRM_PivotData_AbstractData {
  /**
   * @inheritdoc
   */
  const ROWS_API_LIMIT = 500;

  /**
   * @inheritdoc
   */
  const ROWS_PAGINATED_LIMIT = 1000;

  /**
   * @inheritdoc
   */
  const ROWS_MULTIVALUES_LIMIT = 500;

  /**
   * @inheritdoc
   */
  const ROWS_RETURN_LIMIT = 1000;

  /**
   * CRM_PivotData_DataActivity constructor.
   */
  public function __construct() {
    parent::__construct('Activity');

    $this->additionalHeaderFields['Activity Date'] = null;
    $this->additionalHeaderFields['Activity Expire Date'] = null;
  }

  /**
   * @inheritdoc
   */
  protected function getEntityApiParams(array $inputParams) {
    $params = array(
      'sequential' => 1,
      'is_current_revision' => 1,
      'is_deleted' => 0,
      'is_test' => 0,
      'return' => implode(',', array_keys($this->getFields())),
      'options' => array(
        'sort' => 'activity_date_time ASC, id ASC',
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
   * @inheritdoc
   */
  protected function setCustomValue($key, $value) {
    $result = $value;

    switch ($key) {
      case 'campaign_id':
        if (!empty($value)) {
          $campaign = civicrm_api3('Campaign', 'getsingle', array(
            'sequential' => 1,
            'return' => 'title',
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
  }

  /**
   * @inheritdoc
   */
  protected function getEntityIndex(array $row) {
    return substr($row['Activity Date Time'], 0, 10);
  }

  /**
   * @inheritdoc
   */
  protected function getFields() {
    if (empty($this->fields)) {
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
        'SELECT g.id AS group_id, f.id AS id, f.label AS label, f.data_type AS data_type, 
          f.html_type AS html_type, f.date_format AS date_format, og.name AS option_group_name 
        FROM `civicrm_custom_group` g 
        LEFT JOIN `civicrm_custom_field` f ON f.custom_group_id = g.id 
        LEFT JOIN `civicrm_option_group` og ON og.id = f.option_group_id 
        WHERE g.extends = \'Activity\' AND g.is_active = 1 AND f.is_active = 1 
        AND f.html_type NOT IN (\'TextArea\', \'RichTextEditor\') AND (f.data_type <> \'String\' OR (f.data_type = \'String\' AND f.html_type <> \'Text\'))
      ');

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

      $this->fields = $result;
    }

    return $this->fields;
  }

  /**
   * @inheritdoc
   */
  public function getCount(array $params = array()) {
    $apiParams = array(
      'is_current_revision' => 1,
      'is_deleted' => 0,
      'is_test' => 0,
    );

    $startDate = !empty($params['start_date']) ? $params['start_date'] : NULL;
    $endDate = !empty($params['end_date']) ? $params['end_date'] : NULL;

    $activityDateFilter = $this->getAPIDateFilter($startDate, $endDate);

    if (!empty($activityDateFilter)) {
      $apiParams['activity_date_time'] = $activityDateFilter;
    }

    return civicrm_api3('Activity', 'getcount', $apiParams);
  }

  /**
   * @inheritdoc
   */
  public function getDateFields() {
    $result = parent::getDateFields();

    $result[] = ts('Activity Date');
    $result[] = ts('Activity Expire Date');

    return $result;
  }

}
