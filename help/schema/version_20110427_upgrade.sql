--
-- AMBERDMS BILLING SYSTEM SVN 1131 CHANGES
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0 beta 1 SVN 1120 to 1.5.0 beta 2 SVN 1131
--


-- Missing Translations

INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'cdr_rate_import_options', 'CDR Rate Import Options');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'col_prefix', 'International Prefix');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'col_destination', 'Destination/Country/Location');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'col_sale_price', 'Sale Price');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'col_cost_price', 'Cost Price');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'phone_local_prefix', 'Local Calling Prefix');


-- Adjustments for local call zone

ALTER TABLE  `services_customers_ddi` ADD  `local_prefix` BIGINT( 20 ) NOT NULL AFTER  `ddi_finish`;


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20110427' WHERE name='SCHEMA_VERSION' LIMIT 1;


