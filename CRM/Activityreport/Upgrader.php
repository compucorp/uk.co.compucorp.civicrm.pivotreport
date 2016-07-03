<?php

/**
 * Collection of upgrade steps.
 */
class CRM_Activityreport_Upgrader extends CRM_Activityreport_Upgrader_Base {

  /**
   * Installation logic.
   * 
   * @return boolean
   */
  public function install() {
    $this->upgrade_0001();

    return TRUE;
  }

  /**
   * Uninstallation logic.
   * 
   * @return boolean
   */
  public function uninstall()
  {
    CRM_Core_DAO::executeQuery("DELETE FROM `civicrm_navigation` WHERE name = 'activityreport'");
    CRM_Core_BAO_Navigation::resetNavigation();

    return TRUE;
  }

  /**
   * Install Activity Report link under Reports menu.
   * 
   * @return boolean
   */
  public function upgrade_0001() {
    CRM_Core_DAO::executeQuery("DELETE FROM `civicrm_navigation` WHERE name = 'activityreport' and parent_id IS NULL");
    $reportsNavId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', 'Reports', 'id', 'name');
    $navigation = new CRM_Core_DAO_Navigation();
    $params = array (
        'domain_id'  => CRM_Core_Config::domainID(),
        'label'      => ts('Activity Report'),
        'name'       => 'activityreport',
        'url'        => 'civicrm/activity-report',
        'parent_id'  => $reportsNavId,
        'weight'     => 0,
        'permission' => 'access CiviCRM pivot table reports',
        'separator'  => 1,
        'is_active'  => 1
    );
    $navigation->copyValues($params);
    $navigation->save();
    CRM_Core_BAO_Navigation::resetNavigation();

    return TRUE;
  }

  /**
   * Logic which is executing when enabling extension.
   * 
   * @return boolean
   */
  public function onEnable() {
    CRM_Core_DAO::executeQuery("UPDATE civicrm_navigation SET is_active = 1 WHERE name = 'activityreport'");
    CRM_Core_BAO_Navigation::resetNavigation();

    return TRUE;
  }

  /**
   * Logic which is executing when disabling extension.
   * 
   * @return boolean
   */
  public function onDisable() {
    CRM_Core_DAO::executeQuery("UPDATE civicrm_navigation SET is_active = 0 WHERE name = 'activityreport'");
    CRM_Core_BAO_Navigation::resetNavigation();

    return TRUE;
  }
}
