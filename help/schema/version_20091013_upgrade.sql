--
-- AMBERDMS BILLING SYSTEM VERSION 1.3.0 UPGRADES
--
-- There are a substantial number of database changes in this release to upgrade
-- the database to InnoDB and also UTF8.
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Apply Changes
--

DELETE FROM `menu` WHERE `priority`='122';

INSERT INTO `menu` (`priority`, `parent`, `topic`, `link`, `permid`) VALUES
(122, 'Accounts Receivables', 'Add Invoice', 'accounts/ar/invoice-add.php', 21),
(122, 'View Invoices', '', 'accounts/ar/invoice-delete.php', 21);

INSERT INTO `menu` (`id` ,`priority` ,`parent` ,`topic` ,`link` ,`permid`) VALUES (NULL , '950', 'Admin', 'Database Backup', 'admin/db_backup.php', '2');

INSERT INTO `language` (`id` ,`language` ,`label` ,`translation`)VALUES (NULL , 'en_us', 'patch_contents', 'Patch Contents'), (NULL , 'en_us', 'patch_submit', 'Submit Patch');
INSERT INTO `language` (`id` ,`language` ,`label` ,`translation`)VALUES (NULL , 'en_us', 'patch_submit_contact', 'Author''s Email'), (NULL , 'en_us', 'patch_submit_credit','Developer to credit');
INSERT INTO `language` (`id` ,`language` ,`label` ,`translation`)VALUES (NULL , 'en_us', 'patch_description', 'Patch Description');

UPDATE config SET value='opensource' WHERE name='SUBSCRIPTION_SUPPORT';
UPDATE config SET value='' WHERE name='SUBSCRIPTION_ID';

INSERT INTO `config` (`name` ,`value`) VALUES ('PATH_TMPDIR', '/tmp');
INSERT INTO `config` (`name`, `value`) VALUES ('APP_MYSQL_DUMP', '/usr/bin/mysqldump');
INSERT INTO `config` (`name` ,`value`) VALUES ('PHONE_HOME', 'enabled');
INSERT INTO `config` (`name`, `value`) VALUES ('PHONE_HOME_TIMER', '0');


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20091013' WHERE name='SCHEMA_VERSION' LIMIT 1;



