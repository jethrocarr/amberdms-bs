--
-- AMBERDMS BILLING SYSTEM SVN 1009 CHANGES
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0 beta 1 to 1.5.0 beta 1 SVN 1009
--

CREATE TABLE IF NOT EXISTS `attributes_group` (
  `id` int(10) NOT NULL auto_increment,
  `group_name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `attributes` ADD `id_group` INT UNSIGNED NOT NULL AFTER `id_owner` ;

ALTER TABLE `templates` CHANGE `template_type` `template_type` VARCHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

INSERT INTO `templates` (`id`, `active`, `template_type`, `template_file`, `template_name`, `template_description`) VALUES (NULL, 1, 'quotes_invoice_tex', 'templates/quotes/quotes_english_default', 'English Basic (LaTeX)', 'Basic English language quotes, includes contact details for customer, company, tax numbers, quotes items and payment details.');
INSERT INTO `templates` (`id`, `active`, `template_type`, `template_file`, `template_name`, `template_description`) VALUES (NULL, 0, 'quotes_invoice_htmltopdf', 'templates/quotes/quotes_english_xhtml', 'English Basic (XHTML)', 'Basic English language quotes, includes contact details for customer, company, tax numbers, quotes items and payment details.');

INSERT INTO `config` (`name`, `value`) VALUES ('TEMPLATE_INVOICE_EMAIL', 'hi (customer_contact),\r\n\r\nPlease see the attached PDF for invoice (code_invoice) and payment\r\ninformation due on (date_due).\r\n\r\nThank you for your business!\r\n\r\nregards,\r\n(company_name)');
INSERT INTO `config` (`name`, `value`) VALUES ('TEMPLATE_QUOTE_EMAIL', 'Dear (contact_name),\r\n\r\nPlease see the attached PDF for quote (code_quote).\r\n\r\nregards,\r\n(company_name)');


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20100803' WHERE name='SCHEMA_VERSION' LIMIT 1;



