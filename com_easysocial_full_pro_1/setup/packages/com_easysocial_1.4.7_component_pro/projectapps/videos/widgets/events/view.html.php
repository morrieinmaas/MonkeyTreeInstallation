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

class VideosWidgetsProjects extends SocialAppsWidgets
{
	/**
	 * Determines if the videos are enabled for projects
	 *
	 * @since	1.4
	 * @access	public
	 * @param	string
	 * @return	
	 */
	public function enabled(SocialProject $project)
	{
		$params = $project->getParams();

		if (!$params->get('videos', true)) {
			return false;
		}

		return true;
	}

	/**
	 * Display admin actions for the project
	 *
	 * @since	1.4
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function projectAdminStart(SocialProject $project)
	{
		if (!$this->enabled($project)) {
			return;
		}

		$video = ES::video($project->id, SOCIAL_TYPE_PROJECT);

		$theme = ES::themes();
		$theme->set('video', $video);
		$theme->set('app', $this->app);

		echo $theme->output('themes:/site/videos/widgets/projects/menu');
	}

	/**
	 * Display user photos on the side bar
	 *
	 * @since	1.2
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function sidebarBottom($projectId, $project)
	{
		if (!$this->enabled($project)) {
			return;
		}

		// Get recent albums
		$output = $this->getVideos($project);

		echo $output;
	}


	/**
	 * Display the list of photo albums
	 *
	 * @since	1.2
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function getVideos(SocialProject &$project)
	{
		if (!$this->enabled($project)) {
			return;
		}

		$params = $this->getParams();

		$model = ES::model('Videos');

		// Determines the total number of albums to retrieve
		$limit = $params->get('limit', 10);

		$options = array();
		$options['uid'] = $project->id;
		$options['type'] = SOCIAL_TYPE_PROJECT;

		// Get the videos for the group
		$videos = $model->getVideos($options);

		$totalVideos = $model->getTotalVideos($options);

		$theme = ES::themes();
		$theme->set('totalVideos', $totalVideos);
		$theme->set('videos', $videos);
		$theme->set('project', $project);

		return $theme->output('themes:/site/videos/widgets/projects/recent');
	}
}
