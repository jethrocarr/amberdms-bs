--
-- AMBERDMS BILLING SYSTEM SVN 1172 CHANGES
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0 beta 2 SVN 1154 to 1.5.0 beta 2 SVN 1172
--


-- New Configuration Options


INSERT INTO `config` (`name`, `value`) VALUES ('SESSION_TIMEOUT', '7200');
INSERT INTO `config` (`name`, `value`) VALUES ('AUTH_PERMS_CACHE', 'disabled');
INSERT INTO `config` (`name`, `value`) SELECT "ACCOUNTS_EMAIL_ADDRESS" as name, value FROM config WHERE name='COMPANY_CONTACT_EMAIL';
INSERT INTO `config` (`name`, `value`) VALUES ('ACCOUNTS_INVOICE_BATCHREPORT', 'enabled');
INSERT INTO `config` (`name`, `value`) VALUES ('ACCOUNTS_EMAIL_AUTOBCC', 'enabled');

INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'config_accounts_email', 'Accounts Email Options');


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20110519' WHERE name='SCHEMA_VERSION' LIMIT 1;


