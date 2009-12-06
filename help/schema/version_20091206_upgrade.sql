--
-- AMBERDMS BILLING SYSTEM VERSION 1.4.0 UPGRADES
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Apply Changes
--

DROP TABLE `templates`;

CREATE TABLE `templates` (`id` int(11) NOT NULL auto_increment, `active` tinyint(1) NOT NULL, `template_type` varchar(20) NOT NULL, `template_file` varchar(255) NOT NULL, `template_name` varchar(255) NOT NULL, `template_description` text NOT NULL, PRIMARY KEY  (`id`)) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

INSERT INTO `templates` (`id`, `active`, `template_type`, `template_file`, `template_name`, `template_description`) VALUES(2, 1, 'ar_invoice_tex', 'templates/ar_invoice/ar_invoice_english_default', 'English Basic (Default)', 'Basic English language invoice, includes contact details for customer, company, tax numbers, invoice items and payment details.');
INSERT INTO `templates` (`id`, `active`, `template_type`, `template_file`, `template_name`, `template_description`) VALUES(4, 0, 'ar_invoice_tex', 'templates/ar_invoice/ar_invoice_german_default', 'Deutsch Basic', 'Lokalisierte Version des Standards Amberdms Billing System Rechnung, mit der deutschen Sprache, Absender auf der linken Seite und andere kleinere Optimierungen.');
INSERT INTO `menu` (`id` ,`priority` ,`parent` ,`topic` ,`link` ,`permid`) VALUES (NULL , '908', 'Admin', 'template_selection', 'admin/templates.php', '2');
INSERT INTO `language` (`id` ,`language` ,`label` ,`translation`) VALUES (NULL , 'en_us', 'use_this_template', 'Use This Template');
INSERT INTO `language` (`id` ,`language` ,`label` ,`translation`) VALUES (NULL , 'en_us', 'template_selection', 'Template Selection');

ALTER TABLE `customers` CHANGE `address1_zipcode` `address1_zipcode` VARCHAR( 10 ) NOT NULL DEFAULT '0';
ALTER TABLE `customers` CHANGE `address2_zipcode` `address2_zipcode` VARCHAR( 10 ) NOT NULL DEFAULT '0';
ALTER TABLE `vendors` CHANGE `address1_zipcode` `address1_zipcode` VARCHAR( 10 ) NOT NULL DEFAULT '0';
ALTER TABLE `vendors` CHANGE `address2_zipcode` `address2_zipcode` VARCHAR( 10 ) NOT NULL DEFAULT '0';

UPDATE `templates` SET `template_description` = 'Deutschsprachige Version der Standard-PDF-Rechnung. Absender links, Empfänger rechts für Sichtcouvert und weitere kleinere Optimierungen' WHERE `id`=4 LIMIT 1 ;

INSERT INTO `language` (`id` , `language` , `label` ,`translation` ) VALUES (NULL , 'en_us', 'filter_billable_only', 'Billable Only');


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20091206' WHERE name='SCHEMA_VERSION' LIMIT 1;



