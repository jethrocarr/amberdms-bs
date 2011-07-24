--
-- AMBERDMS BILLING SYSTEM SVN 1224
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0 beta 4 SVN 1222 to 1.5.0 beta 4 SVN 1224
--

ALTER TABLE  `cdr_rate_tables_values` CHANGE  `rate_price_sale`  `rate_price_sale` DECIMAL( 11, 4 ) NOT NULL, CHANGE  `rate_price_cost`  `rate_price_cost` DECIMAL( 11, 4 ) NOT NULL;

--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20110722' WHERE name='SCHEMA_VERSION' LIMIT 1;


