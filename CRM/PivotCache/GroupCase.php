<?php

/**
 * @inheritdoc
 */
class CRM_PivotCache_GroupCase extends CRM_PivotCache_AbstractGroup {

  public function __construct($name = NULL, $source = NULL) {
    parent::__construct('Case', $source);
  }
}
