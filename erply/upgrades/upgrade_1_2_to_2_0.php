<?php

// Install mapping table.
$sql = '
CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'erply_mapping` (
  `erply_mapping_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `erply_code` varchar(50) NOT NULL,
  `object_type` varchar(50) NOT NULL,
  `local_id` int(10) unsigned NOT NULL,
  `erply_id` int(10) unsigned NOT NULL,
  `info` text,
  PRIMARY KEY (`erply_mapping_id`),
  KEY `IKEY1` (`erply_code`,`object_type`,`local_id`),
  KEY `IKEY2` (`erply_code`,`object_type`,`erply_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;';
if(!Db::getInstance()->Execute(trim($sql))) {
	print '<div class="alert error">Failed to create database table erply_mapping!</div>';
}

?>