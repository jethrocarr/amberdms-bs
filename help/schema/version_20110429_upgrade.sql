--
-- AMBERDMS BILLING SYSTEM SVN 1138 CHANGES
--


--
-- Kick all user sessions - prevents users who are logged in from
-- experiencing/causing any problems whilst the update runs.
--

TRUNCATE TABLE `users_sessions`;


--
-- Changes from 1.5.0 beta 2 SVN 1131 to 1.5.0 beta 2 SVN 1138
--


-- Missing Translations

INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'config_orders', 'Orders Configuration');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'upload_bank_statement', 'Upload Bank Statement');
INSERT INTO `language` (`id`, `language`, `label`, `translation`) VALUES (NULL, 'en_us', 'BANK_STATEMENT', 'Bank Statement File');


-- Bank Statement Import

CREATE TABLE IF NOT EXISTS `input_structures` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `type_input` varchar(64) NOT NULL,
  `type_file` char(4) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `input_structure_items` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_structure` int(10) unsigned NOT NULL,
  `field_src` varchar(64) NOT NULL,
  `field_dest` varchar(64) NOT NULL,
  `regex` varchar(255) NOT NULL,
  `processing_regex` varchar(255) NOT NULL,
  `data_format` varchar(32) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `id_structure_2` 
(`id_structure`,`field_src`,`field_dest`),
  KEY `id_structure` (`id_structure`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


-- Predefined Banks

INSERT INTO `input_structures` (`id`, `name`, `description`, `type_input`, `type_file`) VALUES(1, 'Westpac', 'Westpac CSV Import', 'bank_statement', 'csv');
INSERT INTO `input_structures` (`id`, `name`, `description`, `type_input`, `type_file`) VALUES(2, 'National Bank', 'National Bank CSV Import', 'bank_statement', 'csv');
INSERT INTO `input_structures` (`id`, `name`, `description`, `type_input`, `type_file`) VALUES(3, 'ASB', 'ASB CSV Import', 'bank_statement', 'csv');

INSERT INTO `input_structure_items` (`id`, `id_structure`, `field_src`, `field_dest`, `regex`, `processing_regex`, `data_format`) VALUES(1, 1, '1', 'date', '', '', 'dd-mm-yyyy');
INSERT INTO `input_structure_items` (`id`, `id_structure`, `field_src`, `field_dest`, `regex`, `processing_regex`, `data_format`) VALUES(2, 1, '2', 'amount', '', '', '');
INSERT INTO `input_structure_items` (`id`, `id_structure`, `field_src`, `field_dest`, `regex`, `processing_regex`, `data_format`) VALUES(3, 1, '3', 'other_party', '', '', '');
INSERT INTO `input_structure_items` (`id`, `id_structure`, `field_src`, `field_dest`, `regex`, `processing_regex`, `data_format`) VALUES(4, 1, '4', 'transaction_type', '', '', '');
INSERT INTO `input_structure_items` (`id`, `id_structure`, `field_src`, `field_dest`, `regex`, `processing_regex`, `data_format`) VALUES(5, 1, '5', 'reference', '', '', '');
INSERT INTO `input_structure_items` (`id`, `id_structure`, `field_src`, `field_dest`, `regex`, `processing_regex`, `data_format`) VALUES(6, 1, '6', 'particulars', '', '', '');
INSERT INTO `input_structure_items` (`id`, `id_structure`, `field_src`, `field_dest`, `regex`, `processing_regex`, `data_format`) VALUES(7, 1, '7', 'code', '', '', '');
INSERT INTO `input_structure_items` (`id`, `id_structure`, `field_src`, `field_dest`, `regex`, `processing_regex`, `data_format`) VALUES(8, 2, '2', 'other_party', '', '', '');
INSERT INTO `input_structure_items` (`id`, `id_structure`, `field_src`, `field_dest`, `regex`, `processing_regex`, `data_format`) VALUES(9, 2, '4', 'transaction_type', '', '', '');
INSERT INTO `input_structure_items` (`id`, `id_structure`, `field_src`, `field_dest`, `regex`, `processing_regex`, `data_format`) VALUES(10, 2, '5', 'reference', '', '', '');
INSERT INTO `input_structure_items` (`id`, `id_structure`, `field_src`, `field_dest`, `regex`, `processing_regex`, `data_format`) VALUES(11, 2, '6', 'amount', '', '', '');
INSERT INTO `input_structure_items` (`id`, `id_structure`, `field_src`, `field_dest`, `regex`, `processing_regex`, `data_format`) VALUES(12, 2, '7', 'date', '', '', 'dd-mm-yyyy');
INSERT INTO `input_structure_items` (`id`, `id_structure`, `field_src`, `field_dest`, `regex`, `processing_regex`, `data_format`) VALUES(13, 3, '1', 'date', '', '', 'dd-mm-yyyy');
INSERT INTO `input_structure_items` (`id`, `id_structure`, `field_src`, `field_dest`, `regex`, `processing_regex`, `data_format`) VALUES(14, 3, '3', 'particulars', '', '', '');
INSERT INTO `input_structure_items` (`id`, `id_structure`, `field_src`, `field_dest`, `regex`, `processing_regex`, `data_format`) VALUES(15, 3, '4', 'reference', '', '', '');
INSERT INTO `input_structure_items` (`id`, `id_structure`, `field_src`, `field_dest`, `regex`, `processing_regex`, `data_format`) VALUES(16, 3, '5', 'other_party', '', '', '');
INSERT INTO `input_structure_items` (`id`, `id_structure`, `field_src`, `field_dest`, `regex`, `processing_regex`, `data_format`) VALUES(17, 3, '6', 'transaction_type', '', '', '');
INSERT INTO `input_structure_items` (`id`, `id_structure`, `field_src`, `field_dest`, `regex`, `processing_regex`, `data_format`) VALUES(18, 3, '7', 'amount', '', '', '');


--
-- Set Schema Version
--

UPDATE `config` SET `value` = '20110429' WHERE name='SCHEMA_VERSION' LIMIT 1;


