--
-- AMBERDMS BILLING SYSTEM SVN 1299
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0 beta SVN 1287 to 1.5.0 beta 10 SVN 1299
--

INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'billing_direct_debit', 'Direct debit account');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'billing_method', 'Billing method');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'billing_options', 'Billing options');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'balance_owed', 'Balance');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'amount_owing', 'Amount Owed');

ALTER TABLE `customers` ADD `billing_method` VARCHAR( 12 ) NOT NULL DEFAULT 'manual' AFTER `reseller_id`;
ALTER TABLE `customers` ADD `billing_direct_debit` VARCHAR( 32 ) NULL AFTER `billing_method`;

INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES (NULL, 230, 'Customers', 'Billing Export', 'customers/customers-billing.php', 3);


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20120723' WHERE name='SCHEMA_VERSION' LIMIT 1;


