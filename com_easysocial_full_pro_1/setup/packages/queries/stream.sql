/*
* @package    EasySocial
* @copyright  Copyright (C) 2009 - 2011 StackIdeas Private Limited. All rights reserved.
* @license    GNU/GPL, see LICENSE.php
* EasySocial is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

CREATE TABLE IF NOT EXISTS `#__social_stream` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `actor_id` bigint(20) unsigned NOT NULL,
  `alias` varchar(255) DEFAULT '',
  `actor_type` varchar(64) DEFAULT 'user',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `edited` DATETIME NULL DEFAULT '0000-00-00 00:00:00',
  `title` text,
  `content` text,
  `context_type` varchar(64) DEFAULT '',
  `verb` varchar(64) DEFAULT '',
  `stream_type` varchar(15) DEFAULT NULL,
  `sitewide` tinyint(1) DEFAULT '0',
  `target_id` BIGINT( 20 ) NOT NULL,
  `location_id` int(11) NOT NULL,
  `mood_id` int(11) NOT NULL,
  `with` text NOT NULL,
  `ispublic` tinyint(3) default 0 NOT NULL,
  `cluster_id` int(11) default 0 null,
  `cluster_type` varchar(64) null,
  `cluster_access` tinyint(3) default 0,
  `params` text null,
  `state` tinyint(3) default 1 NOT NULL,
  `privacy_id` int(11) NULL,
  `access` int(11) default 0 NOT NULL,
  `custom_access` text NULL,
  `last_action` varchar(255) NULL,
  `last_userid` bigint(20) unsigned default 0,
  PRIMARY KEY (`id`),
  KEY `stream_actor` (`actor_id`),
  KEY `stream_created` (`created`),
  KEY `stream_modified` (`modified`),
  KEY `stream_alias` (`alias`),
  KEY `stream_source` (`actor_type`),
  KEY `idx_stream_context_type` ( `context_type` ),
  KEY `idx_stream_target` ( `target_id` ),
  KEY `idx_actor_modified` ( `actor_id`, `modified` ),
  KEY `idx_target_context_modified` ( `target_id`, `context_type`, `modified` ),
  KEY `idx_sitewide_modified` ( `sitewide`, `modified` ),
  KEY `idx_ispublic` ( `ispublic`, `modified` ),
  KEY `idx_clusterid` ( `cluster_id` ),
  KEY `idx_cluster_items` ( `cluster_id`, `cluster_type`, `modified` ),
  KEY `idx_cluster_access` ( `cluster_id`, `cluster_access` ),
  KEY `idx_access` (`access`),
  KEY `idx_custom_access` (`access`, `custom_access` (255)),
  KEY `idx_stream_total_cluster` (`cluster_id`, `cluster_access`, `context_type`, `id`, `actor_id`),
  KEY `idx_stream_total_user` (`cluster_id`, `access`, `actor_id`, `context_type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__social_stream_history` (
  `id` bigint(20) unsigned NOT NULL,
  `actor_id` bigint(20) unsigned NOT NULL,
  `alias` varchar(255) DEFAULT '',
  `actor_type` varchar(64) DEFAULT 'user',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `edited` DATETIME NULL DEFAULT '0000-00-00 00:00:00',
  `title` text,
  `content` text,
  `context_type` varchar(64) DEFAULT '',
  `verb` varchar(64) DEFAULT '',
  `stream_type` varchar(15) DEFAULT NULL,
  `sitewide` tinyint(1) DEFAULT '0',
  `target_id` BIGINT( 20 ) NOT NULL,
  `location_id` int(11) NOT NULL,
  `mood_id` int(11) NOT NULL,
  `with` text NOT NULL,
  `ispublic` tinyint(3) default 0 NOT NULL,
  `cluster_id` int(11) default 0 null,
  `cluster_type` varchar(64) null,
  `cluster_access` tinyint(3) default 0,
  `params` text null,
  `state` tinyint(3) default 1 NOT NULL,
  `privacy_id` int(11) NULL,
  `access` int(11) default 0 NOT NULL,
  `custom_access` text NULL,
  `last_action` varchar(255) NULL,
  `last_userid` bigint(20) unsigned default 0,
  PRIMARY KEY (`id`),
  KEY `stream_history_created` (`created`),
  KEY `stream_history_modified` (`modified`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_stream_assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stream_id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `data` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_stream_hide` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `uid` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `context` varchar(255) DEFAULT NULL,
  `actor_id` bigint(20) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `stream_hide_user` (`user_id`),
  KEY `stream_hide_uid` (`uid`),
  KEY `stream_hide_actorid` (`actor_id`),
  KEY `stream_hide_user_uid` (`user_id`,`uid`),
  KEY `idx_stream_hide_context` (`context`, `user_id`, `uid`, `actor_id`),
  KEY `idx_stream_hide_actor` (`actor_id`, `user_id`, `uid`, `context`),
  KEY `idx_stream_hide_uid` (`uid`, `user_id`, `type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_stream_item` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `actor_id` bigint(20) unsigned NOT NULL,
  `actor_type` varchar(255) DEFAULT 'people',
  `context_type` varchar(64) DEFAULT '',
  `context_id` bigint(20) unsigned DEFAULT '0',
  `verb` varchar(64) DEFAULT '',
  `target_id` bigint(20) unsigned DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `uid` bigint(20) unsigned NOT NULL,
  `sitewide` tinyint(1) DEFAULT '0',
  `params` text null,
  `state` tinyint(3) default 1 NOT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_actor` (`actor_id`),
  KEY `activity_created` (`created`),
  KEY `activity_context` (`context_type`),
  KEY `activity_context_id` (`context_id`),
  KEY `idx_context_verb` (`context_type`, `verb`),
  KEY `idx_uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_stream_item_history` (
  `id` bigint(20) unsigned NOT NULL,
  `actor_id` bigint(20) unsigned NOT NULL,
  `actor_type` varchar(255) DEFAULT 'people',
  `context_type` varchar(64) DEFAULT '',
  `context_id` bigint(20) unsigned DEFAULT '0',
  `verb` varchar(64) DEFAULT '',
  `target_id` bigint(20) unsigned DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `uid` bigint(20) unsigned NOT NULL,
  `sitewide` tinyint(1) DEFAULT '0',
  `params` text null,
  `state` tinyint(3) default 1 NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_history_uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_stream_tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stream_id` bigint(20) unsigned NOT NULL,
  `uid` bigint(20) unsigned NOT NULL,
  `utype` varchar(255) DEFAULT 'user',
  `with` tinyint(3) unsigned DEFAULT '0',
  `offset` int(11) DEFAULT '0',
  `length` int(11) DEFAULT '0',
  `title` varchar(255) NULL,
  PRIMARY KEY (`id`),
  KEY `streamtags_streamid` (`stream_id`),
  KEY `streamtags_uidtype` (`uid`,`utype`),
  KEY `streamtags_uidoffset` (`stream_id`, `offset`),
  KEY `streamtags_title` (`title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__social_stream_filter` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` bigint(20) unsigned NOT NULL,
  `utype` varchar(255) DEFAULT 'user',
  `title` varchar(255) not null,
  `alias` varchar(255) not null,
  `user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `streamfilter_uidtype` (`uid`, `utype`),
  KEY `streamfilter_alias` (`alias`),
  KEY `streamfilter_cluster_user` ( `uid`, `utype`, `user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_stream_filter_item` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `filter_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `content` TEXT NULL,
  PRIMARY KEY (`id`),
  KEY `filteritem_fid` (`filter_id`),
  KEY `filteritem_type` (`type`),
  KEY `filteritem_fidtype` (`filter_id`, `type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_stream_sticky` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stream_id` bigint(20) unsigned NOT NULL,
  `created` DATETIME NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `idx_streamid` (`stream_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


