# ************************************************************
# Sequel Pro SQL dump
# Version 4499
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: localhost (MySQL 5.5.42)
# Database: arion_dev
# Generation Time: 2016-01-16 23:29:09 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table accounts
# ------------------------------------------------------------

DROP TABLE IF EXISTS `accounts`;

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `accounts` WRITE;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;

/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table fields
# ------------------------------------------------------------

DROP TABLE IF EXISTS `fields`;

CREATE TABLE `fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) NOT NULL,
  `item_type_id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `label` varchar(128) DEFAULT NULL,
  `field_type` varchar(64) NOT NULL DEFAULT 'text',
  `in_title` tinyint(1) NOT NULL DEFAULT '0',
  `field_options` varchar(4096) NOT NULL DEFAULT '',
  `field_item_type_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `item_type_id` (`item_type_id`),
  KEY `account_id` (`account_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `fields` WRITE;
/*!40000 ALTER TABLE `fields` DISABLE KEYS */;

/*!40000 ALTER TABLE `fields` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table groups
# ------------------------------------------------------------

DROP TABLE IF EXISTS `groups`;

CREATE TABLE `groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table groups_users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `groups_users`;

CREATE TABLE `groups_users` (
  `group_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  UNIQUE KEY `group_user` (`group_id`,`user_id`),
  KEY `group_id` (`group_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table item_types
# ------------------------------------------------------------

DROP TABLE IF EXISTS `item_types`;

CREATE TABLE `item_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `plural_name` varchar(128) DEFAULT NULL,
  `label` varchar(128) DEFAULT NULL,
  `plural_label` varchar(128) DEFAULT NULL,
  `are_users` tinyint(1) NOT NULL DEFAULT '0',
  `own_users` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `account_id` (`account_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `item_types` WRITE;
/*!40000 ALTER TABLE `item_types` DISABLE KEYS */;


/*!40000 ALTER TABLE `item_types` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table item_values
# ------------------------------------------------------------

DROP TABLE IF EXISTS `item_values`;

CREATE TABLE `item_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) DEFAULT NULL,
  `item_id` int(11) NOT NULL,
  `ver` int(11) NOT NULL DEFAULT '1',
  `prev_ver` int(11) NOT NULL,
  `sub_field_count` int(11) NOT NULL DEFAULT '1',
  `sub_value_count` int(11) NOT NULL DEFAULT '1',
  `field_id` int(11) NOT NULL,
  `sub_field_id` int(11) NOT NULL,
  `sub_value_id` int(11) NOT NULL,
  `value` varchar(21800) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `item_values` WRITE;
/*!40000 ALTER TABLE `item_values` DISABLE KEYS */;


/*!40000 ALTER TABLE `item_values` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table items
# ------------------------------------------------------------

DROP TABLE IF EXISTS `items`;

CREATE TABLE `items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) NOT NULL,
  `item_type_id` int(11) NOT NULL,
  `field_count` int(11) NOT NULL DEFAULT '0',
  `ver` int(11) NOT NULL DEFAULT '1',
  `title` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_type_id` (`item_type_id`),
  KEY `account_id` (`account_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `items` WRITE;
/*!40000 ALTER TABLE `items` DISABLE KEYS */;


/*!40000 ALTER TABLE `items` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table login_attempts
# ------------------------------------------------------------

DROP TABLE IF EXISTS `login_attempts`;

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(40) COLLATE utf8_bin NOT NULL,
  `login` varchar(50) COLLATE utf8_bin NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;



# Dump of table mailbox_messages
# ------------------------------------------------------------

DROP TABLE IF EXISTS `mailbox_messages`;

CREATE TABLE `mailbox_messages` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int(11) DEFAULT NULL,
  `mailbox_id` int(11) NOT NULL,
  `imap_uid` int(11) NOT NULL,
  `from` varchar(256) NOT NULL DEFAULT '',
  `subject` varchar(256) NOT NULL DEFAULT '',
  `body` longtext NOT NULL,
  `date` datetime NOT NULL,
  `to` varchar(256) NOT NULL DEFAULT '',
  `cc` varchar(2048) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



# Dump of table mailboxes
# ------------------------------------------------------------

DROP TABLE IF EXISTS `mailboxes`;

CREATE TABLE `mailboxes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `server` varchar(128) DEFAULT NULL,
  `port` int(11) DEFAULT '993',
  `username` varchar(128) DEFAULT NULL,
  `password` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table mako_migrations
# ------------------------------------------------------------

DROP TABLE IF EXISTS `mako_migrations`;

CREATE TABLE `mako_migrations` (
  `batch` int(10) unsigned NOT NULL,
  `package` varchar(255) DEFAULT NULL,
  `version` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



# Dump of table relationships
# ------------------------------------------------------------

DROP TABLE IF EXISTS `relationships`;

CREATE TABLE `relationships` (
  `id` int(11) NOT NULL,
  `from_item_id` int(11) NOT NULL,
  `to_item_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `from_item_id` (`from_item_id`),
  KEY `to_item_id` (`to_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int(11) DEFAULT NULL,
  `user_type` enum('restricted','normal','admin','') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'restricted',
  `user_item_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `ip` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `is_locked` tinyint(1) DEFAULT '0',
  `failed_attempts` int(11) DEFAULT '0',
  `last_fail_at` datetime DEFAULT NULL,
  `locked_until` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;


/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
