CREATE TABLE IF NOT EXISTS `civicrm_pivotreport_config` (
  `id` INT unsigned NOT NULL AUTO_INCREMENT,
  `entity` VARCHAR(255) NOT NULL,
  `label` VARCHAR(255) NOT NULL,
  `json_config` TEXT,
  PRIMARY KEY ( `id` )
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
