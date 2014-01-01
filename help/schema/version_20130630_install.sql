--
-- AMBERDMS BILLING SYSTEM 1.5.0 RC 1
--

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


--
-- Create Database
--

CREATE DATABASE `billing_system` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `billing_system`;



--
-- Table structure for table `account_ap`
--

DROP TABLE IF EXISTS `account_ap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_ap` (
  `id` int(11) NOT NULL auto_increment,
  `locked` tinyint(1) NOT NULL default '0',
  `vendorid` int(11) NOT NULL default '0',
  `employeeid` int(11) NOT NULL default '0',
  `dest_account` int(11) NOT NULL default '0',
  `code_invoice` varchar(255) NOT NULL,
  `code_ordernumber` varchar(255) NOT NULL,
  `code_ponumber` varchar(255) NOT NULL,
  `date_due` date NOT NULL default '0000-00-00',
  `date_trans` date NOT NULL default '0000-00-00',
  `date_create` date NOT NULL default '0000-00-00',
  `amount_total` decimal(11,2) NOT NULL default '0.00',
  `amount_tax` decimal(11,2) NOT NULL default '0.00',
  `amount` decimal(11,2) NOT NULL default '0.00',
  `amount_paid` decimal(11,2) NOT NULL default '0.00',
  `notes` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_ap`
--

LOCK TABLES `account_ap` WRITE;
/*!40000 ALTER TABLE `account_ap` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_ap` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `account_ap_credit`
--

DROP TABLE IF EXISTS `account_ap_credit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_ap_credit` (
  `id` int(11) NOT NULL auto_increment,
  `locked` tinyint(1) NOT NULL default '0',
  `vendorid` int(11) NOT NULL default '0',
  `invoiceid` int(10) unsigned NOT NULL,
  `employeeid` int(11) NOT NULL default '0',
  `dest_account` int(11) NOT NULL default '0',
  `code_credit` varchar(255) NOT NULL,
  `code_ordernumber` varchar(255) NOT NULL,
  `code_ponumber` varchar(255) NOT NULL,
  `date_trans` date NOT NULL default '0000-00-00',
  `date_create` date NOT NULL default '0000-00-00',
  `amount_total` decimal(11,2) NOT NULL default '0.00',
  `amount_tax` decimal(11,2) NOT NULL default '0.00',
  `amount` decimal(11,2) NOT NULL default '0.00',
  `notes` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_ap_credit`
--

LOCK TABLES `account_ap_credit` WRITE;
/*!40000 ALTER TABLE `account_ap_credit` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_ap_credit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `account_ar`
--

DROP TABLE IF EXISTS `account_ar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_ar` (
  `id` int(11) NOT NULL auto_increment,
  `locked` tinyint(1) NOT NULL default '0',
  `customerid` int(11) NOT NULL default '0',
  `employeeid` int(11) NOT NULL default '0',
  `dest_account` int(11) NOT NULL default '0',
  `code_invoice` varchar(255) NOT NULL,
  `code_ordernumber` varchar(255) NOT NULL,
  `code_ponumber` varchar(255) NOT NULL,
  `date_due` date NOT NULL default '0000-00-00',
  `date_trans` date NOT NULL default '0000-00-00',
  `date_create` date NOT NULL default '0000-00-00',
  `date_sent` date NOT NULL default '0000-00-00',
  `sentmethod` varchar(10) NOT NULL,
  `amount_total` decimal(11,2) NOT NULL default '0.00',
  `amount_tax` decimal(11,2) NOT NULL default '0.00',
  `amount` decimal(11,2) NOT NULL default '0.00',
  `amount_paid` decimal(11,2) NOT NULL default '0.00',
  `notes` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_ar`
--

LOCK TABLES `account_ar` WRITE;
/*!40000 ALTER TABLE `account_ar` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_ar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `account_ar_credit`
--

DROP TABLE IF EXISTS `account_ar_credit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_ar_credit` (
  `id` int(11) NOT NULL auto_increment,
  `locked` tinyint(1) NOT NULL default '0',
  `customerid` int(11) NOT NULL default '0',
  `invoiceid` int(10) unsigned NOT NULL,
  `employeeid` int(11) NOT NULL default '0',
  `dest_account` int(11) NOT NULL default '0',
  `code_credit` varchar(255) NOT NULL,
  `code_ordernumber` varchar(255) NOT NULL,
  `code_ponumber` varchar(255) NOT NULL,
  `date_trans` date NOT NULL default '0000-00-00',
  `date_create` date NOT NULL default '0000-00-00',
  `date_sent` date NOT NULL default '0000-00-00',
  `sentmethod` varchar(10) NOT NULL,
  `amount_total` decimal(11,2) NOT NULL default '0.00',
  `amount_tax` decimal(11,2) NOT NULL default '0.00',
  `amount` decimal(11,2) NOT NULL default '0.00',
  `notes` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_ar_credit`
--

LOCK TABLES `account_ar_credit` WRITE;
/*!40000 ALTER TABLE `account_ar_credit` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_ar_credit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `account_chart_menu`
--

DROP TABLE IF EXISTS `account_chart_menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_chart_menu` (
  `id` int(11) NOT NULL auto_increment,
  `value` varchar(50) NOT NULL,
  `groupname` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_chart_menu`
--

LOCK TABLES `account_chart_menu` WRITE;
/*!40000 ALTER TABLE `account_chart_menu` DISABLE KEYS */;
INSERT INTO `account_chart_menu` VALUES (1,'ar_summary_account','Accounts Receivables','Account to file all unpaid AR transactions/invoices too.'),(2,'ar_income','Accounts Receivables','Use this account to record income.'),(3,'tax_summary_account','Tax','Use this account for sales taxes.'),(6,'ar_payment','Accounts Receivables','Allow payments made by customers to be placed into this account'),(7,'ap_summary_account','Accounts Payable','Account to file all unpaid AP transactions/invoices too.'),(8,'ap_expense','Accounts Payable','Use this account for AP expenses'),(9,'ap_payment','Accounts Payable','Allow invoice payments to be taken from this account'),(10,'ap_expense_employeewages','Accounts Payable','Use this account for paying staff wages/expenses');
/*!40000 ALTER TABLE `account_chart_menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `account_chart_type`
--

DROP TABLE IF EXISTS `account_chart_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_chart_type` (
  `id` int(11) NOT NULL auto_increment,
  `value` varchar(20) NOT NULL,
  `total_mode` varchar(6) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_chart_type`
--

LOCK TABLES `account_chart_type` WRITE;
/*!40000 ALTER TABLE `account_chart_type` DISABLE KEYS */;
INSERT INTO `account_chart_type` VALUES (1,'Heading',''),(2,'Asset','debit'),(3,'Liability','credit'),(4,'Equity',''),(5,'Income','credit'),(6,'Expense','debit');
/*!40000 ALTER TABLE `account_chart_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `account_chart_types_menus`
--

DROP TABLE IF EXISTS `account_chart_types_menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_chart_types_menus` (
  `id` int(11) NOT NULL auto_increment,
  `menuid` int(11) NOT NULL,
  `chart_typeid` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_chart_types_menus`
--

LOCK TABLES `account_chart_types_menus` WRITE;
/*!40000 ALTER TABLE `account_chart_types_menus` DISABLE KEYS */;
INSERT INTO `account_chart_types_menus` VALUES (1,6,2),(2,9,2),(3,1,2),(4,8,2),(5,3,3),(6,9,3),(7,7,3),(8,2,5),(9,8,6);
/*!40000 ALTER TABLE `account_chart_types_menus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `account_charts`
--

DROP TABLE IF EXISTS `account_charts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_charts` (
  `id` int(11) NOT NULL auto_increment,
  `code_chart` int(11) NOT NULL default '0',
  `description` varchar(255) NOT NULL,
  `chart_type` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_charts`
--

LOCK TABLES `account_charts` WRITE;
/*!40000 ALTER TABLE `account_charts` DISABLE KEYS */;
INSERT INTO `account_charts` VALUES (1,1000,'CURRENT ASSETS',1),(2,1060,'Current Account',2),(3,1061,'Savings Account',2),(5,1065,'Petty Cash',2),(6,1200,'Accounts Receivables',2),(7,1205,'Allowance for doubtful accounts',2),(8,1500,'INVENTORY ASSETS',1),(9,1520,'Inventory / General',2),(10,1530,'Inventory / Aftermarket Parts',2),(11,1800,'CAPITAL ASSETS',1),(12,1820,'Computer Equipment',2),(13,2000,'CURRENT LIABILITIES',1),(14,2100,'Accounts Payable',3),(15,2110,'Reimburse to Staff',3),(16,2310,'Sales Tax (GST)',3),(17,4000,'SALES REVENUE',1),(18,4020,'Sales / General',5),(19,4021,'Sales / Internet Services',5),(20,4022,'Sales / Webdevelopment',5),(21,4023,'Sales / Computer Hardware',5),(22,4024,'Sales / Server Support',5),(23,4300,'CONSULTING REVENUE',1),(24,4320,'Consulting',5),(25,4400,'OTHER REVENUE',1),(26,4440,'Interest',5),(28,4460,'Captial Investment',4),(29,5000,'COST OF GOODS SOLD',1),(30,5010,'Consulting Expenses',6),(31,5020,'Parts Purchased',6),(32,5600,'GENERAL & ADMINISTRATIVE EXPENSES',1),(33,5610,'Accounting & Legal',6),(34,5611,'Shareholder/Employee Withdrawals',6),(35,5612,'Webhosting',6),(36,5615,'Advertising & Promotions',6),(37,5620,'Bad Debts',6),(38,5680,'Taxes',6),(39,5681,'Withholding Tax',6),(40,5685,'Insurance',6),(41,5690,'Interest & Bank Charges',6),(42,5700,'Office Supplies',6),(43,5760,'Rent',6),(44,5765,'Repair & Maintenance',6),(45,5785,'Travel & Entertainment',6),(46,5790,'Utilities',6),(47,5800,'Colocation Hosting',6),(48,5810,'Foreign Exchange Loss',6),(49,5820,'Training/Conferences',6),(50,5830,'Shipping',6),(51,1840,'Furniture',2);
/*!40000 ALTER TABLE `account_charts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `account_charts_menus`
--

DROP TABLE IF EXISTS `account_charts_menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_charts_menus` (
  `id` int(11) NOT NULL auto_increment,
  `chartid` int(11) NOT NULL default '0',
  `menuid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_charts_menus`
--

LOCK TABLES `account_charts_menus` WRITE;
/*!40000 ALTER TABLE `account_charts_menus` DISABLE KEYS */;
INSERT INTO `account_charts_menus` VALUES (1,2,6),(2,2,9),(3,3,6),(4,3,9),(7,5,6),(8,5,9),(9,6,1),(10,12,8),(11,14,7),(12,15,9),(13,16,3),(14,18,2),(15,19,2),(16,20,2),(17,21,2),(18,22,2),(19,24,2),(20,30,8),(21,31,8),(22,33,8),(23,34,8),(24,35,8),(25,36,8),(26,40,8),(27,42,8),(28,43,8),(29,44,8),(30,45,8),(31,46,8),(32,47,8),(33,49,8),(34,50,8),(35,10,8);
/*!40000 ALTER TABLE `account_charts_menus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `account_gl`
--

DROP TABLE IF EXISTS `account_gl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_gl` (
  `id` int(11) NOT NULL auto_increment,
  `locked` tinyint(1) NOT NULL default '0',
  `code_gl` varchar(255) NOT NULL,
  `date_trans` date NOT NULL default '0000-00-00',
  `employeeid` int(11) NOT NULL default '0',
  `description` varchar(255) NOT NULL,
  `notes` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_gl`
--

LOCK TABLES `account_gl` WRITE;
/*!40000 ALTER TABLE `account_gl` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_gl` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `account_items`
--

DROP TABLE IF EXISTS `account_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_items` (
  `id` int(11) NOT NULL auto_increment,
  `invoiceid` int(11) NOT NULL default '0',
  `invoicetype` varchar(15) NOT NULL,
  `type` varchar(15) NOT NULL,
  `customid` int(11) NOT NULL default '0',
  `chartid` int(11) NOT NULL default '0',
  `quantity` float NOT NULL default '0',
  `units` varchar(10) NOT NULL,
  `amount` decimal(11,2) NOT NULL default '0.00',
  `price` decimal(11,2) NOT NULL default '0.00',
  `description` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_items`
--

LOCK TABLES `account_items` WRITE;
/*!40000 ALTER TABLE `account_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `account_items_options`
--

DROP TABLE IF EXISTS `account_items_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_items_options` (
  `id` int(11) NOT NULL auto_increment,
  `itemid` int(11) NOT NULL default '0',
  `option_name` varchar(20) NOT NULL,
  `option_value` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_items_options`
--

LOCK TABLES `account_items_options` WRITE;
/*!40000 ALTER TABLE `account_items_options` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_items_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `account_quotes`
--

DROP TABLE IF EXISTS `account_quotes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_quotes` (
  `id` int(11) NOT NULL auto_increment,
  `customerid` int(11) NOT NULL default '0',
  `employeeid` int(11) NOT NULL default '0',
  `code_quote` varchar(255) NOT NULL,
  `date_trans` date NOT NULL default '0000-00-00',
  `date_validtill` date NOT NULL default '0000-00-00',
  `date_create` date NOT NULL default '0000-00-00',
  `date_sent` date NOT NULL default '0000-00-00',
  `sentmethod` varchar(10) NOT NULL,
  `amount_total` decimal(11,2) NOT NULL default '0.00',
  `amount_tax` decimal(11,2) NOT NULL default '0.00',
  `amount` decimal(11,2) NOT NULL default '0.00',
  `notes` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_quotes`
--

LOCK TABLES `account_quotes` WRITE;
/*!40000 ALTER TABLE `account_quotes` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_quotes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `account_taxes`
--

DROP TABLE IF EXISTS `account_taxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_taxes` (
  `id` int(11) NOT NULL auto_increment,
  `name_tax` varchar(255) NOT NULL,
  `chartid` int(11) NOT NULL default '0',
  `taxrate` float NOT NULL default '0',
  `taxnumber` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `default_customers` tinyint(1) NOT NULL default '0',
  `default_vendors` tinyint(1) NOT NULL default '0',
  `default_services` tinyint(1) NOT NULL default '0',
  `default_products` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_taxes`
--

LOCK TABLES `account_taxes` WRITE;
/*!40000 ALTER TABLE `account_taxes` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_taxes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `account_trans`
--

DROP TABLE IF EXISTS `account_trans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_trans` (
  `id` int(11) NOT NULL auto_increment,
  `type` varchar(15) NOT NULL,
  `customid` int(11) NOT NULL default '0',
  `chartid` int(11) NOT NULL default '0',
  `date_trans` date NOT NULL default '0000-00-00',
  `amount_debit` decimal(11,2) NOT NULL default '0.00',
  `amount_credit` decimal(11,2) NOT NULL default '0.00',
  `source` varchar(255) NOT NULL,
  `memo` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_trans`
--

LOCK TABLES `account_trans` WRITE;
/*!40000 ALTER TABLE `account_trans` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_trans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attributes`
--

DROP TABLE IF EXISTS `attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attributes` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_owner` int(10) unsigned NOT NULL,
  `id_group` int(10) unsigned NOT NULL,
  `type` varchar(10) NOT NULL,
  `key` varchar(80) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attributes`
--

LOCK TABLES `attributes` WRITE;
/*!40000 ALTER TABLE `attributes` DISABLE KEYS */;
/*!40000 ALTER TABLE `attributes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attributes_group`
--

DROP TABLE IF EXISTS `attributes_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attributes_group` (
  `id` int(10) NOT NULL auto_increment,
  `group_name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attributes_group`
--

LOCK TABLES `attributes_group` WRITE;
/*!40000 ALTER TABLE `attributes_group` DISABLE KEYS */;
/*!40000 ALTER TABLE `attributes_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `billing_cycles`
--

DROP TABLE IF EXISTS `billing_cycles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `billing_cycles` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `priority` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_cycles`
--

LOCK TABLES `billing_cycles` WRITE;
/*!40000 ALTER TABLE `billing_cycles` DISABLE KEYS */;
INSERT INTO `billing_cycles` VALUES (1,'monthly',31,'Bill the customer every month since the start date',1),(2,'6monthly',186,'Bill the customer every 6 months since the start date.',1),(3,'yearly',365,'Bill the customer once a year on the start date',1),(4,'quarterly',93,'Bill the customer every quarter since the start date.',1),(5,'weekly',7,'Bill the customer every week since the start date.',1),(6,'fortnightly',14,'Bill the customer every two weeks since the start date.',1);
/*!40000 ALTER TABLE `billing_cycles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `billing_modes`
--

DROP TABLE IF EXISTS `billing_modes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `billing_modes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_modes`
--

LOCK TABLES `billing_modes` WRITE;
/*!40000 ALTER TABLE `billing_modes` DISABLE KEYS */;
INSERT INTO `billing_modes` VALUES (1,'periodend','Service is billed after it has been delivered.',1),(2,'periodadvance','Service is billed in advance (before the service period has started)',1),(3,'monthend','Service is billed after it has been delivered, with every period ending on the last day of the month.',1),(4,'monthadvance','Service is billed in advance of the month beginning. The billing period will always end on the last date of the month.',1),(5,'monthtelco','Telco-style billing - charge for a service at the start of the month and charge for the previous month\'s usage.',1),(6,'periodtelco','Telco-style billing - charge for a service at the start of the period and charge for the previous period\'s usage.',1);
/*!40000 ALTER TABLE `billing_modes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cdr_rate_billgroups`
--

DROP TABLE IF EXISTS `cdr_rate_billgroups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cdr_rate_billgroups` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `billgroup_name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9001 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cdr_rate_billgroups`
--

LOCK TABLES `cdr_rate_billgroups` WRITE;
/*!40000 ALTER TABLE `cdr_rate_billgroups` DISABLE KEYS */;
INSERT INTO `cdr_rate_billgroups` VALUES (0,'Unknown Region'),(1,'Local'),(2,'National'),(3,'Mobile'),(4,'International');
/*!40000 ALTER TABLE `cdr_rate_billgroups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cdr_rate_tables`
--

DROP TABLE IF EXISTS `cdr_rate_tables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cdr_rate_tables` (
  `id` int(11) NOT NULL auto_increment,
  `id_vendor` int(11) NOT NULL,
  `id_usage_mode` int(11) NOT NULL,
  `rate_table_name` varchar(255) NOT NULL,
  `rate_table_description` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cdr_rate_tables`
--

LOCK TABLES `cdr_rate_tables` WRITE;
/*!40000 ALTER TABLE `cdr_rate_tables` DISABLE KEYS */;
/*!40000 ALTER TABLE `cdr_rate_tables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cdr_rate_tables_overrides`
--

DROP TABLE IF EXISTS `cdr_rate_tables_overrides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cdr_rate_tables_overrides` (
  `id` int(11) NOT NULL auto_increment,
  `option_type` varchar(20) NOT NULL,
  `option_type_id` int(11) NOT NULL,
  `rate_prefix` varchar(20) NOT NULL,
  `rate_description` varchar(255) NOT NULL,
  `rate_billgroup` int(10) unsigned NOT NULL,
  `rate_price_sale` decimal(11,2) NOT NULL,
  `rate_price_extraunits` decimal(11,2) NOT NULL,
  `rate_included_units` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cdr_rate_tables_overrides`
--

LOCK TABLES `cdr_rate_tables_overrides` WRITE;
/*!40000 ALTER TABLE `cdr_rate_tables_overrides` DISABLE KEYS */;
/*!40000 ALTER TABLE `cdr_rate_tables_overrides` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cdr_rate_tables_values`
--

DROP TABLE IF EXISTS `cdr_rate_tables_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cdr_rate_tables_values` (
  `id` int(11) NOT NULL auto_increment,
  `id_rate_table` int(11) NOT NULL,
  `rate_prefix` varchar(20) NOT NULL,
  `rate_description` varchar(255) NOT NULL,
  `rate_billgroup` int(10) unsigned NOT NULL,
  `rate_price_sale` decimal(11,4) NOT NULL,
  `rate_price_cost` decimal(11,4) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cdr_rate_tables_values`
--

LOCK TABLES `cdr_rate_tables_values` WRITE;
/*!40000 ALTER TABLE `cdr_rate_tables_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `cdr_rate_tables_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cdr_rate_usage_modes`
--

DROP TABLE IF EXISTS `cdr_rate_usage_modes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cdr_rate_usage_modes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cdr_rate_usage_modes`
--

LOCK TABLES `cdr_rate_usage_modes` WRITE;
/*!40000 ALTER TABLE `cdr_rate_usage_modes` DISABLE KEYS */;
INSERT INTO `cdr_rate_usage_modes` VALUES (1,'per_minute','All calls charged on a per-minute basis, rounded up to nearest whole minute.'),(2,'per_second','All calls charged on a per-second basis.'),(3,'first_min_then_per_second','Calls charged for minimum of one minute and then per second afterwards.');
/*!40000 ALTER TABLE `cdr_rate_usage_modes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `config` (
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY  (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `config`
--

LOCK TABLES `config` WRITE;
/*!40000 ALTER TABLE `config` DISABLE KEYS */;
INSERT INTO `config` VALUES ('ACCOUNTS_AP_INVOICENUM','100'),('ACCOUNTS_AR_INVOICENUM','100'),('ACCOUNTS_AUTOPAY','1'),('ACCOUNTS_CREDIT_NUM','CR100'),('ACCOUNTS_EMAIL_ADDRESS','accounts@example.com'),('ACCOUNTS_EMAIL_AUTOBCC','enabled'),('ACCOUNTS_GL_LOCK','0'),('ACCOUNTS_GL_TRANSNUM','100'),('ACCOUNTS_INVOICE_AUTOEMAIL','disabled'),('ACCOUNTS_INVOICE_BATCHREPORT','enabled'),('ACCOUNTS_INVOICE_LOCK','0'),('ACCOUNTS_QUOTES_NUM','100'),('ACCOUNTS_SERVICES_ADVANCEBILLING','28'),('ACCOUNTS_SERVICES_DATESHIFT','1'),('ACCOUNTS_TERMS_DAYS','20'),('APP_MYSQL_DUMP','/usr/bin/mysqldump'),('APP_PDFLATEX','/usr/bin/pdflatex'),('APP_WKHTMLTOPDF','/opt/wkhtmltopdf/bin/wkhtmltopdf'),('AUTH_METHOD','sql'),('AUTH_PERMS_CACHE','disabled'),('BLACKLIST_ENABLE','enabled'),('BLACKLIST_LIMIT','10'),('CODE_ACCOUNT','1000'),('CODE_CUSTOMER','100'),('CODE_PRODUCT','100'),('CODE_PROJECT','100'),('CODE_STAFF','100'),('CODE_VENDOR','100'),('COMPANY_ADDRESS1_CITY','Example City'),('COMPANY_ADDRESS1_COUNTRY','Example Country'),('COMPANY_ADDRESS1_STATE',''),('COMPANY_ADDRESS1_STREET','54a Stallman Lane\r\nFreeburbs'),('COMPANY_ADDRESS1_ZIPCODE','0000'),('COMPANY_CONTACT_EMAIL','accounts@example.com'),('COMPANY_CONTACT_FAX','00-11-111-1112'),('COMPANY_CONTACT_PHONE','00-11-111-1111'),('COMPANY_NAME','Example Ltd'),('COMPANY_PAYMENT_DETAILS','Please pay all invoices by direct transfer to XX-XXXX-XXXXXXX.'),('CURRENCY_DEFAULT_DECIMAL_SEPARATOR','.'),('CURRENCY_DEFAULT_NAME','NZD'),('CURRENCY_DEFAULT_SYMBOL','$'),('CURRENCY_DEFAULT_SYMBOL_POSITION','before'),('CURRENCY_DEFAULT_THOUSANDS_SEPARATOR',','),('DATA_STORAGE_LOCATION','use_database'),('DATA_STORAGE_METHOD','database'),('DATEFORMAT','yyyy-mm-dd'),('EMAIL_ENABLE','enabled'),('JOURNAL_LOCK','0'),('LANGUAGE_DEFAULT','en_us'),('LANGUAGE_LOAD','preload'),('MODULE_CUSTOMER_PORTAL',''),('ORDERS_BILL_ENDOFMONTH','1'),('ORDERS_BILL_ONSERVICE','1'),('PATH_TMPDIR','/tmp'),('PHONE_HOME','enabled'),('PHONE_HOME_TIMER','1303861257'),('SCHEMA_VERSION','20120723'),('SERVICES_USAGEALERTS_ENABLE','1'),('SERVICE_CDR_BILLSELF','local'),('SERVICE_CDR_DB_HOST',''),('SERVICE_CDR_DB_NAME',''),('SERVICE_CDR_DB_PASSWORD',''),('SERVICE_CDR_DB_TYPE',''),('SERVICE_CDR_DB_USERNAME',''),('SERVICE_CDR_EXPORT_FORMAT','csv_padded'),('SERVICE_CDR_LOCAL','destination'),('SERVICE_CDR_MODE','internal'),('SERVICE_MIGRATION_MODE','0'),('SERVICE_PARTPERIOD_MODE','merge'),('SERVICE_TRAFFIC_DB_HOST',''),('SERVICE_TRAFFIC_DB_NAME',''),('SERVICE_TRAFFIC_DB_PASSWORD',''),('SERVICE_TRAFFIC_DB_TYPE',''),('SERVICE_TRAFFIC_DB_USERNAME',''),('SERVICE_TRAFFIC_MODE','internal'),('SESSION_TIMEOUT','7200'),('SUBSCRIPTION_ID','2c8562610ca81150cea7d6886ab67f58'),('SUBSCRIPTION_SUPPORT','opensource'),('TABLE_LIMIT','1000'),('TEMPLATE_CREDIT_EMAIL','hi (customer_contact)\r\n\r\nPlease see the attached PDF for CREDIT NOTE (code_credit) against invoice (code_invoice).\r\n\r\nregards,\r\n(company_name)'),('TEMPLATE_INVOICE_EMAIL','hi (customer_contact),\r\n\r\nPlease see the attached PDF for invoice (code_invoice) and payment\r\ninformation due on (date_due).\r\n\r\nThank you for your business!\r\n\r\nregards,\r\n(company_name)'),('TEMPLATE_INVOICE_REMINDER_EMAIL','hi (customer_contact),\n\nPayment for invoice (code_invoice) was due on (date_due) and is now (days_overdue) days overdue.\n\nPlease see the attached PDF for invoice (code_invoice) and payment information.\n\nThank you for your business!\n\nregards,\n(company_name)'),('TEMPLATE_QUOTE_EMAIL','Dear (contact_name),\r\n\r\nPlease see the attached PDF for quote (code_quote).\r\n\r\nregards,\r\n(company_name)'),('THEME_DEFAULT','1'),('TIMESHEET_BOOKTOFUTURE','disabled'),('TIMESHEET_LOCK','0'),('TIMEZONE_DEFAULT','SYSTEM'),('UPLOAD_MAXBYTES','5242880');
/*!40000 ALTER TABLE `config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_contact_records`
--

DROP TABLE IF EXISTS `customer_contact_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_contact_records` (
  `id` int(11) NOT NULL auto_increment,
  `contact_id` int(11) NOT NULL,
  `type` enum('phone','email','fax','mobile') NOT NULL,
  `label` varchar(255) NOT NULL,
  `detail` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_contact_records`
--

LOCK TABLES `customer_contact_records` WRITE;
/*!40000 ALTER TABLE `customer_contact_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_contact_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_contacts`
--

DROP TABLE IF EXISTS `customer_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_contacts` (
  `id` int(11) NOT NULL auto_increment,
  `customer_id` int(11) NOT NULL,
  `role` enum('other','accounts') NOT NULL,
  `contact` varchar(255) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_contacts`
--

LOCK TABLES `customer_contacts` WRITE;
/*!40000 ALTER TABLE `customer_contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `id` int(11) NOT NULL auto_increment,
  `code_customer` varchar(50) NOT NULL,
  `name_customer` varchar(255) NOT NULL,
  `date_start` date NOT NULL default '0000-00-00',
  `date_end` date NOT NULL default '0000-00-00',
  `tax_number` varchar(255) NOT NULL default '0',
  `tax_default` int(11) NOT NULL default '0',
  `address1_street` varchar(255) NOT NULL,
  `address1_city` varchar(255) NOT NULL,
  `address1_state` varchar(255) NOT NULL,
  `address1_country` varchar(255) NOT NULL,
  `address1_zipcode` varchar(10) NOT NULL default '0',
  `address2_street` varchar(255) NOT NULL,
  `address2_city` varchar(255) NOT NULL,
  `address2_state` varchar(255) NOT NULL,
  `address2_country` varchar(255) NOT NULL,
  `address2_zipcode` varchar(10) NOT NULL default '0',
  `discount` float NOT NULL,
  `portal_password` varchar(255) NOT NULL,
  `portal_salt` varchar(20) NOT NULL,
  `portal_login_time` bigint(20) NOT NULL,
  `portal_login_ipaddress` varchar(15) NOT NULL,
  `reseller_customer` varchar(32) NOT NULL default 'standard',
  `reseller_id` int(11) default NULL,
  `billing_method` varchar(12) NOT NULL default 'manual',
  `billing_direct_debit` varchar(32) default NULL,
  PRIMARY KEY  (`id`),
  KEY `reseller_id` (`reseller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers_credits`
--

DROP TABLE IF EXISTS `customers_credits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers_credits` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `date_trans` date NOT NULL,
  `type` varchar(10) NOT NULL,
  `amount_total` decimal(11,2) NOT NULL,
  `id_custom` int(10) unsigned NOT NULL,
  `id_employee` int(10) unsigned NOT NULL,
  `id_customer` int(10) unsigned NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers_credits`
--

LOCK TABLES `customers_credits` WRITE;
/*!40000 ALTER TABLE `customers_credits` DISABLE KEYS */;
/*!40000 ALTER TABLE `customers_credits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers_orders`
--

DROP TABLE IF EXISTS `customers_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers_orders` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_customer` int(10) unsigned NOT NULL,
  `date_ordered` date NOT NULL,
  `type` varchar(15) NOT NULL,
  `customid` int(10) unsigned NOT NULL,
  `quantity` float NOT NULL,
  `units` varchar(10) NOT NULL,
  `amount` decimal(11,2) NOT NULL,
  `price` decimal(11,2) NOT NULL,
  `discount` int(3) unsigned NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers_orders`
--

LOCK TABLES `customers_orders` WRITE;
/*!40000 ALTER TABLE `customers_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `customers_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers_taxes`
--

DROP TABLE IF EXISTS `customers_taxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers_taxes` (
  `id` int(11) NOT NULL auto_increment,
  `customerid` int(11) NOT NULL,
  `taxid` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers_taxes`
--

LOCK TABLES `customers_taxes` WRITE;
/*!40000 ALTER TABLE `customers_taxes` DISABLE KEYS */;
/*!40000 ALTER TABLE `customers_taxes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `file_upload_data`
--

DROP TABLE IF EXISTS `file_upload_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `file_upload_data` (
  `id` int(11) NOT NULL auto_increment,
  `fileid` int(11) NOT NULL default '0',
  `data` blob NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='Table for use as database-backed file storage system';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `file_upload_data`
--

LOCK TABLES `file_upload_data` WRITE;
/*!40000 ALTER TABLE `file_upload_data` DISABLE KEYS */;
INSERT INTO `file_upload_data` VALUES (1,1,'âPNG\r\n\Z\n\0\0\0\rIHDR\0\0\0Ê\0\0\0<\0\0\0rœ∆Ú\0\0\0sRGB\0ÆŒÈ\0\0\0bKGD\0ˇ\0ˇ\0ˇ†Ωßì\0\0\0	pHYs\0\0\0\0\0öú\0\0\0tIMEŸ+x3∂S\0\0\0tEXtComment\0Created with The GIMPÔd%n\0\0\rèIDATx⁄Ìùy|T’«øìIB6≤cRH$ÅêÅ\0	à‚ÖZ®∂µ‚ÇTî≤©µUiU‹Z§nAA≠E§¥®®-X,‘ZdkX¬\"»÷@\"Ñ d≤/˝„LxÔÕL&ô,$–Û˝|ﬁ\'πÔΩπ˜Ω˚ÓÔùsœΩs«V≥Ü\ZEiS¯i(ä\nSQ¶¢®0EQa*ä\nSQ¶¢®0EQa*ä¢¬T¶¢(ÕÅ´ñúQ\r?ø¶Œl–ß÷TÊA`úë˛z\Z~ˆú_F¸è ø–Hœè‹⁄ÚÂNxÊ-5“Y©∞iû\n” Âà˘æ¬¨Çœ¸UXˇG¨ÿ+6ÈËˆ´1j1•UY∑^x◊Hwé˚ˇ¶ˆ1E-fÿ˘/Ælµ>1EÖŸ*øaÁ∆C À`≥˚Ú¡â¨ÁEÅÑ©÷}¶CÒNgßeD\rá–î,A{®ö*OBÒ^8µ\néŒÉÚcûØ%Ì-7]«B(Xˇ‚Óê|+OB¡R88îïÛB{C“cy5ÿ√†Ùê‹ˇ·Pu∆Ωú‘7¨¡≤¸Öpb)tºæ3B“Å\ZplÉ‹◊ ˇÌ¶?èÄ8…?jÑ§Å$TA…>8πrgC≈Òfo/,Ü/r Á†KÛ87N∑Óõ¸C∏™èëÆ™ÇWˇ\no˝rAÄ?ÙIÅâ◊√≠CUò-Kyûà&ÈQc_‘Pÿ∏ é8Ô.∫/Ñ†D„úºÜ(Q◊∫Áo/í-r0$<97Ict%ˆc§ãwC‚CqπÈEr|Áß˝}ÿ:⁄˜ókÛ0Œ	ÈùßAÙwaÎ`®.u)gî5öÍÿ	\'A¥Kk [‘Pÿ}\'–HO#~tõ\rˆ`óPÙá˛êÄîq‚√f}º_‰¿íπÔ/.sﬂ?“TÕ•Âp˝Ø≠£ÚJXªC∂OæÑ†@¶ÔDè®˚X…>Ÿj9¯D^%¬èÄKÊCˆP±›ÊXEYºˆNÙÓ*WùÅÍ\nàõ©Ó=ﬁÖ/SÎ∂úµ$<\0ˆœ«⁄≈C˙˚⁄”*J3Ì≥†”}pd¶˜rÎ^ß∞∆àı<ÚúÔœ°„$HùÌæø‚§‘s≠ß‚ﬂ“ﬂÉm◊A·äVo>Õµä“ïwVBhê\n”w2ñ◊}Ï‡ì\"F√iÅ]∑@øˇ+Í\ZH∏ éAúiP¨™véÜjá5œ≤£ío¡«‡ÿnX)ø àiê∆W+Œé‡‡tÔ˜`Åí˝px&TC“t±ÜgÖó)ø]πØÇtôaîrÌı	”\né]p‡QqÉ√8Û1π’IO¿±7†ÚT√üAªãe(ÀÃâe∞wîÁÇ§Ω~‰Ù.ÏRO∫BMy≥4É;á√e=d∏dÂfìSÊïÌü&˜Á¬úè¨«‚¢‡Ÿ{ £ã∏µèºπ\'Tò-OŸa˜XËı7c_Úw7pˇœ¡ëÌ˛˘›wxŒ∑∫éøÉ≠}‘à!ı_SU	¸Á\Z(;Ï¥ƒêæÿ≈˙ÔáÏaP]b∏∫Iè«C”ë†π7¥ !ﬁAmüµh3T|#Ã¸íËp#{”ky¯µ3˘Ç˘ê3\Z™ã%]Y\0ªnó.ÄÑÏJÄÿ‡¯ªÕÚXG\rÑQ@QâUò·°ã—û?≥`Tª,-∑‰I‘K˛œLÖû…ê9û6ø›Ö1éY∞é¸Ôw6∆ ŸŒFñ¿—◊º‘BîLthﬂ⁄uK‰ÁÏà%πXìÑ˙ØÁ‰rCîµ}NWÚ¢(⁄Ê“«µãÂÛfÈ\n˛nàÚÏΩ~Ö÷ Q¯Âæ	3Új˜.DÃu˙˘˘Ü0k?◊L¬lTøtßKL.—e-}R†_\Zl‹£¬ÙçÏa^˙ò_◊}ÏÎá!Ú\nÈüY>sˆ‹Ì≈:LÑ.3≈MmˆúÁ»q±lEŒŸÓ“ø≠ÙÄ™ÁÒÔÚdFEH˝M/ìNæ=É†Œ÷tƒ ŸÍuÅZµÈ∏∫®iâûœª‰b¶Ô~⁄º˘ŸÏ‘9è\"f§ŒÒ‡5Ü+b≠Wõ≠˛2]˚±û‹Q◊·ê∫Aﬁ®©®cøK?œÊcﬁ∂F6{X´6ù\nów[`∑@õÁ¬ôí◊ıwÓ÷$*{…[∞„˜cù¶X”•á$∫Xl≤x…œ@Á_∑Õ{Ïÿ∞˝>∂÷aô¬’êˇN˝ü+œk’Íà	áØrçÙ—:n˚®Œ’%ëÿ≥™™Àå(gÏı–i*‰Œ≤~.‰k:°Uî\0aYm˜æ£G\0vq_Õ˜úl=Ø(€∑|œlrüúƒ@ﬁ¸z¨l ç/ı÷@Ìﬁ≠¢ôﬁ)∞ﬁ‰›o⁄#:D\Z˚N;‡ÛmmøI∑Ωπ≤]ü˜æuuÈ◊$ ÿ•ôè¡Wì]Ú}¬˙zÔ◊Öˆ∂¶;‹—√€Ó”NÜî\rW5†§yÚ¸’∑|Ûˇ‰ÚrÍ\rùCf]∏ZÁxô|1`ø∏˝ÕLlÑãµ+ÄwV»–»¡<Ÿjπa∞ãØÑq3·å3òÏ(ÅªüÉ¢Rµòæì¯†˜„À†tmß∫/í…\0g›Æ¬ëÁÂÌs\\‰å≠˚Bè≈∞9”∆|ª÷j]bGBÊz8ΩÇSDî\rÈS∂&	Se⁄_˘1ÓbÊ\0ô≤Áÿ·c?•å[∆é4πÙOA‹¯ˆs¶ÒèÜ∞ô(akπ˜˚ÄÓÓ˚Óò·Ú~]#á˜óÔVnﬁk[∂^æ˜ôáÚ¡qà≤mZL_H~“òıPq :mÔ(5\r[ÑtÉ‘πF˙o§ëô	\0	ì!fÑÃoÕ_‘vÔ?ë‹s@$ÑvweYÆ‘Acÿuút	ƒÖ§ ¥¬Ñ©2´(,£EE	–∑õÆA1+,ò.ì°äÀdrÅ£ba‰e*Ãñ#ÚZ∏xöuﬂWì¨„áïßd ‹¸≠î∏€ ~¨ÛâÌñ·ôb±Û”`ÎPÚU€≠«vôS{z£´∑\n∂rÁl(Ußa€pÿ3äºX‹äB8˛Ï∫SÇF-¿áO√„w@œ$iÁ˝‹IÔWåŸ@fÜe¡∫9–©C€oﬁ6˝>Á˚)¨wïUäs<O\nhmº-	“Cæ!S„¸vIsøP„Â€+µ›Ü BW.=ÿ\"AüÊ`«ÿuHH]†kßÛßEÍ\n \r´hãlÁ+≈9ÓÂÊ§<Ø’áC|•g≤lÁ©©PEÖ©(ä∫≤Ÿ√≠S˜ ˛´u¢¬TZG∂÷Å∫≤ä¢®0EQa*ä\nSQ¶¢®0aﬂkô|#Gûøu2·EXª›{›ú‰πÕˇõœQa*Áú™™Î~÷Á»RîﬁX7ß˛|\Zré\nÛª?√¨%Úˇ≥·ö‰ˇ’[‡∂gö~‹Ã¸O‡“	ê1Nñ7‹∏€˙÷˛ÌB3Ëz+,]O/ÄÀ&BÚÕ∞Ïk^œÉæwC÷=p¿¥6Ù7Öp›√–k¨î±yèµåiØCø{·≥l˘\\÷=íœCs›≠rCØÁ˜ÔCÍË˝SYS’ïo\na‰4HøKÓ}ÕVŸ(Mñk<E“ç)◊!HM\0ª›{›4ƒ”pµ§œ.ígñ|3|l*sÆ‘om›ùèﬁFõÊ∆2õˆ»\Z£ï≤oHF”èõπa0|9∂ΩÛÅ…/[-XßXÿ\Zº˜‹¸§tÇıØ¬íß¨¬©™íÜ∏ıM˘≠å˚MãôO}E÷D›˛Gx{ö∏xµTV…ÑÎMÛ‡öL˘‹ƒÎ%ü¥D(+o‹ı<ı∂úó˝xÙv˜:û2ÆÏ\r;ÁÀ9Y©∆˛qﬂìk;BÆΩ1Â/ﬂ\0#.≠øn\Z„U$têg∂x:¸“TÊ˝≥·˛•úû…÷∫Sa6Y©\"®”hóßK˙ÛÌ\"∫¶7≥˚∞X‘ûcÂÚÊ≥±Ÿ‡Áœõd¶Bu5‹tïëv]Ù©ˆ‹€Ü…Ôe‘≤j3¸¸UËs7‹˛[8iZ(œ¸¯J#˝ÔÌ÷|Ã)¯r=Éz¬ù3‡O+›◊œ¯t3L¯Åëox®ÁÚÕ˜·K˘ˇÿh”[›¯äÕ£ùÀﬂ^⁄›ZÊ⁄p£≥.G_›ˆ°Dõûí\0ù„≈Õò]≈’⁄ó›;KÖ7Â∏ô1øÅ∑ëÇKÀ!zîÈÌÂg]Ú∞]†·ö˘Bu\r¨õ\r!~;√no¯è›¯r==´∑¬‚5«O‡”‹∏œoÛñ_\\\nßä†cÛ/Ñüü¸äó7·j≥%›Ÿ^¸b“[˛ü˚7ËõbT|Sè◊r¶X\\#Ä7ñ…˜çÀbÁ\Z4Y\rÉM+Å_õ	ÛñZ˚_u1∏ó5ü∆^OÓ	÷ûõ\0Ÿ˚‹èÕí:)„¥√ËF‘ñˇÁUby}eÕV∏∫o√Í¶9ÿ”¯E∞˜?k⁄≥TazÈg+ÄÀ{@\\¥X≥⁄î„Ê˚Ã{a»}–ˇ^(<#V†1ÿÌ∞Áàf-ÅóLeÃæVmë@K˙]∞pe›˘º4	^˘@Ú…9ÿ¯ÎπÂi	¸\\1UÓ—ıægM•ﬂ%.ˆÁ¬/OÅ7?ñ‡œõK⁄WñiucΩ’MsÚ“$˘}ÕÃÒ∞uÑü¬‘•Eî#sºàŒqá©§L^¿6|/ò˝!¨~I˚òä¿ñ7Zß‹d˝ÿäJn\'pµòä¢\\¯}LEQa*ä¢¬TEÖ©(*LEQTòä¢¬T•e˘ôıï¬2\ZÍñ\0\0\0\0IENDÆB`Ç');
/*!40000 ALTER TABLE `file_upload_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `file_uploads`
--

DROP TABLE IF EXISTS `file_uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `file_uploads` (
  `id` int(11) NOT NULL auto_increment,
  `customid` int(11) NOT NULL default '0',
  `type` varchar(20) NOT NULL,
  `timestamp` bigint(20) unsigned NOT NULL default '0',
  `file_name` varchar(255) NOT NULL,
  `file_size` varchar(255) NOT NULL,
  `file_location` char(2) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `file_uploads`
--

LOCK TABLES `file_uploads` WRITE;
/*!40000 ALTER TABLE `file_uploads` DISABLE KEYS */;
INSERT INTO `file_uploads` VALUES (1,0,'COMPANY_LOGO',1234659343,'company_logo.png','3640','db');
/*!40000 ALTER TABLE `file_uploads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `input_structure_items`
--

DROP TABLE IF EXISTS `input_structure_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `input_structure_items` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_structure` int(10) unsigned NOT NULL,
  `field_src` varchar(64) NOT NULL,
  `field_dest` varchar(64) NOT NULL,
  `regex` varchar(255) NOT NULL,
  `processing_regex` varchar(255) NOT NULL,
  `data_format` varchar(32) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `id_structure_2` (`id_structure`,`field_src`,`field_dest`),
  KEY `id_structure` (`id_structure`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `input_structure_items`
--

LOCK TABLES `input_structure_items` WRITE;
/*!40000 ALTER TABLE `input_structure_items` DISABLE KEYS */;
INSERT INTO `input_structure_items` VALUES (1,1,'1','date','','','dd-mm-yyyy'),(2,1,'2','amount','','',''),(3,1,'3','other_party','','',''),(4,1,'4','transaction_type','','',''),(5,1,'5','reference','','',''),(6,1,'6','particulars','','',''),(7,1,'7','code','','',''),(8,2,'2','other_party','','',''),(9,2,'4','transaction_type','','',''),(10,2,'5','reference','','',''),(11,2,'6','amount','','',''),(12,2,'7','date','','','dd-mm-yyyy'),(13,3,'1','date','','','dd-mm-yyyy'),(14,3,'3','particulars','','',''),(15,3,'4','reference','','',''),(16,3,'5','other_party','','',''),(17,3,'6','transaction_type','','',''),(18,3,'7','amount','','','');
/*!40000 ALTER TABLE `input_structure_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `input_structures`
--

DROP TABLE IF EXISTS `input_structures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `input_structures` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `type_input` varchar(64) NOT NULL,
  `type_file` char(4) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `input_structures`
--

LOCK TABLES `input_structures` WRITE;
/*!40000 ALTER TABLE `input_structures` DISABLE KEYS */;
INSERT INTO `input_structures` VALUES (1,'Westpac','Westpac CSV Import','bank_statement','csv'),(2,'National Bank','National Bank CSV Import','bank_statement','csv'),(3,'ASB','ASB CSV Import','bank_statement','csv');
/*!40000 ALTER TABLE `input_structures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `journal`
--

DROP TABLE IF EXISTS `journal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `journal` (
  `id` int(11) NOT NULL auto_increment,
  `locked` tinyint(1) NOT NULL default '0',
  `journalname` varchar(50) NOT NULL,
  `type` varchar(20) NOT NULL,
  `userid` int(11) NOT NULL default '0',
  `customid` int(11) NOT NULL default '0',
  `timestamp` bigint(20) unsigned NOT NULL default '0',
  `content` text NOT NULL,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `journalname` (`journalname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `journal`
--

LOCK TABLES `journal` WRITE;
/*!40000 ALTER TABLE `journal` DISABLE KEYS */;
/*!40000 ALTER TABLE `journal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `language`
--

DROP TABLE IF EXISTS `language`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `language` (
  `id` int(11) NOT NULL auto_increment,
  `language` varchar(20) NOT NULL,
  `label` varchar(255) NOT NULL,
  `translation` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `language` (`language`),
  KEY `label` (`label`)
) ENGINE=InnoDB AUTO_INCREMENT=587 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `language`
--

LOCK TABLES `language` WRITE;
/*!40000 ALTER TABLE `language` DISABLE KEYS */;
INSERT INTO `language` VALUES (1,'en_us','name_customer','Customer Name'),(2,'en_us','name_contact','Contact Name'),(4,'en_us','customer_view','Customer Details'),(5,'en_us','address_billing','Billing Address'),(6,'en_us','address_shipping','Shipping Address'),(7,'en_us','id_customer','Customer ID'),(8,'en_us','submit','Save Information'),(9,'en_us','username_amberdms_bs','Username'),(10,'en_us','password_amberdms_bs','Password'),(11,'en_us','name_staff','Employee Name'),(12,'en_us','filter_date_start','Start Date'),(13,'en_us','filter_date_end','End Date'),(14,'en_us','filter_employee_id','Employee'),(15,'en_us','filter_customerid','Customer'),(16,'en_us','filter_hide_closed','Hide Options'),(17,'en_us','code_invoice','Invoice Number'),(18,'en_us','code_ordernumber','Order Number'),(19,'en_us','code_ponumber','PO Number'),(20,'en_us','date_trans','Transaction Date'),(21,'en_us','date_due','Date Due'),(22,'en_us','amount_tax','Amount of Tax'),(23,'en_us','amount_total','Total Amount'),(24,'en_us','amount_paid','Amount Paid'),(25,'en_us','sent','Sent'),(26,'en_us','filter_employeeid','Employee'),(27,'en_us','amount','Amount'),(28,'en_us','customerid','Customer'),(29,'en_us','employeeid','Employee'),(30,'en_us','dest_account','Destination Account'),(31,'en_us','notes','Notes/Details'),(32,'en_us','ar_invoice_details','AR Invoice Details'),(33,'en_us','ar_invoice_financials','AR Invoice Financials'),(34,'en_us','ar_invoice_other','AR Invoice Additional Details'),(35,'en_us','item_info','Item Information'),(36,'en_us','price','Price'),(37,'en_us','qnty','Qnty'),(38,'en_us','quantity','Quantity'),(39,'en_us','units','Units'),(40,'en_us','description','Description'),(41,'en_us','name_tax','Tax Name'),(42,'en_us','chartid','Account'),(43,'en_us','ar_invoice_item','AR Invoice Item Details'),(44,'en_us','timegroupid','Time Group'),(45,'en_us','productid','Product'),(46,'en_us','account','Account'),(47,'en_us','source','Source'),(48,'en_us','filter_title_search','Search Title'),(49,'en_us','filter_content_search','Search Content'),(50,'en_us','filter_hide_events','Hide Options'),(51,'en_us','ar_invoice_delete','Delete AR Invoice'),(52,'en_us','name_vendor','Vendor Name'),(53,'en_us','filter_vendorid','Vendor'),(54,'en_us','ap_invoice_details','AP Invoice Details'),(55,'en_us','ap_invoice_financials','AP Invoice Financials'),(56,'en_us','ap_invoice_other','AP Invoice Additional Details'),(57,'en_us','ap_invoice_item','AP Invoice Item Details'),(58,'en_us','ap_invoice_delete','Delete AP Invoice'),(59,'en_us','quote_details','Quote Details'),(60,'en_us','quote_other','Quote Additional Details'),(61,'en_us','quotes_invoice_item','Quote Item Details'),(62,'en_us','quote_delete','Delete Quote'),(63,'en_us','quote_convert_details','AR Invoice Details'),(64,'en_us','quote_convert_financials','AR Invoice Financials'),(65,'en_us','journal_edit','Journal Entry Details'),(66,'en_us','title','Entry Title'),(67,'en_us','contents','Entry Content/Details'),(68,'en_us','upload','File to Upload'),(69,'en_us','code_quote','Quote Number'),(70,'en_us','delete_confirm','Confirm Deletion'),(71,'en_us','date_validtill','Expiry Date'),(72,'en_us','taxrate','Tax Rate'),(73,'en_us','taxnumber','Tax Number'),(74,'en_us','tax_details','Tax Details'),(75,'en_us','tax_delete','Delete Tax'),(76,'en_us','filter_mode','Mode'),(77,'en_us','code_chart','Account ID'),(78,'en_us','chart_type','Account Type'),(79,'en_us','debit','Debit'),(80,'en_us','credit','Credit'),(81,'en_us','chart_details','Account Details'),(82,'en_us','chart_delete','Delete Account'),(83,'en_us','memo','Memo/Details'),(84,'en_us','item_id','Transaction ID'),(85,'en_us','code_reference','Transaction ID'),(86,'en_us','filter_search','Search'),(87,'en_us','general_ledger_transaction_details','GL Transaction Details'),(88,'en_us','code_gl','Transaction ID'),(89,'en_us','filter_searchbox','Search'),(90,'en_us','code_customer','Customer ID'),(91,'en_us','contact_phone','Phone Number'),(92,'en_us','contact_email','Email Address'),(93,'en_us','contact_fax','Fax Number'),(94,'en_us','date_start','Start Date'),(95,'en_us','date_end','End Date'),(96,'en_us','tax_number','Tax Number'),(97,'en_us','address1_street','Street'),(98,'en_us','address1_city','City'),(99,'en_us','address1_state','State'),(100,'en_us','address1_country','Country'),(101,'en_us','address1_zipcode','Zipcode'),(102,'en_us','address2_street','Street'),(103,'en_us','address2_city','City'),(104,'en_us','address2_state','State'),(105,'en_us','address2_country','Country'),(106,'en_us','address2_zipcode','Zipcode'),(107,'en_us','filter_hide_ex_customers','Hide Options'),(108,'en_us','tax_default','Default Tax'),(109,'en_us','customer_taxes','Customer Tax Options'),(110,'en_us','name_service','Service Name'),(111,'en_us','active','Active'),(112,'en_us','typeid','Type'),(113,'en_us','date_period_next','Next Period'),(114,'en_us','label','translation'),(115,'en_us','service_edit','Service Details'),(116,'en_us','service_add','Service Details'),(117,'en_us','service_billing','Service Billing Details'),(118,'en_us','customer_delete','Delete Customer'),(119,'en_us','name_service','Service Name'),(120,'en_us','active','Active'),(121,'en_us','typeid','Type'),(122,'en_us','date_period_next','Next Period'),(123,'en_us','label','translation'),(124,'en_us','service_edit','Service Details'),(125,'en_us','service_add','Service Details'),(126,'en_us','service_billing','Service Billing Details'),(127,'en_us','customer_delete','Delete Customer'),(128,'en_us','paid','Paid'),(129,'en_us','invoiced','Invoiced'),(130,'en_us','service_delete','Delete Service'),(131,'en_us','code_vendor','Vendor ID'),(132,'en_us','filter_hide_ex_vendors','Hide Options'),(133,'en_us','vendor_taxes','Vendor Tax Options'),(134,'en_us','vendor_view','Vendor Details'),(135,'en_us','vendor_delete','Delete Vendor'),(136,'en_us','staff_code','Employee ID'),(137,'en_us','staff_position','Employee Position'),(138,'en_us','filter_hide_ex_employees','Hide Options'),(139,'en_us','staff_view','Employee Details'),(140,'en_us','staff_delete','Delete Employee'),(141,'en_us','code_product','Product ID'),(142,'en_us','name_product','Product Name'),(143,'en_us','account_sales','Sales Account'),(144,'en_us','price_cost','Cost Price'),(145,'en_us','price_sale','Sale Price'),(146,'en_us','date_current','Current Date'),(147,'en_us','quantity_instock','Quantity Instock'),(148,'en_us','paid','Paid'),(149,'en_us','invoiced','Invoiced'),(150,'en_us','service_delete','Delete Service'),(151,'en_us','code_vendor','Vendor ID'),(152,'en_us','filter_hide_ex_vendors','Hide Options'),(153,'en_us','vendor_taxes','Vendor Tax Options'),(154,'en_us','vendor_view','Vendor Details'),(155,'en_us','vendor_delete','Delete Vendor'),(156,'en_us','staff_code','Employee ID'),(157,'en_us','staff_position','Employee Position'),(158,'en_us','filter_hide_ex_employees','Hide Options'),(159,'en_us','staff_view','Employee Details'),(160,'en_us','staff_delete','Delete Employee'),(161,'en_us','code_product','Product ID'),(162,'en_us','name_product','Product Name'),(163,'en_us','account_sales','Sales Account'),(164,'en_us','price_cost','Cost Price'),(165,'en_us','price_sale','Sale Price'),(166,'en_us','date_current','Current Date'),(167,'en_us','quantity_instock','Quantity Instock'),(168,'en_us','quantity_vendor','Vendor\'s Stock Quantity'),(169,'en_us','product_view','Product Details'),(170,'en_us','product_pricing','Product Pricing'),(171,'en_us','product_quantity','Product Stock'),(172,'en_us','product_supplier','Supplier Details'),(173,'en_us','code_product_vendor','Vendor Product ID'),(174,'en_us','product_delete','Delete Product'),(175,'en_us','included_units','Units Included'),(176,'en_us','price_extraunits','Price (per extra unit)'),(177,'en_us','billing_cycle','Billing Cycle'),(178,'en_us','service_details','Service Details'),(179,'en_us','service_plan','Service Plan Details'),(180,'en_us','service_plan_custom','Service Plan Options'),(181,'en_us','billing_mode','Billing Mode'),(182,'en_us','code_project','Project ID'),(183,'en_us','filter_hide_ex_projects','Hide Options'),(184,'en_us','details','details'),(185,'en_us','project_view','Project Details'),(186,'en_us','name_phase','Phase Name'),(187,'en_us','phase_edit','Phase Details'),(188,'en_us','date','Date'),(189,'en_us','time_group','Time Group'),(190,'en_us','time_booked','Time Booked'),(191,'en_us','filter_phaseid','Phase Name'),(192,'en_us','phaseid','Phase Name'),(193,'en_us','timereg_day','Day Time Booking'),(194,'en_us','filter_no_group','Hide Options'),(195,'en_us','time_billed','Billable Hours'),(196,'en_us','time_not_billed','Unbillable Hours'),(197,'en_us','timebilled_details','Time Group Details'),(198,'en_us','timebilled_selected','Time Group Registered Hours'),(199,'en_us','name_group','Time Group Name'),(200,'en_us','time_bill','Billable'),(201,'en_us','time_nobill','Unbillable'),(202,'en_us','project_delete','Delete Project'),(203,'en_us','name_project','Project Name'),(204,'en_us','projectandphase','Project/Phase'),(205,'en_us','priority','Priority'),(206,'en_us','filter_hide_ex_tickets','Hide Options'),(207,'en_us','status','Status'),(208,'en_us','code_support_ticket','Ticket ID'),(209,'en_us','support_ticket_details','Ticket Details'),(210,'en_us','support_ticket_status','Ticket Status'),(211,'en_us','support_delete','Delete Ticket'),(212,'en_us','username','Username'),(213,'en_us','realname','Realname'),(214,'en_us','password','Password'),(215,'en_us','password_confirm','Password - Confirm'),(216,'en_us','time','Time'),(217,'en_us','ipaddress','IP Address'),(218,'en_us','option_lang','Languages'),(219,'en_us','option_debug','Debugging'),(220,'en_us','user_view','User Details'),(221,'en_us','user_password','User Authentication'),(222,'en_us','user_info','User Information'),(223,'en_us','user_options','Account Options'),(224,'en_us','lastlogin_time','Lastlogin Time'),(225,'en_us','lastlogin_ipaddress','Lastlogin IP Address'),(226,'en_us','id_user','User ID'),(227,'en_us','user_permissions','User Permissions'),(228,'en_us','user_permissions_staff','User Staff Access Rights'),(229,'en_us','user_delete','Delete User Account'),(230,'en_us','blacklist_control','Blacklist Options'),(231,'en_us','blacklist_enable','Enable Blacklisting'),(232,'en_us','blacklist_limit','Max authentication attempts'),(233,'en_us','date_period_first','First Period'),(234,'en_us','option_dateformat','Date Format'),(235,'en_us','option_timezone','Timezone'),(236,'en_us','general_ledger_transaction_rows','GL Transaction Rows'),(237,'en_us','vendorid','Vendor'),(238,'en_us','tax_setup_options','Tax Setup Options'),(239,'en_us','ar_invoice_item_tax','AR Invoice Item Tax Selection'),(240,'en_us','ap_invoice_item_tax','AP Invoice Item Tax Selection'),(241,'en_us','instance_amberdms_bs','Customer Instance ID'),(242,'en_us','user_permissions_selectstaff','User Staff Configuration'),(243,'en_us','id_staff','Employee'),(244,'en_us','auditlock','Audit Locking'),(245,'en_us','date_lock','Lock Before'),(246,'en_us','content','Content'),(247,'en_us','content','Content'),(248,'en_us','option_shrink_tableoptions','Table Options Feature'),(249,'en_us','option_concurrent_logins','Concurrent Logins'),(250,'en_us','tbl_lnk_details','details'),(251,'en_us','tbl_lnk_permissions','permissions'),(252,'en_us','tbl_lnk_staffaccess','staffaccess'),(253,'en_us','tbl_lnk_timesheet','timesheet'),(254,'en_us','tbl_lnk_view_timeentry','View Time Entry'),(255,'en_us','date_as_of','Date as of'),(256,'en_us','mode','Mode'),(257,'en_us','service_tax','Service Tax'),(258,'en_us','usage_mode','Usage Mode'),(259,'en_us','serviceid','Service Plan'),(260,'en_us','timebilled_selection','Time Selection'),(261,'en_us','filter_groupby','Group By'),(262,'en_us','sender','Sender'),(263,'en_us','subject','Subject'),(264,'en_us','email_to','Email (To)'),(265,'en_us','email_message','Email Message'),(266,'en_us','email_cc','Email (CC)'),(267,'en_us','email_bcc','Email (BCC)'),(268,'en_us','account_purchase','Purchase Account'),(269,'en_us','description_useall','Description Useall'),(270,'en_us','general_ledger_transaction_submit','GL Transaction Submit'),(271,'en_us','invoice_gen_date','Invoice Gen Date'),(272,'en_us','services_customers_id','Service-Customer Assignment ID'),(273,'en_us','alert_extraunits','Alert for every specified number of extra units'),(274,'en_us','service_plan_alerts','Service Plan Alerts'),(275,'en_us','usage_summary','Usage Summary'),(276,'en_us','internal_only','Internal Only'),(277,'en_us','id_support_ticket','Ticket ID'),(278,'en_us','discount','Discount'),(279,'en_us','customer_purchase','Customer Purchase Options'),(280,'en_us','vendor_purchase','Vendor Purchase Options'),(281,'en_us','service_options_licenses','Service Options'),(282,'en_us','filter_hide_ex_products','Hide EOL Products'),(283,'en_us','option_default_employeeid','Default Employee'),(284,'en_us','taxid','Tax ID'),(285,'en_us','manual_option','Manual Option'),(286,'en_us','manual_amount','Manual Amount'),(287,'en_us','patch_contents','Patch Contents'),(288,'en_us','patch_submit','Submit Patch'),(289,'en_us','patch_submit_contact','Author\'s Email'),(290,'en_us','patch_submit_credit','Developer to credit'),(291,'en_us','patch_description','Patch Description'),(292,'en_us','use_this_template','Use This Template'),(293,'en_us','template_selection','Template Selection'),(294,'en_us','filter_billable_only','Billable Only'),(295,'en_us','option_theme','Theme'),(296,'en_us','id_service_customer','Service-Customer Assignment ID'),(297,'en_us','service_controls','Service Control and Management'),(298,'en_us','service_bundle_item','Service Bundle Components'),(299,'en_us','service_bundle','Service Bundle Details'),(300,'en_us','service_type','Service Type'),(301,'en_us','tbl_lnk_delete','delete'),(302,'en_us','bundle_details','Bundle Details'),(303,'en_us','description_service','Service Description'),(304,'en_us','description_bundle','Bundle Description'),(305,'en_us','name_bundle','Bundle Name'),(306,'en_us','bundle_services','Bundle Services'),(307,'en_us','id_service','Service'),(308,'en_us','bundle_services','Bundle Services'),(309,'en_us','id_service','Service'),(310,'en_us','menu_config_company','Company Details'),(311,'en_us','menu_config_locale','Locale'),(312,'en_us','menu_config_integration','Integration'),(313,'en_us','menu_config_app','Application'),(314,'en_us','menu_config_services','Services'),(315,'en_us','config_company_details','Company Details'),(316,'en_us','config_company_contact','Company Contact Details'),(317,'en_us','config_company_invoices','Invoice Configuration'),(318,'en_us','config_appearance','Application Appearance'),(319,'en_us','config_date','Date Settings'),(320,'en_us','config_currency','Currency Configuration'),(321,'en_us','config_integration','Integration and connectivity options'),(322,'en_us','config_defcodes','Default Application Configuration'),(323,'en_us','config_accounts','Accounts Options'),(324,'en_us','config_timesheet','Timesheet Configuration'),(325,'en_us','config_auditlocking','Audit Locking Configuration'),(326,'en_us','config_contributions','Contributions'),(327,'en_us','config_security','Security Configuration'),(328,'en_us','config_misc','Miscellaneous Options'),(329,'en_us','config_dangerous','Dangerous/System Options'),(330,'en_us','tbl_lnk_adjust_override','Adjust Override'),(331,'en_us','tbl_lnk_override','Override Rate'),(332,'en_us','tbl_lnk_delete_override','Delete Override'),(333,'en_us','id_rate_table','Rate Table'),(334,'en_us','filter_service_type','Service Type Filter'),(335,'en_us','menu_service_cdr_rates','Manage CDR Pricing Rates'),(336,'en_us','tbl_lnk_product_details','Product Details'),(337,'en_us','product_tax','Product Tax Options'),(338,'en_us','rate_table_name','Rate Table Name'),(339,'en_us','rate_table_description','Description'),(340,'en_us','tbl_lnk_items','Items'),(341,'en_us','filter_name_vendor','Vendor Filter'),(342,'en_us','rate_table_view','Rate Table Details'),(343,'en_us','id_vendor','Vendor'),(344,'en_us','rate_prefix','Rate Prefix'),(345,'en_us','rate_description','Description'),(346,'en_us','rate_price_sale','Sale Price'),(347,'en_us','rate_price_cost','Cost Price'),(348,'en_us','tbl_lnk_item_edit','Edit Rate'),(349,'en_us','tbl_lnk_item_delete','Delete Rate'),(350,'en_us','rate_table_delete','Delete Rate Table'),(351,'en_us','rate_table_add','Create Rate Table'),(352,'en_us','create_rate_table','Create Rate Table'),(353,'en_us','rate_override','Rate Override'),(354,'en_us','menu_service_cdr_rates','CDR Rate Tables'),(355,'en_us','menu_service_cdr_rates_view','View Rate Tables'),(356,'en_us','menu_service_cdr_rates_add','Add Rate Table'),(357,'en_us','menu_services_groups','Manage Service Groups'),(358,'en_us','menu_services_groups_view','View Service Groups'),(359,'en_us','menu_services_groups_add','Add Service Group'),(360,'en_us','group_name','Group Name'),(361,'en_us','group_description','Group Description'),(362,'en_us','service_group_view','Group Details'),(363,'en_us','service_group_members','Group Members'),(364,'en_us','id_service_group','Service Group'),(365,'en_us','filter_id_service_group','Service Group Filter'),(366,'en_us','service_group_delete','Delete Service Group'),(367,'en_us','service_group_add','Add Service Group'),(368,'en_us','id_usage_mode','Usage Mode'),(369,'en_us','group_products','Products'),(370,'en_us','group_discount','Discounts'),(371,'en_us','group_time','Contracting Hours'),(372,'en_us','group_other','Other'),(373,'en_us','id_parent','Parent'),(374,'en_us','menu_products_groups','Manage Product Groups'),(375,'en_us','menu_products_groups_view','View Product Groups'),(376,'en_us','menu_products_groups_add','Add Product Group'),(377,'en_us','id_product_group','Product Group'),(378,'en_us','filter_id_product_group','Product Group Filter'),(379,'en_us','cdr_rate_import_mode','Rate Import Mode'),(380,'en_us','cdr_rate_import_cost_price','Import Cost Price'),(381,'en_us','cdr_rate_import_sale_price','Import Sale Price'),(382,'en_us','cdr_import_delete_existing','Delete all existing rates in this rate table & insert from import.'),(383,'en_us','cdr_import_update_existing','Update existing rates that have matching prefixes but do not delete any.'),(384,'en_us','cdr_import_cost_price_use_csv','Fetch call cost price from import'),(385,'en_us','cdr_import_cost_price_nothing','Do not fetch call costs from import'),(386,'en_us','cdr_import_sale_price_use_csv','Fetch sale price of calls from import'),(387,'en_us','cdr_import_sale_price_nothing','Do not fetch sale price from import'),(388,'en_us','cdr_rate_import_options ','Call Rate Import Options'),(389,'en_us','cdr_import_sale_price_margin','Take the cost price and add the specified margin'),(390,'en_us','cdr_rate_import_sale_price_margin','Margin to add to cost price to calculate sale price'),(391,'en_us','billing_cycle_string','Billing Cycle'),(392,'en_us','service_price','Service Price'),(393,'en_us','service_options_ddi','Service DDI Configuration'),(394,'en_us','phone_ddi_single','Phone Number'),(395,'en_us','service_migration','Service Migration Options'),(396,'en_us','migration_date_period_usage_override','Migration Date Override Options'),(397,'en_us','migration_use_period_date','Start charging usage from the first period date.'),(398,'en_us','migration_use_usage_date','Start charging usage from the specified date, but charge the plan fee from the first period date.'),(399,'en_us','migration_date_period_usage_first','Usage Start Date'),(400,'en_us','cdr_import_cost_price_nothing','Do not fetch call costs from import'),(401,'en_us','billing_cycle_string','Billing Cycle'),(402,'en_us','service_price','Service Price'),(403,'en_us','service_options_ddi','Service DDI Configuration'),(404,'en_us','phone_ddi_single','Phone Number'),(405,'en_us','service_migration','Service Migration Options'),(406,'en_us','migration_date_period_usage_override','Migration Date Override Options'),(407,'en_us','migration_use_period_date','Start charging usage from the first period date.'),(408,'en_us','migration_use_usage_date','Start charging usage from the specified date, but charge the plan fee from the first period date.'),(409,'en_us','migration_date_period_usage_first','Usage Start Date'),(410,'en_us','address1_same_as_2','Shipping Option'),(411,'en_us','address1_same_as_2_help','Use the billing address as the shipping address'),(412,'en_us','id_service_group_usage','Service Usage Group'),(413,'en_us','projectid','Project'),(414,'en_us','tbl_lnk_attributes','attributes'),(415,'en_us','attribute_key','Attribute Key'),(416,'en_us','attribute_value','Attribute Value'),(417,'en_us','filter_invoice_notes_search','Invoice Notes Search'),(418,'en_us','option_translation','Translation Mode'),(419,'en_us','show_only_non-translated_fields','Show only non-translated fields'),(420,'en_us','show_all_translatable_fields','Show all translatable fields'),(421,'en_us','trans_form_title','Translation Utility'),(422,'en_us','trans_form_desc','Use this utility to translate the application into your native language, by entering the label below followed by the native language version.'),(423,'en_us','translate','Translate'),(424,'en_us','trans_label','Label'),(425,'en_us','trans_translation','Translation'),(426,'en_us','config_usage_traffic','Data Usage/Traffic Configuration'),(427,'en_us','Save Changes','Save Changes'),(428,'en_us','config_usage_cdr','Call Record Database Configuration'),(429,'en_us','config_migration','Service Migration Options'),(430,'en_us','date_sent','Date Sent'),(431,'en_us','days_overdue','Days Overdue'),(432,'en_us','send_reminder','Send Reminder?'),(433,'en_us','menu_config_dependencies','Dependencies'),(434,'en_us','dependency_status','Status'),(435,'en_us','dependency_name','Name'),(436,'en_us','dependency_location','location'),(437,'en_us','tax_set_default','Set Default Taxes'),(438,'en_us','tax_auto_enable','Auto Enable Taxes'),(439,'en_us','price_setup','Setup Charge'),(440,'en_us','service_upstream','Upstream Provider Details'),(441,'en_us','upstream_help_message','Some services are resold services provided by a different provider - for example, a utility service provided by a wholeseller. It can be useful to store notes in a private field below relating to this service, for support and management purposes.'),(442,'en_us','upstream_id','Upstream Vendor'),(443,'en_us','upstream_notes','Service Notes'),(444,'en_us','config_usage_units','Data Usage/Traffic Unit Options'),(445,'en_us','service_units_enabled','Enabled Service Units'),(446,'en_us','service_unit_selection_help','ABS can handle service usage tracking with a number of different unit types, you can select which ones you wish to offer to administrators here - sometimes it can be useful to remove undesired options to clarify the UI for accounts staff.'),(447,'en_us','error_no_units_available','There are no unit types enabled that meet the requirements of this service type - see the admin/services page for configuration.'),(448,'en_us','config_service_types','Service Type Options'),(449,'en_us','service_types_enabled','Enabled Service Types'),(450,'en_us','service_types_selection_help','Not all service types are appropriate for all users, you can enable/disable specific service types to simplify the user interface for regular users.'),(451,'en_us','error_no_types_available','There are no service types enabled!  See the admin/services page for configuration options.'),(452,'en_us','error_no_billing_cycles_available','There are no billing cycles enabled! See the admin/services page for configuration options'),(453,'en_us','config_billing_cycle','Billing Cycle Options'),(454,'en_us','billing_cycle_enabled','Enabled Billing Cycle Options'),(455,'en_us','billing_cycle_selection_help','Not all billing cycles are appropiate for all businesses, you can enable/disable specific billing cycle types here as per your requirements to simplify user options.'),(456,'en_us','config_billing_mode','Billing Mode Options'),(457,'en_us','billing_mode_enabled','Enabled Billing Modes'),(458,'en_us','billing_mode_selection_help','Depending on your business needs, you may wish to disable some of the billing modes to simplify the UI for your users'),(459,'en_us','ipv4_details','IPv4 Details'),(460,'en_us','ipv4_address','IPv4 Address'),(461,'en_us','ipv4_cidr','CIDR/Subnet'),(462,'en_us','customer_contacts','Customer Contact Details'),(463,'en_us','vendor_contacts','Vendor Contact Details'),(464,'en_us','rate_table_details','Rate Table Details'),(465,'en_us','rate_table_items','Rate/Prefix/Area Details'),(466,'en_us','cdr_upload','Rate Table Import'),(467,'en_us','cdr_rate_import_file','Upload Rates File'),(468,'en_us','service_plan_cdr','Call Rates & Pricing'),(469,'en_us','service_plan_ddi','Phone Number/DDI Limits & Rates'),(470,'en_us','service_plan_trunks','Call Trunking Limits & Rates'),(471,'en_us','phone_ddi_included_units','Included Phone Numbers'),(472,'en_us','phone_ddi_price_extra_units','Price per additional phone number'),(473,'en_us','phone_trunk_included_units','Number of Trunks (concurrent calls)'),(474,'en_us','phone_trunk_price_extra_units','Price per additional trunk'),(475,'en_us','ddi_details','Service DDI'),(476,'en_us','ddi_start','DDI Start Range'),(477,'en_us','ddi_finish','DDI End Rage'),(478,'en_us','service_options_trunks','Service Trunk Configuration'),(479,'en_us','phone_trunk_included_units','Included Trunks'),(480,'en_us','phone_trunk_quantity','Quantity of Trunks'),(481,'en_us','number_src','Source Number'),(482,'en_us','number_dst','Destination Number'),(483,'en_us','billable_seconds','Billable Seconds'),(484,'en_us','config_orders','Customer Order Configuration'),(485,'en_us','date_ordered','Date Ordered'),(486,'en_us','order_basic','Order Details'),(487,'en_us','order_product','Order Product Item'),(488,'en_us','type','Type'),(489,'en_us','service_setup','Service Setup Charges'),(490,'en_us','discount_setup','Setup Charge Discount'),(491,'en_us','info_setup_help','If you set a setup fee below, it will be charged once the service is activated and added to the customer orders page.'),(492,'en_us','cdr_rate_import_options','CDR Rate Import Options'),(493,'en_us','col_prefix','International Prefix'),(494,'en_us','col_destination','Destination/Country/Location'),(495,'en_us','col_sale_price','Sale Price'),(496,'en_us','col_cost_price','Cost Price'),(497,'en_us','phone_local_prefix','Local Calling Prefix'),(498,'en_us','config_orders','Orders Configuration'),(499,'en_us','upload_bank_statement','Upload Bank Statement'),(500,'en_us','BANK_STATEMENT','Bank Statement File'),(501,'en_us','invoiced_plan','Plan Invoice'),(502,'en_us','invoiced_usage','Usage Invoice'),(503,'en_us','cdr_import_mode_regular','Regular CSV Import Mode'),(504,'en_us','cdr_import_mode_nz_NAD','New Zealand NAD Import Mode'),(505,'en_us','nad_import_details','NAD Import Details'),(506,'en_us','nad_import_options','NAD Import Options'),(507,'en_us','nad_country_prefix','Prefix'),(508,'en_us','nad_price_cost','Cost Price'),(509,'en_us','nad_price_sale','Sale Price'),(510,'en_us','nad_default_destination','Default Destination'),(511,'en_us','filter_searchbox_prefix','Filter Prefix'),(512,'en_us','filter_searchbox_desc','Filter Description'),(513,'en_us','config_options_cdr','CDR Configuration Options'),(514,'en_us','config_accounts_email','Accounts Email Options'),(515,'en_us','filter_search_summarise','Group Results'),(516,'en_us','option_table_limit','Table Max Rows'),(517,'en_us','help_table_limit','Maximum number of table rows to display on one page'),(518,'en_us','rate_billgroup','Billing Group/Region'),(519,'en_us','tbl_lnk_item_expand','Expand/Show'),(520,'en_us','filter_billgroup','Filter by Bill Group'),(521,'en_us','menu_credit_notes','Credit Notes'),(522,'en_us','menu_credit_notes_view','View Credit Note'),(523,'en_us','menu_credit_notes_add','Add Credit Note'),(524,'en_us','code_credit','Credit Note'),(525,'en_us','filter_credit_notes_search','Search Credit Note Details'),(526,'en_us','ar_credit_details','Credit Note Details'),(527,'en_us','ar_credit_financials','Credit Note Financials'),(528,'en_us','ar_credit_other','Other Credit Note Details'),(529,'en_us','ar_credit_delete','Delete Credit Note'),(530,'en_us','submit_add_credit_item','Credit Selected Item'),(531,'en_us','submit_credit_delete','Delete Credit Note'),(532,'en_us','ar_credit_invoice_item','Credit Item'),(533,'en_us','ar_credit_invoice_item_tax','Credited Tax'),(534,'en_us','submit_credit_lock','Lock Credit Note'),(535,'en_us','filter_id_employee','By Employee'),(536,'en_us','employee','Employee'),(537,'en_us','accounts','Accounts'),(538,'en_us','invoice','Invoice'),(539,'en_us','credit_refund_details','Refund Details'),(540,'en_us','credit_refund_amount','Refund Amount'),(541,'en_us','id_employee','Employee'),(542,'en_us','account_asset','Asset Account'),(543,'en_us','account_dest','Dest Account'),(544,'en_us','service_options_data_traffic','Service Data Traffic Options'),(545,'en_us','service_parent','Parent Service'),(546,'en_us','date_period_last','Last Service Period'),(547,'en_us','help_accounts_services_dateshift','Number of days to backdate an invoice to align with an end of calender month date.'),(548,'en_us','menu_service_traffic_types','Traffic Types'),(549,'en_us','menu_service_traffic_types_add','Add Traffic Type'),(550,'en_us','menu_service_traffic_types_view','View Traffic Types'),(551,'en_us','type_name','Name'),(552,'en_us','type_label','Label/ID Name'),(553,'en_us','type_description','Description'),(554,'en_us','traffic_type_add','Define Traffic Type'),(555,'en_us','traffic_type_view','Traffic Type Details'),(556,'en_us','traffic_type_delete','Delete Traffic Type'),(557,'en_us','header_traffic_cap_active','Active'),(558,'en_us','header_traffic_cap_name','Traffic Type'),(559,'en_us','header_traffic_cap_mode','Traffic Mode'),(560,'en_us','header_traffic_units_included','Included Units'),(561,'en_us','header_traffic_units_price','Additional Unit Cost'),(562,'en_us','traffic_caps','Service Plan Traffic Caps/Options'),(563,'en_us','config_services_email','Service Email Options'),(564,'en_us','billing_cdr_csv_output_help','Check to attach CDR CSV output at time of billing'),(565,'en_us','billing_cdr_csv_output','CDR CSV Output'),(566,'en_us','No Customer of Reseller avaliable.','No Resellers available\r\n'),(567,'en_us','customer_of_reseller','Customer of Reseller'),(568,'en_us','reseller','Reseller'),(569,'en_us','standalone','Standalone'),(570,'en_us','reseller_id','Customer of Reseller'),(571,'en_us','reseller_customer','Customer type'),(572,'en_us','reseller_options','Reseller options'),(573,'en_us','reseller_customer_help','This customer is a reseller'),(574,'en_us','contact_mobile','Contact Mobile'),(575,'en_us','customer_reseller','Customer\'s Reseller/Parent'),(576,'en_us','service_price_monthly','Monthly Service Charges'),(577,'en_us','service_price_yearly','Yearly Service Charges'),(578,'en_us','filter_show_prices_with_discount','Display prices with discounts applied.'),(579,'en_us','billing_cycles','Billing Cycles'),(580,'en_us','price_monthly','Monthly Service Charges'),(581,'en_us','price_yearly','Yearly Service Charges'),(582,'en_us','billing_direct_debit','Direct debit account'),(583,'en_us','billing_method','Billing method'),(584,'en_us','billing_options','Billing options'),(585,'en_us','balance_owed','Balance'),(586,'en_us','amount_owing','Amount Owed');
/*!40000 ALTER TABLE `language` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `language_avaliable`
--

DROP TABLE IF EXISTS `language_avaliable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `language_avaliable` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(20) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `language_avaliable`
--

LOCK TABLES `language_avaliable` WRITE;
/*!40000 ALTER TABLE `language_avaliable` DISABLE KEYS */;
INSERT INTO `language_avaliable` VALUES (1,'en_us'),(2,'custom');
/*!40000 ALTER TABLE `language_avaliable` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu`
--

DROP TABLE IF EXISTS `menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu` (
  `id` int(11) NOT NULL auto_increment,
  `priority` int(11) NOT NULL default '0',
  `parent` varchar(50) NOT NULL,
  `topic` varchar(50) NOT NULL,
  `link` varchar(50) NOT NULL,
  `permid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=243 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu`
--

LOCK TABLES `menu` WRITE;
/*!40000 ALTER TABLE `menu` DISABLE KEYS */;
INSERT INTO `menu` VALUES (1,210,'Customers','View Customers','customers/customers.php',3),(2,220,'Customers','Add Customer','customers/add.php',4),(4,1,'top','Overview','home.php',0),(5,200,'top','Customers','customers/customers.php',3),(6,300,'top','Vendors/Suppliers','vendors/vendors.php',5),(7,400,'top','Human Resources','hr/staff.php',7),(12,211,'View Customers','','customers/view.php',0),(13,100,'top','Accounts','accounts/accounts.php',0),(15,700,'top','Time Keeping','timekeeping/timereg.php',17),(16,800,'top','Support Tickets','support/support.php',9),(17,310,'Vendors/Suppliers','View Vendors','vendors/vendors.php',5),(18,320,'Vendors/Suppliers','Add Vendor','vendors/add.php',6),(19,311,'View Vendors','','vendors/view.php',5),(21,410,'Human Resources','View Staff','hr/staff.php',7),(22,420,'Human Resources','Add Staff','hr/staff-add.php',8),(23,411,'View Staff','','hr/staff-view.php',7),(24,810,'Support Tickets','View Tickets','support/support.php',9),(25,820,'Support Tickets','Add Ticket','support/add.php',10),(26,510,'top','Products','products/products.php',11),(27,511,'Products','View Products','products/products.php',11),(28,512,'Products','Add Product','products/add.php',12),(30,514,'Products','','products/products.php',11),(31,520,'top','Services','services/services.php',13),(32,521,'Services','','services/services.php',13),(33,522,'Services','View Services','services/services.php',13),(34,523,'View Services','','services/view.php',13),(35,524,'Services','Add Service','services/add.php',14),(36,513,'View Products','','products/view.php',11),(37,530,'top','Projects','projects/projects.php',15),(38,531,'Projects','','projects/projects.php',15),(39,533,'View Projects','','projects/view.php',15),(40,534,'Projects','Add Project','projects/add.php',16),(41,532,'Projects','View Projects','projects/projects.php',15),(42,701,'Time Keeping','','timekeeping/timekeeping.php',17),(43,710,'Time Keeping','Time Registration','timekeeping/timereg.php',17),(44,720,'Time Keeping','Unbilled Time','timekeeping/unbilled.php',32),(46,535,'View Projects','','projects/phases.php',15),(47,536,'View Projects','','projects/timebooked.php',15),(48,900,'top','Admin','admin/admin.php',2),(49,910,'Admin','User Management','user/users.php',2),(51,930,'Admin','Brute-Force Blacklist','admin/blacklist.php',2),(52,901,'Admin','','admin/admin.php',2),(53,911,'User Management','','user/users.php',2),(54,912,'User Management','View Users','user/users.php',2),(55,913,'User Management','Add User','user/user-add.php',2),(56,914,'View Users','','user/user-view.php',2),(57,915,'View Users','','user/user-permissions.php',2),(58,916,'View Users','','user/user-staffaccess.php',2),(60,811,'View Tickets','','support/view.php',9),(61,213,'View Customers','','customers/journal.php',3),(62,214,'View Customers','','customers/journal-edit.php',3),(63,812,'View Tickets','','support/journal.php',9),(64,812,'View Tickets','','support/journal-edit.php',9),(65,312,'View Vendors','','vendors/journal.php',5),(66,313,'View Vendors','','vendors/journal-edit.php',5),(67,412,'View Staff','','hr/staff-journal.php',7),(68,413,'View Staff','','hr/staff-journal-edit.php',7),(69,917,'View Users','','user/user-journal.php',2),(70,918,'View Users','','user/user-journal-edit.php',2),(71,537,'View Projects','','projects/journal.php',15),(72,538,'View Projects','','projects/journal-edit.php',15),(73,514,'View Products','','products/journal.php',11),(74,514,'View Products','','products/journal-edit.php',11),(75,101,'Accounts','','accounts/accounts.php',0),(76,110,'Accounts','Chart of Accounts','accounts/charts/charts.php',18),(77,111,'Chart of Accounts','View Accounts','accounts/charts/charts.php',18),(78,112,'Chart of Accounts','Add Account','accounts/charts/add.php',19),(79,113,'View Accounts','','accounts/charts/view.php',18),(80,916,'View Users','','user/user-staffaccess-edit.php',2),(81,120,'Accounts','Accounts Receivables','accounts/ar/ar.php',20),(84,121,'Accounts Receivables','View Invoices','accounts/ar/ar.php',20),(85,140,'Accounts','Taxes','accounts/taxes/taxes.php',22),(86,141,'Taxes','View Taxes','accounts/taxes/taxes.php',22),(87,142,'View Taxes','','accounts/taxes/view.php',22),(88,143,'Taxes','Add Taxes','accounts/taxes/add.php',23),(89,124,'View Invoices','','accounts/ar/invoice-view.php',20),(90,124,'View Invoices','','accounts/ar/journal-edit.php',20),(91,124,'View Invoices','','accounts/ar/journal.php',20),(92,113,'View Accounts','','accounts/charts/ledger.php',18),(93,124,'View Invoices','','accounts/ar/invoice-payments.php',20),(94,124,'View Invoices','','accounts/ar/invoice-items.php',20),(96,142,'View Taxes','','accounts/taxes/ledger.php',22),(97,142,'View Taxes','','accounts/taxes/tax_collected.php',22),(98,142,'View Taxes','','accounts/taxes/tax_paid.php',22),(99,130,'Accounts','Accounts Payable','accounts/ap/ap.php',24),(100,131,'Accounts Payable','View AP Invoices','accounts/ap/ap.php',24),(101,132,'Accounts Payable','Add AP Invoice','accounts/ap/invoice-add.php',25),(102,134,'View AP Invoices','','accounts/ap/invoice-delete.php',25),(103,134,'View AP Invoices','','accounts/ap/invoice-view.php',24),(104,134,'View AP Invoices','','accounts/ap/journal-edit.php',24),(105,134,'View AP Invoices','','accounts/ap/journal.php',24),(106,134,'View AP Invoices','','accounts/ap/invoice-payments.php',24),(107,134,'View AP Invoices','','accounts/ap/invoice-items.php',24),(108,536,'View Projects','','projects/timebilled.php',15),(109,536,'View Projects','','projects/timebilled-edit.php',15),(110,536,'View Projects','','projects/timebilled-delete.php',15),(111,535,'View Projects','','projects/phase-edit.php',15),(112,535,'View Projects','','projects/phase-delete.php',15),(113,711,'Time Registration','','timekeeping/timereg-day.php',17),(114,535,'View Projects','','projects/delete.php',15),(115,514,'View Products','','products/delete.php',11),(116,214,'View Customers','','customers/delete.php',3),(117,313,'View Vendors','','vendors/delete.php',5),(118,413,'View Staff','','hr/staff-delete.php',7),(119,811,'View Tickets','','support/delete.php',9),(120,142,'View Taxes','','accounts/taxes/delete.php',22),(121,918,'View Users','','user/user-delete.php',2),(122,115,'Accounts','General Ledger','accounts/gl/gl.php',26),(123,116,'General Ledger','View GL Transactions','accounts/gl/gl.php',26),(124,117,'General Ledger','Add GL Transaction','accounts/gl/add.php',27),(126,124,'View Invoices','','accounts/ar/invoice-items-edit.php',21),(127,124,'View Invoices','','accounts/ar/invoice-payments-edit.php',21),(128,134,'View AP Invoices','','accounts/ap/invoice-payments-edit.php',25),(129,134,'View AP Invoices','','accounts/ap/invoice-items-edit.php',25),(130,117,'View GL Transactions','','accounts/gl/view.php',26),(131,117,'View GL Transactions','','accounts/gl/delete.php',27),(132,150,'Accounts','Quotes','accounts/quotes/quotes.php',28),(133,151,'Quotes','View Quotes','accounts/quotes/quotes.php',28),(134,152,'Quotes','Add Quote','accounts/quotes/quotes-add.php',29),(135,152,'View Quotes','','accounts/quotes/quotes-delete.php',29),(136,154,'View Quotes','','accounts/quotes/quotes-view.php',28),(137,154,'View Quotes','','accounts/quotes/journal-edit.php',28),(138,154,'View Quotes','','accounts/quotes/journal.php',28),(139,154,'View Quotes','','accounts/quotes/quotes-items.php',28),(140,154,'View Quotes','','accounts/quotes/quotes-items-edit.php',29),(141,152,'View Quotes','','accounts/quotes/quotes-convert.php',29),(142,916,'View Users','','user/user-staffaccess-add.php',2),(143,523,'View Services','','services/plan.php',13),(144,523,'View Services','','services/journal.php',13),(145,523,'View Services','','services/journal-edit.php',13),(146,523,'View Services','','services/delete.php',14),(147,211,'View Customers','','customers/invoices.php',3),(148,211,'View Customers','','customers/services.php',3),(149,211,'View Customers','','customers/service-edit.php',4),(150,211,'View Customers','','customers/service-delete.php',4),(151,311,'View Vendors','','vendors/invoices.php',5),(152,211,'View Customers','','customers/service-history.php',4),(153,711,'Time Registration','','timekeeping/timereg-day-edit.php',17),(154,113,'View Accounts','','accounts/charts/delete.php',19),(155,124,'View Invoices','','accounts/ar/invoice-export.php',20),(156,154,'View Quotes','','accounts/quotes/quotes-export.php',28),(157,180,'Accounts','Reports','accounts/reports/reports.php',30),(158,181,'Reports','Trial Balance','accounts/reports/trialbalance.php',30),(159,181,'Reports','','accounts/reports/reports.php',30),(160,182,'Reports','Income Statement','accounts/reports/incomestatement.php',30),(161,183,'Reports','Balance Sheet','accounts/reports/balancesheet.php',30),(162,905,'Admin','Configuration','admin/config.php',2),(163,940,'Admin','Audit Locking','admin/auditlock.php',2),(164,411,'View Staff','','hr/staff-timebooked.php',7),(167,122,'Accounts Receivables','Add Invoice','accounts/ar/invoice-add.php',21),(168,122,'View Invoices','','accounts/ar/invoice-delete.php',21),(169,950,'Admin','Database Backup','admin/db_backup.php',2),(170,908,'Admin','template_selection','admin/templates.php',2),(171,906,'Configuration','menu_config_company','admin/config_company.php',2),(173,906,'Configuration','menu_config_integration','admin/config_integration.php',2),(174,906,'Configuration','menu_config_services','admin/config_services.php',2),(175,906,'Configuration','menu_config_app','admin/config_application.php',2),(176,906,'Configuration','menu_config_locale','admin/config_locale.php',2),(177,906,'Configuration','','admin/config.php',2),(178,211,'View Customers','','customers/portal.php',3),(179,525,'Services','menu_service_cdr_rates','services/cdr-rates.php',13),(180,526,'menu_service_cdr_rates','menu_service_cdr_rates_view','services/cdr-rates.php',13),(181,526,'menu_service_cdr_rates','menu_service_cdr_rates_add','services/cdr-rates-add.php',13),(182,527,'menu_service_cdr_rates_view','','services/cdr-rates-view.php',13),(183,527,'menu_service_cdr_rates_view','','services/cdr-rates-items.php',13),(184,527,'menu_service_cdr_rates_view','','services/cdr-rates-delete.php',14),(185,523,'View Services','','services/bundles.php',13),(186,523,'View Services','','services/bundles-service-add.php',14),(187,523,'View Services','','services/bundles-service-edit.php',13),(188,527,'menu_service_cdr_rates_view','','services/cdr-rates-items-edit.php',13),(189,523,'View Services','','services/cdr-override.php',13),(190,523,'View Services','','services/cdr-override-edit.php',14),(191,190,'Accounts','Import','accounts/import/bankstatement.php',35),(192,191,'Import','Bank Statement','accounts/import/bankstatement.php',35),(193,192,'Bank Statement','','accounts/import/bankstatement-assign.php',35),(194,193,'Bank Statement','','accounts/import/bankstatement-csv.php',35),(195,211,'View Customers','','customers/service-cdr-override.php',3),(196,211,'View Customers','','customers/service-cdr-override-edit.php',4),(197,528,'Services','menu_services_groups','services/groups.php',13),(198,529,'menu_services_groups','menu_services_groups_view','services/groups.php',13),(199,530,'menu_services_groups_view','','services/groups-view.php',13),(200,530,'menu_services_groups_view','','services/groups-delete.php',14),(201,530,'menu_services_groups','menu_services_groups_add','services/groups-add.php',14),(202,211,'View Customers','','customers/service-ddi.php',3),(203,211,'View Customers','','customers/service-ddi-edit.php',4),(204,211,'View Customers','','customers/service-ipv4.php',3),(205,211,'View Customers','','customers/service-ipv4-edit.php',4),(206,514,'Products','menu_products_groups','products/groups.php',11),(207,515,'menu_products_groups','menu_products_groups_view','products/groups.php',11),(208,516,'menu_products_groups_view','','products/groups-view.php',11),(209,516,'menu_products_groups_view','','products/groups-delete.php',12),(210,516,'menu_products_groups','menu_products_groups_add','products/groups-add.php',12),(211,527,'menu_service_cdr_rates_view','','services/cdr-rates-import.php',14),(212,527,'menu_service_cdr_rates_view','','services/cdr-rates-import-csv.php',14),(213,211,'View Customers','','customers/service-history-cdr.php',4),(214,211,'View Customers','','customers/attributes.php',3),(215,123,'Accounts Receivables','Bulk Payment','accounts/ar/invoice-bulk-payments.php',21),(216,133,'Accounts Payable','Bulk Payment','accounts/ap/invoice-bulk-payments.php',25),(217,123,'Accounts Receivables','Account Statements','accounts/ar/account-statements.php',20),(218,906,'Configuration','menu_config_dependencies','admin/config_dependencies.php',2),(219,211,'View Customers','','customers/orders.php',3),(220,211,'View Customers','','customers/orders-view.php',3),(221,125,'Accounts Receivables','menu_credit_notes','accounts/ar/ar-credits.php',20),(222,125,'menu_credit_notes','menu_credit_notes_view','accounts/ar/ar-credits.php',20),(223,126,'menu_credit_notes','menu_credit_notes_add','accounts/ar/credit-add.php',21),(224,126,'menu_credit_notes_view','','accounts/ar/ar-credits.php',20),(225,126,'menu_credit_notes_view','','accounts/ar/credit-view.php',20),(226,126,'menu_credit_notes_view','','accounts/ar/credit-items.php',20),(227,126,'menu_credit_notes_view','','accounts/ar/credit-items-edit.php',21),(228,126,'menu_credit_notes_view','','accounts/ar/credit-payments.php',20),(229,126,'menu_credit_notes_view','','accounts/ar/credit-payments-edit.php',21),(230,126,'menu_credit_notes_view','','accounts/ar/credit-journal.php',20),(231,126,'menu_credit_notes_view','','accounts/ar/credit-journal-edit.php',20),(232,126,'menu_credit_notes_view','','accounts/ar/credit-export.php',20),(233,126,'menu_credit_notes_view','','accounts/ar/credit-delete.php',21),(234,211,'View Customers','','customers/credit.php',4),(235,211,'View Customers','','customers/credit-refund.php',4),(236,529,'Services','menu_service_traffic_types','services/traffic-types.php',13),(237,530,'menu_service_traffic_types','menu_service_traffic_types_view','services/traffic-types.php',13),(238,530,'menu_service_traffic_types','menu_service_traffic_types_add','services/traffic-types-add.php',14),(239,531,'menu_service_traffic_types_view','','services/traffic-types-view.php',13),(240,531,'menu_service_traffic_types_view','','services/traffic-types-delete.php',14),(241,211,'View Customers','','customers/reseller.php',4),(242,230,'Customers','Billing Export','customers/customers-billing.php',3);
/*!40000 ALTER TABLE `menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL,
  `value` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8 COMMENT='Stores all the possible permissions';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,1,'disabled','Enabling the disabled permission will prevent the user from being able to login.'),(2,1,'admin','Provides access to user and configuration management features (note: any user with admin can provide themselves with access to any other section of this program)'),(3,4,'customers_view','Allows the user to view & search customer records'),(4,4,'customers_write','Allows the user to make changes to customer records or add new customers.'),(5,12,'vendors_view','Allows the user to view & search vendor/supplier records.'),(6,12,'vendors_write','Allows the user to make changes to vendor records or add new vendors.'),(7,8,'staff_view','Allows the user to view & search staff records.'),(8,8,'staff_write','Allows the user to make changes to staff records or add new employees.'),(9,9,'support_view','Allow the user to view support tickets.'),(10,9,'support_write','Allow the user to create and adjust support tickets.'),(11,5,'products_view','Allows the user to view & search product records'),(12,5,'products_write','Allows the user to make changes to product records or add new products.'),(13,7,'services_view','Allow the user to view configured services.'),(14,7,'services_write','Allow the user to modify services'),(15,6,'projects_view','Allows the user to view & search projects, phases and to view time booked against the project.'),(16,6,'projects_write','Allows the user to make changes to projects and phases or to add new projects and phases.'),(17,10,'timekeeping','Allows the user to view and book time using the time registration features for all the employees they have been assigned access to (see the user staff access rights page for details)'),(18,3,'accounts_charts_view','Allows the user to view the Chart of Accounts.'),(19,3,'accounts_charts_write','Allows the user to modify the Charts of Accounts, add new accounts or perform other operations.'),(20,3,'accounts_ar_view','Allow user to view invoices or transactions under Accounts Receivables'),(21,3,'accounts_ar_write','Allow user to create invoices or transactions under Accounts Receivables'),(22,3,'accounts_taxes_view','Allows user to view configured taxes.'),(23,3,'accounts_taxes_write','Allows user to adjust configured taxes.'),(24,3,'accounts_ap_view','Allow user to view invoices belonging to Accounts Payable'),(25,3,'accounts_ap_write','Allow user to create or adjust invoices belonging to Accounts Payable'),(26,3,'accounts_gl_view','Allows the user to view general ledger transactions'),(27,3,'accounts_gl_write','Allows the user to create general ledger transactions'),(28,3,'accounts_quotes_view','Allows user to view all quotes'),(29,3,'accounts_quotes_write','Allows the user to create and edit quotes'),(30,3,'accounts_reports','View/Create financial reports'),(31,7,'services_write_usage','Permit this user to upload service usage records via SOAP interface'),(32,6,'projects_timegroup','Permit the user to group unbilled time into groups for invoicing.'),(33,10,'timekeeping_all_view','Allow the user to view timesheets and unbilled for ALL staff.'),(34,10,'timekeeping_all_write','Allow the user to adjust for any employee.'),(35,3,'accounts_import_statement','This permission allows users to import bank statements into the system'),(36,13,'customers_portal_auth','Allow access to authentication fuctions via SOAP API.'),(37,4,'customers_credit','Permits user to add and make credit transactions to a customer'),(38,11,'devel_translate','Allows the user to enter new translations and edit translations that have already been provided.'),(39,4,'customers_orders','Allow the user to place or adjust customer orders');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions_groups`
--

DROP TABLE IF EXISTS `permissions_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions_groups` (
  `id` int(11) NOT NULL auto_increment,
  `priority` int(11) NOT NULL,
  `group_name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions_groups`
--

LOCK TABLES `permissions_groups` WRITE;
/*!40000 ALTER TABLE `permissions_groups` DISABLE KEYS */;
INSERT INTO `permissions_groups` VALUES (1,100,'general'),(3,400,'accounts'),(4,200,'customers'),(5,500,'products'),(6,700,'projects'),(7,600,'services'),(8,800,'human_resources'),(9,900,'support'),(10,1000,'timekeeping'),(11,2000,'development'),(12,300,'vendors'),(13,1100,'api');
/*!40000 ALTER TABLE `permissions_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions_staff`
--

DROP TABLE IF EXISTS `permissions_staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions_staff` (
  `id` int(11) NOT NULL auto_increment,
  `value` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions_staff`
--

LOCK TABLES `permissions_staff` WRITE;
/*!40000 ALTER TABLE `permissions_staff` DISABLE KEYS */;
INSERT INTO `permissions_staff` VALUES (18,'timereg_view','Able to view staff member\'s booked time.'),(19,'timereg_write','Able to edit/book time for the staff member.');
/*!40000 ALTER TABLE `permissions_staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_groups`
--

DROP TABLE IF EXISTS `product_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_groups` (
  `id` int(11) NOT NULL auto_increment,
  `id_parent` int(10) unsigned NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `group_description` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_groups`
--

LOCK TABLES `product_groups` WRITE;
/*!40000 ALTER TABLE `product_groups` DISABLE KEYS */;
INSERT INTO `product_groups` VALUES (1,0,'General Products','Default grouping for all products.');
/*!40000 ALTER TABLE `product_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL auto_increment,
  `id_product_group` int(10) unsigned NOT NULL,
  `code_product` varchar(50) NOT NULL,
  `name_product` varchar(255) NOT NULL,
  `details` text NOT NULL,
  `price_cost` decimal(11,2) NOT NULL default '0.00',
  `price_sale` decimal(11,2) NOT NULL default '0.00',
  `date_start` date NOT NULL,
  `date_end` date NOT NULL,
  `date_current` date NOT NULL default '0000-00-00',
  `quantity_instock` int(11) NOT NULL default '0',
  `quantity_vendor` int(11) NOT NULL default '0',
  `vendorid` int(11) NOT NULL default '0',
  `code_product_vendor` varchar(50) NOT NULL,
  `account_sales` int(11) NOT NULL default '0',
  `account_purchase` int(11) NOT NULL,
  `units` varchar(10) NOT NULL,
  `discount` float NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products_taxes`
--

DROP TABLE IF EXISTS `products_taxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products_taxes` (
  `id` int(11) NOT NULL auto_increment,
  `productid` int(11) NOT NULL,
  `taxid` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products_taxes`
--

LOCK TABLES `products_taxes` WRITE;
/*!40000 ALTER TABLE `products_taxes` DISABLE KEYS */;
/*!40000 ALTER TABLE `products_taxes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_phases`
--

DROP TABLE IF EXISTS `project_phases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_phases` (
  `id` int(11) NOT NULL auto_increment,
  `projectid` int(11) NOT NULL default '0',
  `name_phase` varchar(255) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_phases`
--

LOCK TABLES `project_phases` WRITE;
/*!40000 ALTER TABLE `project_phases` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_phases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects` (
  `id` int(11) NOT NULL auto_increment,
  `code_project` varchar(50) NOT NULL,
  `name_project` varchar(255) NOT NULL,
  `date_start` date NOT NULL default '0000-00-00',
  `date_end` date NOT NULL default '0000-00-00',
  `internal_only` tinyint(1) NOT NULL,
  `details` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_groups`
--

DROP TABLE IF EXISTS `service_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_groups` (
  `id` int(11) NOT NULL auto_increment,
  `id_parent` int(10) unsigned NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `group_description` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_groups`
--

LOCK TABLES `service_groups` WRITE;
/*!40000 ALTER TABLE `service_groups` DISABLE KEYS */;
INSERT INTO `service_groups` VALUES (1,0,'General Services','');
/*!40000 ALTER TABLE `service_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_types`
--

DROP TABLE IF EXISTS `service_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_types` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_types`
--

LOCK TABLES `service_types` WRITE;
/*!40000 ALTER TABLE `service_types` DISABLE KEYS */;
INSERT INTO `service_types` VALUES (1,'generic_no_usage','Generic service with no usage billing - ideal for regular, fixed price charges (eg: yearly domain name fee)',1),(2,'data_traffic','Data traffic accounting - suitable for ISP services',1),(3,'time','Time accounting - Suitable for use with ISP dialup accounts or other time-based services',1),(4,'generic_with_usage','Generic service with usage billing.',1),(5,'licenses','Ideal for services like software as a service, this service type will do regular billings with flexbile quantities of items.',1),(6,'bundle','Bundle Service - A service that can contain mulitple other services.',1),(7,'phone_trunk','Trunk phone service with multiple DDIs and phones.',1),(8,'phone_single','A single individual phone line/service.',1),(9,'phone_tollfree','Toll free phone line - has both inbound and outbound call charges applied.',1);
/*!40000 ALTER TABLE `service_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_units`
--

DROP TABLE IF EXISTS `service_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_units` (
  `id` int(11) NOT NULL auto_increment,
  `typeid` int(11) NOT NULL default '0',
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `numrawunits` bigint(20) unsigned NOT NULL default '0',
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_units`
--

LOCK TABLES `service_units` WRITE;
/*!40000 ALTER TABLE `service_units` DISABLE KEYS */;
INSERT INTO `service_units` VALUES (1,2,'GB','Gigabyte - 1000e3 bytes',1000000000,1),(2,2,'MB','Megabyte - 1000e2 bytes',1000000,1),(3,2,'GiB','Gibibytes - 1024e3 bytes',1073741824,1),(4,2,'MiB','Mebibytes - 1024e2 bytes',1048576,1),(5,3,'Hours','',3600,1);
/*!40000 ALTER TABLE `service_units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_usage_alerts`
--

DROP TABLE IF EXISTS `service_usage_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_usage_alerts` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_service_customer` int(10) unsigned NOT NULL,
  `id_service_period` int(10) unsigned NOT NULL,
  `id_type` int(10) unsigned NOT NULL,
  `date_sent` date NOT NULL,
  `date_update` date NOT NULL,
  `usage_current` decimal(20,2) unsigned NOT NULL,
  `usage_alerted` decimal(20,2) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_usage_alerts`
--

LOCK TABLES `service_usage_alerts` WRITE;
/*!40000 ALTER TABLE `service_usage_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_usage_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_usage_modes`
--

DROP TABLE IF EXISTS `service_usage_modes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_usage_modes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_usage_modes`
--

LOCK TABLES `service_usage_modes` WRITE;
/*!40000 ALTER TABLE `service_usage_modes` DISABLE KEYS */;
INSERT INTO `service_usage_modes` VALUES (1,'incrementing','Total usage during entire period will be billed.'),(2,'peak','Highest amount of usage on any day during the period will be billed.'),(3,'average','Average amount of usage across the entire period will be billed.');
/*!40000 ALTER TABLE `service_usage_modes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_usage_records`
--

DROP TABLE IF EXISTS `service_usage_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_usage_records` (
  `id` int(11) NOT NULL auto_increment,
  `id_service_customer` int(11) NOT NULL default '0',
  `date` date NOT NULL default '0000-00-00',
  `price` decimal(11,4) NOT NULL,
  `usage1` bigint(20) unsigned NOT NULL default '0',
  `usage2` bigint(20) unsigned NOT NULL default '0',
  `usage3` bigint(20) unsigned NOT NULL default '0',
  `billgroup` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_usage_records`
--

LOCK TABLES `service_usage_records` WRITE;
/*!40000 ALTER TABLE `service_usage_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_usage_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services` (
  `id` int(11) NOT NULL auto_increment,
  `name_service` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL,
  `chartid` int(11) NOT NULL default '0',
  `typeid` int(11) NOT NULL default '0',
  `id_rate_table` int(11) NOT NULL,
  `id_service_group` int(11) NOT NULL,
  `id_service_group_usage` int(11) NOT NULL,
  `units` varchar(255) NOT NULL default '0',
  `price` decimal(11,2) NOT NULL default '0.00',
  `price_extraunits` decimal(11,2) NOT NULL default '0.00',
  `price_setup` decimal(11,2) NOT NULL,
  `discount` float NOT NULL,
  `included_units` int(11) NOT NULL default '0',
  `billing_cycle` int(11) NOT NULL default '0',
  `billing_mode` int(11) NOT NULL default '0',
  `usage_mode` int(11) NOT NULL default '0',
  `description` text NOT NULL,
  `upstream_id` int(11) NOT NULL,
  `upstream_notes` text NOT NULL,
  `alert_80pc` tinyint(4) NOT NULL,
  `alert_100pc` tinyint(4) NOT NULL,
  `alert_extraunits` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services_bundles`
--

DROP TABLE IF EXISTS `services_bundles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services_bundles` (
  `id` int(11) NOT NULL auto_increment,
  `id_bundle` int(11) NOT NULL,
  `id_service` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services_bundles`
--

LOCK TABLES `services_bundles` WRITE;
/*!40000 ALTER TABLE `services_bundles` DISABLE KEYS */;
/*!40000 ALTER TABLE `services_bundles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services_customers`
--

DROP TABLE IF EXISTS `services_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services_customers` (
  `id` int(11) NOT NULL auto_increment,
  `serviceid` int(11) NOT NULL default '0',
  `customerid` int(11) NOT NULL default '0',
  `bundleid` int(11) NOT NULL,
  `bundleid_component` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL default '0',
  `date_period_first` date NOT NULL default '0000-00-00',
  `date_period_next` date NOT NULL default '0000-00-00',
  `date_period_last` date NOT NULL default '0000-00-00',
  `quantity` int(11) NOT NULL default '0',
  `description` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services_customers`
--

LOCK TABLES `services_customers` WRITE;
/*!40000 ALTER TABLE `services_customers` DISABLE KEYS */;
/*!40000 ALTER TABLE `services_customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services_customers_ddi`
--

DROP TABLE IF EXISTS `services_customers_ddi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services_customers_ddi` (
  `id` int(11) NOT NULL auto_increment,
  `id_service_customer` int(11) NOT NULL,
  `ddi_start` bigint(20) NOT NULL,
  `ddi_finish` bigint(20) NOT NULL,
  `local_prefix` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services_customers_ddi`
--

LOCK TABLES `services_customers_ddi` WRITE;
/*!40000 ALTER TABLE `services_customers_ddi` DISABLE KEYS */;
/*!40000 ALTER TABLE `services_customers_ddi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services_customers_ipv4`
--

DROP TABLE IF EXISTS `services_customers_ipv4`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services_customers_ipv4` (
  `id` int(11) NOT NULL auto_increment,
  `id_service_customer` int(11) NOT NULL,
  `ipv4_address` varchar(15) NOT NULL,
  `ipv4_cidr` int(2) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services_customers_ipv4`
--

LOCK TABLES `services_customers_ipv4` WRITE;
/*!40000 ALTER TABLE `services_customers_ipv4` DISABLE KEYS */;
/*!40000 ALTER TABLE `services_customers_ipv4` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services_customers_periods`
--

DROP TABLE IF EXISTS `services_customers_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services_customers_periods` (
  `id` int(11) NOT NULL auto_increment,
  `id_service_customer` int(11) NOT NULL default '0',
  `date_start` date NOT NULL default '0000-00-00',
  `date_end` date NOT NULL default '0000-00-00',
  `date_billed` date NOT NULL default '0000-00-00',
  `invoiceid` int(11) NOT NULL default '0',
  `invoiceid_usage` int(11) NOT NULL,
  `rebill` tinyint(1) NOT NULL,
  `usage_summary` decimal(20,2) NOT NULL,
  `usage_alerted` decimal(20,2) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services_customers_periods`
--

LOCK TABLES `services_customers_periods` WRITE;
/*!40000 ALTER TABLE `services_customers_periods` DISABLE KEYS */;
/*!40000 ALTER TABLE `services_customers_periods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services_options`
--

DROP TABLE IF EXISTS `services_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services_options` (
  `id` int(11) NOT NULL auto_increment,
  `option_type` varchar(10) NOT NULL,
  `option_type_id` int(11) NOT NULL,
  `option_name` varchar(50) NOT NULL,
  `option_value` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services_options`
--

LOCK TABLES `services_options` WRITE;
/*!40000 ALTER TABLE `services_options` DISABLE KEYS */;
/*!40000 ALTER TABLE `services_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services_taxes`
--

DROP TABLE IF EXISTS `services_taxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services_taxes` (
  `id` int(11) NOT NULL auto_increment,
  `serviceid` int(11) NOT NULL,
  `taxid` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services_taxes`
--

LOCK TABLES `services_taxes` WRITE;
/*!40000 ALTER TABLE `services_taxes` DISABLE KEYS */;
INSERT INTO `services_taxes` VALUES (3,1,1),(4,2,1),(5,3,1),(6,4,1),(7,5,1);
/*!40000 ALTER TABLE `services_taxes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff` (
  `id` int(11) NOT NULL auto_increment,
  `name_staff` varchar(255) NOT NULL,
  `staff_code` varchar(255) NOT NULL,
  `staff_position` varchar(255) NOT NULL,
  `contact_phone` varchar(255) NOT NULL,
  `contact_fax` varchar(255) NOT NULL,
  `contact_email` varchar(255) NOT NULL,
  `date_start` date NOT NULL default '0000-00-00',
  `date_end` date NOT NULL default '0000-00-00',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff`
--

LOCK TABLES `staff` WRITE;
/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
INSERT INTO `staff` VALUES (1,'Automated System','AUTO','Automatically generated invoices will be assigned to this employee.','','','','0000-00-00','0000-00-00');
/*!40000 ALTER TABLE `staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_tickets`
--

DROP TABLE IF EXISTS `support_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(255) NOT NULL,
  `status` int(11) NOT NULL default '0',
  `priority` int(11) NOT NULL default '0',
  `date_start` date NOT NULL default '0000-00-00',
  `date_end` date NOT NULL default '0000-00-00',
  `customerid` int(11) NOT NULL default '0',
  `productid` int(11) NOT NULL default '0',
  `projectid` int(11) NOT NULL default '0',
  `serviceid` int(11) NOT NULL default '0',
  `details` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_tickets`
--

LOCK TABLES `support_tickets` WRITE;
/*!40000 ALTER TABLE `support_tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_tickets_priority`
--

DROP TABLE IF EXISTS `support_tickets_priority`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_tickets_priority` (
  `id` int(11) NOT NULL auto_increment,
  `value` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_tickets_priority`
--

LOCK TABLES `support_tickets_priority` WRITE;
/*!40000 ALTER TABLE `support_tickets_priority` DISABLE KEYS */;
INSERT INTO `support_tickets_priority` VALUES (1,'High',''),(2,'Medium',''),(3,'Low',''),(4,'Unsorted','');
/*!40000 ALTER TABLE `support_tickets_priority` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_tickets_status`
--

DROP TABLE IF EXISTS `support_tickets_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_tickets_status` (
  `id` int(11) NOT NULL auto_increment,
  `value` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_tickets_status`
--

LOCK TABLES `support_tickets_status` WRITE;
/*!40000 ALTER TABLE `support_tickets_status` DISABLE KEYS */;
INSERT INTO `support_tickets_status` VALUES (1,'Reported',''),(2,'Testing',''),(3,'Closed','');
/*!40000 ALTER TABLE `support_tickets_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `templates`
--

DROP TABLE IF EXISTS `templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `templates` (
  `id` int(11) NOT NULL auto_increment,
  `active` tinyint(1) NOT NULL,
  `template_type` varchar(32) NOT NULL,
  `template_file` varchar(255) NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `template_description` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `templates`
--

LOCK TABLES `templates` WRITE;
/*!40000 ALTER TABLE `templates` DISABLE KEYS */;
INSERT INTO `templates` VALUES (2,1,'ar_invoice_tex','templates/ar_invoice/ar_invoice_english_default','English Basic (LaTeX)','Basic English language invoice, includes contact details for customer, company, tax numbers, invoice items and payment details.'),(4,0,'ar_invoice_tex','templates/ar_invoice/ar_invoice_german_default','Deutsch Basic (LaTeX)','Deutschsprachige Version der Standard-PDF-Rechnung. Absender links, Empf√É¬§nger rechts f√É¬ºr Sichtcouvert und weitere kleinere Optimierungen'),(5,0,'ar_invoice_htmltopdf','templates/ar_invoice/ar_invoice_htmltopdf_simple','English Basic (XHTML)','Basic English language invoice, includes contact details for customer, company, tax numbers, invoice items and payment details.'),(6,0,'ar_invoice_htmltopdf','templates/ar_invoice/ar_invoice_htmltopdf_telcostyle','Telco Style Invoicing (XHTML)','Featured two+ page invoice which has an overview/summary page of the service/product groups, payment information and additional pages containing all the line items, grouped by service/product groups.\r\n\r\nThis invoice is designed for providers such as ISPs, telcos and other service providers.'),(7,1,'quotes_invoice_tex','templates/quotes/quotes_english_default','English Basic (LaTeX)','Basic English language quotes, includes contact details for customer, company, tax numbers, quotes items and payment details.'),(8,0,'quotes_invoice_htmltopdf','templates/quotes/quotes_english_xhtml','English Basic (XHTML)','Basic English language quotes, includes contact details for customer, company, tax numbers, quotes items and payment details.'),(9,1,'ar_credit_htmltopdf','templates/ar_credit/ar_credit_htmltopdf_simple','English Basic (XHTML)','Basic English language AR credit note.');
/*!40000 ALTER TABLE `templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `themes`
--

DROP TABLE IF EXISTS `themes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `themes` (
  `id` int(11) NOT NULL auto_increment,
  `theme_name` varchar(255) NOT NULL,
  `theme_creator` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `themes`
--

LOCK TABLES `themes` WRITE;
/*!40000 ALTER TABLE `themes` DISABLE KEYS */;
INSERT INTO `themes` VALUES (1,'default','amberdms'),(3,'web2.0','amberdms'),(4,'classic','amberdms');
/*!40000 ALTER TABLE `themes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `time_groups`
--

DROP TABLE IF EXISTS `time_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `time_groups` (
  `id` int(11) NOT NULL auto_increment,
  `locked` tinyint(1) NOT NULL default '0',
  `name_group` varchar(255) NOT NULL,
  `projectid` int(11) NOT NULL default '0',
  `customerid` int(11) NOT NULL default '0',
  `description` text NOT NULL,
  `invoiceid` int(11) NOT NULL default '0',
  `invoiceitemid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `time_groups`
--

LOCK TABLES `time_groups` WRITE;
/*!40000 ALTER TABLE `time_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `time_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timereg`
--

DROP TABLE IF EXISTS `timereg`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timereg` (
  `id` int(11) NOT NULL auto_increment,
  `locked` tinyint(1) NOT NULL default '0',
  `employeeid` int(11) NOT NULL default '0',
  `phaseid` int(11) NOT NULL default '0',
  `groupid` int(11) NOT NULL default '0',
  `billable` tinyint(1) NOT NULL default '0',
  `date` date NOT NULL default '0000-00-00',
  `time_booked` int(11) NOT NULL default '0',
  `description` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timereg`
--

LOCK TABLES `timereg` WRITE;
/*!40000 ALTER TABLE `timereg` DISABLE KEYS */;
/*!40000 ALTER TABLE `timereg` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `traffic_caps`
--

DROP TABLE IF EXISTS `traffic_caps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `traffic_caps` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_service` int(10) unsigned NOT NULL,
  `id_traffic_type` int(10) unsigned NOT NULL,
  `mode` varchar(10) NOT NULL,
  `units_price` decimal(11,2) NOT NULL,
  `units_included` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `traffic_caps`
--

LOCK TABLES `traffic_caps` WRITE;
/*!40000 ALTER TABLE `traffic_caps` DISABLE KEYS */;
/*!40000 ALTER TABLE `traffic_caps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `traffic_types`
--

DROP TABLE IF EXISTS `traffic_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `traffic_types` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `type_name` varchar(255) NOT NULL,
  `type_description` text NOT NULL,
  `type_label` varchar(20) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `traffic_types`
--

LOCK TABLES `traffic_types` WRITE;
/*!40000 ALTER TABLE `traffic_types` DISABLE KEYS */;
INSERT INTO `traffic_types` VALUES (1,'Any','Default region configured for all services - any unmatched traffic types will go against this.','*');
/*!40000 ALTER TABLE `traffic_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL auto_increment,
  `username` varchar(255) NOT NULL,
  `realname` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `password_salt` varchar(20) NOT NULL,
  `contact_email` varchar(255) NOT NULL,
  `concurrent_logins` tinyint(1) NOT NULL default '0',
  `time` bigint(20) NOT NULL default '0',
  `ipaddress` varchar(128) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `ipaddress` (`ipaddress`),
  KEY `time` (`time`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='User authentication system.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'setup','Setup Account','f2c370789ab902f977b3e483238967a8805bd21e','rokedpf8fiv9qgxf2uc2','support@amberdms.com',1,1303861257,'10.8.5.182');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_blacklist`
--

DROP TABLE IF EXISTS `users_blacklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_blacklist` (
  `id` int(11) NOT NULL auto_increment,
  `ipaddress` varchar(128) NOT NULL,
  `failedcount` int(11) NOT NULL default '0',
  `time` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Prevents automated login attacks.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_blacklist`
--

LOCK TABLES `users_blacklist` WRITE;
/*!40000 ALTER TABLE `users_blacklist` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_blacklist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_options`
--

DROP TABLE IF EXISTS `users_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_options` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL default '0',
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=180 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_options`
--

LOCK TABLES `users_options` WRITE;
/*!40000 ALTER TABLE `users_options` DISABLE KEYS */;
INSERT INTO `users_options` VALUES (23,5,'lang','en_us'),(24,5,'debug','on'),(45,6,'lang','en_us'),(46,6,'dateformat','yyyy-mm-dd'),(47,6,'timezone','SYSTEM'),(48,6,'debug','disabled'),(49,5,'timezone','SYSTEM'),(63,7,'lang','en_us'),(64,7,'dateformat','yyyy-mm-dd'),(65,7,'timezone','SYSTEM'),(66,7,'debug','disabled'),(144,4,'lang','en_us'),(145,4,'dateformat','dd-mm-yyyy'),(146,4,'timezone','SYSTEM'),(147,4,'shrink_tableoptions','on'),(148,4,'debug',''),(149,4,'concurrent_logins','on'),(174,1,'lang','en_us'),(175,1,'timezone','SYSTEM'),(176,1,'dateformat','dd-mm-yyyy'),(177,1,'shrink_tableoptions',''),(178,1,'debug',''),(179,1,'concurrent_logins','on');
/*!40000 ALTER TABLE `users_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_permissions`
--

DROP TABLE IF EXISTS `users_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_permissions` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL default '0',
  `permid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=90 DEFAULT CHARSET=utf8 COMMENT='Stores user permissions.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_permissions`
--

LOCK TABLES `users_permissions` WRITE;
/*!40000 ALTER TABLE `users_permissions` DISABLE KEYS */;
INSERT INTO `users_permissions` VALUES (1,1,2),(5,1,4),(6,1,3),(7,1,5),(8,1,6),(9,1,7),(10,1,8),(11,1,9),(12,1,10),(13,1,11),(14,1,12),(15,1,13),(16,1,14),(17,1,15),(18,1,16),(19,1,17),(26,1,18),(27,1,19),(28,1,20),(29,1,21),(30,1,22),(31,1,23),(32,1,24),(33,1,25),(34,1,26),(35,1,27),(36,1,28),(37,1,29),(67,1,30),(89,1,32);
/*!40000 ALTER TABLE `users_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_permissions_staff`
--

DROP TABLE IF EXISTS `users_permissions_staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_permissions_staff` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL default '0',
  `staffid` int(11) NOT NULL default '0',
  `permid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_permissions_staff`
--

LOCK TABLES `users_permissions_staff` WRITE;
/*!40000 ALTER TABLE `users_permissions_staff` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_permissions_staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_sessions`
--

DROP TABLE IF EXISTS `users_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_sessions` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL,
  `authkey` varchar(40) NOT NULL,
  `ipaddress` varchar(128) NOT NULL,
  `time` bigint(20) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_sessions`
--

LOCK TABLES `users_sessions` WRITE;
/*!40000 ALTER TABLE `users_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_contact_records`
--

DROP TABLE IF EXISTS `vendor_contact_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendor_contact_records` (
  `id` int(11) NOT NULL auto_increment,
  `contact_id` int(11) NOT NULL,
  `type` enum('phone','email','fax','mobile') NOT NULL,
  `label` varchar(255) NOT NULL,
  `detail` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_contact_records`
--

LOCK TABLES `vendor_contact_records` WRITE;
/*!40000 ALTER TABLE `vendor_contact_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_contact_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_contacts`
--

DROP TABLE IF EXISTS `vendor_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendor_contacts` (
  `id` int(11) NOT NULL auto_increment,
  `vendor_id` int(11) NOT NULL,
  `role` enum('other','accounts') NOT NULL,
  `contact` varchar(255) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_contacts`
--

LOCK TABLES `vendor_contacts` WRITE;
/*!40000 ALTER TABLE `vendor_contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendors`
--

DROP TABLE IF EXISTS `vendors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendors` (
  `id` int(11) NOT NULL auto_increment,
  `name_vendor` varchar(255) NOT NULL,
  `code_vendor` varchar(50) NOT NULL,
  `date_start` date NOT NULL default '0000-00-00',
  `date_end` date NOT NULL default '0000-00-00',
  `tax_default` int(11) NOT NULL default '0',
  `tax_number` varchar(255) NOT NULL default '0',
  `address1_street` varchar(255) NOT NULL,
  `address1_city` varchar(255) NOT NULL,
  `address1_state` varchar(255) NOT NULL,
  `address1_country` varchar(255) NOT NULL,
  `address1_zipcode` varchar(10) NOT NULL default '0',
  `address2_street` varchar(255) NOT NULL,
  `address2_city` varchar(255) NOT NULL,
  `address2_state` varchar(255) NOT NULL,
  `address2_country` varchar(255) NOT NULL,
  `address2_zipcode` varchar(10) NOT NULL default '0',
  `discount` float NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendors`
--

LOCK TABLES `vendors` WRITE;
/*!40000 ALTER TABLE `vendors` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendors_credits`
--

DROP TABLE IF EXISTS `vendors_credits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendors_credits` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `date_trans` date NOT NULL,
  `type` varchar(10) NOT NULL,
  `amount_total` decimal(11,2) NOT NULL,
  `id_custom` int(10) unsigned NOT NULL,
  `id_employee` int(10) unsigned NOT NULL,
  `id_vendor` int(10) unsigned NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendors_credits`
--

LOCK TABLES `vendors_credits` WRITE;
/*!40000 ALTER TABLE `vendors_credits` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendors_credits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendors_taxes`
--

DROP TABLE IF EXISTS `vendors_taxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendors_taxes` (
  `id` int(11) NOT NULL auto_increment,
  `vendorid` int(11) NOT NULL,
  `taxid` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendors_taxes`
--

LOCK TABLES `vendors_taxes` WRITE;
/*!40000 ALTER TABLE `vendors_taxes` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendors_taxes` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


--
-- Upgrades
--
INSERT INTO services_options (option_type, option_type_id, option_name, option_value) SELECT 'customer', services_customers.id, 'quantity', services_customers.quantity FROM services_customers;
ALTER TABLE `services_customers` DROP `quantity`;


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20130630' WHERE name='SCHEMA_VERSION' LIMIT 1;


