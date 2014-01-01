--
-- AMBERDMS BILLING SYSTEM SVN 1102 CHANGES
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0 beta 1 SVN 1102 to 1.5.0 beta 1 SVN 1115
--


-- New Customer Orders Feature


CREATE TABLE  `customers_orders` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`id_customer` INT UNSIGNED NOT NULL ,
`type` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`customid` INT UNSIGNED NOT NULL ,
`quantity` FLOAT NOT NULL ,
`units` VARCHAR( 10 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`amount` DECIMAL( 11, 2 ) NOT NULL ,
`price` DECIMAL( 11, 2 ) NOT NULL ,
`description` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
) ENGINE = INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE  `customers_orders` ADD  `date_ordered` DATE NOT NULL AFTER  `id_customer`;
ALTER TABLE  `customers_orders` ADD  `discount` INT( 3 ) UNSIGNED NOT NULL AFTER  `price`;

INSERT INTO `config` (`name`, `value`) VALUES ('ORDERS_BILL_ONSERVICE', '1');
INSERT INTO `config` (`name`, `value`) VALUES ('ORDERS_BILL_ENDOFMONTH', '1');

INSERT INTO `permissions` (`id`, `group_id`, `value`, `description`) VALUES (NULL, '4', 'customers_orders', 'Allow the user to place or adjust customer orders');

INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'config_orders', 'Customer Order Configuration');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'date_ordered', 'Date Ordered');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'order_basic', 'Order Details');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'order_product', 'Order Product Item');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'type', 'Type');

INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES (NULL, '211', 'View Customers', '', 'customers/orders.php', '3');
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES (NULL, '211', 'View Customers', '', 'customers/orders-view.php', '3');

--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20110420' WHERE name='SCHEMA_VERSION' LIMIT 1;


