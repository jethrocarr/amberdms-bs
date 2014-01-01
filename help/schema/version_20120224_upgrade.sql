--
-- AMBERDMS BILLING SYSTEM SVN 1288
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0 beta SVN 1247 to 1.5.0 beta 9 SVN 1287
--

ALTER TABLE `service_usage_records` CHANGE `price` `price` DECIMAL( 11, 4 ) NOT NULL ;
ALTER TABLE `customers` ADD `reseller_customer` VARCHAR( 32 ) NOT NULL DEFAULT 'standard', ADD `reseller_id` INT( 11 ) NULL DEFAULT NULL;
ALTER TABLE `customers` ADD INDEX ( `reseller_id` ) ;

INSERT INTO `config` (`name`, `value`) VALUES ('SERVICE_CDR_EXPORT_FORMAT', 'csv_padded');

INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES (NULL , '211', 'View Customers', '', 'customers/reseller.php', '4');

INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'billing_cdr_csv_output_help', 'Check to attach CDR CSV output at time of billing');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'billing_cdr_csv_output', 'CDR CSV Output');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'No Customer of Reseller avaliable.', 'No Resellers available\r\n');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'customer_of_reseller', 'Customer of Reseller');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'reseller', 'Reseller');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'standalone', 'Standalone');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'reseller_id', 'Customer of Reseller');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'reseller_customer', 'Customer type');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'reseller_options', 'Reseller options');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'reseller_customer_help', 'This customer is a reseller');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'contact_mobile', 'Contact Mobile');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'customer_reseller', 'Customer\'s Reseller/Parent');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'service_price_monthly', 'Monthly Service Charges');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'service_price_yearly', 'Yearly Service Charges');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'filter_show_prices_with_discount', 'Display prices with discounts applied.');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'billing_cycles', 'Billing Cycles');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'price_monthly', 'Monthly Service Charges');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'price_yearly', 'Yearly Service Charges');


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20120224' WHERE name='SCHEMA_VERSION' LIMIT 1;


