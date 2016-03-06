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
 * Widgets for group
 *
 * @since    1.0
 * @access    public
 */
class NewsWidgetsProjects extends SocialAppsWidgets
{
    /**
     * Display admin actions for the project
     *
     * @since   1.2
     * @access  public
     * @param   string
     * @return
     */
    public function projectAdminStart($project)
    {
        if ($this->app->state == SOCIAL_STATE_UNPUBLISHED) {
            return;
        }

        if (!$project->getParams()->get('news', true)) {
            return;
        }

        $theme = FD::themes();
        $theme->set('app', $this->app);
        $theme->set('project', $project);

        echo $theme->output('themes:/apps/project/news/widgets/widget.menu');
    }

    /**
     * Display user photos on the side bar
     *
     * @since    1.0
     * @access    public
     * @param    string
     * @return
     */
    public function sidebarBottom($projectId)
    {
        // Set the max length of the item
        $params = $this->app->getParams();
        $enabled = $params->get('widget', true);

        if (!$enabled) {
            return;
        }

        // Get the project
        $project = FD::project($projectId);

        if (!$project->getParams()->get('news', true)) {
            return;
        }

        $options = array('limit' => (int) $params->get('widget_total', 5));

        $model = FD::model('Projects');
        $items = $model->getNews($project->id, $options);

        $theme = FD::themes();
        $theme->set('project', $project);
        $theme->set('app', $this->app);
        $theme->set('items', $items);

        echo $theme->output('themes:/apps/project/news/widgets/widget.news');
    }
}
