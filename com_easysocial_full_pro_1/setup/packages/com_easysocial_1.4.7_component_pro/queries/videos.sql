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

CREATE TABLE IF NOT EXISTS `#__social_videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary key for this table',
  `title` varchar(255) NOT NULL COMMENT 'Title of the video',
  `description` text NOT NULL COMMENT 'The description of the video',
  `user_id` int(11) NOT NULL COMMENT 'The user id that created this video',
  `uid` int(11) NOT NULL COMMENT 'This video may belong to another node other than the user.',
  `type` varchar(255) NOT NULL COMMENT 'This video may belong to another node other than the user.',
  `created` datetime NOT NULL,
  `state` tinyint(3) NOT NULL,
  `featured` tinyint(3) NOT NULL,
  `category_id` int(11) NOT NULL,
  `hits` int(11) NOT NULL COMMENT 'Total hits received for this video',
  `duration` varchar(255) NOT NULL COMMENT 'Duration of the video',
  `size` int(11) NOT NULL COMMENT 'The file size of the video',
  `params` text NOT NULL COMMENT 'Store video params',
  `storage` varchar(255) NOT NULL COMMENT 'Storage for videos',
  `path` text NOT NULL,
  `original` text NOT NULL,
  `file_title` varchar(255) NOT NULL,
  `source` varchar(255) NOT NULL,
  `thumbnail` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `title` (`title`,`user_id`,`state`,`featured`,`category_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_videos_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `alias` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `state` tinyint(3) NOT NULL,
  `default` tinyint(3) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL COMMENT 'The user id that created this category',
  `created` datetime NOT NULL,
  `ordering` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `state` (`state`,`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__social_videos_categories_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `profile_id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`,`profile_id`,`type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;