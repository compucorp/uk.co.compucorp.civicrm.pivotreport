ALTER TABLE `civicrm_pivotreportcache` ADD `is_active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `expired_date`;

UPDATE `civicrm_pivotreportcache` SET is_active = 1;
