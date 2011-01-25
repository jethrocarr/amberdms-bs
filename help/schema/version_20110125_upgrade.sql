--
-- AMBERDMS BILLING SYSTEM SVN 1072 CHANGES
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0 beta 1 SVN 1066 to 1.5.0 beta 1 SVN 1072
--


-- New Dependency Page Functionality
--
INSERT INTO `menu` (
`id` ,
`priority` ,
`parent` ,
`topic` ,
`link` ,
`permid`
)
VALUES (NULL , '906', 'Configuration', 'menu_config_dependencies', 'admin/config_dependencies.php', '2');


INSERT INTO `language` (
`id` ,
`language` ,
`label` ,
`translation`
) 
VALUES
(NULL , 'en_us', 'menu_config_dependencies', 'Dependencies'),
(NULL , 'en_us', 'dependency_status', 'Status'),
(NULL , 'en_us', 'dependency_name', 'Name'),
(NULL , 'en_us', 'dependency_location', 'location');




-- New Vendor Contacts Functionality
--

CREATE TABLE `vendor_contacts` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`vendor_id` INT NOT NULL ,
`role` ENUM( 'other', 'accounts' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`contact` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;


CREATE TABLE `vendor_contact_records` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`contact_id` INT NOT NULL ,
`type` ENUM( 'phone', 'email', 'fax', 'mobile' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`label` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`detail` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

INSERT INTO vendor_contacts( id, vendor_id, role, contact, description ) SELECT id, id, "accounts", IF( name_contact != "", name_contact, "accounts" ) , "" FROM vendors;
INSERT INTO vendor_contact_records( contact_id, type , label, detail ) SELECT id, "phone", "phone", contact_phone FROM vendors WHERE contact_phone != "";
INSERT INTO vendor_contact_records( contact_id, type , label, detail ) SELECT id, "email", "email", contact_email FROM vendors WHERE contact_email != "";
INSERT INTO vendor_contact_records( contact_id, type , label, detail ) SELECT id, "fax", "fax", contact_fax FROM vendors WHERE contact_fax != "";

ALTER TABLE `vendors` DROP `contact_email` , DROP `contact_phone` , DROP `contact_fax` , DROP `name_contact` ;



-- New Tax functionality
--
ALTER TABLE `account_taxes` ADD `default_customers` BOOLEAN NOT NULL DEFAULT '0',
ADD `default_vendors` BOOLEAN NOT NULL DEFAULT '0',
ADD `default_services` BOOLEAN NOT NULL DEFAULT '0',
ADD `default_products` BOOLEAN NOT NULL DEFAULT '0';

INSERT INTO `language` (
`id` ,
`language` ,
`label` ,
`translation`
)
VALUES 
(NULL , 'en_us', 'tax_set_default', 'Set Default Taxes'),
(NULL , 'en_us', 'tax_auto_enable', 'Auto Enable Taxes');


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20110125' WHERE name='SCHEMA_VERSION' LIMIT 1;


