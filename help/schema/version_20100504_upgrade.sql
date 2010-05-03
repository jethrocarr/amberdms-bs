--
-- AMBERDMS BILLING SYSTEM 1.5.0 ALPHA UPGRADES
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;

--
-- Changes from 1.5.0 alpha 5 to 1.5.0 alpha 6
--

ALTER TABLE `account_items` CHANGE `type` `type` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;

ALTER TABLE `services_customers_periods` DROP `usage_date_billed`;
ALTER TABLE `services_customers_periods` DROP `usage_invoiceid`;
ALTER TABLE `services_customers_periods` ADD `invoiceid_usage` INT NOT NULL AFTER `invoiceid`;

ALTER TABLE `services` ADD `id_service_group_usage` INT NOT NULL AFTER `id_service_group` ;

UPDATE services SET id_service_group_usage='1';


INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL , 'en_us', 'billing_cycle_string', 'Billing Cycle');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL , 'en_us', 'service_price', 'Service Price');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL , 'en_us', 'service_options_ddi', 'Service DDI Configuration');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL , 'en_us', 'phone_ddi_single', 'Phone Number');

INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL , 'en_us', 'service_migration', 'Service Migration Options');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL , 'en_us', 'migration_date_period_usage_override', 'Migration Date Override Options');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL , 'en_us', 'migration_use_period_date', 'Start charging usage from the first period date.');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL , 'en_us', 'migration_use_usage_date', 'Start charging usage from the specified date, but charge the plan fee from the first period date.');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL , 'en_us', 'migration_date_period_usage_first', 'Usage Start Date');





--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20100504' WHERE name='SCHEMA_VERSION' LIMIT 1;



