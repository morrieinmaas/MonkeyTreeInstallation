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

CREATE TABLE IF NOT EXISTS `#__social_notifications` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `context_type` varchar(255) NOT NULL,
  `cmd` text NOT NULL,
  `app_id` bigint(20) DEFAULT NULL,
  `title` text NOT NULL,
  `content` text NOT NULL,
  `image` text NOT NULL,
  `created` datetime NOT NULL,
  `state` tinyint(4) NOT NULL DEFAULT '0',
  `actor_id` int(11) NOT NULL,
  `actor_type` varchar(255) NOT NULL,
  `target_id` int(11) NOT NULL,
  `target_type` varchar(255) NOT NULL,
  `params` text NOT NULL,
  `url` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `node_id` (`uid`,`created`),
  KEY `idx_target_state` ( `target_id`, `target_type`, `state` ),
  KEY `idx_target_created` (`target_id`,`target_type`, `created`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
