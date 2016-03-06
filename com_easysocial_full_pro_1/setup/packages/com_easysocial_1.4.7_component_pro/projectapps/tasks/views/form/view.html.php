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

class TasksViewForm extends SocialAppsView
{
    /**
     * Displays the application output in the canvas.
     *
     * @since   1.3
     * @access  public
     * @param   integer $uid    The project id.
     */
    public function display($uid = null, $docType = null)
    {
        $project = FD::project($uid);

        // Check if the viewer is allowed here.
        if (!$project->canViewItem()) {
            return $this->redirect($project->getPermalink(false));
        }

        // Get app params
        $params = $this->app->getParams();

        // Load the milestone
        $id = JRequest::getInt('milestoneId');
        $milestone = FD::table('Milestone');
        $milestone->load($id);

        if (!empty($milestone->id)) {
            FD::page()->title(JText::_('APP_PROJECT_TASKS_TITLE_EDITING_MILESTONE'));
        } else {
            FD::page()->title(JText::_('APP_PROJECT_TASKS_TITLE_CREATE_MILESTONE'));
        }

        // Get a list of members from the group
        $members = FD::model('Projects')->getMembers($project->id);

        $this->set('members', $members);
        $this->set('milestone', $milestone);
        $this->set('params', $params);
        $this->set('project', $project);

        echo parent::display('views/form');
    }

}
