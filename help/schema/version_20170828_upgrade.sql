--
-- AMBERDMS BILLING SYSTEM 20170828
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;

UPDATE menu SET priority=124 WHERE id=221 LIMIT 1;


--
-- Missing translation labels
--



--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20170828' WHERE name='SCHEMA_VERSION' LIMIT 1;


