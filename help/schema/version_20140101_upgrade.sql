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

ALTER TABLE `users_sessions` CHANGE `ipaddress` `ipv6` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `users_sessions` ADD `ipv4` VARCHAR( 15 ) NOT NULL AFTER `authkey`;
ALTER TABLE `menu` ADD `config` VARCHAR( 255 ) NOT NULL ;


--
-- Missing translation labels
--

INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'KEY', 'TRANS');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'product_group_members', 'Members of Product Group');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'product_group_delete', 'Delete Product Group');


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20140101' WHERE name='SCHEMA_VERSION' LIMIT 1;


