<?php

class CRM_PivotReport_BAO_PivotReportConfig extends CRM_PivotReport_DAO_PivotReportConfig {

  public static function getConfigList($entity) {
    $result = [];

    $labels = civicrm_api3('PivotReportConfig', 'get', [
      'entity' => $entity,
      'return' => ['label'],
      'options' => [
        'sort' => 'label ASC',
        'limit' => 0,
      ],
    ]);

    foreach ($labels['values'] as $id => $value) {
      $result[$id] = $value['label'];
    }

    return $result;
  }
}
