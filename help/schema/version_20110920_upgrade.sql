--
-- AMBERDMS BILLING SYSTEM SVN 1247
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0 beta 5 SVN 1231 to 1.5.0 beta 5 SVN 1247
--


--
-- Support new traffic types
--

CREATE TABLE IF NOT EXISTS `traffic_types` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `type_name` varchar(255) NOT NULL,
  `type_description` text NOT NULL,
  `type_label` varchar(20) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;


INSERT INTO `traffic_types` (`id`, `type_name`, `type_description`, `type_label`) VALUES(1, 'Any', 'Default region configured for all services - any unmatched traffic types will go against this.', '*');



--
-- Support for multiple traffic caps
--

CREATE TABLE IF NOT EXISTS `traffic_caps` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_service` int(10) unsigned NOT NULL,
  `id_traffic_type` int(10) unsigned NOT NULL,
  `mode` varchar(10) NOT NULL,
  `units_price` decimal(11,2) NOT NULL,
  `units_included` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(NULL, 529, 'Services', 'menu_service_traffic_types', 'services/traffic-types.php', 13);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(NULL, 530, 'menu_service_traffic_types', 'menu_service_traffic_types_view', 'services/traffic-types.php', 13);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(NULL, 530, 'menu_service_traffic_types', 'menu_service_traffic_types_add', 'services/traffic-types-add.php', 14);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(NULL, 531, 'menu_service_traffic_types_view', '', 'services/traffic-types-view.php', 13);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(NULL, 531, 'menu_service_traffic_types_view', '', 'services/traffic-types-delete.php', 14);


INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'menu_service_traffic_types', 'Traffic Types');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'menu_service_traffic_types_add', 'Add Traffic Type');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'menu_service_traffic_types_view', 'View Traffic Types');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'type_name', 'Name');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'type_label', 'Label/ID Name');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'type_description', 'Description');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'traffic_type_add', 'Define Traffic Type');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'traffic_type_view', 'Traffic Type Details');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'traffic_type_delete', 'Delete Traffic Type');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'header_traffic_cap_active', 'Active');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'header_traffic_cap_name', 'Traffic Type');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'header_traffic_cap_mode', 'Traffic Mode');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'header_traffic_units_included', 'Included Units');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'header_traffic_units_price', 'Additional Unit Cost');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'traffic_caps', 'Service Plan Traffic Caps/Options');


INSERT INTO traffic_caps (id_service, id_traffic_type, mode, units_price, units_included) SELECT id, '1', 'capped', price_extraunits, included_units FROM services LEFT JOIN service_types ON service_types.id = services.typeid WHERE service_types.name='data_traffic';

UPDATE services LEFT JOIN service_types ON service_types.id = services.typeid SET price_extraunits='0', included_units='0' WHERE service_types.name='data_traffic';


--
-- Traffic cap override formatting ports.
-- 

UPDATE `services_options` SET option_name='cap_units_included_1' WHERE option_name='included_units';
UPDATE `services_options` SET option_name='cap_units_price_1' WHERE option_name='price_extraunits';



--
-- Upgraded usage notification handling code
--

CREATE TABLE `service_usage_alerts` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`id_service_customer` INT UNSIGNED NOT NULL ,
`id_service_period` INT UNSIGNED NOT NULL ,
`id_type` INT UNSIGNED NOT NULL ,
`date_sent` DATE NOT NULL ,
`date_update` DATE NOT NULL ,
`usage_current` DECIMAL( 20, 2 ) UNSIGNED NOT NULL ,
`usage_alerted` DECIMAL( 20, 2 ) UNSIGNED NOT NULL
) ENGINE = INNODB;


INSERT INTO `config` (`name`, `value`) VALUES ('SERVICES_USAGEALERTS_ENABLE', '1');

INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'config_services_email', 'Service Email Options');




--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20110920' WHERE name='SCHEMA_VERSION' LIMIT 1;


