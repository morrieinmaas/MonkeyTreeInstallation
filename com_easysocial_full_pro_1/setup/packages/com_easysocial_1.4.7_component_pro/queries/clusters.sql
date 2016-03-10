/*
* @package		EasySocial
* @copyright	Copyright (C) 2009 - 2011 StackIdeas Private Limited. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* EasySocial is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

CREATE TABLE IF NOT EXISTS `#__social_clusters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `cluster_type` varchar(255) NOT NULL,
  `creator_type` varchar(255) NOT NULL,
  `creator_uid` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `alias` varchar(255) NOT NULL,
  `state` tinyint(3) NOT NULL,
  `featured` tinyint(3) NOT NULL,
  `created` datetime NOT NULL,
  `params` text NOT NULL,
  `hits` int(11) NOT NULL,
  `type` tinyint(3) NOT NULL,
  `key` varchar(255) NOT NULL,
  `parent_id` int(11) NOT NULL DEFAULT 0,
  `parent_type` varchar(255) NOT NULL,
  `longitude` varchar(255) NOT NULL COMMENT 'The longitude value of the event for proximity search purposes',
  `latitude` varchar(255) NOT NULL COMMENT 'The latitude value of the event for proximity search purposes',
  `address` text NOT NULL COMMENT 'The full address value of the event for displaying purposes',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `featured` (`featured`),
  KEY `idx_state` (`state`),
  KEY `idx_clustertype` (`cluster_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*
CREATE TABLE IF NOT EXISTS `#__social_pclusters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `cluster_type` varchar(255) NOT NULL,
  `creator_type` varchar(255) NOT NULL,
  `creator_uid` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `alias` varchar(255) NOT NULL,
  `state` tinyint(3) NOT NULL,
  `featured` tinyint(3) NOT NULL,
  `created` datetime NOT NULL,
  `params` text NOT NULL,
  `hits` int(11) NOT NULL,
  `type` tinyint(3) NOT NULL,
  `key` varchar(255) NOT NULL,
  `parent_id` int(11) NOT NULL DEFAULT 0,
  `parent_type` varchar(255) NOT NULL,
  `longitude` varchar(255) NOT NULL COMMENT 'The longitude value of the event for proximity search purposes',
  `latitude` varchar(255) NOT NULL COMMENT 'The latitude value of the event for proximity search purposes',
  `address` text NOT NULL COMMENT 'The full address value of the event for displaying purposes',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `featured` (`featured`),
  KEY `idx_state` (`state`),
  KEY `idx_clustertype` (`cluster_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
*/
CREATE TABLE IF NOT EXISTS `#__social_clusters_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `alias` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `created` datetime NOT NULL,
  `state` tinyint(3) NOT NULL,
  `uid` int(11) NOT NULL COMMENT 'The creator of the category',
  `ordering` tinyint(3) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
/*
CREATE TABLE IF NOT EXISTS `#__social_pclusters_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `alias` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `created` datetime NOT NULL,
  `state` tinyint(3) NOT NULL,
  `uid` int(11) NOT NULL COMMENT 'The creator of the category',
  `ordering` tinyint(3) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
*/
CREATE TABLE IF NOT EXISTS `#__social_clusters_news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cluster_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created` datetime NOT NULL,
  `created_by` int(11) NOT NULL,
  `state` tinyint(3) NOT NULL,
  `hits` int(11) NOT NULL,
  `comments` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cluster_id` (`cluster_id`,`created_by`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
/*
CREATE TABLE IF NOT EXISTS `#__social_pclusters_news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cluster_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created` datetime NOT NULL,
  `created_by` int(11) NOT NULL,
  `state` tinyint(3) NOT NULL,
  `hits` int(11) NOT NULL,
  `comments` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cluster_id` (`cluster_id`,`created_by`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
*/
CREATE TABLE IF NOT EXISTS `#__social_clusters_nodes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cluster_id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `created` datetime NOT NULL,
  `state` tinyint(4) NOT NULL,
  `owner` tinyint(3) NOT NULL,
  `admin` tinyint(3) NOT NULL,
  `invited_by` int(11) NOT NULL,
  `reminder_sent` tinyint(3) NULL default 0,
  PRIMARY KEY (`id`),
  KEY `cluster_id` (`cluster_id`,`state`),
  KEY `invited_by` (`invited_by`),
  KEY `idx_clusters_nodes_uid` (`uid`),
  KEY `idx_clusters_nodes_user` (`uid`,`state`, `created`),
  KEY `idx_members` (`cluster_id`, `type`, `state` ),
  KEY `idx_reminder_sent` (`reminder_sent`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*
CREATE TABLE IF NOT EXISTS `#__social_pclusters_nodes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cluster_id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `created` datetime NOT NULL,
  `state` tinyint(4) NOT NULL,
  `owner` tinyint(3) NOT NULL,
  `admin` tinyint(3) NOT NULL,
  `invited_by` int(11) NOT NULL,
  `reminder_sent` tinyint(3) NULL default 0,
  PRIMARY KEY (`id`),
  KEY `cluster_id` (`cluster_id`,`state`),
  KEY `invited_by` (`invited_by`),
  KEY `idx_clusters_nodes_uid` (`uid`),
  KEY `idx_clusters_nodes_user` (`uid`,`state`, `created`),
  KEY `idx_members` (`cluster_id`, `type`, `state` ),
  KEY `idx_reminder_sent` (`reminder_sent`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
*/
CREATE TABLE IF NOT EXISTS `#__social_step_sessions` (
  `session_id` varchar(200) NOT NULL,
  `uid` bigint(20) NOT NULL,
  `type` varchar(255) NOT NULL,
  `created` datetime NOT NULL,
  `values` text NOT NULL,
  `step` bigint(20) NOT NULL,
  `step_access` text NOT NULL,
  `errors` text NOT NULL,
  UNIQUE KEY `session_id` (`session_id`),
  KEY `profile_id` (`uid`),
  KEY `step` (`step`),
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*
CREATE TABLE IF NOT EXISTS `#__social_pstep_sessions` (
  `session_id` varchar(200) NOT NULL,
  `uid` bigint(20) NOT NULL,
  `type` varchar(255) NOT NULL,
  `created` datetime NOT NULL,
  `values` text NOT NULL,
  `step` bigint(20) NOT NULL,
  `step_access` text NOT NULL,
  `errors` text NOT NULL,
  UNIQUE KEY `session_id` (`session_id`),
  KEY `profile_id` (`uid`),
  KEY `step` (`step`),
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
*/
CREATE TABLE IF NOT EXISTS `#__social_clusters_categories_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `profile_id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'create',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`,`profile_id`),
  KEY `category_id_2` (`category_id`,`profile_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
/*
CREATE TABLE IF NOT EXISTS `#__social_pclusters_categories_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `profile_id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'create',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`,`profile_id`),
  KEY `category_id_2` (`category_id`,`profile_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
*/
CREATE TABLE IF NOT EXISTS `#__social_events_meta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cluster_id` int(11) NOT NULL COMMENT 'The event cluster id',
  `start` datetime NOT NULL COMMENT 'The start datetime of the event',
  `end` datetime NOT NULL COMMENT 'The end datetime of the event',
  `timezone` varchar(255) NOT NULL COMMENT 'The optional timezone of the event for datetime calculation',
  `all_day` tinyint(3) NOT NULL COMMENT 'Flag if this event is an all day event',
  `group_id` int(11) NOT NULL COMMENT 'The group id if this is a group event',
  `reminder` int(11) NULL default 0 COMMENT 'the number of days before the actual event date',
  PRIMARY KEY (`id`),
  KEY `cluster_id` (`cluster_id`),
  KEY `idx_reminder` (`reminder`),
  KEY `idx_upcoming_reminder` (`reminder`,`start`),
  KEY `idx_start` (`start`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*
CREATE TABLE IF NOT EXISTS `#__social_projects_meta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cluster_id` int(11) NOT NULL COMMENT 'The project cluster id',
  `start` datetime NOT NULL COMMENT 'The start datetime of the project',
  `end` datetime NOT NULL COMMENT 'The end datetime of the project',
  `timezone` varchar(255) NOT NULL COMMENT 'The optional timezone of the project for datetime calculation',
  `all_day` tinyint(3) NOT NULL COMMENT 'Flag if this project is an all day project',
  `group_id` int(11) NOT NULL COMMENT 'The group id if this is a group project',
  `reminder` int(11) NULL default 0 COMMENT 'the number of days before the actual project date',
  PRIMARY KEY (`id`),
  KEY `cluster_id` (`cluster_id`),
  KEY `idx_reminder` (`reminder`),
  KEY `idx_upcoming_reminder` (`reminder`,`start`),
  KEY `idx_start` (`start`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
*/