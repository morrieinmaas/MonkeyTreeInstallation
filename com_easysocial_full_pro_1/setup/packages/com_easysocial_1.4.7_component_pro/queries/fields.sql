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

CREATE TABLE IF NOT EXISTS `#__social_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unique_key` text NOT NULL,
  `app_id` int(11) NOT NULL,
  `step_id` int(11) NOT NULL,
  `title` text NOT NULL,
  `display_title` tinyint(3) NOT NULL,
  `description` text NOT NULL,
  `display_description` tinyint(3) NOT NULL,
  `default` text NOT NULL,
  `validation` text NOT NULL,
  `state` tinyint(3) NOT NULL DEFAULT '1',
  `searchable` tinyint(4) NOT NULL DEFAULT '1',
  `required` tinyint(4) NOT NULL DEFAULT '0',
  `params` text NOT NULL,
  `ordering` int(11) NOT NULL DEFAULT '0',
  `core` tinyint(4) NOT NULL DEFAULT '0',
  `visible_registration` tinyint(3) NOT NULL,
  `visible_edit` tinyint(3) NOT NULL,
  `visible_display` tinyint(3) NOT NULL,
  `visible_mini_registration` tinyint(3) NOT NULL DEFAULT '0',
  `friend_suggest` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `field_id` (`app_id`),
  KEY `required` (`required`),
  KEY `searchable` (`searchable`),
  KEY `state` (`state`),
  KEY `step_id` (`step_id`),
  KEY `friend_suggest` (`friend_suggest`),
  KEY `idx_unique_key` (`unique_key` (64))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__social_fields_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `field_id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `datakey` varchar(255) NOT NULL,
  `data` text NOT NULL,
  `params` text NOT NULL,
  `raw` text,
  PRIMARY KEY (`id`),
  KEY `field_id` (`field_id`,`uid`),
  KEY `node_id` (`uid`),
  KEY `idx_uid_type` ( `uid`, `type` ),
  FULLTEXT KEY `fields_data_raw` (`raw`),
  KEY `idx_type_raw` (`type` (25), `raw` (255) ),
  KEY `idx_type_key_raw` (`type` (25), `datakey` (50), `raw` (255) )
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__social_fields_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `key` varchar(255) NOT NULL,
  `ordering` int(11) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `default` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_parents` ( `parent_id`, `key` ),
  KEY `idx_parentid` ( `parent_id` )
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_fields_position` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `field_id` int(11) NOT NULL,
  `position` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `field_id` (`field_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_fields_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `match_text` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `field_id` (`parent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_fields_steps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` bigint(20) NOT NULL,
  `type` varchar(255) NOT NULL,
  `title` text NOT NULL,
  `description` text NOT NULL,
  `state` tinyint(4) NOT NULL,
  `created` datetime NOT NULL,
  `sequence` int(11) NOT NULL,
  `visible_registration` tinyint(3) NOT NULL,
  `visible_edit` tinyint(3) NOT NULL,
  `visible_display` tinyint(3) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `workflow_id` (`uid`),
  KEY `state` (`state`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__social_profiles` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `title` text NOT NULL,
  `alias` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `gid` text NOT NULL,
  `default` tinyint(4) NOT NULL,
  `default_avatar` int(11) DEFAULT NULL COMMENT 'If this field contains an id, it''s from the default avatar, otherwise use system''s default avatar.',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `state` tinyint(4) NOT NULL,
  `params` text NOT NULL,
  `registration` tinyint(4) NOT NULL DEFAULT 1,
  `ordering` int(11) NOT NULL,
  `community_access` TINYINT(3) NOT NULL DEFAULT 1,
  `apps` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `state` (`state`),
  KEY `profile_esad` (`community_access`),
  KEY `idx_profile_access` (`id`, `community_access`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_profiles_maps` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `profile_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `state` tinyint(4) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `profile_id` (`profile_id`),
  KEY `idx_userid` ( `user_id`),
  KEY `idx_profile_users` (`profile_id`, `user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
