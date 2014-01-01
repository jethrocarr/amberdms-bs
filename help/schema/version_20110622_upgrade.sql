--
-- AMBERDMS BILLING SYSTEM SVN 1203 CHANGES
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0 beta 2 SVN 1192 to 1.5.0 beta 2 SVN 1203
--

DROP TABLE `account_credit`;

CREATE TABLE IF NOT EXISTS `customers_credits` (`id` int(10) unsigned NOT NULL auto_increment, `date_trans` date NOT NULL, `type` varchar(10) NOT NULL, `amount_total` decimal(11,2) NOT NULL, `id_custom` int(10) unsigned NOT NULL, `id_employee` int(10) unsigned NOT NULL, `id_customer` int(10) unsigned NOT NULL, `description` varchar(255) NOT NULL, PRIMARY KEY  (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
CREATE TABLE IF NOT EXISTS `vendors_credits` (`id` int(10) unsigned NOT NULL auto_increment, `date_trans` date NOT NULL, `type` varchar(10) NOT NULL, `amount_total` decimal(11,2) NOT NULL, `id_custom` int(10) unsigned NOT NULL, `id_employee` int(10) unsigned NOT NULL, `id_vendor` int(10) unsigned NOT NULL, `description` varchar(255) NOT NULL, PRIMARY KEY  (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
CREATE TABLE IF NOT EXISTS `account_ar_credit` (`id` int(11) NOT NULL auto_increment, `locked` tinyint(1) NOT NULL default '0', `customerid` int(11) NOT NULL default '0', `invoiceid` int(10) unsigned NOT NULL, `employeeid` int(11) NOT NULL default '0', `dest_account` int(11) NOT NULL default '0', `code_credit` varchar(255) NOT NULL, `code_ordernumber` varchar(255) NOT NULL, `code_ponumber` varchar(255) NOT NULL, `date_trans` date NOT NULL default '0000-00-00', `date_create` date NOT NULL default '0000-00-00', `date_sent` date NOT NULL default '0000-00-00', `sentmethod` varchar(10) NOT NULL, `amount_total` decimal(11,2) NOT NULL default '0.00', `amount_tax` decimal(11,2) NOT NULL default '0.00', `amount` decimal(11,2) NOT NULL default '0.00', `notes` text NOT NULL, PRIMARY KEY  (`id`)) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
CREATE TABLE IF NOT EXISTS `account_ap_credit` (`id` int(11) NOT NULL auto_increment, `locked` tinyint(1) NOT NULL default '0', `vendorid` int(11) NOT NULL default '0', `invoiceid` int(10) unsigned NOT NULL, `employeeid` int(11) NOT NULL default '0', `dest_account` int(11) NOT NULL default '0', `code_credit` varchar(255) NOT NULL, `code_ordernumber` varchar(255) NOT NULL, `code_ponumber` varchar(255) NOT NULL, `date_trans` date NOT NULL default '0000-00-00', `date_create` date NOT NULL default '0000-00-00', `amount_total` decimal(11,2) NOT NULL default '0.00', `amount_tax` decimal(11,2) NOT NULL default '0.00', `amount` decimal(11,2) NOT NULL default '0.00', `notes` text NOT NULL,  PRIMARY KEY  (`id`)) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE  `account_items` CHANGE  `invoicetype`  `invoicetype` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE  `account_trans` CHANGE  `type`  `type` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

INSERT INTO `config` (`name`, `value`) VALUES ('ACCOUNTS_CREDIT_NUM', 'CR100');

INSERT INTO `templates` (`id`, `active`, `template_type`, `template_file`, `template_name`, `template_description`) VALUES (NULL, '1', 'ar_credit_htmltopdf', 'templates/ar_credit/ar_credit_htmltopdf_simple', 'English Basic (XHTML)', 'Basic English language AR credit note.');
INSERT INTO  `config` (`name` ,`value`) VALUES ('TEMPLATE_CREDIT_EMAIL',  'hi (customer_contact)\r\n\r\nPlease see the attached PDF for CREDIT NOTE (code_credit) against invoice (code_invoice).\r\n\r\nregards,\r\n(company_name)');

INSERT INTO `menu` (`priority`, `parent`, `topic`, `link`, `permid`) VALUES(125, 'Accounts Receivables', 'menu_credit_notes', 'accounts/ar/ar-credits.php', 20);
INSERT INTO `menu` (`priority`, `parent`, `topic`, `link`, `permid`) VALUES(125, 'menu_credit_notes', 'menu_credit_notes_view', 'accounts/ar/ar-credits.php', 20);
INSERT INTO `menu` (`priority`, `parent`, `topic`, `link`, `permid`) VALUES(126, 'menu_credit_notes', 'menu_credit_notes_add', 'accounts/ar/credit-add.php', 21);
INSERT INTO `menu` (`priority`, `parent`, `topic`, `link`, `permid`) VALUES(126, 'menu_credit_notes_view', '', 'accounts/ar/ar-credits.php', 20);
INSERT INTO `menu` (`priority`, `parent`, `topic`, `link`, `permid`) VALUES(126, 'menu_credit_notes_view', '', 'accounts/ar/credit-view.php', 20);
INSERT INTO `menu` (`priority`, `parent`, `topic`, `link`, `permid`) VALUES(126, 'menu_credit_notes_view', '', 'accounts/ar/credit-items.php', 20);
INSERT INTO `menu` (`priority`, `parent`, `topic`, `link`, `permid`) VALUES(126, 'menu_credit_notes_view', '', 'accounts/ar/credit-items-edit.php', 21);
INSERT INTO `menu` (`priority`, `parent`, `topic`, `link`, `permid`) VALUES(126, 'menu_credit_notes_view', '', 'accounts/ar/credit-payments.php', 20);
INSERT INTO `menu` (`priority`, `parent`, `topic`, `link`, `permid`) VALUES(126, 'menu_credit_notes_view', '', 'accounts/ar/credit-payments-edit.php', 21);
INSERT INTO `menu` (`priority`, `parent`, `topic`, `link`, `permid`) VALUES(126, 'menu_credit_notes_view', '', 'accounts/ar/credit-journal.php', 20);
INSERT INTO `menu` (`priority`, `parent`, `topic`, `link`, `permid`) VALUES(126, 'menu_credit_notes_view', '', 'accounts/ar/credit-journal-edit.php', 20);
INSERT INTO `menu` (`priority`, `parent`, `topic`, `link`, `permid`) VALUES(126, 'menu_credit_notes_view', '', 'accounts/ar/credit-export.php', 20);
INSERT INTO `menu` (`priority`, `parent`, `topic`, `link`, `permid`) VALUES(126, 'menu_credit_notes_view', '', 'accounts/ar/credit-delete.php', 21);
INSERT INTO `menu` (`priority`, `parent`, `topic`, `link`, `permid`) VALUES(211, 'View Customers', '', 'customers/credit.php', '4');

INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'menu_credit_notes', 'Credit Notes');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'menu_credit_notes_view', 'View Credit Note');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'menu_credit_notes_add', 'Add Credit Note');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'code_credit', 'Credit Note');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'filter_credit_notes_search', 'Search Credit Note Details');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'ar_credit_details', 'Credit Note Details');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'ar_credit_financials', 'Credit Note Financials');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'ar_credit_other', 'Other Credit Note Details');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'ar_credit_delete', 'Delete Credit Note');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'submit_add_credit_item', 'Credit Selected Item');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'submit_credit_delete', 'Delete Credit Note');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'ar_credit_invoice_item', 'Credit Item');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'ar_credit_invoice_item_tax', 'Credited Tax');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'submit_credit_lock', 'Lock Credit Note');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'filter_id_employee', 'By Employee');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'employee', 'Employee');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'accounts', 'Accounts');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'invoice', 'Invoice');


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20110622' WHERE name='SCHEMA_VERSION' LIMIT 1;


