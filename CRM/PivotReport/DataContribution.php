<?php

/**
 * Provides a functionality to prepare Activity entity data for Pivot Table.
 */
class CRM_PivotReport_DataContribution extends CRM_PivotReport_AbstractData {

  /**
   * CRM_PivotReport_DataContribution constructor.
   */
  public function __construct() {
    parent::__construct('Contribution');
  }

  /**
   * @inheritdoc
   */
  protected function getEntityApiParams(array $inputParams) {
    $params = array(
      'sequential' => 1,
      'is_test' => 0,
      'return' => implode(',', array_keys($this->getFields())),
      'api.Contact.getsingle' => array(
        'id' => '$value.contact_id',
        'return' => array('display_name', 'sort_name', 'contact_type')
      ),
      'options' => array(
        'sort' => 'receive_date ASC',
        'limit' => self::ROWS_API_LIMIT,
      ),
    );

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
    return substr($row['Date Received'], 0, 10);
  }

  /**
   * @inheritdoc
   */
  protected function getFields() {
    if (empty($this->fields)) {
      $unsetFields = array(
      );
      // Get standard Fields of Activity entity.
      $fields = CRM_Contribute_DAO_Contribution::fields();

      foreach ($unsetFields as $unsetField) {
        unset($fields[$unsetField]);
      }

      $keys = CRM_Contribute_DAO_Contribution::fieldKeys();
      $result = array();

      // Now get Custom Fields for entity.
      $customFieldsResult = CRM_Core_DAO::executeQuery(
        'SELECT g.id AS group_id, f.id AS id, f.label AS label, f.data_type AS data_type, ' .
        'f.html_type AS html_type, f.date_format AS date_format, og.name AS option_group_name ' .
        'FROM `civicrm_custom_group` g ' .
        'LEFT JOIN `civicrm_custom_field` f ON f.custom_group_id = g.id ' .
        'LEFT JOIN `civicrm_option_group` og ON og.id = f.option_group_id ' .
        'WHERE g.extends = \'Contribution\' AND g.is_active = 1 
        AND f.is_active = 1 
        AND f.html_type NOT IN (\'TextArea\', \'RichTextEditor\') 
        AND (
          f.data_type <> \'String\' 
          OR (f.data_type = \'String\' AND f.html_type <> \'Text\')
        )'
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

      $fields['api.Contact.getsingle'] = array('api_call' => true);
      $fields['display_name'] = array('name' => 'display_name', 'title' => 'Display Name');
      $fields['sort_name'] = array('name' => 'sort_name', 'title' => 'Sort Name');
      $fields['contact_type'] = array('name' => 'contact_type', 'title' => 'Contact Type');
      $fields['id'] = array('name' => 'id', 'title' => 'ID');

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

    $result = civicrm_api3('Contribution', 'getoptions', array(
      'field' => $field['name'],
    ));

    return $result['values'];
  }

  /**
   * @inheritdoc
   */
  protected function getCount(array $params) {
    $apiParams = array(
      'is_current_revision' => 1,
      'is_deleted' => 0,
      'is_test' => 0,
    );

    return civicrm_api3('Contribution', 'getcount', $apiParams);
  }

}
