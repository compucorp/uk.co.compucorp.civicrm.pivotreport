<?php

class CRM_Activityreport_BAO_ActivityReportConfig extends CRM_Activityreport_DAO_ActivityReportConfig {

  public static function getConfigList($entity) {
    $result = array();

    $labels = civicrm_api3('ActivityReportConfig', 'get', array(
      'entity' => $entity,
      'return' => array('label'),
      'options' => array(
        'sort' => 'label ASC',
      ),
    ));

    foreach ($labels['values'] as $id => $value) {
      $result[$id] = $value['label'];
    }

    return $result;
  }
}
