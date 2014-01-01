--
-- AMBERDMS BILLING SYSTEM SVN 1066 CHANGES
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0 beta 1 SVN 1034 to 1.5.0 beta 1 SVN 1066
--



--
-- IPv6 Compatibility Changes
--

ALTER TABLE `users_sessions` CHANGE `ipaddress` `ipaddress` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `users_blacklist` CHANGE `ipaddress` `ipaddress` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `users` CHANGE `ipaddress` `ipaddress` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;


--
-- Translation Tools
--

DELETE FROM `permissions` WHERE `permissions`.`id` =38;
DELETE FROM `permissions` WHERE `permissions`.`id` =39;

INSERT INTO `permissions` (`id`, `value`, `description`) VALUES(38, 'devel_translate', 'Allows the user to enter new translations and edit translations that have already been provided.');

ALTER TABLE `language_avaliable` CHANGE `name` `name` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
INSERT INTO `language_avaliable` (`id`, `name`) VALUES ('2', 'custom');



--
-- New Account Statement Report Page
--

INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES (NULL , '123', 'Accounts Receivables', 'Account Statements', 'accounts/ar/account-statements.php', '20');


INSERT INTO `config` (`name`, `value`) VALUES ('TEMPLATE_INVOICE_REMINDER_EMAIL', 'hi (customer_contact),

Payment for invoice (code_invoice) was due on (date_due) and is now (days_overdue) days overdue.

Please see the attached PDF for invoice (code_invoice) and payment information.

Thank you for your business!

regards,
(company_name)');





--
-- New Permission Groups Page
-- 

CREATE TABLE IF NOT EXISTS `permissions_groups` (
 `id` int(11) NOT NULL auto_increment,
 `priority` int(11) NOT NULL,
 `group_name` varchar(255) character set utf8 NOT NULL,
 PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1 ;


ALTER TABLE `permissions` ADD `group_id` INT NOT NULL AFTER `id` ;

INSERT INTO `permissions_groups` (`id`, `priority`, `group_name`) VALUES
(1, 100, 'general'),
(3, 400, 'accounts'),
(4, 200, 'customers'),
(5, 500, 'products'),
(6, 700, 'projects'),
(7, 600, 'services'),
(8, 800, 'human_resources'),
(9, 900, 'support'),
(10, 1000, 'timekeeping'),
(11, 2000, 'development'),
(12, 300, 'vendors'),
(13, 1100, 'api');


UPDATE `permissions` SET `group_id` = '1' WHERE `permissions`.`id` = 1;
UPDATE `permissions` SET `group_id` = '1' WHERE `permissions`.`id` = 2;
UPDATE `permissions` SET `group_id` = '3' WHERE `permissions`.`id` = 3;
UPDATE `permissions` SET `group_id` = '3' WHERE `permissions`.`id` = 4;
UPDATE `permissions` SET `group_id` = '12' WHERE `permissions`.`id` = 5;
UPDATE `permissions` SET `group_id` = '12' WHERE `permissions`.`id` = 6;
UPDATE `permissions` SET `group_id` = '8' WHERE `permissions`.`id` = 7;
UPDATE `permissions` SET `group_id` = '8' WHERE `permissions`.`id` = 8;
UPDATE `permissions` SET `group_id` = '9' WHERE `permissions`.`id` = 9;
UPDATE `permissions` SET `group_id` = '9' WHERE `permissions`.`id` = 10;
UPDATE `permissions` SET `group_id` = '5' WHERE `permissions`.`id` = 11;
UPDATE `permissions` SET `group_id` = '5' WHERE `permissions`.`id` = 12;
UPDATE `permissions` SET `group_id` = '7' WHERE `permissions`.`id` = 13;
UPDATE `permissions` SET `group_id` = '7' WHERE `permissions`.`id` = 14;
UPDATE `permissions` SET `group_id` = '6' WHERE `permissions`.`id` = 15;
UPDATE `permissions` SET `group_id` = '6' WHERE `permissions`.`id` = 16;
UPDATE `permissions` SET `group_id` = '10' WHERE `permissions`.`id` = 17;
UPDATE `permissions` SET `group_id` = '3' WHERE `permissions`.`id` = 18;
UPDATE `permissions` SET `group_id` = '3' WHERE `permissions`.`id` = 19;
UPDATE `permissions` SET `group_id` = '3' WHERE `permissions`.`id` = 20;
UPDATE `permissions` SET `group_id` = '3' WHERE `permissions`.`id` = 21;
UPDATE `permissions` SET `group_id` = '3' WHERE `permissions`.`id` = 22;
UPDATE `permissions` SET `group_id` = '3' WHERE `permissions`.`id` = 23;
UPDATE `permissions` SET `group_id` = '3' WHERE `permissions`.`id` = 24;
UPDATE `permissions` SET `group_id` = '3' WHERE `permissions`.`id` = 25;
UPDATE `permissions` SET `group_id` = '3' WHERE `permissions`.`id` = 26;
UPDATE `permissions` SET `group_id` = '3' WHERE `permissions`.`id` = 27;
UPDATE `permissions` SET `group_id` = '3' WHERE `permissions`.`id` = 28;
UPDATE `permissions` SET `group_id` = '3' WHERE `permissions`.`id` = 29;
UPDATE `permissions` SET `group_id` = '3' WHERE `permissions`.`id` = 30;
UPDATE `permissions` SET `group_id` = '3' WHERE `permissions`.`id` = 35;
UPDATE `permissions` SET `group_id` = '4' WHERE `permissions`.`id` = 37;
UPDATE `permissions` SET `group_id` = '13' WHERE `permissions`.`id` = 36;
UPDATE `permissions` SET `group_id` = '4' WHERE `permissions`.`id` = 3;
UPDATE `permissions` SET `group_id` = '4' WHERE `permissions`.`id` = 4;
UPDATE `permissions` SET `group_id` = '6' WHERE `permissions`.`id` = 32;
UPDATE `permissions` SET `group_id` = '7' WHERE `permissions`.`id` = 31;
UPDATE `permissions` SET `group_id` = '10' WHERE `permissions`.`id` = 33;
UPDATE `permissions` SET `group_id` = '10' WHERE `permissions`.`id` = 34;
UPDATE `permissions` SET `group_id` = '11' WHERE `permissions`.`id` = 38;




--
-- New Contacts Page
--
-- We have to port all the contact information to the new structure before dropping the contacts.
--

CREATE TABLE `customer_contacts` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`customer_id` INT NOT NULL ,
`role` ENUM( 'other', 'accounts' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`contact` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;


CREATE TABLE `customer_contact_records` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`contact_id` INT NOT NULL ,
`type` ENUM( 'phone', 'email', 'fax', 'mobile' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`label` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`detail` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;


INSERT INTO customer_contacts( id, customer_id, role, contact, description ) SELECT id, id, "accounts", IF( name_contact != "", name_contact, "accounts" ) , "" FROM customers;
INSERT INTO customer_contact_records( contact_id, type , label, detail ) SELECT id, "phone", "phone", contact_phone FROM customers WHERE contact_phone != "";
INSERT INTO customer_contact_records( contact_id, type , label, detail ) SELECT id, "email", "email", contact_email FROM customers WHERE contact_email != "";
INSERT INTO customer_contact_records( contact_id, type , label, detail ) SELECT id, "fax", "fax", contact_fax FROM customers WHERE contact_fax != "";

ALTER TABLE `customers` DROP `contact_email` , DROP `contact_phone` , DROP `contact_fax` , DROP `name_contact` ;



--
-- New Language Fields
--

INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'option_translation', 'Translation Mode');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'show_only_non-translated_fields', 'Show only non-translated fields');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'show_all_translatable_fields', 'Show all translatable fields');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'trans_form_title', 'Translation Utility');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'trans_form_desc', 'Use this utility to translate the application into your native language, by entering the label below followed by the native language version.');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'translate', 'Translate');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'trans_label', 'Label');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'trans_translation', 'Translation');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'config_usage_traffic', 'Data Usage/Traffic Configuration');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'Save Changes', 'Save Changes');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'config_usage_cdr', 'Call Record Database Configuration');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'config_migration', 'Service Migration Options');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'date_sent', 'Date Sent');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'days_overdue', 'Days Overdue');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'send_reminder', 'Send Reminder?');


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20101006' WHERE name='SCHEMA_VERSION' LIMIT 1;


