--
-- AMBERDMS BILLING SYSTEM 20130807
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Modifications to include contact login capability to customer portal schema
--

INSERT INTO `config` (`name`, `value`) VALUES ('CUSTOMER_PORTAL_CONTACT_LOGIN', 'disabled');

ALTER TABLE `customer_contacts`
    CHANGE COLUMN `role` `role` VARCHAR(30) NOT NULL,
    ADD COLUMN `portal_password` VARCHAR(255) NULL,
    ADD COLUMN `portal_salt` VARCHAR(20) NULL,
    ADD COLUMN `portal_login_time` BIGINT(20) NULL,
    ADD COLUMN `portal_login_ipaddress` VARCHAR(15) NULL;

--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20130807' WHERE name='SCHEMA_VERSION' LIMIT 1;
