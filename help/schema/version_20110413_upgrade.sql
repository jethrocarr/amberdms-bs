--
-- AMBERDMS BILLING SYSTEM SVN 1102 CHANGES
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0 beta 1 SVN 1072 to 1.5.0 beta 1 SVN 1102
--


-- New service fields

ALTER TABLE  `services` ADD  `price_setup` DECIMAL( 11, 2 ) NOT NULL AFTER  `price_extraunits`;
ALTER TABLE  `services` ADD  `upstream_id` INT NOT NULL AFTER  `description`, ADD  `upstream_notes` TEXT NOT NULL AFTER  `upstream_id`;

INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'price_setup', 'Setup Charge');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'service_upstream', 'Upstream Provider Details');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'upstream_help_message', 'Some services are resold services provided by a different provider - for example, a utility service provided by a wholeseller. It can be useful to store notes in a private field below relating to this service, for support and management purposes.');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'upstream_id', 'Upstream Vendor');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'upstream_notes', 'Service Notes');


-- New UI Configuration Options
ALTER TABLE  `service_units` ADD  `active` BOOLEAN NOT NULL AFTER  `numrawunits`;
ALTER TABLE  `service_types` ADD  `active` BOOLEAN NOT NULL AFTER  `description`;
ALTER TABLE  `billing_modes` ADD  `active` BOOLEAN NOT NULL AFTER  `description`;
ALTER TABLE  `billing_cycles` ADD  `active` BOOLEAN NOT NULL AFTER  `description`;

UPDATE `service_units` SET active='1';
UPDATE `billing_modes` SET active='1';
UPDATE `service_types` SET active='1';
UPDATE `billing_cycles` SET active='1';

INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'config_usage_units', 'Data Usage/Traffic Unit Options');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'service_units_enabled', 'Enabled Service Units');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'service_unit_selection_help', 'ABS can handle service usage tracking with a number of different unit types, you can select which ones you wish to offer to administrators here - sometimes it can be useful to remove undesired options to clarify the UI for accounts staff.');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'error_no_units_available', 'There are no unit types enabled that meet the requirements of this service type - see the admin/services page for configuration.');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'config_service_types', 'Service Type Options');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'service_types_enabled', 'Enabled Service Types');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'service_types_selection_help', 'Not all service types are appropriate for all users, you can enable/disable specific service types to simplify the user interface for regular users.');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'error_no_types_available', 'There are no service types enabled!  See the admin/services page for configuration options.');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'error_no_billing_cycles_available', 'There are no billing cycles enabled! See the admin/services page for configuration options');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'config_billing_cycle', 'Billing Cycle Options');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'billing_cycle_enabled', 'Enabled Billing Cycle Options');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'billing_cycle_selection_help', 'Not all billing cycles are appropiate for all businesses, you can enable/disable specific billing cycle types here as per your requirements to simplify user options.');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'config_billing_mode', 'Billing Mode Options');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'billing_mode_enabled', 'Enabled Billing Modes');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'billing_mode_selection_help', 'Depending on your business needs, you may wish to disable some of the billing modes to simplify the UI for your users');



-- IPv4 Data Service

INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'ipv4_details', 'IPv4 Details');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'ipv4_address', 'IPv4 Address');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'ipv4_cidr', 'CIDR/Subnet');


-- Translations/Labels for existing features

INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'customer_contacts', 'Customer Contact Details');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'vendor_contacts', 'Vendor Contact Details');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'rate_table_details', 'Rate Table Details');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'rate_table_items', 'Rate/Prefix/Area Details');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'cdr_upload', 'Rate Table Import');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'cdr_rate_import_file', 'Upload Rates File');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'service_plan_cdr', 'Call Rates & Pricing');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'service_plan_ddi', 'Phone Number/DDI Limits & Rates');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'service_plan_trunks', 'Call Trunking Limits & Rates');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'phone_ddi_included_units', 'Included Phone Numbers');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'phone_ddi_price_extra_units', 'Price per additional phone number');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'phone_trunk_included_units', 'Number of Trunks (concurrent calls)');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'phone_trunk_price_extra_units', 'Price per additional trunk');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'ddi_details', 'Service DDI');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'ddi_start', 'DDI Start Range');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'ddi_finish', 'DDI End Rage');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'service_options_trunks', 'Service Trunk Configuration');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'phone_trunk_included_units', 'Included Trunks');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'phone_trunk_quantity', 'Quantity of Trunks');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'number_src', 'Source Number');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'number_dst', 'Destination Number');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'billable_seconds', 'Billable Seconds');


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20110413' WHERE name='SCHEMA_VERSION' LIMIT 1;


