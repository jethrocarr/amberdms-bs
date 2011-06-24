--
-- AMBERDMS BILLING SYSTEM SVN 1209 CHANGES
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0 beta 2 SVN 1203 to 1.5.0 beta 3 SVN 1209
--

INSERT INTO `config` (`name`, `value`) VALUES ('ACCOUNTS_AUTOPAY', '1');

INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'credit_refund_details', 'Refund Details');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'credit_refund_amount', 'Refund Amount');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'id_employee', 'Employee');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'account_asset', 'Asset Account');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'account_dest', 'Dest Account');

INSERT INTO  `menu` (`id` , `priority` , `parent` , `topic` , `link` , `permid` ) VALUES ( NULL ,  '211',  'View Customers',  '',  'customers/credit-refund.php',  '4');


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20110624' WHERE name='SCHEMA_VERSION' LIMIT 1;


