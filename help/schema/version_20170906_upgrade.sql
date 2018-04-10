--
-- AMBERDMS BILLING SYSTEM 20170906
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes for latest version of Amberphplib
--
ALTER TABLE `projects` ADD COLUMN `project_quote` VARCHAR(50) NOT NULL;

INSERT INTO `config` (`name`, `value`) VALUES ('COMPANY_B2C_TERMS', ''),('COMPANY_B2B_TERMS','');

INSERT INTO `templates` VALUES (11,1,'quotes_invoice_htmltopdf','templates/quotes/quotes_english_xhtml_UK','English Basic UK (XHTML)','Basic English language quotes, includes contact details for customer, company, tax numbers, quotes items and payment details.');

--
-- Missing translation labels
--
UPDATE `language` SET `label`='config_company_registration',`translation`='Company Registration Details' WHERE `label`='config_company_invoices';
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (590,'en_us', 'config_company_terms', 'Terms of Business'),(591,'en_us','terms_of_business','Terms of Business'),(592,'en_us','terms_business','Business to Business'),(593,'en_us','terms_consumer','Business to Consumer'),(594,'en_us','terms_none','None'),(595,'en_us','project_quote','Associated Quote ID');

--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20170906' WHERE name='SCHEMA_VERSION' LIMIT 1;


