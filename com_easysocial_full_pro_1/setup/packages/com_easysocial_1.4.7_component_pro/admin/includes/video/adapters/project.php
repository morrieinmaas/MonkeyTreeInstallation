<?php
/**
* @package		EasySocial
* @copyright	Copyright (C) 2010 - 2015 Stack Ideas Sdn Bhd. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* EasySocial is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined('_JEXEC') or die('Unauthorized Access');

require_once(__DIR__ . '/abstract.php');

class SocialVideoAdapterProject extends SocialVideoAdapter
{
	protected $project = null;
	protected $projectAccess = null;

	public function __construct($uid, $type, SocialTableVideo $table)
	{
		$this->project = ES::project($uid);
		$this->projectAccess = $this->project->getAccess();

		parent::__construct($uid, $type, $table);
	}

	/**
	 * Retrieves the group alias
	 *
	 * @since	1.4
	 * @access	public
	 * @param	string
	 * @return	
	 */
	public function getAlias()
	{
		return $this->project->getAlias();
	}
	
	/**
	 * Renders the group mini header
	 *
	 * @since	1.4
	 * @access	public
	 * @param	string
	 * @return	
	 */
	public function getMiniHeader()
	{
		$layout = $this->input->get('layout');

		return ES::template()->html('html.miniheader', $this->project);
	}

	/**
	 * Determines if the current user can edit this video
	 *
	 * @since	1.4
	 * @access	public
	 * @param	string
	 * @return	
	 */
	public function isEditable()
	{
		// If this is a new video, they should be able to edit
		if (!$this->table->id && $this->allowCreation()) {
			return true;
		}

		// Group owners and admins are allowed
		if ($this->project->isAdmin() || $this->project->isOwner()) {
			return true;
		}
		
		// Edited video
		if ($this->table->id && $this->table->user_id == $this->my->id) {
			return true;
		}

		// If user is a site admin they should be allowed to edit videos
		if ($this->my->isSiteAdmin()) {
			return true;
		}
		
		return false;
	}

	/**
	 * Checks if the provided user is allowed to view this video
	 *
	 * @since	1.4
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function isViewable($userId = null)
	{
		// Open groups allows anyone to view the contents from the group
		if ($this->project->isOpen()) {
			return true;
		}

		// Allow group owner
		if (($this->project->isClosed() || $this->project->isInviteOnly()) && $this->project->isMember()) {
			return true;
		}

		// Allow group owner
		if ($this->project->isAdmin() || $this->project->isOwner()) {
			return true;
		}

		// Allow site admin
		if ($this->my->isSiteAdmin()) {
			return true;
		}

		return false;
	}

	/**
	 * Retrieves the upload size limit 
	 *
	 * @since	1.4
	 * @access	public
	 * @param	string
	 * @return	
	 */
	public function getUploadLimit()
	{
		$limit = (int) $this->projectAccess->get('videos.maxsize');

		// If this is set to unlimited, the limit should be based on the php's upload limit
		if ($limit === 0) {
			$limit = ini_get('upload_max_filesize');

			return $limit;
		}

		return $limit . 'M';
	}

	/**
	 * Determines if the user is allowed to create videos
	 *
	 * @since	1.4
	 * @access	public
	 * @param	string
	 * @return	
	 */
	public function allowCreation()
	{
		if (!$this->projectAccess->allowed('videos.create')) {
			return false;
		}
		
		if ($this->hasExceededLimit()) {
			return false;
		}

		return true;
	}


	/**
	 * Determines if the user exceeded the limit to upload videos
	 *
	 * @since	1.4
	 * @access	public
	 * @param	string
	 * @return	
	 */
	public function hasExceededLimit()
	{
		static $items = array();

		$total = $this->project->getTotalVideos();
		
        if ($this->projectAccess->exceeded('videos.total', $this->project->getTotalVideos())) {
            return true;
        }

        return false;
	}

	/**
	 * Determines if the user can add a tag for this video
	 *
	 * @since	1.4
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function canAddTag()
	{
		// Super admin can always do anything they want
		if ($this->my->isSiteAdmin()) {
			return true;
		}

		if ($this->project->isAdmin() || $this->project->isOwner()) {
			return true;
		}

		// Allow owner to add tags.
		if ($this->table->user_id == $this->my->id) {
			return true;
		}

		return false;
	}

	/**
	 * Determines if the current user can access the videos section.
	 *
	 * @since	1.4
	 * @access	public
	 * @param	string
	 * @return	
	 */
	public function canAccessVideos()
	{
		if ($this->my->isSiteAdmin()) {
			return true;
		}

		if ($this->project->isMember()) {
			return true;
		}

		if ($this->project->isOpen()) {
			return true;
		}

		return false;
	}
	
	/**
	 * Determines if the user can unfeature the video
	 *
	 * @since	1.4
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function canUnfeature()
	{
		if ($this->table->isUnfeatured()) {
			return false;
		}


		// Group owners and admins are allowed to feature videos in a group
		if ($this->project->isAdmin() || $this->project->isOwner()) {
			return true;
		}

		if ($this->my->isSiteAdmin()) {
			return true;
		}

		return false;
	}

	/**
	 * Determines if the user can feature the video
	 *
	 * @since	1.4
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function canFeature()
	{
		// If this video is featured already, it should never be possible to feature the video again
		if ($this->table->isFeatured()) {
			return false;
		}

		// Group owners and admins are allowed to feature videos in a group
		if ($this->project->isAdmin() || $this->project->isOwner()) {
			return true;
		}

		if ($this->my->isSiteAdmin()) {
			return true;
		}

		return false;
	}

	/**
	 * Determines if the user can delete the video
	 *
	 * @since	1.4
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function canDelete()
	{
		// Allow group admin and owners to delete videos
		if ($this->project->isAdmin() || $this->project->isOwner()) {
			return true;
		}

		if ($this->my->isSiteAdmin()) {
			return true;
		}

		return false;
	}

	/**
	 * Determines if the user can edit the video
	 *
	 * @since	1.4
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function canEdit()
	{
		// If video wasn't created yet, it needs to check against "can create".
		if (!$this->table->id) {
			return $this->allowCreation();
		}

		if ($this->table->user_id == $this->my->id) {
			return true;
		}

		// Allow group admin and owners to edit videos
		if ($this->project->isAdmin() || $this->project->isOwner()) {
			return true;
		}

		if ($this->my->isSiteAdmin()) {
			return true;
		}

		return false;
	}

	/**
	 * Determines if the current user is allowed to process the video
	 *
	 * @since	1.4
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function canProcess()
	{
		if ($this->table->user_id == $this->my->id) {
			return true;
		}

		// Allow group admin and owners to edit videos
		if ($this->project->isAdmin() || $this->project->isOwner()) {
			return true;
		}

		if ($this->my->isSiteAdmin()) {
			return true;
		}

		return false;
	}

	/**
	 * Retrieves the page title for listing
	 *
	 * @since	1.4
	 * @access	public
	 * @param	string
	 * @return	
	 */
	public function getListingPageTitle()
	{
		// Set the title to the user's videos page title
		$title = JText::sprintf('COM_EASYSOCIAL_VIDEOS_PROJECT_PAGE_TITLE', $this->project->getName());

		return $title;
	}

	/**
	 * Retrieves the page title for listing
	 *
	 * @since	1.4
	 * @access	public
	 * @param	string
	 * @return	
	 */
	public function getFeaturedPageTitle()
	{
		$user = ES::user($this->uid);

		// Set the title to the user's videos page title
		$title = JText::sprintf('COM_EASYSOCIAL_VIDEOS_PROJECT_FEATURED_PAGE_TITLE', $this->project->getName());

		return $title;
	}

	/**
	 * Retrieves the page title for listing
	 *
	 * @since	1.4
	 * @access	public
	 * @param	string
	 * @return	
	 */
	public function getCategoryPageTitle(SocialTableVideoCategory $category)
	{
		// Set the title to the user's videos page title
		$title = JText::sprintf('COM_EASYSOCIAL_VIDEOS_PROJECT_CATEGORY_PAGE_TITLE', $this->project->getName(), $category->title);

		return $title;
	}
}
