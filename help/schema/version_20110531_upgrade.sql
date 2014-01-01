--
-- AMBERDMS BILLING SYSTEM SVN 1182 CHANGES
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0 beta 2 SVN 1171 to 1.5.0 beta 2 SVN 1182
--


-- New Configuration Options


INSERT INTO `config` (`name`, `value`) VALUES ('SERVICE_CDR_BILLSELF', 'local');


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20110531' WHERE name='SCHEMA_VERSION' LIMIT 1;


