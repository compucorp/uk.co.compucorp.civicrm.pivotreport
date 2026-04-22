<?php

/**
 * @inheritdoc
 */
class CRM_PivotCache_GroupActivity extends CRM_PivotCache_AbstractGroup {

  public function __construct($name = NULL, $source = NULL) {
    parent::__construct('Activity', $source);
  }
}
