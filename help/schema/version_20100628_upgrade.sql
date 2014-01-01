--
-- AMBERDMS BILLING SYSTEM 1.5.0 BETA 1 UPGRADES
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0 alpha 6 to 1.5.0 beta 1
--

UPDATE `templates` SET template_type='ar_invoice_tex', template_file='templates/ar_invoice/ar_invoice_english_default', template_name='English Basic (LaTeX)', template_description='Basic English language invoice, includes contact details for customer, company, tax numbers, invoice items and payment details.' WHERE id='2' LIMIT 1;
UPDATE `templates` SET template_type='ar_invoice_tex', template_file='templates/ar_invoice/ar_invoice_german_default', template_name='Deutsch Basic (LaTeX)' WHERE id='4' LIMIT 1;

INSERT INTO `templates` (`id`, `active`, `template_type`, `template_file`, `template_name`, `template_description`) VALUES (NULL, 0, 'ar_invoice_htmltopdf', 'templates/ar_invoice/ar_invoice_htmltopdf_simple', 'English Basic (XHTML)', 'Basic English language invoice, includes contact details for customer, company, tax numbers, invoice items and payment details.');
INSERT INTO `templates` (`id`, `active`, `template_type`, `template_file`, `template_name`, `template_description`) VALUES (NULL, 0, 'ar_invoice_htmltopdf', 'templates/ar_invoice/ar_invoice_htmltopdf_telcostyle', 'Telco Style Invoicing (XHTML)', 'Featured two+ page invoice which has an overview/summary page of the service/product groups, payment information and additional pages containing all the line items, grouped by service/product groups.\r\n\r\nThis invoice is designed for providers such as ISPs, telcos and other service providers.');


INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'cdr_import_cost_price_nothing', 'Do not fetch call costs from import');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'billing_cycle_string', 'Billing Cycle');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'service_price', 'Service Price');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'service_options_ddi', 'Service DDI Configuration');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'phone_ddi_single', 'Phone Number');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'service_migration', 'Service Migration Options');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'migration_date_period_usage_override', 'Migration Date Override Options');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'migration_use_period_date', 'Start charging usage from the first period date.');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'migration_use_usage_date', 'Start charging usage from the specified date, but charge the plan fee from the first period date.');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'migration_date_period_usage_first', 'Usage Start Date');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'address1_same_as_2', 'Shipping Option');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'address1_same_as_2_help', 'Use the billing address as the shipping address');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'id_service_group_usage', 'Service Usage Group');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES(NULL, 'en_us', 'projectid', 'Project');


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20100628' WHERE name='SCHEMA_VERSION' LIMIT 1;



