<?php

/**
 * @inheritdoc
 */
class CRM_PivotCache_GroupMembership extends CRM_PivotCache_AbstractGroup {

  public function __construct($name = NULL, $source = NULL) {
    parent::__construct('Membership', $source);
  }
}
