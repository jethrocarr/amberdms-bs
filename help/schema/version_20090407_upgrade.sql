
--
-- AMBERDMS BILLING SYSTEM VERSION 1.2.0 UPGRADES
--

INSERT INTO `language` ( `id` , `language` , `label` , `translation` ) VALUES (NULL , 'en_us', 'discount', 'Discount');
INSERT INTO `language` ( `id` , `language` , `label` , `translation` ) VALUES (NULL , 'en_us', 'customer_purchase', 'Customer Purchase Options');
INSERT INTO `language` ( `id` , `language` , `label` , `translation` ) VALUES (NULL , 'en_us', 'vendor_purchase', 'Vendor Purchase Options');

ALTER TABLE `customers` ADD `discount` FLOAT NOT NULL ;
ALTER TABLE `vendors` ADD `discount` FLOAT NOT NULL ;
ALTER TABLE `products` ADD `discount` FLOAT NOT NULL ;


UPDATE `language` SET label='quote_convert_financials' WHERE label='quote_invoice_financials' LIMIT 1;
INSERT INTO `language` ( `id` , `language` , `label` , `translation` ) VALUES (NULL , 'en_us', 'service_options_licenses', 'Service Options');


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20090407' WHERE name='SCHEMA_VERSION' LIMIT 1;


