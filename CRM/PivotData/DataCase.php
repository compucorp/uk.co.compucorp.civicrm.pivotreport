<?php

/**
 * Provides a functionality to prepare Case data for Pivot Table.
 */
class CRM_PivotData_DataCase extends CRM_PivotData_AbstractData {

  /**
   * CRM_PivotData_DataCase constructor.
   */
  public function __construct() {
    parent::__construct('Case');
  }

  /**
   * @inheritdoc
   */
  protected function getEntityApiParams(array $inputParams) {
    $params = array(
      'sequential' => 1,
      'api.Contact.get' => array('id' => array('IN' => '$value.client_id'), 'return' => array('id', 'contact_type', 'contact_sub_type', 'display_name')),
      'return' => array_merge($this->getCaseFields(), array('contacts')),
      'options' => array(
        'sort' => 'start_date ASC',
        'limit' => self::ROWS_API_LIMIT,
      ),
    );

    return $params;
  }

  protected function getCaseFields() {
    $result = array();
    $fields = array_keys($this->getFields());

    foreach ($fields as $field) {
      $fieldParts = explode('.', $field);
      if ($fieldParts[0] === 'case') {
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

    foreach ($data as $key => $case) {
      $caseValues = $this->getCaseValues($case);
      $clientValues = $this->getRowValues($case['api.Contact.get']['values'][0], 'client');
      $managerValues = $this->getManager($case['contacts']);

      $row = array_merge($this->emptyRow, $this->additionalHeaderFields, $caseValues, $clientValues, $managerValues);
      $result[] = $this->formatRow($key, $row);
    }

    return $result;
  }

  /**
   * Returns Case fields and values.
   *
   * @param array $data
   *
   * @return array
   */
  protected function getCaseValues($data) {
    $result = array();
    $fields = $this->getFields();
    $include = array('id', 'status_id', 'start_date', 'end_date');

    foreach ($data as $key => $value) {
      $resultKey = 'case.' . $key;
      if (empty($fields[$resultKey])) {
        continue;
      }

      if (in_array($key, $include) || CRM_Utils_String::startsWith($key, 'custom_')) {
        $result[$resultKey] = $value;
      }
    }

    return $result;
  }

  /**
   * Returns Case fields and values basing on specified entity name.
   *
   * @param array $data
   * @param string $entityName
   *
   * @return array
   */
  protected function getRowValues($data, $entityName) {
    $result = array();
    $fields = $this->getFields();

    foreach ($data as $key => $value) {
      $fieldsKey = $entityName . '.' . $key;

      if (empty($fields[$fieldsKey])) {
        continue;
      }

      $resultKey = $fieldsKey;
      if (!is_array($fields[$fieldsKey])) {
        $resultKey = $fields[$fieldsKey];
      }

      $result[$resultKey] = $value;
    }

    return $result;
  }

  /**
   * Returns an array containing manager label as key and manager display
   * name as value.
   *
   * @param array $contacts
   *
   * @return array
   */
  protected function getManager($contacts) {
    foreach ($contacts as $contact) {
      if (!empty($contact['manager']) && (int) $contact['manager'] === 1) {
        return array(
          ts('Case Manager Display Name') => $contact['display_name'],
        );
      }
    }

    return array(
      ts('Case Manager Display Name') => '',
    );
  }

  /**
   * Returns an array containing formatted rows of specified array.
   *
   * @param int $key
   * @param array $row
   *
   * @return array
   */
  protected function formatRow($key, $row) {
    $fields = $this->getFields();
    $result = array();

    foreach ($row as $key => $value) {
      $label = $key;
      if (!empty($fields[$key]['title'])) {
        $label = $fields[$key]['title'];
      }
      $label = ts($label);

      $formattedValue = $this->formatValue($key, $value);
      $result[$label] = $formattedValue;

      if (is_array($formattedValue)) {
        $this->multiValues[$key][] = $label;
      }
    }

    ksort($result);

    return $result;
  }

  /**
   * @inheritdoc
   */
  protected function getEntityIndex(array $row) {
    return substr($row['Case Start Date'], 0, 10);
  }

  /**
   * @inheritdoc
   */
  protected function getFields() {
    if (empty($this->fields)) {
      $fields = array();
      $keys = array();
      $groups = array('case', 'client', 'manager');

      // Get standard Fields of Case entity.
      $includeCaseFields = array('case_id', 'case_status_id', 'case_start_date', 'case_end_date');
      $caseFields = CRM_Case_DAO_Case::fields();

      foreach ($includeCaseFields as $includeField) {
        $fields['case'][$includeField] = $caseFields[$includeField];
      }

      $keys['case'] = CRM_Case_DAO_Case::fieldKeys();
      $result = array();

      // Now get Custom Fields of Case entity.
      $customFieldsResult = CRM_Core_DAO::executeQuery(
        'SELECT g.id AS group_id, f.id AS id, f.label AS label, f.data_type AS data_type, ' .
        'f.html_type AS html_type, f.date_format AS date_format, og.name AS option_group_name ' .
        'FROM `civicrm_custom_group` g ' .
        'LEFT JOIN `civicrm_custom_field` f ON f.custom_group_id = g.id ' .
        'LEFT JOIN `civicrm_option_group` og ON og.id = f.option_group_id ' .
        'WHERE g.extends = \'Case\' AND g.is_active = 1 AND f.is_active = 1 '
      );

      while ($customFieldsResult->fetch()) {
        $customField = new CRM_Core_BAO_CustomField();
        $customField->id = $customFieldsResult->id;
        $customField->find(true);

        $fields['case']['custom_' . $customFieldsResult->id] = array(
          'name' => 'custom_' . $customFieldsResult->id,
          'title' => $customFieldsResult->label,
          'pseudoconstant' => array(
            'optionGroupName' => $customFieldsResult->option_group_name,
          ),
          'customField' => (array)$customField,
        );
      }

      // Additional fields connected with Case data.
      $fields['client']['id'] = ts('Case Client ID');
      $fields['client']['display_name'] = ts('Case Client Display Name');
      $fields['client']['contact_type'] = ts('Case Client Type');
      $fields['client']['contact_sub_type'] = ts('Case Client Subtype');

      $fields['manager']['display_name'] = ts('Case Manager Display Name');

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
  protected function getCount(array $params) {
    return civicrm_api3('Case', 'getcount', array());
  }
}