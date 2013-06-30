--
-- AMBERDMS BILLING SYSTEM 20130630
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Additions to 2.0.0
--

INSERT INTO services_options (option_type, option_type_id, option_name, option_value) SELECT 'customer', services_customers.id, 'quantity', services_customers.quantity FROM services_customers;
ALTER TABLE `services_customers` DROP `quantity`;


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20130630' WHERE name='SCHEMA_VERSION' LIMIT 1;


