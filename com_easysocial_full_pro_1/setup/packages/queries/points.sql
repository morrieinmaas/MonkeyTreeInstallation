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

CREATE TABLE IF NOT EXISTS `#__social_points` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `command` varchar(255) NOT NULL,
  `extension` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL COMMENT 'The title of the points',
  `description` text NOT NULL,
  `alias` varchar(255) NOT NULL COMMENT 'The permalink that links to the points.',
  `created` datetime NOT NULL COMMENT 'Creation datetime of the points.',
  `threshold` int(11) DEFAULT NULL COMMENT 'Optional value if app needs to give points based on certain actions multiple times.',
  `interval` tinyint(3) NOT NULL DEFAULT '0' COMMENT '0 - every time , 1 - once , 2 - twice - n times',
  `points` int(11) NOT NULL DEFAULT '0' COMMENT 'The amount of points to be given.',
  `state` tinyint(3) NOT NULL COMMENT 'The state of the points. 0 - unpublished, 1 - published ',
  `params` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `state` (`state`),
  KEY `command_id` (`command`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__social_points_history` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'Primary key for this table',
  `points_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL COMMENT 'The target user id',
  `points` int(11) NOT NULL COMMENT 'The number of points',
  `created` datetime NOT NULL COMMENT 'The date time value when the user earned the point.',
  `state` tinyint(3) NOT NULL COMMENT 'The publish state',
  `message` text NOT NULL COMMENT 'Any custom message set for this points assignment',
  PRIMARY KEY (`id`),
  KEY `state` (`state`),
  KEY `points_id` (`points_id`),
  KEY `idx_userid` ( `user_id`),
  KEY `user_points` (`user_id`, `points`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
