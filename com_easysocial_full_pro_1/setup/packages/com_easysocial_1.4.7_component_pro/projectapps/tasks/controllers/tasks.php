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

class TasksControllerTasks extends SocialAppsController
{
    /**
     * Displays delete confirmation dialog
     *
     * @since    1.2
     * @access    public
     */
    public function delete()
    {
        // Check for request forgeries.
        FD::checkToken();

        // Ensure that the user is logged in.
        FD::requireLogin();

        $ajax = FD::ajax();

        // Get the project
        $projectId = JRequest::getInt('projectId', 0);
        $project = FD::project($projectId);

        $my = FD::user();

        // Check if the user is allowed to create a discussion
        if (!$project->getGuest()->isGuest() && !$my->isSiteAdmin()) {
            return $ajax->reject();
        }

        $id = JRequest::getInt('id');
        $task = FD::table('Task');
        $task->load($id);

        if (!$id || !$task->id || $task->uid != $project->id) {
            return $ajax->reject();
        }

        $task->delete();

        // @points: projects.task.delete
        $points = FD::points();
        $points->assign('projects.task.delete', 'com_easysocial', $my->id);

        return $ajax->resolve();
    }

    /**
     * Displays delete confirmation dialog
     *
     * @since    1.2
     * @access    public
     */
    public function confirmDelete()
    {
        // Check for request forgeries.
        FD::checkToken();

        // Ensure that the user is logged in.
        FD::requireLogin();

        $ajax = FD::ajax();

        // Get the project
        $projectId = JRequest::getInt('projectId', 0);
        $project = FD::project($projectId);

        $my = FD::user();

        // Check if the user is allowed to create a discussion
        if (!$project->getGuest()->isGuest() && !$my->isSiteAdmin()) {
            return $ajax->reject();
        }

        $theme = FD::themes();
        $contents = $theme->output('apps/project/tasks/views/dialog.delete.task');

        return $ajax->resolve($contents);
    }

    /**
     * Allows caller to resolve a task
     *
     * @since    1.2
     * @access    public
     */
    public function resolve()
    {
        // Check for request forgeries
        FD::checkToken();

        // Only allow logged in users
        FD::requireLogin();

        // Load up ajax library
        $ajax = FD::ajax();

        // Get the current project and logged in user
        $projectId = JRequest::getInt('projectId');
        $project = FD::project($projectId);
        $my = FD::user();

        if (!$project || !$projectId) {
            return $ajax->reject('failed');
        }

        // Test if the current user is a member of this project.
        if (!$project->getGuest()->isGuest() && !$my->isSiteAdmin()) {
            return $ajax->reject();
        }

        // Determines if this is a new record
        $id = JRequest::getInt('id');
        $task = FD::table('Task');
        $task->load($id);

        $task->resolve();

        // @points: projects.task.resolve
        $points = FD::points();
        $points->assign('projects.task.resolve', 'com_easysocial', $my->id);

        // Get the app
        $app = $this->getApp();

        // Get the application params
        $params = $app->getParams();

        if ($params->get('notify_complete_task', true)) {
            $milestone = FD::table('Milestone');
            $milestone->load($task->milestone_id);

            // Get the redirection url
            $url = FRoute::apps(array('layout' => 'canvas', 'customView' => 'item', 'uid' => $project->getAlias(), 'type' => SOCIAL_TYPE_PROJECT, 'id' => $app->getAlias(), 'milestoneId' => $milestone->id), false);

            $project->notifyMembers('task.completed', array('userId' => $my->id, 'id' => $task->id, 'title' => $task->title, 'content' => $milestone->description, 'permalink' => $url, 'milestone' => $milestone->title));
        }

        return $ajax->resolve();
    }

    /**
     * Allows caller to unresolve a task
     *
     * @since    1.2
     * @access    public
     */
    public function unresolve()
    {
        // Check for request forgeries
        FD::checkToken();

        // Only allow logged in users
        FD::requireLogin();

        // Load up ajax library
        $ajax = FD::ajax();

        // Get the current project and logged in user
        $projectId = JRequest::getInt('projectId');
        $project = FD::project($projectId);
        $my = FD::user();

        if (!$project || !$projectId) {
            return $ajax->reject();
        }

        // Test if the current user is a member of this project.
        if (!$project->getGuest()->isGuest() && !$my->isSiteAdmin()) {
            return $ajax->reject();
        }

        // Determines if this is a new record
        $id = JRequest::getInt('id');
        $task = FD::table('Task');
        $task->load($id);

        $task->state = 1;
        $task->store();

        // @points: projects.task.unresolve
        $points = FD::points();
        $points->assign('projects.task.unresolve', 'com_easysocial', $my->id);

        return $ajax->resolve();
    }


    /**
     * Allows caller to create a new task given the milestone id and the project id.
     *
     * @since    1.2
     * @access    public
     */
    public function save()
    {
        // Check for request forgeries
        FD::checkToken();

        // Only allow logged in users
        FD::requireLogin();

        // Load up ajax library
        $ajax = FD::ajax();

        // Get the current project and logged in user
        $projectId = JRequest::getInt('projectId');
        $project = FD::project($projectId);
        $my = FD::user();

        if (!$project || !$projectId) {
            return $ajax->reject();
        }

        // Test if the current user is a member of this project.
        if (!$project->getGuest()->isGuest()) {
            return $ajax->reject();
        }

        // Determines if this is a new record
        $id = JRequest::getInt('id');
        $task = FD::table('Task');
        $task->load($id);

        $milestoneId = JRequest::getInt('milestoneId');
        $title = JRequest::getVar('title');
        $due = JRequest::getVar('due');
        $assignee = JRequest::getVar('assignee');

        // Save task
        $task->milestone_id = $milestoneId;
        $task->uid = $project->id;
        $task->type = SOCIAL_TYPE_PROJECT;
        $task->title = $title;
        $task->due = $due;
        $task->user_id = !$assignee ? $my->id : $assignee;
        $task->state = SOCIAL_TASK_UNRESOLVED;

        if (!$task->title) {
            return $ajax->reject();
        }

        $task->store();

        if (!$id) {
            // @points: projects.task.create
            $points = FD::points();
            $points->assign('projects.task.create', 'com_easysocial', $my->id);

            // Get the app
            $app = $this->getApp();

            // Get the application params
            $params = $app->getParams();

            // Send notification
            if ($params->get('notify_new_task', true)) {
                $milestone = FD::table('Milestone');
                $milestone->load($milestoneId);

                // Get the redirection url
                $url = FRoute::apps(array('layout' => 'canvas', 'customView' => 'item', 'uid' => $project->getAlias(), 'type' => SOCIAL_TYPE_PROJECT, 'id' => $app->getAlias(), 'milestoneId' => $milestoneId), false);

                $project->notifyMembers('task.create', array('userId' => $my->id, 'id' => $task->id, 'title' => $task->title, 'content' => $milestone->description, 'permalink' => $url, 'milestone' => $milestone->title));
            }

            // Create stream
            $task->createStream('createTask');
        }

        // Get the contents
        $theme = FD::themes();
        $theme->set('project', $project);
        $theme->set('task', $task);
        $theme->set('user', $my);

        $output = $theme->output('apps/project/tasks/views/item.task');
        return $ajax->resolve($output);
    }
}
