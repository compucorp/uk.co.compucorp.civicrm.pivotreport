<?php

/**
 * Provides a functionality to prepare People data for Pivot Table.
 */
class CRM_PivotData_DataContact extends CRM_PivotData_AbstractData {

  /**
   * CRM_PivotData_DataContact constructor.
   */
  public function __construct() {
    parent::__construct('Contact');
  }

  /**
   * {@inheritdoc}
   */
  protected function getData(array $inputParams, $offset = 0) {
    $jobContractFields = $this->getEntityFields('jobcontract');
    $idKey = array_search('id', $jobContractFields);
    //adding the ID as part of the return field gives unpredictable results with mismatched jobroles
    //Id will still get returned nonetheless
    unset($jobContractFields[$idKey]);
    $params = [
      'sequential' => 1,
      'return' => $this->getEntityFields('contact'),
//      'contact_id' => ['IN' => [204, 206, 113, 208, 151, 152, 153, 154, 155]],
      'contact_type' => 'Individual',
      'is_deleted' => 0,
      'api.HRJobContract.get' => [
        'sequential' => 1,
        'contact_id' => "\$value.contact_id",
        'return' => $jobContractFields,
        'api.HrJobRoles.get' => ['sequential' => 1, 'job_contract_id' => "\$value.id", 'return' => $this->getEntityFields('jobroles')],
      ],
      'options' => [
        'limit' => self::ROWS_API_LIMIT,
        'offset' => $offset
      ],
    ];

    return civicrm_api3('Contact', 'get', $params)['values'];
  }

  /**
   * Returns an array containing Entity fields.
   *
   * @return array
   */
  protected function getEntityFields($entityName) {
    $result = [];
    $fields = array_keys($this->getFields());

    foreach ($fields as $field) {
      $fieldParts = explode('.', $field);
      if ($fieldParts[0] === $entityName) {
        $result[] = $fieldParts[1];
      }
    }

    return $result;
  }

  /**
   * @inheritdoc
   */
  protected function formatResult($data, $dataKey = null, $level = 0) {
    $result = [];
    $resultRow = [];

    foreach ($data as $key => $dataRow) {
      $contactValues = $this->getRowValues($dataRow, 'contact');
      $contractValues = $dataRow['api.HRJobContract.get']['values'];
      foreach($contractValues as $contract) {
        $contractRow = $this->getRowValues($contract, 'jobcontract');
        $roleRow = $this->getRowValues(array_shift($contract['api.HrJobRoles.get']['values']), 'jobroles');
        $row = array_merge($contactValues, $contractRow, $roleRow);
        $row = $this->formatRow($key, $row);
        $resultRow[] = $row;
      }

      if(empty($resultRow)) {
        $resultRow[] = $this->formatRow($key, $contactValues);
      }

      $headers = array_merge($this->emptyRow, $this->additionalHeaderFields);
      $finalRow = [];
      foreach($resultRow as $row) {
        foreach ($headers as $key => $header) {
          if (isset($row[$key])) {
            $finalRow[$key] = $row[$key];
          }
          else {
            $finalRow[$key] = '';
          }

        }
        $result[] = $finalRow;
      }

      $resultRow = [];
    }

    return $result;
  }

  /**
   * @inheritdoc
   */
  protected function getEntityIndex(array $row) {
    return substr($row['Contract Start Date'], 0, 10);
  }

  /**
   * @inheritdoc
   */
  protected function getFields() {
    if (empty($this->fields)) {
      $fields = [];
      $keys = [];
      $groups = ['contact', 'jobcontract', 'jobroles'];
      $result = [];

      // Now get Custom Fields for Contact entity.
      $customFieldsResult = CRM_Core_DAO::executeQuery(
        'SELECT g.id AS group_id, f.id AS id, f.label AS label, f.data_type AS data_type, ' .
        'f.html_type AS html_type, f.date_format AS date_format, og.name AS option_group_name ' .
        'FROM `civicrm_custom_group` g ' .
        'LEFT JOIN `civicrm_custom_field` f ON f.custom_group_id = g.id ' .
        'LEFT JOIN `civicrm_option_group` og ON og.id = f.option_group_id ' .
        'WHERE g.extends IN(\'Individual\',\'Contact\') AND g.is_active = 1 AND f.is_active = 1 '
      );

      while ($customFieldsResult->fetch()) {
        $customField = new CRM_Core_BAO_CustomField();
        $customField->id = $customFieldsResult->id;
        $customField->find(true);

        $fields['contact']['custom_' . $customFieldsResult->id] = array(
          'name' => 'custom_' . $customFieldsResult->id,
          'title' => $customFieldsResult->label,
          'pseudoconstant' => array(
            'optionGroupName' => $customFieldsResult->option_group_name,
          ),
          'customField' => (array)$customField,
        );
      }
      //Add contact fields
      $fields['contact']['display_name'] = ['name' => 'display_name', 'title' => 'Display Name'];
      $fields['contact']['contact_type'] = ['name' => 'contact_type', 'title' => 'Contact Type'];
      $fields['contact']['contact_id'] = ['name' => 'contact_id', 'title' => 'Contact ID'];
      $fields['contact']['birth_date'] = ['name' => 'contact_id', 'title' => 'Birth Date'];

      $fields['jobcontract']['id'] = ['name' => 'id', 'title' => ts('Contract ID')];
      $fields['jobcontract']['position'] = ['name' => 'position', 'title' => ts('Contract Position')];
      $fields['jobcontract']['title'] = ['name' => 'title', 'title' => ts('Contract Title')];
      $fields['jobcontract']['contract_type'] = ['name' => 'contract_type', 'title' => ts('Contract Type')];
      $fields['jobcontract']['period_start_date'] = ['name' => 'period_start_date', 'title' => ts('Contract Start Date')];
      $fields['jobcontract']['period_end_date'] = ['name' => 'period_end_date', 'title' => ts('Contract End Date')];
      $fields['jobcontract']['is_current'] = ['name' => 'is_current', 'title' => ts('Current Contract')];

      $fields['jobroles']['id'] = ['name' => 'id', 'title' => ts('Role ID')];
      $fields['jobroles']['start_date'] = ['name' => 'start_date', 'title' => ts('Role Start Date')];
      $fields['jobroles']['end_date'] = ['name' => 'end_date', 'title' => ts('Role End Date')];
      $fields['jobroles']['title'] = ['name' => 'title', 'title' => ts('Role Title')];
      $fields['jobroles']['description'] = ['name' => 'description', 'title' => ts('Role Description')];
      $fields['jobroles']['location'] = ['name' => 'location', 'title' => ts('Role Location')];
      $fields['jobroles']['department'] = ['name' => 'department', 'title' => ts('Role Department')];
      $fields['jobroles']['functional_area'] = ['name' => 'functional_area', 'title' => ts('Role Functional Area')];
      $fields['jobroles']['hours'] = ['name' => 'hours', 'title' => ts('Role Hours')];
      $fields['jobroles']['role_hours_unit'] = ['name' => 'role_hours_unit', 'title' => ts('Role Hours Unit')];
      $fields['jobroles']['level_type'] = ['name' => 'level_type', 'title' => ts('Role Level Type')];
      $fields['jobroles']['organization'] = ['name' => 'organization', 'title' => ts('Role Organization')];


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
  public function getCount(array $params = []) {
    $apiParams = [
      'is_deleted' => 0,
      'contact_type' => 'Individual',
//      'id' => ['IN' => [204, 206, 113, 208, 151, 152, 153, 154, 155]],
    ];

    return civicrm_api3('Contact', 'getcount', $apiParams);
  }
}
