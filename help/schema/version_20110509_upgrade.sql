--
-- AMBERDMS BILLING SYSTEM SVN 1154 CHANGES
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0 beta 2 SVN 1150 to 1.5.0 beta 2 SVN 1154
--


-- UI/Translation Changes for NAD Import & Local Prefix/Region Handling

INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'cdr_import_mode_regular', 'Regular CSV Import Mode');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'cdr_import_mode_nz_NAD', 'New Zealand NAD Import Mode');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'nad_import_details', 'NAD Import Details');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'nad_import_options', 'NAD Import Options');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'nad_country_prefix', 'Prefix');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'nad_price_cost', 'Cost Price');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'nad_price_sale', 'Sale Price');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'nad_default_destination', 'Default Destination');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'filter_searchbox_prefix', 'Filter Prefix');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'filter_searchbox_desc', 'Filter Description');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'config_options_cdr', 'CDR Configuration Options');


-- CDR Handling Changes

INSERT INTO `config` (`name`, `value`) VALUES ('SERVICE_CDR_LOCAL', 'destination');

ALTER TABLE  `services_customers_ddi` CHANGE  `local_prefix`  `local_prefix` VARCHAR( 255 ) NOT NULL;


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20110509' WHERE name='SCHEMA_VERSION' LIMIT 1;


