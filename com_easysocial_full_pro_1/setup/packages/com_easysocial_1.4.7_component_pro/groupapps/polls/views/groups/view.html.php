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

/**
 * Groups view for tasks
 *
 * @since	1.2
 * @access	public
 */
class PollsViewGroups extends SocialAppsView
{
	/**
	 * Displays the application output in the canvas.
	 *
	 * @since	1.2
	 * @access	public
	 * @param	int		The user id that is currently being viewed.
	 */
	public function display( $groupId = null , $docType = null )
	{
		$group 	= FD::group( $groupId );

		// Check if the viewer is allowed here.
		if(!$group->canViewItem()) {
			return $this->redirect( $group->getPermalink( false ) );
		}

		// Get app params
		$params = $this->app->getParams();
		
		$options = array('cluster_id' => $groupId);

		$model = FD::model( 'Polls' );
		$polls	= $model->getPolls($options);

		$pollLib = FD::get('Polls');

		foreach ($polls as $poll) {	
			// Load the author
			$author = FD::user($poll->created_by);
			$poll->author = $author;

			$poll->content = $pollLib->getDisplay($poll->id);
		}
	
		$this->set( 'polls', $polls );
		$this->set( 'params', $params );
		$this->set( 'group', $group );

		echo parent::display( 'views/default' );
	}

}
