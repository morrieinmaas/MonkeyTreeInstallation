<?php
/**
* @package		EasySocial
* @copyright	Copyright (C) 2010 - 2014 Stack Ideas Sdn Bhd. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* EasySocial is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined( '_JEXEC' ) or die( 'Unauthorized Access' );

require_once( dirname( __FILE__ ) . '/abstract.php' );

/**
 * project adapter for albums
 *
 * @since	1.3
 * @access	public
 *
 */
class SocialAlbumsAdapterProject extends SocialAlbumsAdapter
{
	private $project = null;

	public function __construct(SocialAlbums $lib)
	{
		$this->project = FD::project($lib->uid);

		parent::__construct($lib);
	}

	/**
	 * Displays the albums heading for an project
	 *
	 * @since	1.3
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function heading()
	{
		$theme = FD::themes();
		$theme->set('project', $this->project);

		$output = $theme->output('site/albums/header.project');

		return $output;
	}

	public function isValidNode()
	{
		if (!$this->project || !$this->project->id) {
			$this->lib->setError(JText::_('COM_EASYSOCIAL_ALBUMS_PROJECT_INVALID_PROJECT_ID_PROVIDED'));
			return false;
		}

		return true;
	}

	/**
	 * Get the album link for this project album
	 *
	 * @since	1.3
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function getViewAlbumsLink($xhtml = true)
	{
		$url = FRoute::albums(array('uid' => $this->project->getAlias(), 'type' => SOCIAL_TYPE_PROJECT), $xhtml);

		return $url;
	}

	/**
	 * Retrieves the page title
	 *
	 * @since	1.3
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function getPageTitle($layout, $prefix = true)
	{
		if ($layout == 'item') {
			$title = $this->lib->data->get('title');
		}

		if ($layout == 'form') {
			$title 	= JText::_('COM_EASYSOCIAL_PAGE_TITLE_CREATE_ALBUM');
		}

		if ($layout == 'default') {
			$title	= JText::_( 'COM_EASYSOCIAL_PAGE_TITLE_ALBUMS' );
		}

		if ($prefix) {
			$title 	= $this->project->getName() . ' - ' . $title;
		}

		return $title;
	}

	/**
	 * Determines if the current viewer can view the album
	 *
	 * @since	1.3
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function viewable()
	{
		// Site admin should always be able to view
		if ($this->my->isSiteAdmin()) {
			return true;
		}

		// project admin is always allowed to view
		if ($this->project->isAdmin()) {
			return true;
		}

		// If the project is public, it should be viewable
		if ($this->project->isOpen()) {
			return true;
		}

		// project members should be allowed
		if ($this->project->isMember()) {
			return true;
		}

		return false;
	}

	/**
	 * Determines if the current viewer can delete the album
	 *
	 * @since	1.3
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function deleteable()
	{
		// If this is a core album, it should never be allowed to delete
		if ($this->album->isCore()) {
			return false;
		}

		// Super admins are allowed to edit
		if ($this->my->isSiteAdmin()) {
			return true;
		}

		// project admin's are always allowed
		if ($this->project->isAdmin()) {
			return true;
		}

		// Owner of the albums are allowed to edit
		if ($this->album->user_id == $this->my->id) {
			return true;
		}

		return false;
	}

	/**
	 * Determines if the viewer can edit the album
	 *
	 * @since	1.3
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function editable()
	{
		// Perhaps the person is creating a new album
		if (!$this->album->id) {
			return true;
		}

		// Super admins are allowed to edit
		if ($this->my->isSiteAdmin()) {
			return true;
		}

		// Group admin's are always allowed
		if ($this->project->isAdmin()) {
			return true;
		}

		// If user is a member, allow them to edit
		if ($this->project->isMember()) {
			return true;
		}

		// Owner of the albums are allowed to edit
		if ($this->my->id == $this->album->user_id) {
			return true;
		}

		return false;
	}

	/**
	 * Set the current breadcrumbs for the page
	 *
	 * @since	1.3
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function setBreadcrumbs($layout)
	{
		// Set the link to the groups
		FD::page()->breadcrumb($this->project->getName(), $this->project->getPermalink());

		if ($layout == 'form') {
			FD::page()->breadcrumb($this->getPageTitle('default', false));
		}

		if ($layout == 'item') {
			FD::page()->breadcrumb($this->getPageTitle('default', false) , FRoute::albums(array('uid' => $this->project->id , 'type' => SOCIAL_TYPE_GROUP)));
		}

		// Set the albums breadcrumb
		FD::page()->breadcrumb($this->getPageTitle($layout, false));
	}

	public function setPrivacy( $privacy , $customPrivacy )
	{
		// We don't really need to use the privacy library here.
	}

	/**
	 * Determines if the user is allowed to create albums in this project
	 *
	 * @since	1.3
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function canCreateAlbums()
	{
		// If the user is a site admin, they are allowed to
		if ($this->my->isSiteAdmin()) {
			return true;
		}

		// If they are a member of the project, they are allowed to.
		if ($this->project->isMember($this->my->id) && $this->my->getAccess()->get('albums.create') && $this->project->getCategory()->getAcl()->get('photos.enabled', true)) {			
			return true;
		}

		return false;
	}

	/**
	 * Determines if the viewer can upload into the album
	 *
	 * @since	1.3
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function canUpload()
	{
		// Site admins are always allowed
		if ($this->my->isSiteAdmin()) {
			return true;
		}

		// project admins are always allowed
		if ($this->project->isAdmin()) {
			return true;
		}

		// project members are allowed to upload and collaborate in albums
		if ($this->project->isMember()) {
			return true;
		}

		// If the current viewer is the owner of the album
		if ($this->lib->data->user_id == $this->my->id) {
			return true;
		}

		return false;
	}

	public function exceededLimits()
	{
		// @TODO: Check for group limits

		return false;
	}

	public function getExceededHTML()
	{
		$theme = FD::themes();
		$theme->set( 'user', $my );
		$html = $theme->output( 'site/albums/exceeded' );

		return $this->output( $html, $album->data );
	}

	/**
	 * Determines if the user is allowed to set the cover for the album
	 *
	 * @since	1.3
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function canSetCover()
	{
		// Site admin's can do anything they want
		if ($this->my->isSiteAdmin()) {
			return true;
		}

		// Group admins are allowed
		if ($this->project->isAdmin()) {
			return true;
		}

		// If the user is the owner, they are allowed
		if ($this->album->user_id == $this->my->id) {
			return true;
		}

		return false;
	}

	/**
	 * Determines if the album is owned by the current user
	 *
	 * @since	1.3
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function isOwner()
	{
		// Site admins should always be treated as the owner
		if ($this->my->isSiteAdmin()) {
			return true;
		}

		// Group admins should always be treated as the owner
		if ($this->project->isAdmin()) {
			return true;
		}

		// If the user is the creator of the album, they should also be treated as the owner
		if ($this->album->user_id == $this->my->id) {
			return true;
		}

		return false;
	}

	public function allowMediaBrowser()
	{
		// Site admins should always be treated as the owner
		if ($this->my->isSiteAdmin()) {
			return true;
		}

		if ($this->project->isAdmin()) {
			return true;
		}

		return false;
	}

	public function hasPrivacy()
	{
		return false;
	}

	/**
	 * Retrieves the creation link
	 *
	 * @since	1.3
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function getCreateLink()
	{
		$url = FRoute::albums(array('layout' => 'form', 'uid' => $this->project->getAlias(), 'type' => SOCIAL_TYPE_PROJECT));

		return $url;
	}

	/**
	 * Retrieves the upload limit
	 *
	 * @since	1.3
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function getUploadLimit()
	{
		$access = $this->project->getAccess();

		return $access->get('photos.maxsize') . 'M';
	}

	public function isblocked()
	{
		if (FD::user()->id != $this->project->creator_uid) {
			return FD::user()->isBlockedBy($this->project->creator_uid);
		}
		return false;
	}
}
