--
-- AMBERDMS BILLING SYSTEM SVN 1150 CHANGES
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0 beta 2 SVN 1138 to 1.5.0 beta 2 SVN 1150
--


-- UI/Translation Changes

INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'invoiced_plan', 'Plan Invoice');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'invoiced_usage', 'Usage Invoice');


-- Usage Structural Changes

ALTER TABLE  `services_customers_periods` ADD  `usage_alerted` DECIMAL( 20, 2 ) NOT NULL;


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20110502' WHERE name='SCHEMA_VERSION' LIMIT 1;


