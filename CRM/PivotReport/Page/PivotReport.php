<?php

require_once 'CRM/Core/Page.php';

class CRM_PivotReport_Page_PivotReport extends CRM_Core_Page {
  function run() {
    $args = func_get_args();

    $entity = !empty($args[1]['entity']) ? $args[1]['entity'] : NULL;
    $entityInstance = new CRM_PivotReport_Entity($entity);
    $supportedEntities = CRM_PivotReport_Entity::getSupportedEntities();

    CRM_Utils_System::setTitle(ts($entity . ' Pivot Report'));

    $this->assign('options_array', array_combine($supportedEntities, $supportedEntities));
    $this->assign('CRMDataType', $entity);
    $this->assign('cacheBuilt', $entityInstance->getGroupInstance()->isCacheBuilt());

    // PivotReport configuration.
    $this->assign('configList', CRM_PivotReport_BAO_PivotReportConfig::getConfigList($entity));
    $this->assign('canManagePivotReportConfig', true);

    parent::run();
  }
}
