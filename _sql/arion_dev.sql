-- Adminer 4.2.1 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `accounts`;
CREATE TABLE `accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `fields`;
CREATE TABLE `fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) NOT NULL,
  `item_type_id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `label` varchar(128) DEFAULT NULL,
  `field_type` varchar(64) NOT NULL DEFAULT 'text',
  `field_options` varchar(4096) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `item_type_id` (`item_type_id`),
  KEY `account_id` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `fields` (`id`, `account_id`, `item_type_id`, `name`, `label`, `field_type`, `field_options`) VALUES
(1,	1,	1,	'email_addresses',	'Email Addresses',	'template',	'{\'repeatable\': true, \n    fields: \n    {\n        \'email_type\': {\n            \'label\': \'Email Type\',\n            \'sub_field_id\': 1,\n            \'type\': \'select\',\n            \'options\': [\'Business\',\'Personal\']\n        },\n        \'email\': {\n            \'label\': \'Email\',\n            \'sub_field_id\': 2,\n            \'type\': \'text\'\n            \'validation\': [\'valid_email\']\n        }\n    }\n}\n'),
(2,	1,	2,	'description',	'Description',	'textarea',	''),
(3,	1,	2,	'status',	'Status',	'enum',	'{\'options\': [\'open\': \'Open\', \'in_progress\': \'In Progress\', \'closed\': \'Closed\']}'),
(4,	1,	2,	'assigned_to',	'Assigned To',	'user_list',	'');

DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `groups_users`;
CREATE TABLE `groups_users` (
  `group_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  UNIQUE KEY `group_user` (`group_id`,`user_id`),
  KEY `group_id` (`group_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `groups` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `items`;
CREATE TABLE `items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) NOT NULL,
  `item_type_id` int(11) NOT NULL,
  `title` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_type_id` (`item_type_id`),
  KEY `account_id` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `items` (`id`, `account_id`, `item_type_id`, `title`) VALUES
(1,	1,	2,	'First item');

DROP TABLE IF EXISTS `item_types`;
CREATE TABLE `item_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `plural_name` varchar(128) DEFAULT NULL,
  `label` varchar(128) DEFAULT NULL,
  `plural_label` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `account_id` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `item_types` (`id`, `account_id`, `name`, `plural_name`, `label`, `plural_label`) VALUES
(1,	1,	'contact',	'contacts',	'Contact',	'Contacts'),
(2,	1,	'task',	'tasks',	'Task',	'Tasks'),
(3,	1,	'company',	'companies',	'Company',	'Companies'),
(4,	1,	'timesheet',	'timesheets',	'Timesheet',	'Timesheets'),
(5,	1,	'invoice',	'invoices',	'Invoice',	'Invoices');

DROP TABLE IF EXISTS `item_values`;
CREATE TABLE `item_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) DEFAULT NULL,
  `item_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `sub_field_id` int(11) NOT NULL,
  `sub_value_id` int(11) NOT NULL,
  `value` varchar(21800) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `item_values` (`id`, `account_id`, `item_id`, `field_id`, `sub_field_id`, `sub_value_id`, `value`) VALUES
(4,	1,	1,	2,	0,	0,	'This is the item description for the First Item'),
(5,	1,	1,	3,	0,	0,	'open'),
(6,	1,	1,	4,	0,	0,	'2'),
(7,	1,	1,	5,	0,	0,	'Invalid value');

DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(40) COLLATE utf8_bin NOT NULL,
  `login` varchar(50) COLLATE utf8_bin NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `relationships`;
CREATE TABLE `relationships` (
  `id` int(11) NOT NULL,
  `from_item_id` int(11) NOT NULL,
  `to_item_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `from_item_id` (`from_item_id`),
  KEY `to_item_id` (`to_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `ip` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `action_token` char(64) COLLATE utf8_unicode_ci DEFAULT '',
  `access_token` char(64) COLLATE utf8_unicode_ci DEFAULT '',
  `activated` tinyint(1) NOT NULL DEFAULT '0',
  `banned` tinyint(1) NOT NULL DEFAULT '0',
  `failed_attempts` int(11) NOT NULL DEFAULT '0',
  `last_fail_at` datetime DEFAULT NULL,
  `locked_until` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- 2015-09-20 22:28:40
