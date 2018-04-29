--
-- AMBERDMS BILLING SYSTEM 20170828
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;

INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`, `config`) VALUES (243,152,'View Quotes','','accounts/quotes/quotes-convert-project.php',29,'');


--
-- Missing translation labels
--



--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20171126' WHERE name='SCHEMA_VERSION' LIMIT 1;


