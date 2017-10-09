<?php

require_once 'CRM/Core/Page.php';

class CRM_Activityreport_Page_PivotReport extends CRM_Core_Page {
  function run() {
    $args = func_get_args();

    $entity = !empty($args[1]['entity']) ? $args[1]['entity'] : NULL;
    $entityInstance = new CRM_Activityreport_Entity($entity);
    $supportedEntities = CRM_Activityreport_Entity::getSupportedEntities();

    CRM_Utils_System::setTitle(ts('Pivot Report'));

    $this->assign('reportTitle', ts($entity . ' Report'));
    $this->assign('options_array', array_combine($supportedEntities, $supportedEntities));
    $this->assign('CRMDataType', $entity);
    $this->assign('cacheBuilt', $entityInstance->getGroupInstance()->isCacheBuilt());

    // PivotReport configuration.
    $this->assign('configList', CRM_Activityreport_BAO_ActivityReportConfig::getConfigList($entity));
    $this->assign('canManagePivotReportConfig', true);

    parent::run();
  }
}
