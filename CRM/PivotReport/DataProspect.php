<?php

/**
 * Provides a functionality to prepare Prospect data for Pivot Table.
 */
class CRM_PivotReport_DataProspect extends CRM_PivotReport_AbstractData {

  /**
   * CRM_PivotReport_DataProspect constructor.
   */
  public function __construct() {
    parent::__construct('Prospect', 'ProspectConverted');
  }

  /**
   * @inheritdoc
   */
  protected function getEntityApiParams(array $inputParams) {
    $params = array(
      'sequential' => 1,
      'api.Case.getsingle' => array('id' => '$value.prospect_case_id', 'api.Contact.get' => array('id' => array('IN' => '$value.client_id'), 'return' => array('id', 'contact_type', 'contact_sub_type', 'display_name'))),
      'api.Contribution.get' => array('id' => '$value.payment_entity_id', 'return' => array('id', 'financial_type_id', 'contribution_status', 'total_amount', 'receive_date', 'receipt_date')),
      'api.Pledge.get' => array('id' => '$value.payment_entity_id', 'return' => array('id', 'financial_type_id', 'status_id', 'pledge_amount', 'pledge_total_paid', 'pledge_create_date', 'pledge_start_date', 'pledge_end_date')),
      'options' => array(
        'sort' => 'prospect_case_id ASC',
        'limit' => self::ROWS_API_LIMIT,
      ),
    );

    return $params;
  }

  /**
   * @inheritdoc
   */
  protected function formatResult($data, $dataKey = null, $level = 0) {
    $result = array();
    $fields = $this->getFields();

    foreach ($data as $key => $prospect) {
      $caseValues = $this->getCaseValues($prospect['api.Case.getsingle']);
      $clientValues = $this->getRowValues($prospect['api.Case.getsingle']['api.Contact.get']['values'][0], 'client');
      $managerValues = $this->getManager($prospect['api.Case.getsingle']['contacts']);

      $paymentValues = array();
      if ((int) $prospect['payment_type_id'] === CRM_Prospect_BAO_ProspectConverted::PAYMENT_TYPE_CONTRIBUTION) {
        $paymentValues = $this->getRowValues($prospect['api.Contribution.get']['values'][0], 'contribution');
      } else {
        $paymentValues = $this->getRowValues($prospect['api.Pledge.get']['values'][0], 'pledge');
        $paymentValues['Pledge Balance'] = CRM_Utils_Money::format((float) $paymentValues['pledge.pledge_amount'] - (float) $paymentValues[$fields['pledge.pledge_total_paid']], NULL, NULL, TRUE);
      }

      $row = array_merge($this->emptyRow, $this->additionalHeaderFields, $caseValues, $clientValues, $managerValues, $paymentValues);
      $result[] = $this->formatRow($key, $row);
    }

    return $result;
  }

  /**
   * Returns Prospect related Case fields and values.
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
   * Returns Prospect related fields and values basing on specified entity name.
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
          'Case Manager Display Name' => $contact['display_name'],
        );
      }
    }

    return array(
      'Case Manager Display Name' => '',
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
    return NULL;
  }

  /**
   * @inheritdoc
   */
  protected function getFields() {
    if (empty($this->fields)) {
      $fields = array();
      $keys = array();
      $groups = array('case', 'client', 'manager', 'contribution', 'pledge');

      // Get standard Fields of Case entity.
      $includeCaseFields = array('case_id', 'case_status_id', 'case_start_date', 'case_end_date');
      $caseFields = CRM_Case_DAO_Case::fields();

      foreach ($includeCaseFields as $includeField) {
        $fields['case'][$includeField] = $caseFields[$includeField];
      }

      $keys['case'] = CRM_Case_DAO_Case::fieldKeys();
      $result = array();

      // Now get Custom Fields of Activity entity.
      $customFieldsResult = CRM_Core_DAO::executeQuery(
        'SELECT g.id AS group_id, f.id AS id, f.label AS label, f.data_type AS data_type, ' .
        'f.html_type AS html_type, f.date_format AS date_format, og.name AS option_group_name ' .
        'FROM `civicrm_custom_group` g ' .
        'LEFT JOIN `civicrm_custom_field` f ON f.custom_group_id = g.id ' .
        'LEFT JOIN `civicrm_option_group` og ON og.id = f.option_group_id ' .
        'WHERE g.extends = \'Case\' AND g.is_active = 1 AND g.name IN (\'Prospect_Financial_Information\', \'Prospect_More_Information\', \'Prospect_Substatus\') AND f.is_active = 1 AND f.html_type NOT IN (\'TextArea\', \'RichTextEditor\') AND (f.data_type <> \'String\' OR (f.data_type = \'String\' AND f.html_type <> \'Text\')) '
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

      // Additional fields connected with Prospect data.
      $fields['client']['id'] = 'Case Client ID';
      $fields['client']['display_name'] = 'Case Client Display Name';
      $fields['client']['contact_type'] = 'Case Client Type';
      $fields['client']['contact_sub_type'] = 'Case Client Subtype';

      $fields['manager']['display_name'] = 'Case Manager Display Name';

      $fields['pledge']['pledge_start_date'] = array('title' => 'Pledge Start Date', 'type' => CRM_Utils_Type::T_DATE);
      $fields['pledge']['pledge_end_date'] = array('title' => 'Pledge End Date', 'type' => CRM_Utils_Type::T_DATE);
      $fields['pledge']['pledge_total_paid'] = 'Pledge Total Paid';
      $fields['pledge']['pledge_balance'] = 'Pledge Balance';

      $includeFields = array(
        'contribution' => array(
          'contribution_id', 'financial_type_id', 'total_amount', 'receive_date', 'receipt_date',
        ),
        'pledge' => array(
          'pledge_id', 'pledge_financial_type_id', 'pledge_status_id', 'pledge_amount', 'pledge_create_date',
        ),
      );

      // Include Contribution fields.
      $contributionFields = CRM_Contribute_DAO_Contribution::fields();
      foreach ($includeFields['contribution'] as $fieldKey) {
        $fields['contribution'][$fieldKey] = $contributionFields[$fieldKey];
      }
      $fields['contribution']['contribution_status'] = 'Contribution Status';

      // Include Pledge fields.
      $pledgeFields = CRM_Pledge_DAO_Pledge::fields();
      foreach ($includeFields['pledge'] as $fieldKey) {
        $fields['pledge'][$fieldKey] = $pledgeFields[$fieldKey];
      }

      foreach ($groups as $group) {
        foreach ($fields[$group] as $key => $value) {
          if (!empty($keys[$group][$value['name']])) {
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

    $result = civicrm_api3('Case', 'getoptions', array(
      'field' => $field['name'],
    ));

    return $result['values'];
  }

  /**
   * @inheritdoc
   */
  protected function getCount(array $params) {
    return civicrm_api3('ProspectConverted', 'getcount', array());
  }
}