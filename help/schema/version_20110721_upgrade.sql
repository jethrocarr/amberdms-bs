--
-- AMBERDMS BILLING SYSTEM SVN 1209 CHANGES
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0 beta 3 SVN 1209 to 1.5.0 beta 4 SVN 1222
--

INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'service_options_data_traffic', 'Service Data Traffic Options');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'service_parent', 'Parent Service');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'date_period_last', 'Last Service Period');

ALTER TABLE  `services_customers` ADD  `date_period_last` DATE NOT NULL DEFAULT  '0000-00-00' AFTER  `date_period_next`;
ALTER TABLE  `services_customers_periods` ADD  `rebill` BOOLEAN NOT NULL AFTER  `invoiceid_usage`;


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20110721' WHERE name='SCHEMA_VERSION' LIMIT 1;


