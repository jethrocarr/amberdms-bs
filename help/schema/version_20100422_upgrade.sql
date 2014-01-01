--
-- AMBERDMS BILLING SYSTEM 1.5.0 ALPHA UPGRADES
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0_alpha_2 to 1.5.0_alpha_3
--


ALTER TABLE `services` ADD `discount` FLOAT NOT NULL AFTER `price_extraunits` ;

ALTER TABLE `services_customers_ddi` CHANGE `ddi_start` `ddi_start` BIGINT( 20 ) NOT NULL;
ALTER TABLE `services_customers_ddi` CHANGE `ddi_finish` `ddi_finish` BIGINT( 20 ) NOT NULL ;

CREATE TABLE IF NOT EXISTS `services_customers_ipv4` (
  `id` int(11) NOT NULL auto_increment,
  `id_service_customer` int(11) NOT NULL,
  `ipv4_address` varchar(15) NOT NULL,
  `ipv4_cidr` int(2) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'group_products', 'Products');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'group_discount', 'Discounts');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'group_time', 'Contracting Hours');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'group_other', 'Other');

INSERT INTO `permissions` (`id`, `value`, `description`) VALUES (NULL, 'customers_credit', 'Permits user to add and make credit transactions to a customer');


CREATE TABLE IF NOT EXISTS `account_credit` (
  `id` int(11) NOT NULL auto_increment,
  `locked` int(11) NOT NULL,
  `id_organisation` int(11) NOT NULL,
  `id_employee` int(11) NOT NULL,
  `id_custom` int(11) NOT NULL,
  `type` char(6) NOT NULL,
  `code_credit` varchar(255) NOT NULL,
  `date_trans` bigint(20) NOT NULL,
  `amount` decimal(11,2) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `account_credit` ADD `description` VARCHAR( 255 ) NOT NULL AFTER `amount` ;




--
-- Import Clean Menu
--


TRUNCATE TABLE `menu`;

INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(1, 210, 'Customers', 'View Customers', 'customers/customers.php', 3);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(2, 220, 'Customers', 'Add Customer', 'customers/add.php', 4);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(4, 1, 'top', 'Overview', 'home.php', 0);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(5, 200, 'top', 'Customers', 'customers/customers.php', 3);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(6, 300, 'top', 'Vendors/Suppliers', 'vendors/vendors.php', 5);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(7, 400, 'top', 'Human Resources', 'hr/staff.php', 7);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(12, 211, 'View Customers', '', 'customers/view.php', 0);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(13, 100, 'top', 'Accounts', 'accounts/accounts.php', 0);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(15, 700, 'top', 'Time Keeping', 'timekeeping/timekeeping.php', 17);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(16, 800, 'top', 'Support Tickets', 'support/support.php', 9);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(17, 310, 'Vendors/Suppliers', 'View Vendors', 'vendors/vendors.php', 5);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(18, 320, 'Vendors/Suppliers', 'Add Vendor', 'vendors/add.php', 6);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(19, 311, 'View Vendors', '', 'vendors/view.php', 5);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(21, 410, 'Human Resources', 'View Staff', 'hr/staff.php', 7);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(22, 420, 'Human Resources', 'Add Staff', 'hr/staff-add.php', 8);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(23, 411, 'View Staff', '', 'hr/staff-view.php', 7);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(24, 810, 'Support Tickets', 'View Tickets', 'support/support.php', 9);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(25, 820, 'Support Tickets', 'Add Ticket', 'support/add.php', 10);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(26, 510, 'top', 'Products', 'products/products.php', 11);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(27, 511, 'Products', 'View Products', 'products/products.php', 11);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(28, 512, 'Products', 'Add Product', 'products/add.php', 12);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(30, 514, 'Products', '', 'products/products.php', 11);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(31, 520, 'top', 'Services', 'services/services.php', 13);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(32, 521, 'Services', '', 'services/services.php', 13);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(33, 522, 'Services', 'View Services', 'services/services.php', 13);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(34, 523, 'View Services', '', 'services/view.php', 13);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(35, 524, 'Services', 'Add Service', 'services/add.php', 14);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(36, 513, 'View Products', '', 'products/view.php', 11);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(37, 530, 'top', 'Projects', 'projects/projects.php', 15);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(38, 531, 'Projects', '', 'projects/projects.php', 15);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(39, 533, 'View Projects', '', 'projects/view.php', 15);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(40, 534, 'Projects', 'Add Project', 'projects/add.php', 16);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(41, 532, 'Projects', 'View Projects', 'projects/projects.php', 15);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(42, 701, 'Time Keeping', '', 'timekeeping/timekeeping.php', 17);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(43, 710, 'Time Keeping', 'Time Registration', 'timekeeping/timereg.php', 17);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(44, 720, 'Time Keeping', 'Unbilled Time', 'timekeeping/unbilled.php', 32);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(46, 535, 'View Projects', '', 'projects/phases.php', 15);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(47, 536, 'View Projects', '', 'projects/timebooked.php', 15);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(48, 900, 'top', 'Admin', 'admin/admin.php', 2);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(49, 910, 'Admin', 'User Management', 'user/users.php', 2);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(51, 930, 'Admin', 'Brute-Force Blacklist', 'admin/blacklist.php', 2);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(52, 901, 'Admin', '', 'admin/admin.php', 2);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(53, 911, 'User Management', '', 'user/users.php', 2);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(54, 912, 'User Management', 'View Users', 'user/users.php', 2);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(55, 913, 'User Management', 'Add User', 'user/user-add.php', 2);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(56, 914, 'View Users', '', 'user/user-view.php', 2);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(57, 915, 'View Users', '', 'user/user-permissions.php', 2);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(58, 916, 'View Users', '', 'user/user-staffaccess.php', 2);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(60, 811, 'View Tickets', '', 'support/view.php', 9);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(61, 213, 'View Customers', '', 'customers/journal.php', 3);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(62, 214, 'View Customers', '', 'customers/journal-edit.php', 3);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(63, 812, 'View Tickets', '', 'support/journal.php', 9);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(64, 812, 'View Tickets', '', 'support/journal-edit.php', 9);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(65, 312, 'View Vendors', '', 'vendors/journal.php', 5);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(66, 313, 'View Vendors', '', 'vendors/journal-edit.php', 5);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(67, 412, 'View Staff', '', 'hr/staff-journal.php', 7);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(68, 413, 'View Staff', '', 'hr/staff-journal-edit.php', 7);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(69, 917, 'View Users', '', 'user/user-journal.php', 2);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(70, 918, 'View Users', '', 'user/user-journal-edit.php', 2);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(71, 537, 'View Projects', '', 'projects/journal.php', 15);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(72, 538, 'View Projects', '', 'projects/journal-edit.php', 15);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(73, 514, 'View Products', '', 'products/journal.php', 11);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(74, 514, 'View Products', '', 'products/journal-edit.php', 11);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(75, 101, 'Accounts', '', 'accounts/accounts.php', 0);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(76, 110, 'Accounts', 'Chart of Accounts', 'accounts/charts/charts.php', 18);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(77, 111, 'Chart of Accounts', 'View Accounts', 'accounts/charts/charts.php', 18);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(78, 112, 'Chart of Accounts', 'Add Account', 'accounts/charts/add.php', 19);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(79, 113, 'View Accounts', '', 'accounts/charts/view.php', 18);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(80, 916, 'View Users', '', 'user/user-staffaccess-edit.php', 2);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(81, 120, 'Accounts', 'Accounts Receivables', 'accounts/ar/ar.php', 20);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(84, 121, 'Accounts Receivables', 'View Invoices', 'accounts/ar/ar.php', 20);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(85, 140, 'Accounts', 'Taxes', 'accounts/taxes/taxes.php', 22);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(86, 141, 'Taxes', 'View Taxes', 'accounts/taxes/taxes.php', 22);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(87, 142, 'View Taxes', '', 'accounts/taxes/view.php', 22);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(88, 143, 'Taxes', 'Add Taxes', 'accounts/taxes/add.php', 23);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(89, 124, 'View Invoices', '', 'accounts/ar/invoice-view.php', 20);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(90, 124, 'View Invoices', '', 'accounts/ar/journal-edit.php', 20);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(91, 124, 'View Invoices', '', 'accounts/ar/journal.php', 20);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(92, 113, 'View Accounts', '', 'accounts/charts/ledger.php', 18);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(93, 124, 'View Invoices', '', 'accounts/ar/invoice-payments.php', 20);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(94, 124, 'View Invoices', '', 'accounts/ar/invoice-items.php', 20);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(96, 142, 'View Taxes', '', 'accounts/taxes/ledger.php', 22);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(97, 142, 'View Taxes', '', 'accounts/taxes/tax_collected.php', 22);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(98, 142, 'View Taxes', '', 'accounts/taxes/tax_paid.php', 22);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(99, 130, 'Accounts', 'Accounts Payable', 'accounts/ap/ap.php', 24);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(100, 131, 'Accounts Payable', 'View AP Invoices', 'accounts/ap/ap.php', 24);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(101, 132, 'Accounts Payable', 'Add AP Invoice', 'accounts/ap/invoice-add.php', 25);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(102, 134, 'View AP Invoices', '', 'accounts/ap/invoice-delete.php', 25);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(103, 134, 'View AP Invoices', '', 'accounts/ap/invoice-view.php', 24);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(104, 134, 'View AP Invoices', '', 'accounts/ap/journal-edit.php', 24);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(105, 134, 'View AP Invoices', '', 'accounts/ap/journal.php', 24);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(106, 134, 'View AP Invoices', '', 'accounts/ap/invoice-payments.php', 24);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(107, 134, 'View AP Invoices', '', 'accounts/ap/invoice-items.php', 24);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(108, 536, 'View Projects', '', 'projects/timebilled.php', 15);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(109, 536, 'View Projects', '', 'projects/timebilled-edit.php', 15);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(110, 536, 'View Projects', '', 'projects/timebilled-delete.php', 15);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(111, 535, 'View Projects', '', 'projects/phase-edit.php', 15);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(112, 535, 'View Projects', '', 'projects/phase-delete.php', 15);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(113, 711, 'Time Registration', '', 'timekeeping/timereg-day.php', 17);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(114, 535, 'View Projects', '', 'projects/delete.php', 15);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(115, 514, 'View Products', '', 'products/delete.php', 11);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(116, 214, 'View Customers', '', 'customers/delete.php', 3);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(117, 313, 'View Vendors', '', 'vendors/delete.php', 5);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(118, 413, 'View Staff', '', 'hr/staff-delete.php', 7);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(119, 811, 'View Tickets', '', 'support/delete.php', 9);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(120, 142, 'View Taxes', '', 'accounts/taxes/delete.php', 22);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(121, 918, 'View Users', '', 'user/user-delete.php', 2);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(122, 115, 'Accounts', 'General Ledger', 'accounts/gl/gl.php', 26);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(123, 116, 'General Ledger', 'View GL Transactions', 'accounts/gl/gl.php', 26);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(124, 117, 'General Ledger', 'Add GL Transaction', 'accounts/gl/add.php', 27);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(126, 124, 'View Invoices', '', 'accounts/ar/invoice-items-edit.php', 21);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(127, 124, 'View Invoices', '', 'accounts/ar/invoice-payments-edit.php', 21);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(128, 134, 'View AP Invoices', '', 'accounts/ap/invoice-payments-edit.php', 25);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(129, 134, 'View AP Invoices', '', 'accounts/ap/invoice-items-edit.php', 25);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(130, 117, 'View GL Transactions', '', 'accounts/gl/view.php', 26);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(131, 117, 'View GL Transactions', '', 'accounts/gl/delete.php', 27);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(132, 150, 'Accounts', 'Quotes', 'accounts/quotes/quotes.php', 28);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(133, 151, 'Quotes', 'View Quotes', 'accounts/quotes/quotes.php', 28);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(134, 152, 'Quotes', 'Add Quote', 'accounts/quotes/quotes-add.php', 29);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(135, 152, 'View Quotes', '', 'accounts/quotes/quotes-delete.php', 29);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(136, 154, 'View Quotes', '', 'accounts/quotes/quotes-view.php', 28);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(137, 154, 'View Quotes', '', 'accounts/quotes/journal-edit.php', 28);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(138, 154, 'View Quotes', '', 'accounts/quotes/journal.php', 28);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(139, 154, 'View Quotes', '', 'accounts/quotes/quotes-items.php', 28);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(140, 154, 'View Quotes', '', 'accounts/quotes/quotes-items-edit.php', 29);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(141, 152, 'View Quotes', '', 'accounts/quotes/quotes-convert.php', 29);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(142, 916, 'View Users', '', 'user/user-staffaccess-add.php', 2);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(143, 523, 'View Services', '', 'services/plan.php', 13);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(144, 523, 'View Services', '', 'services/journal.php', 13);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(145, 523, 'View Services', '', 'services/journal-edit.php', 13);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(146, 523, 'View Services', '', 'services/delete.php', 14);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(147, 211, 'View Customers', '', 'customers/invoices.php', 3);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(148, 211, 'View Customers', '', 'customers/services.php', 3);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(149, 211, 'View Customers', '', 'customers/service-edit.php', 4);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(150, 211, 'View Customers', '', 'customers/service-delete.php', 4);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(151, 311, 'View Vendors', '', 'vendors/invoices.php', 5);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(152, 211, 'View Customers', '', 'customers/service-history.php', 4);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(153, 711, 'Time Registration', '', 'timekeeping/timereg-day-edit.php', 17);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(154, 113, 'View Accounts', '', 'accounts/charts/delete.php', 19);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(155, 124, 'View Invoices', '', 'accounts/ar/invoice-export.php', 20);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(156, 154, 'View Quotes', '', 'accounts/quotes/quotes-export.php', 28);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(157, 180, 'Accounts', 'Reports', 'accounts/reports/reports.php', 30);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(158, 181, 'Reports', 'Trial Balance', 'accounts/reports/trialbalance.php', 30);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(159, 181, 'Reports', '', 'accounts/reports/reports.php', 30);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(160, 182, 'Reports', 'Income Statement', 'accounts/reports/incomestatement.php', 30);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(161, 183, 'Reports', 'Balance Sheet', 'accounts/reports/balancesheet.php', 30);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(162, 905, 'Admin', 'Configuration', 'admin/config.php', 2);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(163, 940, 'Admin', 'Audit Locking', 'admin/auditlock.php', 2);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(164, 411, 'View Staff', '', 'hr/staff-timebooked.php', 7);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(167, 122, 'Accounts Receivables', 'Add Invoice', 'accounts/ar/invoice-add.php', 21);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(168, 122, 'View Invoices', '', 'accounts/ar/invoice-delete.php', 21);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(169, 950, 'Admin', 'Database Backup', 'admin/db_backup.php', 2);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(170, 908, 'Admin', 'template_selection', 'admin/templates.php', 2);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(171, 906, 'Configuration', 'menu_config_company', 'admin/config_company.php', 2);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(173, 906, 'Configuration', 'menu_config_integration', 'admin/config_integration.php', 2);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(174, 906, 'Configuration', 'menu_config_services', 'admin/config_services.php', 2);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(175, 906, 'Configuration', 'menu_config_app', 'admin/config_application.php', 2);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(176, 906, 'Configuration', 'menu_config_locale', 'admin/config_locale.php', 2);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(177, 906, 'Configuration', '', 'admin/config.php', 2);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(178, 211, 'View Customers', '', 'customers/portal.php', 3);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(179, 525, 'Services', 'menu_service_cdr_rates', 'services/cdr-rates.php', 13);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(180, 526, 'menu_service_cdr_rates', 'menu_service_cdr_rates_view', 'services/cdr-rates.php', 13);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(181, 526, 'menu_service_cdr_rates', 'menu_service_cdr_rates_add', 'services/cdr-rates-add.php', 13);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(182, 527, 'menu_service_cdr_rates_view', '', 'services/cdr-rates-view.php', 13);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(183, 527, 'menu_service_cdr_rates_view', '', 'services/cdr-rates-items.php', 13);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(184, 527, 'menu_service_cdr_rates_view', '', 'services/cdr-rates-delete.php', 14);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(185, 523, 'View Services', '', 'services/bundles.php', 13);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(186, 523, 'View Services', '', 'services/bundles-service-add.php', 14);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(187, 523, 'View Services', '', 'services/bundles-service-edit.php', 13);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(188, 527, 'menu_service_cdr_rates_view', '', 'services/cdr-rates-items-edit.php', 13);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(189, 523, 'View Services', '', 'services/cdr-override.php', 13);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(190, 523, 'View Services', '', 'services/cdr-override-edit.php', 14);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(191, 190, 'Accounts', 'Import', 'accounts/import/bankstatement.php', 35);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(192, 191, 'Import', 'Bank Statement', 'accounts/import/bankstatement.php', 35);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(193, 192, 'Bank Statement', '', 'accounts/import/bankstatement-assign.php', 35);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(194, 193, 'Bank Statement', '', 'accounts/import/bankstatement-csv.php', 35);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(195, 211, 'View Customers', '', 'customers/service-cdr-override.php', 3);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(196, 211, 'View Customers', '', 'customers/service-cdr-override-edit.php', 4);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(197, 528, 'Services', 'menu_services_groups', 'services/groups.php', 13);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(198, 529, 'menu_services_groups', 'menu_services_groups_view', 'services/groups.php', 13);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(199, 530, 'menu_services_groups_view', '', 'services/groups-view.php', 13);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(200, 530, 'menu_services_groups_view', '', 'services/groups-delete.php', 14);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(201, 530, 'menu_services_groups', 'menu_services_groups_add', 'services/groups-add.php', 14);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(202, 211, 'View Customers', '', 'customers/service-ddi.php', 3);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(203, 211, 'View Customers', '', 'customers/service-ddi-edit.php', 4);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(204, 211, 'View Customers', '', 'customers/service-ipv4.php', 3);
INSERT INTO `menu` (`id`, `priority`, `parent`, `topic`, `link`, `permid`) VALUES(205, 211, 'View Customers', '', 'customers/service-ipv4-edit.php', 4);




--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20100422' WHERE name='SCHEMA_VERSION' LIMIT 1;



