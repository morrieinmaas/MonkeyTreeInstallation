<?php
/**
* @package      EasySocial
* @copyright    Copyright (C) 2010 - 2015 Stack Ideas Sdn Bhd. All rights reserved.
* @license      GNU/GPL, see LICENSE.php
* EasySocial is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined('_JEXEC') or die('Unauthorized Access');

FD::import('site:/views/views');

class EasySocialViewProjects extends EasySocialSiteView
{
    /**
     * Renders the RSS feed for project page
     *
     * @since   4.0
     * @access  public
     * @param   string
     * @return  
     */
    public function display()
    {
        // Get the project id
        $id = $this->input->get('id', 0, 'int');

        // Load up the project
        $project = FD::project($id);

        // Ensure that the project really exists
        if (empty($project) || empty($project->id)) {
            return JError::raiseError(404, JText::_('COM_EASYSOCIAL_PROJECTS_INVALID_PROJECT_ID'));
        }

        // Ensure that the project is published
        if (!$project->isPublished()) {
            return JError::raiseError(404, JText::_('COM_EASYSOCIAL_PROJECTS_PROJECT_UNAVAILABLE'));
        }

        // Determines if the current user is a guest of this project
        $guest = $project->getGuest($this->my->id);

        // Support for group project
        // If user is not a group member, then redirect to group page
        if ($project->isGroupProject()) {

            $group = ES::group($project->getMeta('group_id'));

            if (!$this->my->isSiteAdmin() && !$project->isOpen() && !$group->isMember()) {
                return JError::raiseError(404, JText::_('COM_EASYSOCIAL_GROUPS_PROJECTS_NO_PERMISSION_TO_VIEW_PROJECT'));
            }
        } else {

            if (!$this->my->isSiteAdmin() && $project->isInviteOnly() && !$guest->isParticipant()) {
                return JError::raiseError(404, JText::_('COM_EASYSOCIAL_PROJECTS_NO_ACCESS_TO_PROJECT'));
            }
        }

        // Check if the current logged in user blocked by the project creator or not.
        if ($this->my->id != $project->creator_uid && $this->my->isBlockedBy($project->creator_uid)) {
            return JError::raiseError(404, JText::_('COM_EASYSOCIAL_PROJECTS_PROJECT_UNAVAILABLE'));
        }

        // Set the title of the feed
        $this->page->title($project->getName());

        // Get the stream library
        $stream = ES::stream();
        $options = array('clusterId' => $project->id, 'clusterType' => $project->cluster_type, 'nosticky' => true);
        $stream->get($options);

        $items = $stream->data;

        if (!$items) {
            return;
        }

        foreach ($items as $item) {
            $feed = new JFeedItem();

            // Cleanse the title
            $feed->title = strip_tags($item->title);

            $content = $item->content . $item->preview;
            $feed->description = $content;

            // Permalink should only be generated for items with a full content
            $feed->link = $item->getPermalink(true);
            $feed->date = $item->created->toSql();
            $feed->category = $item->context;

            // author details
            $author = $item->getActor();
            $feed->author = $author->getName();
            $feed->authorEmail = $this->getRssEmail($author);

            $this->doc->addItem($feed);
        }
    }
}
