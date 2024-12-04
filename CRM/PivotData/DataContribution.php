<?php

/**
 * Provides a functionality to prepare Contribution entity data for Pivot Table.
 */
class CRM_PivotData_DataContribution extends CRM_PivotData_AbstractData {

  /**
   * CRM_PivotData_DataContribution constructor.
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
      'return' => implode(',', $this->getContributionFields()),
      'api.Contact.getsingle' => array(
        'id' => '$value.contact_id',
        'return' => array('display_name', 'sort_name', 'contact_type')
      ),
      'options' => array(
        'sort' => 'receive_date ASC, id ASC',
        'limit' => self::ROWS_API_LIMIT,
      ),
    );

    return $params;
  }

  /**
   * Returns an array containing Contribution fields.
   *
   * @return array
   */
  protected function getContributionFields() {
    $result = array();
    $fields = array_keys($this->getFields());

    foreach ($fields as $field) {
      $fieldParts = explode('.', $field);
      if ($fieldParts[0] === 'contribution') {
        $result[] = $fieldParts[1];
      }
    }

    return $result;
  }

  /**
   * @inheritdoc
   */
  protected function formatResult($data, $dataKey = null, $level = 0) {
    $result = array();

    foreach ($data as $key => $contribution) {
      $contributionValues = $this->getRowValues($contribution, 'contribution');
      $contactValues = $this->getRowValues($contribution['api.Contact.getsingle'], 'contact');

      $row = array_merge($this->emptyRow, $this->additionalHeaderFields, $contributionValues, $contactValues);
      $result[] = $this->formatRow($key, $row);
    }

    return $result;
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
    return substr($row['Date Received'] ?? '', 0, 10);
  }

  /**
   * @inheritdoc
   */
  protected function getFields() {
    if (empty($this->fields)) {
      $fields = array();
      $keys = array();
      $groups = array('contribution', 'contact');

      // Get standard Fields and Keys of Contribution entity.
      $fields['contribution'] = CRM_Contribute_DAO_Contribution::fields();
      $keys['contribution'] = CRM_Contribute_DAO_Contribution::fieldKeys();

      $result = array();

      // Now get Custom Fields for entity.
      $customFieldsResult = CRM_Core_DAO::executeQuery(
        'SELECT g.id AS group_id, f.id AS id, f.label AS label, f.data_type AS data_type, ' .
        'f.html_type AS html_type, f.date_format AS date_format, og.name AS option_group_name ' .
        'FROM `civicrm_custom_group` g ' .
        'LEFT JOIN `civicrm_custom_field` f ON f.custom_group_id = g.id ' .
        'LEFT JOIN `civicrm_option_group` og ON og.id = f.option_group_id ' .
        'WHERE g.extends = \'Contribution\' AND g.is_active = 1 AND f.is_active = 1 '
      );

      while ($customFieldsResult->fetch()) {
        $customField = new CRM_Core_BAO_CustomField();
        $customField->id = $customFieldsResult->id;
        $customField->find(true);

        $fields['contribution']['custom_' . $customFieldsResult->id] = array(
          'name' => 'custom_' . $customFieldsResult->id,
          'title' => $customFieldsResult->label,
          'pseudoconstant' => array(
            'optionGroupName' => $customFieldsResult->option_group_name,
          ),
          'customField' => (array)$customField,
        );
      }

      $fields['contact']['display_name'] = array('name' => 'display_name', 'title' => 'Display Name');
      $fields['contact']['sort_name'] = array('name' => 'sort_name', 'title' => 'Sort Name');
      $fields['contact']['contact_type'] = array('name' => 'contact_type', 'title' => 'Contact Type');
      $fields['contact']['contact_id'] = array('name' => 'contact_id', 'title' => 'Contact ID');

      foreach ($groups as $group) {
        foreach ($fields[$group] as $key => $value) {
          if (!empty($value['name']) && !empty($keys[$group][$value['name']])) {
            $key = $value['name'];
          }
          $result[$group . '.' . $key] = $value;

          if (is_array($value)) {
            $result[$group . '.' . $key]['optionValues'] = $this->getOptionValues($value);
          }
        }
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
      'is_deleted' => 0,
      'is_test' => 0,
    );

    return civicrm_api3('Contribution', 'getcount', $apiParams);
  }

}
