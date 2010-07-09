--
-- AMBERDMS BILLING SYSTEM SVN 981 UPGRADES
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0 beta 1 to 1.5.0 beta 1 SVN 981
--

DELETE FROM `themes` WHERE theme_name='web2.0' LIMIT 1;
DELETE FROM `themes` WHERE theme_name='classic' LIMIT 1;

INSERT INTO `themes` (`id`, `theme_name`, `theme_creator`) VALUES (NULL, 'web2.0', 'amberdms');
INSERT INTO `themes` (`id`, `theme_name`, `theme_creator`) VALUES (NULL, 'classic', 'amberdms');


DROP TABLE IF EXISTS `attributes`;
CREATE TABLE `attributes` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_owner` int(10) unsigned NOT NULL,
  `type` varchar(10) NOT NULL,
  `key` varchar(80) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'tbl_lnk_attributes', 'attributes');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'attribute_key', 'Attribute Key');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'attribute_value', 'Attribute Value');


INSERT INTO `menu` (`id` , `priority` , `parent` , `topic` , `link` , `permid` ) VALUES ( NULL , '211', 'View Customers', '', 'customers/attributes.php', '3' );



--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20100709' WHERE name='SCHEMA_VERSION' LIMIT 1;



