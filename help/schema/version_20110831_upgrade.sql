--
-- AMBERDMS BILLING SYSTEM SVN 1231
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0 beta 5 SVN 1224 to 1.5.0 beta 4 SVN 1231
--

INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'help_accounts_services_dateshift', 'Number of days to backdate an invoice to align with an end of calender month date.');
INSERT INTO `config` (`name`, `value`) VALUES ('ACCOUNTS_SERVICES_DATESHIFT', '1');


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20110831' WHERE name='SCHEMA_VERSION' LIMIT 1;


