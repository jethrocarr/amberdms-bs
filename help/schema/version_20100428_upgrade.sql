--
-- AMBERDMS BILLING SYSTEM 1.5.0 ALPHA UPGRADES
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0_alpha_3 to 1.5.0_alpha_4
--

UPDATE menu SET link='timekeeping/timereg.php' WHERE link='timekeeping/timekeeping.php' LIMIT 1;

ALTER TABLE `services_customers_periods` ADD `usage_date_billed` DATE NOT NULL AFTER `usage_summary`,
ALTER TABLE `services_customers_periods` ADD `usage_invoiceid` INT NOT NULL AFTER `usage_date_billed`;

INSERT INTO `billing_modes` (`id`, `name`, `description`) VALUES (NULL, 'monthtelco', 'Telco-style billing - charge for a service at the start of the month and charge for the previous month''s usage.'), (NULL, 'periodtelco', 'Telco-style billing - charge for a service at the start of the period and charge for the previous period''s usage.');

ALTER TABLE `service_usage_records` CHANGE `services_customers_id`  `id_service_customer`  INT( 11 ) NOT NULL DEFAULT '0';
ALTER TABLE `services_customers_periods` CHANGE `services_customers_id`  `id_service_customer`  INT( 11 ) NOT NULL DEFAULT '0';

INSERT INTO `config` (`name`, `value`) VALUES ('SERVICE_MIGRATION_MODE', '0');
INSERT INTO `config` (`name`, `value`) VALUES ('SERVICE_PARTPERIOD_MODE', 'merge');



--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20100428' WHERE name='SCHEMA_VERSION' LIMIT 1;



