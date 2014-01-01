--
-- AMBERDMS BILLING SYSTEM SVN 1120 CHANGES
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0 beta 1 SVN 1119 to 1.5.0 beta 1 SVN 1120
--


-- Customer Orders Setup Fee

INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'service_setup', 'Service Setup Charges');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'discount_setup', 'Setup Charge Discount');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'info_setup_help', 'If you set a setup fee below, it will be charged once the service is activated and added to the customer orders page.');


-- Adjusted usage summary field for better traffic reporting

ALTER TABLE  `services_customers_periods` CHANGE  `usage_summary`  `usage_summary` DECIMAL( 20, 2 ) NOT NULL;


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20110421' WHERE name='SCHEMA_VERSION' LIMIT 1;


