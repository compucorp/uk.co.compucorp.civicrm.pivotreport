<?php

/**
 * Provides a functionality to prepare Membership entity data for Pivot Table.
 */
class CRM_PivotData_DataMembership extends CRM_PivotData_AbstractData {

  /**
   * CRM_PivotData_DataMembership constructor.
   */
  public function __construct() {
    parent::__construct('Membership');
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
        'sort' => 'join_date ASC',
        'limit' => self::ROWS_API_LIMIT,
      ),
    );

    return $params;
  }

  /**
   * @inheritdoc
   */
  protected function getEntityIndex(array $row) {
    return substr($row['Member Since'], 0, 10);
  }

  /**
   * @inheritdoc
   */
  protected function getFields() {
    if (empty($this->fields)) {
      $unsetFields = array(
      );
      // Get standard Fields of Membership entity.
      $fields = CRM_Member_DAO_Membership::fields();

      foreach ($unsetFields as $unsetField) {
        unset($fields[$unsetField]);
      }

      $keys = CRM_Member_DAO_Membership::fieldKeys();
      $result = array();

      // Now get Custom Fields for entity.
      $customFieldsResult = CRM_Core_DAO::executeQuery(
        'SELECT g.id AS group_id, f.id AS id, f.label AS label, f.data_type AS data_type, ' .
        'f.html_type AS html_type, f.date_format AS date_format, og.name AS option_group_name ' .
        'FROM `civicrm_custom_group` g ' .
        'LEFT JOIN `civicrm_custom_field` f ON f.custom_group_id = g.id ' .
        'LEFT JOIN `civicrm_option_group` og ON og.id = f.option_group_id ' .
        'WHERE g.extends = \'Membership\' AND g.is_active = 1 AND f.is_active = 1 '
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

      $fields['membership_name'] = array('name' => 'membership_name', 'title' => ts('Membership Name'));
      $fields['relationship_name'] = array('name' => 'relationship_name', 'title' => ts('Relationship Name'));

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
  protected function getCount(array $params) {
    $apiParams = array(
      'is_current_revision' => 1,
      'is_deleted' => 0,
      'is_test' => 0,
    );

    return civicrm_api3('Membership', 'getcount', $apiParams);
  }

}
