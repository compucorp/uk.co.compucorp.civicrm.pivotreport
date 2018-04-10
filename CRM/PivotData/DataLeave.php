<?php

/**
 * Provides a functionality to prepare Leave Report data for Pivot Table.
 */
class CRM_PivotData_DataLeave extends CRM_PivotData_AbstractData {

  /**
   * @var
   */
  private $standardHoursOptions;

  /**
   * CRM_PivotData_DataLeave constructor.
   */
  public function __construct() {
    $this->initData();
    parent::__construct('Leave');
  }

  /**
   * {@inheritdoc}
   */
  protected function getData(array $inputParams, $offset = 0) {
    $jobContractFields = $this->getEntityFields('HRJobContract');
    $idKey = array_search('id', $jobContractFields);
    //adding the ID as part of the return field gives unpredictable results with mismatched HrJobRoles
    //Id will still get returned nonetheless
    unset($jobContractFields[$idKey]);

    $params = [
      'sequential' => 1,
      'return' => $this->getEntityFields('LeaveRequest'),
      'api.Contact.get' => [
        'id' => "\$value.contact_id",
        'return' => $this->getEntityFields('Contact')
      ],
      'api.HRJobContract.get' => [
        'sequential' => 1,
        'contact_id' => "\$value.contact_id",
        'return' => $jobContractFields,

        'api.HrJobRoles.get' => [
          'sequential' => 1,
          'job_contract_id' => "\$value.id",
          'return' => $this->getEntityFields('HrJobRoles')
        ],
      ],
      'api.LeaveRequestDate.get' => [
        'sequential' => 1,
        'leave_request_id' => "\$value.id",
        'return' => $this->getEntityFields('LeaveRequestDate'),
        'api.LeaveBalanceChange.get' => [
          'sequential' => 1,
          'source_id' => "\$value.id",
          'source_type' => 'leave_request_day',
          'return' => $this->getEntityFields('LeaveBalanceChange')
        ],
      ],
      'api.AbsenceType.get' => [
        'sequential' => 1,
        'id' => "\$value.type_id",
        'return' => $this->getEntityFields('AbsenceType')
      ],
    ];

    return civicrm_api3('LeaveRequest', 'get', $params)['values'];
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
      $leaveDataRow = $this->getRowValues($dataRow, 'LeaveRequest');
      $leaveDatesData = $dataRow['api.LeaveRequestDate.get']['values'];
      $absenceTypeRow = $this->getRowValues(array_shift($dataRow['api.AbsenceType.get']['values']), 'AbsenceType');
      $contractData = $dataRow['api.HRJobContract.get']['values'];
      $contactDataRow = $this->getRowValues(array_shift($dataRow['api.Contact.get']['values']), 'Contact');
      foreach($leaveDatesData as $leaveDate) {
        $leaveDateRow = $this->getRowValues($leaveDate, 'LeaveRequestDate');
        $leaveBalanceRow = $this->getRowValues(array_shift($leaveDate['api.LeaveBalanceChange.get']['values']), 'LeaveBalanceChange');
        $hasContract = false;
        foreach($contractData as $contract) {
          if ($leaveDate['date'] >= $contract['period_start_date'] && (empty($contract['period_end_date']) || $leaveDate['date'] <= $contract['period_end_date'])) {
            $hasContract = true;
            $currentRevision = $this->getAPIEntityData('HRJobContractRevision', ['jobcontract_id' => $contract['id']], 'getcurrentrevision');
            $contractRow = $this->getRowValues($contract, 'HRJobContract');
            $jobRolesRow = $this->getRowValues(array_shift($contract['api.HrJobRoles.get']['values']), 'HrJobRoles');

            $jobPayData = $this->getAPIEntityData('HRJobPay', ['jobcontract_revision_id' => $currentRevision['pay_revision_id']]);
            $jobHourData = $this->getAPIEntityData('HRJobHour', ['jobcontract_revision_id' => $currentRevision['hour_revision_id']]);
            $jobPensionData = $this->getAPIEntityData('HRJobPension', ['jobcontract_revision_id' => $currentRevision['pension_revision_id']]);
            $jobPayRow = $this->getRowValues(array_shift($jobPayData), 'HRJobPay');
            $jobHourRow = $this->getRowValues(array_shift($jobHourData), 'HRJobHour');
            $jobPensionRow = $this->getRowValues(array_shift($jobPensionData), 'HRJobPension');

            $row = array_merge($leaveDataRow, $absenceTypeRow, $contactDataRow,
              $leaveDateRow, $leaveBalanceRow, $contractRow, $jobRolesRow, $jobPayRow, $jobHourRow, $jobPensionRow);
            $resultRow[] = $this->formatRow($key, $row);
          }
        }

        if(!$hasContract) {
          $row = array_merge($leaveDataRow, $absenceTypeRow, $contactDataRow, $leaveDateRow, $leaveBalanceRow);
          $resultRow[] = $this->formatRow($key, $row);
        }
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
    return substr($row['Absence Date'], 0, 10);
  }

  /**
   * Gets Data from an API.
   *
   * @param string $apiEntityName
   * @param array $params
   * @param string $action
   * @return mixed
   */
  private function getAPIEntityData($apiEntityName, $params = [], $action = '') {
    $defaultParams = [
      'sequential' => 1,
      'return' => $this->getEntityFields($apiEntityName)
    ];

    $params = array_merge($defaultParams, $params);
    $action = $action ? $action : 'get';
    $result = civicrm_api3($apiEntityName, $action, $params);

    return $result['values'];
  }
  /**
   * @inheritdoc
   */
  protected function getFields() {
    if (empty($this->fields)) {
      $fields = [];
      $groups = ['LeaveRequest','Contact', 'HRJobContract',
        'HrJobRoles', 'HRJobHour', 'HRJobPay', 'HRJobPension',
        'LeaveRequestDate', 'AbsenceType', 'LeaveBalanceChange'];
      $result = [];

      //Add Leave Fields
      $fields['LeaveRequest']['id'] = ['name' => 'id', 'title' => 'Absence ID'];
      $fields['LeaveRequest']['type_id'] = ['name' => 'type_id', 'title' => 'Absence Type ID'];
      $fields['LeaveRequest']['contact_id'] = ['name' => 'contact_id', 'title' => 'Employee ID'];
      $fields['LeaveRequest']['from_date'] = ['name' => 'from_date', 'title' => 'Absence Start Date'];
      $fields['LeaveRequest']['to_date'] = ['name' => 'to_date', 'title' => 'Absence End Date'];
      $fields['LeaveRequest']['sickness_reason'] = [
        'name' => 'sickness_reason',
        'title' => 'Sickness Reason',
        'pseudoconstant' => [
          'optionGroupName' => 'hrleaveandabsences_sickness_reason',
        ],
      ];
      $fields['LeaveRequest']['status_id'] = [
        'name' => 'status_id',
        'title' => 'Absence Status',
        'pseudoconstant' => [
          'optionGroupName' => 'hrleaveandabsences_leave_request_status',
        ],
      ];

      //Add Leave Date fields
      $fields['LeaveRequestDate']['date'] = ['name' => 'date', 'title' => 'Absence Date'];

      //AbsenceType fields
      $fields['AbsenceType']['calculation_unit'] = [
        'name' => 'calculation_unit',
        'title' => 'Absence Calculation Unit',
        'pseudoconstant' => [
          'optionGroupName' => 'hrleaveandabsences_absence_type_calculation_unit',
        ],
      ];
      $fields['AbsenceType']['title'] = ['name' => 'title', 'title' => 'Absence Type'];
      $fields['AbsenceType']['allow_accruals_request'] = ['name' => 'allow_accruals_request', 'title' => ts('Is TOIL')];

      //LeaveBalance fields
      $fields['LeaveBalanceChange']['amount'] = ['name' => 'amount', 'title' => 'Absence Amount'];

      //Add Contact fields
      $fields['Contact']['display_name'] = ['name' => 'display_name', 'title' => ts('Employee display name')];
      $fields['Contact']['birth_date'] = [
        'name' => 'birth_date',
        'title' => ts('Employee age'),
        'handler' => 'birthDateHandler'
      ];

      $fields['Contact']['gender_id'] = [
        'name' => 'gender_id',
        'title' => ts('Employee gender'),
        'pseudoconstant' => [
          'optionGroupName' => 'gender',
        ],
      ];

      $lengthOfServiceID = $this->getCustomFieldID('Length_Of_Service', 'Contact_Length_Of_Service');
      if ($lengthOfServiceID) {
        $fields['Contact']['custom_' . $lengthOfServiceID] = [
          'name' => 'custom_' . $lengthOfServiceID,
          'title' => ts('Employee Length Of Service'),
          'handler' => 'lengthOfServiceHandler'
          ];
      }

      //Add Contract fields
      $fields['HRJobContract']['id'] = ['name' => 'id', 'title' => ts('Contract ID')];
      $fields['HRJobContract']['contract_type'] = ['name' => 'contract_type', 'title' => ts('Contract Type')];
      $fields['HRJobContract']['title'] = ['name' => 'title', 'title' => ts('Contract Title')];
      $fields['HRJobContract']['position'] = ['name' => 'position', 'title' => ts('Contract Position')];
      $fields['HRJobContract']['period_start_date'] = ['name' => 'period_start_date', 'title' => ts('Contract Start Date')];
      $fields['HRJobContract']['period_end_date'] = ['name' => 'period_end_date', 'title' => ts('Contract End Date')];
      $fields['HRJobContract']['location'] = [
        'name' => 'location',
        'title' => ts('Contract Normal Place Of Work'),
        'pseudoconstant' => [
          'optionGroupName' => 'hrjc_location',
        ],
      ];
      $fields['HRJobContract']['end_reason'] = [
        'name' => 'end_reason',
        'title' => ts('Contract End Reason'),
        'pseudoconstant' => [
          'optionGroupName' => 'hrjc_contract_end_reason',
        ],
      ];
      $fields['HRJobContract']['is_current'] = ['name' => 'is_current', 'title' => ts('Current Contract')];
      $fields['HRJobContract']['jobcontract_revision_id'] = ['name' => 'jobcontract_revision_id', 'title' => ts('Contract Revision ID')];

      //Job Hour fields
      $fields['HRJobHour']['location_standard_hours'] = [
        'name' => 'location_standard_hours',
        'title' => ts('Contract Location Standard Hours'),
        'handler' => 'locationStandardHoursHandler'
      ];
      $fields['HRJobHour']['hours_type'] = [
        'name' => 'hours_type',
        'title' => ts('Contract Hours Type'),
        'pseudoconstant' => [
          'optionGroupName' => 'hrjc_hours_type',
        ],
      ];
      $fields['HRJobHour']['hours_fte'] = ['name' => 'hours_fte', 'title' => ts('Contract Hours FTE')];

      //Job Pay fields
      $fields['HRJobPay']['is_paid'] = [
        'name' => 'is_paid',
        'title' => ts('Contract Is Paid'),
        'pseudoconstant' => [
          'optionGroupName' => 'hrjc_pay_grade',
        ],
      ];
      $fields['HRJobPay']['pay_scale'] = [
        'name' => 'pay_scale',
        'title' => ts('Contract Pay Scale'),
        'optionValues' => $this->getPayScales()
      ];
      $fields['HRJobPay']['pay_amount'] = ['name' => 'pay_amount', 'title' => ts('Contract Pay Amount')];
      $fields['HRJobPay']['pay_currency'] = [
        'name' => 'pay_currency',
        'title' => ts('Contract Pay Currency'),
        'pseudoconstant' => [
          'optionGroupName' => 'currencies_enabled',
        ],
      ];
      $fields['HRJobPay']['pay_unit'] = ['name' => 'pay_unit', 'title' => ts('Contract Pay Unit')];

      //Job Pension Fields
      $fields['HRJobPension']['is_enrolled'] = [
        'name' => 'is_enrolled',
        'title' => ts('Contract Pension Is Enrolled'),
        'optionValues' => [
          0 => t('No'),
          1 => t('Yes'),
          2 => t('Opted out'),
        ]
      ];

      //Job Roles fields
      $fields['HrJobRoles']['id'] = ['name' => 'id', 'title' => ts('Role ID')];
      $fields['HrJobRoles']['start_date'] = ['name' => 'start_date', 'title' => ts('Role Start Date')];
      $fields['HrJobRoles']['end_date'] = ['name' => 'end_date', 'title' => ts('Role End Date')];
      $fields['HrJobRoles']['title'] = ['name' => 'title', 'title' => ts('Role Title')];
      $fields['HrJobRoles']['description'] = ['name' => 'description', 'title' => ts('Role Description')];
      $fields['HrJobRoles']['location'] = [
        'name' => 'location',
        'title' => ts('Role Location'),
        'pseudoconstant' => [
          'optionGroupName' => 'hrjc_location',
        ],
      ];
      $fields['HrJobRoles']['funder'] = ['name' => 'funder', 'title' => ts('Role Funder')];
      $fields['HrJobRoles']['percent_pay_funder'] = ['name' => 'percent_pay_funder', 'title' => ts('Role Percent Pay Funder')];
      $fields['HrJobRoles']['cost_center'] = ['name' => 'cost_center', 'title' => ts('Role Cost Center')];
      $fields['HrJobRoles']['percent_pay_cost_center'] = ['name' => 'percent_pay_cost_center', 'title' => ts('Role Percent Pay Cost Center')];
      $fields['HrJobRoles']['department'] = [
        'name' => 'department',
        'title' => ts('Role Department'),
        'pseudoconstant' => [
          'optionGroupName' => 'hrjc_department',
        ],
      ];
      $fields['HrJobRoles']['functional_area'] = ['name' => 'functional_area', 'title' => ts('Role Functional Area')];
      $fields['HrJobRoles']['hours'] = ['name' => 'hours', 'title' => ts('Role Hours')];
      $fields['HrJobRoles']['role_hours_unit'] = ['name' => 'role_hours_unit', 'title' => ts('Role Hours Unit')];
      $fields['HrJobRoles']['level_type'] = [
        'name' => 'level_type',
        'title' => ts('Role Level Type'),
        'pseudoconstant' => [
          'optionGroupName' => 'hrjc_level_type',
        ],
      ];
      $fields['HrJobRoles']['organization'] = ['name' => 'organization', 'title' => ts('Role Organization')];
      $fields['HrJobRoles']['region'] = [
        'name' => 'region',
        'title' => ts('Role Region'),
        'pseudoconstant' => [
          'optionGroupName' => 'hrjc_region',
        ],
      ];

      foreach ($groups as $group) {
        foreach ($fields[$group] as $key => $value) {
          $result[$group . '.' . $key] = $value;

          if (isset($value['pseudoconstant'])) {

            if(in_array($value['name'], ['location', 'end_reason'])) {
              $result[$group . '.' . $key]['optionValues'] = $this->getOptionValues($value, 'HRJobDetails');
            }
            else{
              $result[$group . '.' . $key]['optionValues'] = $this->getOptionValues($value, $group);
            }
          }
        }
      }

      $this->fields = $result;
    }

    return $this->fields;
  }

  /**
   * Handler for the location_standard_hours field.
   *
   * @param int $locationHoursID
   * @param array $rowData
   *   Data for other fields belonging to same row as this field value.
   *
   * @return string
   */
  protected function locationStandardHoursHandler($locationHoursID, $rowData) {
    return !empty($this->standardHoursOptions[$locationHoursID]) ?
      $this->standardHoursOptions[$locationHoursID] : '';
  }

  /**
   * Function to load some data on instantiation of this class.
   */
  private function initData() {
    $this->standardHoursOptions = $this->getStandardHoursOptions();
  }

  /**
   * Gets options for the location_standard_hours handler.
   *
   * @return array
   */
  private function getStandardHoursOptions() {
    $result = civicrm_api3('HRHoursLocation', 'get');
    $options = [];

    foreach($result['values'] as $value) {
      $options[$value['id']] = $value['location'] .
        ' (' . $value['standard_hours'] .
        ' / ' . $value['periodicity'] . ')';
    }

    return $options;
  }

  /**
   * Get Pay Scale options needed for the pay_scale field.
   *
   * @return array
   */
  private function getPayScales() {
    $result = civicrm_api3('HRPayScale', 'get');

    return array_column($result['values'], 'pay_scale', 'id');
  }

  /**
   * Gets the Age from the given birth date.
   *
   * @param \DateTime $birthDate
   *
   * @return int
   */
  private function getAge(DateTime $birthDate) {
    $age = 0;
    if ($birthDate < new DateTime('today')) {
      $ageData = CRM_Utils_Date::calculateAge($birthDate->format('Y-m-d'));
      $ageInYears = CRM_Utils_Array::value('years', $ageData);

      if (isset($ageInYears)) {
        $age = $ageInYears;
      }
    }

    return $age;
  }

  /**
   * Handler for the location_standard_hours field.
   *
   * @param string $birthDate
   * @param array $rowData
   *   Data for other fields belonging to same row as this field value.
   *
   * @return string
   */
  protected function birthDateHandler($birthDate, $rowData) {
    if ($birthDate) {
      return 'Not Set';
    }

    return $this->getAge(new DateTime($birthDate));
  }


  /**
   * Handler for the length_of_service Contact custom field.
   *
   * @param int $lengthOfService
   * @param array $rowData
   *   Data for other fields belonging to same row as this field value.
   *
   * @return string
   */
  protected function lengthOfServiceHandler($lengthOfService, $rowData) {
    if (empty($lengthOfService)) {
      return 'Not Set';
    }

    $length = [];
    $today = new DateTime();
    $past = (new DateTime())->sub(new DateInterval('P' . $lengthOfService . 'D'));
    $interval = $today->diff($past);

    $years = intval($interval->format('%y'));
    $months = intval($interval->format('%m'));
    $days = intval($interval->format('%d'));

    if ($years > 0) {
      $length[] = $years > 1 ? "$years years" : "$years year";
    }
    if ($months > 0) {
      $length[] = $months > 1 ? "$months months" : "$months month";
    }
    if ($days > 0) {
      $length[] = $days > 1 ? "$days days" : "$days day";
    }

    return implode(' ', $length);
  }


  /**
   * Gets the ID of a Custom field given the custom field name and the
   * Custom group name it belongs to.
   *
   * @param string $name
   * @param string $groupName
   *
   * @return mixed
   */
  private function getCustomFieldID($name, $groupName) {
    $result = civicrm_api3('CustomField', 'get', [
      'name' => $name,
      'custom_group_id' => $groupName,
    ]);

    return !empty($result['id']) ? $result['id'] : null;
  }

  /**
   * @inheritdoc
   */
  public function getCount(array $params = array()) {
    $apiParams = [
      'return' => ['id'],
      'api.LeaveRequestDate.get' => [
        'leave_request_id' => "\$value.id",
      ],
    ];

    $startDate = !empty($params['start_date']) ? $params['start_date'] : NULL;
    $endDate = !empty($params['end_date']) ? $params['end_date'] : NULL;

    $activityDateFilter = $this->getAPIDateFilter($startDate, $endDate);

    if (!empty($activityDateFilter)) {
      $apiParams['api.LeaveRequestDate.get']['date'] = $activityDateFilter;
      $apiParams['from_date'] = $activityDateFilter;
    }

    $results = civicrm_api3('LeaveRequest', 'get', $apiParams)['values'];
    $count = 0;

    foreach($results as $result) {
      $count += count($result['api.LeaveRequestDate.get']['values']);
    }

    return $count;
  }
}
