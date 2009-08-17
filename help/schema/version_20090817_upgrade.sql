--
-- AMBERDMS BILLING SYSTEM VERSION 1.3.0 UPGRADES
--
-- There are a substantial number of database changes in this release to upgrade
-- the database to InnoDB and also UTF8.
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Apply Changes
--

ALTER TABLE `account_ap`  ENGINE = innodb;
ALTER TABLE `account_ar`  ENGINE = innodb;
ALTER TABLE `account_charts`  ENGINE = innodb;
ALTER TABLE `account_charts_menus`  ENGINE = innodb;
ALTER TABLE `account_chart_menu`  ENGINE = innodb;
ALTER TABLE `account_chart_type`  ENGINE = innodb;
ALTER TABLE `account_chart_types_menus`  ENGINE = innodb;
ALTER TABLE `account_gl`  ENGINE = innodb;
ALTER TABLE `account_items`  ENGINE = innodb;
ALTER TABLE `account_items_options`  ENGINE = innodb;
ALTER TABLE `account_quotes`  ENGINE = innodb;
ALTER TABLE `account_taxes`  ENGINE = innodb;
ALTER TABLE `account_trans`  ENGINE = innodb;
ALTER TABLE `billing_cycles`  ENGINE = innodb;
ALTER TABLE `billing_modes`  ENGINE = innodb;
ALTER TABLE `config`  ENGINE = innodb;
ALTER TABLE `customers`  ENGINE = innodb;
ALTER TABLE `customers_taxes`  ENGINE = innodb;
ALTER TABLE `file_uploads`  ENGINE = innodb;
ALTER TABLE `file_upload_data`  ENGINE = innodb;
ALTER TABLE `journal`  ENGINE = innodb;
ALTER TABLE `language`  ENGINE = innodb;
ALTER TABLE `language_avaliable`  ENGINE = innodb;
ALTER TABLE `menu`  ENGINE = innodb;
ALTER TABLE `permissions`  ENGINE = innodb;
ALTER TABLE `permissions_staff`  ENGINE = innodb;
ALTER TABLE `products`  ENGINE = innodb;
ALTER TABLE `products_taxes`  ENGINE = innodb;
ALTER TABLE `projects`  ENGINE = innodb;
ALTER TABLE `project_phases`  ENGINE = innodb;
ALTER TABLE `services`  ENGINE = innodb;
ALTER TABLE `services_customers`  ENGINE = innodb;
ALTER TABLE `services_customers_options`  ENGINE = innodb;
ALTER TABLE `services_customers_periods`  ENGINE = innodb;
ALTER TABLE `services_taxes`  ENGINE = innodb;
ALTER TABLE `service_types`  ENGINE = innodb;
ALTER TABLE `service_units`  ENGINE = innodb;
ALTER TABLE `service_usage_modes`  ENGINE = innodb;
ALTER TABLE `service_usage_records`  ENGINE = innodb;
ALTER TABLE `staff`  ENGINE = innodb;
ALTER TABLE `support_tickets`  ENGINE = innodb;
ALTER TABLE `support_tickets_priority`  ENGINE = innodb;
ALTER TABLE `support_tickets_status`  ENGINE = innodb;
ALTER TABLE `templates`  ENGINE = innodb;
ALTER TABLE `timereg`  ENGINE = innodb;
ALTER TABLE `time_groups`  ENGINE = innodb;
ALTER TABLE `users`  ENGINE = innodb;
ALTER TABLE `users_blacklist`  ENGINE = innodb;
ALTER TABLE `users_options`  ENGINE = innodb;
ALTER TABLE `users_permissions`  ENGINE = innodb;
ALTER TABLE `users_permissions_staff`  ENGINE = innodb;
ALTER TABLE `users_sessions`  ENGINE = innodb;
ALTER TABLE `vendors`  ENGINE = innodb;
ALTER TABLE `vendors_taxes`  ENGINE = innodb;


UPDATE `menu` SET `parent` = 'View Invoices' WHERE `parent` = 'Accounts Receivables'  LIMIT 1 ;

INSERT INTO `permissions` ( `id` , `value` , `description` ) VALUES (NULL , 'timekeeping_all_view', 'Allow the user to view timesheets and unbilled for ALL staff.'), (NULL , 'timekeeping_all_write', 'Allow the user to adjust for any employee.');

INSERT INTO `config` (`name`, `value`) VALUES ('LANGUAGE_LOAD', 'preload'), ('LANGUAGE_DEFAULT', 'en_us');
INSERT INTO `config` (`name`, `value`) VALUES ('CURRENCY_DEFAULT_SYMBOL_POSITION', 'before');

INSERT INTO `billing_system_devel`.`language` (`id`, `language`, `label`, `translation`) VALUES (NULL , 'en_us', 'date_created', 'Date Created');

INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL , 'en_us', 'filter_hide_ex_products', 'Hide EOL Products');



ALTER DATABASE DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
 
ALTER TABLE `account_ap` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `account_ar` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `account_charts` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `account_charts_menus` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `account_chart_menu` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `account_chart_type` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `account_chart_types_menus` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `account_gl` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `account_items` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `account_items_options` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `account_quotes` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `account_taxes` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `account_trans` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `billing_cycles` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `billing_modes` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `config` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `customers` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `customers_taxes` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `file_uploads` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `file_upload_data` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `journal` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `language` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `language_avaliable` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `menu` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `permissions` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `permissions_staff` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `products` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `products_taxes` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `projects` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `project_phases` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `services` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `services_customers` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `services_customers_options` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `services_customers_periods` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `services_taxes` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `service_types` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `service_units` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `service_usage_modes` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `service_usage_records` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `staff` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `support_tickets` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `support_tickets_priority` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `support_tickets_status` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `templates` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `timereg` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `time_groups` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `users` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `users_blacklist` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `users_options` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `users_permissions` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `users_permissions_staff` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `users_sessions` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `vendors` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
ALTER TABLE `vendors_taxes` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;

ALTER TABLE `account_ap` CHANGE `code_invoice` `code_invoice` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, CHANGE `code_ordernumber` `code_ordernumber` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, CHANGE `code_ponumber` `code_ponumber` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `notes` `notes` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `account_ar` CHANGE `code_invoice` `code_invoice` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, CHANGE `code_ordernumber` `code_ordernumber` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `code_ponumber` `code_ponumber` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `sentmethod` `sentmethod` VARCHAR( 10 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `notes` `notes` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `account_charts` CHANGE `description` `description` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `account_chart_menu` CHANGE `value` `value` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `groupname` `groupname` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `description` `description` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `account_chart_type` CHANGE `value` `value` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `total_mode` `total_mode` VARCHAR( 6 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `account_gl` CHANGE `code_gl` `code_gl` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `description` `description` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `notes` `notes` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `account_items` CHANGE `invoicetype` `invoicetype` VARCHAR( 6 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `type` `type` VARCHAR( 10 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `units` `units` VARCHAR( 10 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `description` `description` VARCHAR( 255 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;
ALTER TABLE `account_items` CHANGE `description` `description` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `account_items_options` CHANGE `option_name` `option_name` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `option_value` `option_value` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `account_quotes` CHANGE `code_quote` `code_quote` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `sentmethod` `sentmethod` VARCHAR( 10 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `notes` `notes` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `account_taxes` CHANGE `name_tax` `name_tax` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `taxnumber` `taxnumber` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `description` `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `account_trans` CHANGE `type` `type` VARCHAR( 6 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `source` `source` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `memo` `memo` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `billing_cycles` CHANGE `name` `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `description` `description` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `billing_modes` CHANGE `name` `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `description` `description` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `config` CHANGE `name` `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `value` `value` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `customers` CHANGE `code_customer` `code_customer` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `name_customer` `name_customer` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `name_contact` `name_contact` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `contact_email` `contact_email` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `contact_phone` `contact_phone` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `contact_fax` `contact_fax` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;
ALTER TABLE `customers` CHANGE `tax_number` `tax_number` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0', CHANGE `address1_street` `address1_street` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `address1_city` `address1_city` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `address1_state` `address1_state` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `address1_country` `address1_country` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `customers` CHANGE `address2_street` `address2_street` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `address2_city` `address2_city` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `address2_state` `address2_state` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `address2_country` `address2_country` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;

ALTER TABLE `file_uploads` CHANGE `type` `type` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `file_name` `file_name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `file_size` `file_size` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `file_location` `file_location` CHAR( 2 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;

ALTER TABLE `journal` CHANGE `journalname` `journalname` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `type` `type` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `content` `content` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `title` `title` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `language` CHANGE `language` `language` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `label` `label` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `translation` `translation` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `language_avaliable` CHANGE `name` `name` VARCHAR( 5 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `menu` CHANGE `parent` `parent` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `topic` `topic` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `link` `link` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `permissions` CHANGE `value` `value` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `description` `description` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `permissions_staff` CHANGE `value` `value` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `description` `description` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `products` CHANGE `code_product` `code_product` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `name_product` `name_product` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `details` `details` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `code_product_vendor` `code_product_vendor` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `units` `units` VARCHAR( 10 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `products_taxes` CHANGE `description` `description` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `projects` CHANGE `code_project` `code_project` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `name_project` `name_project` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `details` `details` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;
ALTER TABLE `project_phases` CHANGE `name_phase` `name_phase` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `description` `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;

ALTER TABLE `services` CHANGE `name_service` `name_service` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `units` `units` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0', CHANGE `description` `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;
ALTER TABLE `services_customers` CHANGE `description` `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL  ;
ALTER TABLE `services_customers_options` CHANGE `name` `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `value` `value` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;
ALTER TABLE `service_types` CHANGE `name` `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `description` `description` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;
ALTER TABLE `service_units` CHANGE `name` `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `description` `description` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `service_usage_modes` CHANGE `name` `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `description` `description` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;

ALTER TABLE `staff` CHANGE `name_staff` `name_staff` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `staff_code` `staff_code` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `staff_position` `staff_position` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `contact_phone` `contact_phone` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `contact_fax` `contact_fax` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `contact_email` `contact_email` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;

ALTER TABLE `support_tickets` CHANGE `title` `title` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `details` `details` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;
ALTER TABLE `support_tickets_priority` CHANGE `value` `value` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `description` `description` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;
ALTER TABLE `support_tickets_status` CHANGE `value` `value` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `description` `description` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;

ALTER TABLE `templates` CHANGE `name` `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `type` `type` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `data` `data` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;

ALTER TABLE `timereg` CHANGE `description` `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL  ;
ALTER TABLE `time_groups` CHANGE `name_group` `name_group` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `description` `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;

ALTER TABLE `users` CHANGE `username` `username` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `realname` `realname` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `password` `password` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `password_salt` `password_salt` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `contact_email` `contact_email` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `ipaddress` `ipaddress` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;
ALTER TABLE `users_blacklist` CHANGE `ipaddress` `ipaddress` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL  ;
ALTER TABLE `users_options` CHANGE `name` `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `value` `value` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;
ALTER TABLE `users_sessions` CHANGE `authkey` `authkey` VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `ipaddress` `ipaddress` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;

ALTER TABLE `vendors` CHANGE `name_vendor` `name_vendor` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `code_vendor` `code_vendor` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `name_contact` `name_contact` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `contact_email` `contact_email` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `contact_phone` `contact_phone` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `contact_fax` `contact_fax` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;
ALTER TABLE `vendors` CHANGE `tax_number` `tax_number` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0', CHANGE `address1_street` `address1_street` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `address1_city` `address1_city` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `address1_state` `address1_state` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `address1_country` `address1_country` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;
ALTER TABLE `vendors` CHANGE `address2_street` `address2_street` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `address2_city` `address2_city` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `address2_state` `address2_state` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , CHANGE `address2_country` `address2_country` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;

INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'option_default_employeeid', 'Default Employee');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'taxid', 'Tax ID');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'manual_option', 'Manual Option');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'manual_amount', 'Manual Amount');


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20090817' WHERE name='SCHEMA_VERSION' LIMIT 1;



