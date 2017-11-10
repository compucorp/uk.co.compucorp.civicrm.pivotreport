<?php

class CRM_PivotReport_BAO_PivotReportConfig extends CRM_PivotReport_DAO_PivotReportConfig {

  public static function getConfigList($entity) {
    $result = array();

    $labels = civicrm_api3('PivotReportConfig', 'get', array(
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
