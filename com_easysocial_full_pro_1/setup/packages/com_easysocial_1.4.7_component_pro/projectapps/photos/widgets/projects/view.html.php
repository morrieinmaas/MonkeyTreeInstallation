<?php
/**
* @package        EasySocial
* @copyright    Copyright (C) 2010 - 2014 Stack Ideas Sdn Bhd. All rights reserved.
* @license        GNU/GPL, see LICENSE.php
* EasySocial is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined('_JEXEC') or die('Unauthorized Access');

/**
 * Displays the project photos in a widget
 *
 * @since    1.3
 * @access   public
 */
class PhotosWidgetsProjects extends SocialAppsWidgets
{
    /**
     * Displays the action for albums
     *
     * @since   1.3
     * @access  public
     * @param   SocialProject $project
     * @return
     */
    public function projectAdminStart($project)
    {
        if ($this->app->state == SOCIAL_STATE_UNPUBLISHED) {
            return;
        }

        $category = $project->getCategory();

        if (!$category->getAcl()->get('photos.enabled', true) || !$project->getParams()->get('photo.albums', true)) {
            return;
        }

        $this->set('project', $project);
        $this->set('app', $this->app);

        echo parent::display('widgets/widget.menu');
    }

    /**
     * Display user photos on the side bar
     *
     * @since   1.2
     * @access  public
     * @return
     */
    public function sidebarBottom($projectId, $project)
    {
        $project = FD::project($projectId);

        $category = $project->getCategory();

        if (!$category->getAcl()->get('photos.enabled', true) || !$project->getParams()->get('photo.albums', true)) {
            return;
        }

        // Get recent albums
        $albumsHTML = $this->getAlbums($project);

        echo $albumsHTML;
    }


    /**
     * Display the list of photo albums
     *
     * @since   1.2
     * @access  public
     * @param   SocialProject The project object
     */
    public function getAlbums(&$project)
    {
        $params = $this->getParams();

        if (!$params->get('widgets_album', true)) {
            return;
        }

        // Get the album model
        $model  = FD::model('Albums');
        $albums = $model->getAlbums($project->id, SOCIAL_TYPE_PROJECT);
        $options = array('core' => false, 'withCovers' => true, 'pagination' => 10);

        // Get the total number of albums
        $total = $model->getTotalAlbums(array('uid' => $project->id, 'type' => SOCIAL_TYPE_PROJECT));

        $this->set('total', $total);
        $this->set('albums', $albums);
        $this->set('project', $project);

        return parent::display('widgets/widget.albums');
    }
}
