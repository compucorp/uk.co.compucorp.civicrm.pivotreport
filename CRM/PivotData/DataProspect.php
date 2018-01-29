<?php

/**
 * Provides a functionality to prepare Prospect data for Pivot Table.
 */
class CRM_PivotData_DataProspect extends CRM_PivotData_DataCase {

  /**
   * CRM_PivotData_DataProspect constructor.
   */
  public function __construct() {
    parent::__construct('Prospect', 'Case');
  }

  /**
   * @inheritdoc
   */
  protected function getEntityApiParams(array $inputParams) {
    $params = array(
      'sequential' => 1,
      'is_deleted' => 0,
      'api.Contact.get' => array('id' => '$value.client_id', 'return' => array('id', 'contact_type', 'contact_sub_type', 'display_name')),
      'api.ProspectConverted.get' => array('prospect_case_id' => '$value.id'),
      'return' => array_merge($this->getCaseFields(), array('subject', 'contacts', 'contact_id')),
      'options' => array(
        'sort' => 'id ASC',
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

    foreach ($data as $key => $inputRow) {
      $caseValues = $this->getCaseValues($inputRow);
      $clientValues = $this->getClients($inputRow['api.Contact.get']['values']);
      $managerValues = $this->getManager($inputRow['contacts']);

      $paymentValues = array();
      if (!empty($inputRow['api.ProspectConverted.get']['values'][0])) {
        $paymentEntityId = $inputRow['api.ProspectConverted.get']['values'][0]['payment_entity_id'];
        if ((int) $inputRow['api.ProspectConverted.get']['values'][0]['payment_type_id'] === CRM_Prospect_BAO_ProspectConverted::PAYMENT_TYPE_CONTRIBUTION) {
          $contribution = civicrm_api3('Contribution', 'get', array(
              'sequential' => 1,
              'id' => $paymentEntityId,
              'return' => array('id', 'financial_type_id', 'contribution_status', 'total_amount', 'receive_date', 'receipt_date'),
              'options' => array(
                'limit' => 1,
              ),
            )
          );

          $paymentValues = $this->getRowValues($contribution['values'][0], 'contribution');
        } else {
          $pledge = civicrm_api3('Pledge', 'get', array(
              'sequential' => 1,
              'id' => $paymentEntityId,
              'return' => array('id', 'pledge_status', 'pledge_amount', 'pledge_total_paid', 'pledge_create_date', 'pledge_start_date', 'pledge_end_date', 'pledge_financial_type'),
              'options' => array(
                'limit' => 1,
              ),
            )
          );

          $paymentValues = $this->getRowValues($pledge['values'][0], 'pledge');
          $paymentValues[ts('Pledge Balance')] = CRM_Utils_Money::format((float) $paymentValues['pledge.pledge_amount'] - (float) $paymentValues[$fields['pledge.pledge_total_paid']], NULL, NULL, TRUE);
        }
      }

      $outputRow = array_merge($this->emptyRow, $this->additionalHeaderFields, $caseValues, $clientValues, $managerValues, $paymentValues);
      $result[] = $this->formatRow($key, $outputRow);
    }

    return $result;
  }

  /**
   * @inheritdoc
   */
  protected function getFields() {
    if (empty($this->fields)) {
      $fields = array();
      $keys = array();
      $groups = array(
        'case' => 'Case',
        'client' => 'Case',
        'manager' => 'Case',
        'contribution' => 'Contribution',
        'pledge' => 'Pledge',
      );

      // Get standard Fields of Case entity.
      $includeCaseFields = array('case_id', 'case_subject', 'case_status_id', 'case_start_date', 'case_end_date');
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

      // Additional fields connected with Prospect data.
      $fields['client']['id'] = ts('Case Client ID');
      $fields['client']['display_name'] = ts('Case Client Display Name');
      $fields['client']['contact_type'] = ts('Case Client Type');
      $fields['client']['contact_sub_type'] = ts('Case Client Subtype');

      $fields['manager']['display_name'] = ts('Case Manager Display Name');

      $fields['pledge']['pledge_start_date'] = array('title' => ts('Pledge Start Date'), 'type' => CRM_Utils_Type::T_DATE);
      $fields['pledge']['pledge_end_date'] = array('title' => ts('Pledge End Date'), 'type' => CRM_Utils_Type::T_DATE);
      $fields['pledge']['pledge_total_paid'] = ts('Pledge Total Paid');
      $fields['pledge']['pledge_balance'] = ts('Pledge Balance');
      $fields['pledge']['pledge_status'] = ts('Pledge Status');
      $fields['pledge']['pledge_financial_type'] = ts('Pledge Financial Type');

      $includeFields = array(
        'contribution' => array(
          'contribution_id', 'financial_type_id', 'total_amount', 'receive_date', 'receipt_date',
        ),
        'pledge' => array(
          'pledge_id', 'pledge_amount', 'pledge_create_date',
        ),
      );

      // Include Contribution fields.
      $contributionFields = CRM_Contribute_DAO_Contribution::fields();
      foreach ($includeFields['contribution'] as $fieldKey) {
        $fields['contribution'][$fieldKey] = $contributionFields[$fieldKey];
      }
      $fields['contribution']['total_amount']['title'] = ts('Total Contribution Amount');
      $fields['contribution']['contribution_status'] = ts('Contribution Status');

      // Include Pledge fields.
      $pledgeFields = CRM_Pledge_DAO_Pledge::fields();
      foreach ($includeFields['pledge'] as $fieldKey) {
        $fields['pledge'][$fieldKey] = $pledgeFields[$fieldKey];
      }

      foreach ($groups as $group => $entity) {
        foreach ($fields[$group] as $key => $value) {
          if (!empty($value['name']) && !empty($keys[$group][$value['name']])) {
            $key = $value['name'];
          }
          $result[$group . '.' . $key] = $value;

          if (is_array($value)) {
            $result[$group . '.' . $key]['optionValues'] = $this->getOptionValues($value, $entity);
          }
        }
      }

      $this->fields = $result;
    }

    return $this->fields;
  }
}
