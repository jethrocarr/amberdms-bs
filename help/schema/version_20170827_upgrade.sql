--
-- AMBERDMS BILLING SYSTEM 20140101
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes for latest version of Amberphplib
--
ALTER TABLE `account_quotes` ADD COLUMN `terms_of_business` VARCHAR(20) NOT NULL DEFAULT 'terms_none';

INSERT INTO `config` (`name`, `value`) VALUES ('COMPANY_REG_NUMBER', '123456789'),('COMPANY_TAX_NUMBER','987654321'),('COMPANY_ADDRESS2_CITY','Example City'),('COMPANY_ADDRESS2_COUNTRY','Example Country'),('COMPANY_ADDRESS2_STATE',''),('COMPANY_ADDRESS2_STREET','54a Stallman Lane\r\nFreeburbs'),('COMPANY_ADDRESS2_ZIPCODE','0000');

INSERT INTO `templates` (`id`,`active`, `template_type`, `template_file`, `template_name`, `template_description`) VALUES ('10','1','ar_invoice_htmltopdf','templates/ar_invoice/ar_invoice_htmltopdf_UK','UK Basic with Company Registration (XHTML)','Basic English template with company registration details and tax information added.');
--
-- Missing translation labels
--



--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20170827' WHERE name='SCHEMA_VERSION' LIMIT 1;


