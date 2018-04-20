<?php

class CRM_PivotReport_Hook_AddPivotReportEntities {
  /**
   * Runs all extensions implementation of
   * hook_addPivotReportEntities and returns the
   * entities array.
   *
   * @return array
   */
  public static function invoke() {
    $reportEntities = [];

    CRM_Utils_Hook::singleton()->invoke(['pivotReport'],
      $reportEntities,
      CRM_Utils_Hook::$_nullObject,
      CRM_Utils_Hook::$_nullObject,
      CRM_Utils_Hook::$_nullObject,
      CRM_Utils_Hook::$_nullObject,
      CRM_Utils_Hook::$_nullObject,
      'addPivotReportEntities'
    );

    return $reportEntities;
  }
}
