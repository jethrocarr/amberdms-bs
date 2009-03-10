--
-- AMBERDMS BILLING SYSTEM VERSION 1.1.0 UPGRADE
--

ALTER TABLE `services` ADD `alert_80pc` TINYINT NOT NULL AFTER `description`, 
ADD `alert_100pc` TINYINT NOT NULL AFTER `alert_80pc` ,
ADD `alert_extraunits` INT NOT NULL AFTER `alert_100pc` ;

INSERT INTO `language` ( `id` , `language` , `label` , `translation` ) VALUES (NULL , 'en_us', 'alert_extraunits', 'Alert for every specified number of extra units');
INSERT INTO `language` ( `id` , `language` , `label` , `translation` ) VALUES (NULL , 'en_us', 'service_plan_alerts', 'Service Plan Alerts');
INSERT INTO `language` ( `id` , `language` , `label` , `translation` ) VALUES (NULL , 'en_us', 'usage_summary', 'Usage Summary');

ALTER TABLE `services_customers_periods` ADD `usage_summary` BIGINT( 20 ) NOT NULL AFTER `invoiceid` ;


INSERT INTO `config` ( `name` , `value` ) VALUES ('CODE_STAFF', '100');

ALTER TABLE `projects` ADD `internal_only` BOOL NOT NULL AFTER `date_end` ;

INSERT INTO `language` ( `id` , `language` , `label` , `translation` ) VALUES (NULL , 'en_us', 'internal_only', 'Internal Only');
INSERT INTO `language` ( `id` , `language` , `label` , `translation` ) VALUES (NULL , 'en_us', 'id_support_ticket', 'Ticket ID');


--
-- Set Schema Version
--
INSERT INTO `config` ( `name` , `value` ) VALUES ('SCHEMA_VERSION', '20090310');


