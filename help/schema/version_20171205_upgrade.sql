--
-- AMBERDMS BILLING SYSTEM 20170906
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;

-- save current setting of sql_mode
SET @old_sql_mode := @@sql_mode ;

-- derive a new value by removing NO_ZERO_DATE and NO_ZERO_IN_DATE
SET @new_sql_mode := @old_sql_mode ;
SET @new_sql_mode := TRIM(BOTH ',' FROM REPLACE(CONCAT(',',@new_sql_mode,','),',NO_ZERO_DATE,'  ,','));
SET @new_sql_mode := TRIM(BOTH ',' FROM REPLACE(CONCAT(',',@new_sql_mode,','),',NO_ZERO_IN_DATE,',','));
SET @@sql_mode := @new_sql_mode ;
--
-- Changes for latest version of Amberphplib
--
INSERT INTO `config` (`name`, `value`) VALUES ('ACCOUNTS_CANCEL_DELETE', '0');
ALTER TABLE `account_ar` ADD COLUMN `cancelled` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `projects` ADD COLUMN (`dest_account` int(11) NOT NULL DEFAULT '0',`customerid` int(11) NOT NULL DEFAULT '0'); 


--
-- Missing translation labels
--
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (596,'en_us','cancelled','Cancelled'),(597,'en_us','cancel_confirm','Confirm Cancellation'),(598,'en_us','ar_invoice_cancel','Cancel AR Invoice'),(599,'en_us','details','Details'),(600,'en_us','project_financials','Project Financials'),(601,'en_us','closed','Completed'),(602,'en_us','select','Select');
--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20171205' WHERE name='SCHEMA_VERSION' LIMIT 1;

SET @@sql_mode := @old_sql_mode ;

