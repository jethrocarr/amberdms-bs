--
-- AMBERDMS BILLING SYSTEM SVN 1034 CHANGES
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0 beta 1 SVN 1009 to 1.5.0 beta 1 SVN 1034
--

INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES (NULL, '123', 'Accounts Receivables', 'Bulk Payment', 'accounts/ar/invoice-bulk-payments.php', '21');
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES (NULL, '133', 'Accounts Payable', 'Bulk Payment', 'accounts/ap/invoice-bulk-payments.php', '25');


INSERT INTO `config` (`name`, `value`) VALUES ('CURRENCY_DEFAULT_THOUSANDS_SEPARATOR', ',');
INSERT INTO `config` (`name`, `value`) VALUES ('CURRENCY_DEFAULT_DECIMAL_SEPARATOR', '.');


INSERT INTO `billing_cycles` (`id`, `name`, `description`) VALUES (NULL, 'quarterly', 'Bill the customer every quarter since the start date.');
INSERT INTO `billing_cycles` (`id`, `name`, `description`) VALUES (NULL, 'weekly', 'Bill the customer every week since the start date.');
INSERT INTO `billing_cycles` (`id`, `name`, `description`) VALUES  (NULL, 'fortnightly', 'Bill the customer every two weeks since the start date.');

ALTER TABLE `billing_cycles` ADD `priority` INT NOT NULL AFTER `name` ;

UPDATE `billing_cycles` SET `priority` = '31' WHERE `id` =1;
UPDATE `billing_cycles` SET `priority` = '186' WHERE `id` =2;
UPDATE `billing_cycles` SET `priority` = '365' WHERE `id` =3;
UPDATE `billing_cycles` SET `priority` = '93' WHERE `id` =4;
UPDATE `billing_cycles` SET `priority` = '7' WHERE `id` =5;
UPDATE `billing_cycles` SET `priority` = '14' WHERE `id` =6;


INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'filter_invoice_notes_search', 'Invoice Notes Search');

--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20100830' WHERE name='SCHEMA_VERSION' LIMIT 1;



