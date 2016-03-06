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

// We need the router
require_once(JPATH_ROOT . '/components/com_content/helpers/route.php');

class DiscussionsViewProjects extends SocialAppsView
{
    /**
     * Displays the application output in the canvas.
     *
     * @since    1.0
     * @access    public
     * @param    int        The user id that is currently being viewed.
     */
    public function display($projectId = null, $docType = null)
    {
        FD::requireLogin();

        $project = FD::project($projectId);

        // Check if the viewer is allowed here.
        if (!$project->canViewItem()) {
            return $this->redirect($project->getPermalink(false));
        }

        // Get app params
        $params = $this->app->getParams();

        $model = FD::model('Discussions');
        $options = array('limit' => $params->get('total', 10));

        $discussions = $model->getDiscussions($project->id, SOCIAL_TYPE_PROJECT, $options);
        $pagination = $model->getPagination();
        $pagination->setVar('option', 'com_easysocial');
        $pagination->setVar('view', 'projects');
        $pagination->setVar('layout', 'item');
        $pagination->setVar('id', $project->getAlias());
        $pagination->setVar('appId', $this->app->getAlias());

        $this->set('app', $this->app);
        $this->set('params', $params);
        $this->set('pagination', $pagination);
        $this->set('project', $project);
        $this->set('discussions', $discussions);

        echo parent::display('projects/default');
    }

}
