<?php

/**
 * @inheritdoc
 */
class CRM_PivotCache_GroupContribution extends CRM_PivotCache_AbstractGroup {

  public function __construct($name = NULL) {
    parent::__construct('Contribution');
  }
}
