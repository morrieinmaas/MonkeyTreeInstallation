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

CREATE TABLE IF NOT EXISTS `#__social_discussions_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) NOT NULL,
  `discussion_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `file_id` (`file_id`,`discussion_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `#__social_discussions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT 'This determines if this is a reply to a discussion. If it is a reply, it should contain the parent''s id here.',
  `uid` int(11) NOT NULL COMMENT 'The unique id this discussion is associated to. For example, if it is associated with a group, it should store the group''s id.',
  `type` varchar(255) NOT NULL COMMENT 'The unique type this discussion is associated to. For example, if it is associated with a group, it should store the type as group',
  `answer_id` int(11) NOT NULL COMMENT 'This is only applicable to main question. This should contain the reference to the discussion that is an answer.',
  `last_reply_id` int(11) NOT NULL COMMENT 'Determines the last reply for the discussion',
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `hits` int(11) NOT NULL DEFAULT '0' COMMENT 'Stores the total views for this discussion.',
  `state` tinyint(3) NOT NULL,
  `created` datetime NOT NULL,
  `last_replied` datetime NOT NULL COMMENT 'Stores the last replied date time.',
  `votes` int(11) NOT NULL COMMENT 'Determines the vote count for this discussion.',
  `total_replies` int(11) NOT NULL DEFAULT '0' COMMENT 'This is to denormalize the reply count of a discussion.',
  `lock` tinyint(3) NOT NULL DEFAULT '0' COMMENT 'Determines if this discussion is locked',
  `params` text NOT NULL COMMENT 'Stores additional raw parameters for the discussion that doesn''t need to be indexed',
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `uid_2` (`uid`,`type`),
  KEY `id` (`id`,`parent_id`),
  KEY `id_2` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
