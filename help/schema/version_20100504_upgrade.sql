--
-- AMBERDMS BILLING SYSTEM 1.5.0 ALPHA UPGRADES
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0_alpha_4 to 1.5.0_alpha_5
--

ALTER TABLE `service_groups` ADD `id_parent` INT UNSIGNED NOT NULL AFTER `id` ;

INSERT INTO `config` (`name`, `value`) VALUES ('SERVICE_TRAFFIC_MODE', 'internal');
INSERT INTO `config` (`name`, `value`) VALUES ('SERVICE_TRAFFIC_DB_TYPE', '');
INSERT INTO `config` (`name`, `value`) VALUES ('SERVICE_TRAFFIC_DB_HOST', '');
INSERT INTO `config` (`name`, `value`) VALUES ('SERVICE_TRAFFIC_DB_NAME', '');
INSERT INTO `config` (`name`, `value`) VALUES ('SERVICE_TRAFFIC_DB_USERNAME', '');
INSERT INTO `config` (`name`, `value`) VALUES ('SERVICE_TRAFFIC_DB_PASSWORD', '');

INSERT INTO `config` (`name`, `value`) VALUES ('SERVICE_CDR_MODE', 'internal');
INSERT INTO `config` (`name`, `value`) VALUES ('SERVICE_CDR_DB_TYPE', '');
INSERT INTO `config` (`name`, `value`) VALUES ('SERVICE_CDR_DB_HOST', '');
INSERT INTO `config` (`name`, `value`) VALUES ('SERVICE_CDR_DB_NAME', '');
INSERT INTO `config` (`name`, `value`) VALUES ('SERVICE_CDR_DB_USERNAME', '');
INSERT INTO `config` (`name`, `value`) VALUES ('SERVICE_CDR_DB_PASSWORD', '');


CREATE TABLE IF NOT EXISTS `product_groups` (
  `id` int(11) NOT NULL auto_increment,
  `id_parent` int(10) unsigned NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `group_description` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

INSERT INTO `product_groups` (`id`, `id_parent`, `group_name`, `group_description`) VALUES ('1', '0', 'General Products', 'Default grouping for all products.');

ALTER TABLE `products` ADD `id_product_group` INT UNSIGNED NOT NULL AFTER `id` ;

UPDATE `products` SET id_product_group='1';



INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES (NULL, '514', 'Products', 'menu_products_groups', 'products/groups.php', 11);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES (NULL, '515', 'menu_products_groups', 'menu_products_groups_view', 'products/groups.php', 11);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES (NULL, '516', 'menu_products_groups_view', '', 'products/groups-view.php', 11);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES (NULL, '516', 'menu_products_groups_view', '', 'products/groups-delete.php', 12);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES (NULL, '516', 'menu_products_groups', 'menu_products_groups_add', 'products/groups-add.php', 12);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES (NULL, '527', 'menu_service_cdr_rates_view', '', 'services/cdr-rates-import.php', '14');
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES (NULL, '527', 'menu_service_cdr_rates_view', '', 'services/cdr-rates-import-csv.php', '14');
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES (NULL, '211', 'View Customers', '', 'customers/service-history-cdr.php', '4');

INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'id_parent', 'Parent');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'menu_products_groups', 'Manage Product Groups');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'menu_products_groups_view', 'View Product Groups');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'menu_products_groups_add', 'Add Product Group');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'id_product_group', 'Product Group');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'filter_id_product_group', 'Product Group Filter');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'cdr_rate_import_mode', 'Rate Import Mode');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'cdr_rate_import_cost_price', 'Import Cost Price');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'cdr_rate_import_sale_price', 'Import Sale Price');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'cdr_import_delete_existing', 'Delete all existing rates in this rate table & insert from import.');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'cdr_import_update_existing', 'Update existing rates that have matching prefixes but do not delete any.');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'cdr_import_cost_price_use_csv', 'Fetch call cost price from import');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'cdr_import_cost_price_nothing', 'Do not fetch call costs from import');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'cdr_import_sale_price_use_csv', 'Fetch sale price of calls from import');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'cdr_import_sale_price_nothing', 'Do not fetch sale price from import');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'cdr_rate_import_options ', 'Call Rate Import Options');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'cdr_import_sale_price_margin', 'Take the cost price and add the specified margin');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'cdr_rate_import_sale_price_margin', 'Margin to add to cost price to calculate sale price');



ALTER TABLE `service_usage_records` ADD `price` DECIMAL( 11, 2 ) NOT NULL AFTER `date` ;
ALTER TABLE `service_usage_records` ADD `usage3` BIGINT( 20 ) UNSIGNED NOT NULL AFTER `usage2`;
ALTER TABLE `service_usage_records` CHANGE `usage3` `usage3` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0';



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



