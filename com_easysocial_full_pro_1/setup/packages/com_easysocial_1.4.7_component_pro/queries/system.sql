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


CREATE TABLE IF NOT EXISTS `#__social_config` (
  `type` VARCHAR(255) NOT NULL,
  `value` text NOT NULL,
  `value_binary` blob NULL,
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_indexer` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `utype` varchar(64) DEFAULT NULL,
  `component` varchar(64) DEFAULT NULL,
  `title` text NOT NULL,
  `content` longtext NOT NULL,
  `link` text,
  `last_update` datetime NOT NULL,
  `ucreator` bigint(20) unsigned DEFAULT '0',
  `image` text,
  PRIMARY KEY (`id`),
  KEY `social_source` (`uid`,`utype`,`component`),
  FULLTEXT KEY `social_indexer_snapshot` (`title`,`content`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_likes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  `uid` bigint(20) NOT NULL,
  `stream_id` bigint(20) NULL default 0,
  `created_by` bigint(20) unsigned DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `social_likes_uid` (`uid`),
  KEY `social_likes_contenttype` (`type`),
  KEY `social_likes_createdby` (`created_by`),
  KEY `social_likes_content_type` (`type`,`uid`),
  KEY `social_likes_content_type_by` (`type`,`uid`,`created_by`),
  KEY `idx_stream_id` (`stream_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_locations` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uid` bigint(20) NOT NULL,
  `type` text NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `created` datetime NOT NULL,
  `short_address` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `longitude` varchar(255) NOT NULL,
  `latitude` varchar(255) NOT NULL,
  `params` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_regions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uid` bigint(20) NOT NULL,
  `type` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(64) NOT NULL,
  `parent_uid` bigint(20) NOT NULL,
  `parent_type` varchar(255) NOT NULL,
  `state` tinyint(4) NOT NULL DEFAULT 1,
  `ordering` int(11) NOT NULL DEFAULT 0,
  `params` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_logger` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `file` varchar(255) NOT NULL,
  `line` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_mailer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_name` text NOT NULL,
  `sender_email` text NOT NULL,
  `replyto_email` text NOT NULL,
  `recipient_name` text NOT NULL,
  `recipient_email` text NOT NULL,
  `title` text NOT NULL,
  `content` text NOT NULL,
  `template` text NOT NULL,
  `html` tinyint(4) NOT NULL,
  `state` tinyint(4) NOT NULL,
  `response` text NOT NULL,
  `created` datetime NOT NULL,
  `params` text NOT NULL,
  `priority` tinyint(4) NOT NULL COMMENT '1 - Low , 2 - Medium , 3 - High , 4 - Highest',
  `language` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `state` (`state`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_migrators` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `oid` bigint(20) unsigned NOT NULL,
  `element` varchar(100) NOT NULL,
  `component` varchar(100) NOT NULL,
  `uid` bigint(20) unsigned NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `component_content` (`component`,`oid`,`element`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Store migrated content id and map with easysocial item id.';


CREATE TABLE IF NOT EXISTS `#__social_registrations` (
  `session_id` varchar(200) NOT NULL,
  `profile_id` bigint(20) NOT NULL,
  `created` datetime NOT NULL,
  `values` text NOT NULL,
  `step` bigint(20) NOT NULL,
  `step_access` text NOT NULL,
  `errors` text NOT NULL,
  UNIQUE KEY `session_id` (`session_id`),
  KEY `profile_id` (`profile_id`),
  KEY `step` (`step`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__social_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` text NOT NULL,
  `message` text NOT NULL,
  `extension` varchar(255) NOT NULL,
  `uid` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `created_by` int(11) NOT NULL,
  `ip` varchar(255) NOT NULL,
  `created` datetime NOT NULL,
  `state` tinyint(3) NOT NULL DEFAULT '0',
  `url` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_shares` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uid` bigint(20) NOT NULL,
  `element` varchar(255) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `content` text NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `shares_element` (`uid`,`element`),
  KEY `shares_element_user` (`uid`,`element`,`user_id`),
  KEY `shares_userid` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__social_themes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `element` varchar(255) NOT NULL,
  `params` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `element` (`element`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL COMMENT 'object id e.g userid, groupid, streamid and etc',
  `type` varchar(64) NOT NULL COMMENT 'subscription type e.g. user, group, stream and etc',
  `user_id` int(11) DEFAULT '0',
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid_type` (`uid`,`type`),
  KEY `uid_type_user` (`uid`,`type`,`user_id`),
  KEY `uid_type_email` (`uid`,`type`),
  KEY `idx_uid` ( `uid` ),
  KEY `idx_type_userid` ( `type`, `user_id` ),
  KEY `idx_userid` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `#__social_users` (
  `user_id` bigint(20) NOT NULL,
  `alias` varchar(255) NOT NULL,
  `state` tinyint(3) NOT NULL,
  `params` text NOT NULL,
  `connections` int(11) NOT NULL,
  `permalink` VARCHAR( 255 ) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'joomla',
  `auth` varchar(255) NOT NULL,
  `completed_fields` int(11) NOT NULL DEFAULT 0,
  `reminder_sent` tinyint(1) DEFAULT 0,
  `require_reset` tinyint(1) DEFAULT 0,
  `block_date` datetime null default '0000-00-00 00:00:00',
  `block_period` int(11) DEFAULT 0,
  PRIMARY KEY (`user_id`),
  KEY `state` (`state`),
  KEY `alias` (`alias`),
  KEY `connections` (`connections`),
  KEY `permalink` (`permalink`),
  KEY `idx_types` (`user_id`,`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `element` varchar(255) NOT NULL,
  `uid` bigint(20) NOT NULL,
  `comment` text NOT NULL,
  `stream_id` bigint(20) NULL default 0,
  `created_by` bigint(20) unsigned DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `depth` bigint(10) DEFAULT '0',
  `parent` bigint(20) DEFAULT '0',
  `child` bigint(20) DEFAULT '0',
  `lft` bigint(20) DEFAULT '0',
  `rgt` bigint(20) DEFAULT '0',
  `params` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `social_comments_uid` (`uid`),
  KEY `social_comments_type` (`element`),
  KEY `social_comments_createdby` (`created_by`),
  KEY `social_comments_content_type` (`element`,`uid`),
  KEY `social_comments_content_type_by` (`element`,`uid`,`created_by`),
  KEY `social_comments_content_parent` (`element`,`uid`,`parent`),
  KEY `idx_comment_batch` (`stream_id`, `element`, `uid`),
  KEY `idx_comment_stream_id` (`stream_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_languages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `locale` varchar(255) NOT NULL,
  `updated` datetime NOT NULL,
  `state` tinyint(3) NOT NULL,
  `translator` varchar(255) NOT NULL,
  `progress` int(11) NOT NULL,
  `params` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hash` varchar(255) NOT NULL,
  `data` text NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `hash` (`hash`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_search_filter` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `element` varchar(255) NOT NULL,
  `uid` bigint(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `alias` varchar(255) NOT NULL,
  `filter` text NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `sitewide` tinyint(1) default 0,
  PRIMARY KEY (`id`),
  KEY `idx_searchfilter_element_id` (`element`,`uid`),
  KEY `idx_searchfilter_owner` (`element`,`uid`, `created_by`),
  KEY `idx_searchfilter_alias` (`alias`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_storage_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `object_id` int(11) NOT NULL,
  `object_type` varchar(255) NOT NULL,
  `target` varchar(255) NOT NULL,
  `state` tinyint(3) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_moods` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary key of the row',
  `namespace` varchar(255) NOT NULL COMMENT 'Determines if this item is tied to a specific item',
  `namespace_uid` int(11) NOT NULL,
  `icon` varchar(255) NOT NULL COMMENT 'Contains the css class for the emoticon',
  `verb` varchar(255) NOT NULL COMMENT 'Feeling, Watching, Eating etc',
  `subject` text NOT NULL COMMENT 'Happy, Sad, Angry etc',
  `custom` tinyint(3) NOT NULL COMMENT 'Determines if the user supplied a custom text',
  `text` text NOT NULL COMMENT 'If there is a custom text, based on the custom column, this text will be used.',
  `user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_broadcasts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `target_id` int(11) NOT NULL,
  `target_type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `link` text NOT NULL,
  `state` tinyint(3) NOT NULL,
  `created` datetime NOT NULL,
  `created_by` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_broadcast` (`target_id`, `target_type`, `state`, `created`),
  KEY `idx_created` (`created`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_bookmarks` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `uid` int(11) NOT NULL COMMENT 'The bookmarked item id',
  `type` varchar(255) NOT NULL COMMENT 'The bookmarked type',
  `created` datetime NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'The owner of the bookmarked item',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`,`type`),
  KEY `user_id` (`user_id`),
  KEY `idx_uid` (`uid`),
  KEY `idx_user_utype` (`uid`, `type`, `user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_block_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `target_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`,`target_id`),
  KEY `idx_userid` (`user_id`),
  KEY `idx_targetid` (`target_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_links_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source_url` text NOT NULL,
  `internal_url` text NOT NULL,
  `storage` varchar(255) NOT NULL DEFAULT 'joomla',
  PRIMARY KEY (`id`),
  KEY `idx_storage_cron` (`storage`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_polls_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `poll_id` bigint(20) unsigned NOT NULL,
  `poll_itemid` bigint(20) not null default 0,
  `user_id` bigint(20) not null,
  `session_id` varchar(255) NULL,
   PRIMARY KEY (`id`),
   KEY `idx_pollid` (`poll_id`),
   KEY `idx_userid` (`user_id`),
   KEY `idx_pollitem` (`poll_itemid`),
   KEY `idx_poll_user` (`poll_id`, `user_id`),
   KEY `idx_poll_item_user` (`poll_id`, `poll_itemid`, `user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_polls_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `poll_id` bigint(20) unsigned NOT NULL,
  `value` text not null,
  `count` bigint(20) not null default 0,
   PRIMARY KEY (`id`),
   KEY `idx_pollid` (`poll_id`),
   KEY `idx_polls` (poll_id, id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_polls` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `element` varchar(255) NOT NULL,
  `uid` bigint(20) NOT NULL,
  `title` text NOT NULL,
  `multiple` tinyint(1) NULL default 0,
  `locked` tinyint(1) NULL default 0,
  `cluster_id` bigint(20) null,
  `created_by` bigint(20) unsigned DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `expiry_date` datetime NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `idx_element_id` (`element`,`uid`),
  KEY `idx_clusterid` (`cluster_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
