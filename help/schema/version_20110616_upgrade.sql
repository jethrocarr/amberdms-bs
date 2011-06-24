--
-- AMBERDMS BILLING SYSTEM SVN 1192 CHANGES
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0 beta 2 SVN 1182 to 1.5.0 beta 2 SVN 1192
--


-- Upgrades to table filtering/UIs

INSERT INTO `config` (`name`, `value`) VALUES ('TABLE_LIMIT', '1000');

INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'filter_search_summarise', 'Group Results');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'option_table_limit', 'Table Max Rows');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'help_table_limit', 'Maximum number of table rows to display on one page');


-- New Bill Group Functionality

ALTER TABLE  `cdr_rate_tables_values` ADD  `rate_billgroup` INT UNSIGNED NOT NULL AFTER  `rate_description`;
ALTER TABLE  `cdr_rate_tables_overrides` ADD  `rate_billgroup` INT UNSIGNED NOT NULL AFTER  `rate_description`;
ALTER TABLE  `service_usage_records` ADD  `billgroup` INT UNSIGNED NOT NULL AFTER  `usage3`;

UPDATE cdr_rate_tables_values SET rate_billgroup='1' WHERE rate_prefix='LOCAL';
UPDATE cdr_rate_tables_values SET rate_billgroup='1' WHERE rate_prefix='DEFAULT';

CREATE TABLE  `cdr_rate_billgroups` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `billgroup_name` VARCHAR( 255 ) NOT NULL) ENGINE = INNODB;

INSERT INTO `cdr_rate_billgroups` (`id`, `billgroup_name`) VALUES(0, 'Unknown Region');
INSERT INTO `cdr_rate_billgroups` (`id`, `billgroup_name`) VALUES(1, 'Local');
INSERT INTO `cdr_rate_billgroups` (`id`, `billgroup_name`) VALUES(2, 'National');
INSERT INTO `cdr_rate_billgroups` (`id`, `billgroup_name`) VALUES(3, 'Mobile');
INSERT INTO `cdr_rate_billgroups` (`id`, `billgroup_name`) VALUES(4, 'International');

INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'rate_billgroup', 'Billing Group/Region');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'tbl_lnk_item_expand', 'Expand/Show');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'filter_billgroup', 'Filter by Bill Group');


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20110616' WHERE name='SCHEMA_VERSION' LIMIT 1;


