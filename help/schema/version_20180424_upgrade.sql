--
-- AMBERDMS BILLING SYSTEM 20170828
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;

INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`, `config`) VALUES (244,313,'View Vendors','',"vendors/credit.php",5,'');
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`, `config`) VALUES (245, 133, 'Accounts Payable', 'menu_credit_notes_ap', 'accounts/ap/ap-credits.php', 20, ''),(246, 134, 'menu_credit_notes_ap', 'menu_credit_notes_view_ap', 'accounts/ap/ap-credits.php', 20, ''),(247, 135, 'menu_credit_notes_ap', 'menu_credit_notes_add_ap', 'accounts/ap/credit-add.php', 21, ''),(248, 135, 'menu_credit_notes_view_ap', '', 'accounts/ap/ap-credits.php', 20, ''),(249, 135, 'menu_credit_notes_view_ap', '', 'accounts/ap/credit-view.php', 20, ''),(250, 135, 'menu_credit_notes_view_ap', '', 'accounts/ap/credit-items.php', 20, ''),(251, 135, 'menu_credit_notes_view_ap', '', 'accounts/ap/credit-items-edit.php', 21, ''),(252, 135, 'menu_credit_notes_view_ap', '', 'accounts/ap/credit-payments.php', 20, ''),(253, 135, 'menu_credit_notes_view_ap', '', 'accounts/ap/credit-payments-edit.php', 21, ''),(254, 135, 'menu_credit_notes_view_ap', '', 'accounts/ap/credit-journal.php', 20, ''),(255, 135, 'menu_credit_notes_view_ap', '', 'accounts/ap/credit-journal-edit.php', 20, ''),(256, 135, 'menu_credit_notes_view_ap', '', 'accounts/ap/credit-delete.php', 21, ''),(257, 313, 'View Vendors', '', 'vendors/credit-refund.php', 5, '');
--
-- Missing translation labels
--
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (603,'en_us','ap_credit_details','Credit Note Details'),(604,'en_us','ap_credit_financials','Credit Note Financials'),(605,'en_us','ap_credit_other','Other Credit Note Details'),(606,'en_us','menu_credit_notes_ap','Credit Notes'),(607,'en_us','menu_credit_notes_view_ap','View Credit Note'),(608,'en_us','menu_credit_notes_add_ap','Add Credit Note'),(609,'en_us','ap_credit_invoice_item','Credit Item'),(610,'en_us','ap_credit_invoice_item_tax','Credited Tax'),(611,'en_us','ap_credit_delete','Delete Credit Note'),(612,'en_us','invoiceid','Invoice'),('613', 'en_us', 'creditnote', 'Credit Note'),('614', 'en_us', 'refund', 'Refund'),('615', 'en_us', 'payment', 'Payment');


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20180424' WHERE name='SCHEMA_VERSION' LIMIT 1;


