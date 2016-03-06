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

class NewsControllerNews extends SocialAppsController
{
    /**
     * Processes deletion
     *
     * @since   1.2
     * @access  public
     */
    public function delete()
    {
        // Check for request forgeriess
        FD::checkToken();

        // Ensure that the user is logged in.
        FD::requireLogin();

        $ajax = FD::ajax();

        $id = JRequest::getInt('id');
        $projectId = JRequest::getInt('ProjectId');
        $project = FD::project($projectId);

        if (!$project->isAdmin()) {
            return $this->redirect($project->getPermalink(false));
        }

        // Load the news
        $news = FD::table('ProjectNews');
        $news->load($id);

        if (!$project->isAdmin()) {
            return $this->redirect($project->getPermalink(false));
        }

        $state = $news->delete();

        // @points: projects.news.delete
        // Deduct points from the news creator when the news is deleted.
        $points = FD::points();
        $points->assign('projects.news.delete', 'com_easysocial', $news->created_by);

        $message = $state ? JText::_('APP_PROJECT_NEWS_DELETED_SUCCESS') : JText::_('APP_PROJECT_NEWS_DELETED_FAILED');
        FD::info()->set($message, SOCIAL_MSG_SUCCESS);

        $this->redirect($project->getPermalink(false));
    }

    /**
     * Triggers the empty content error
     *
     * @since   1.2
     * @access  public
     */
    public function emptyContent()
    {
        // Check for request forgeriess
        FD::checkToken();

        // Ensure that the user is logged in.
        FD::requireLogin();

        $ajax = FD::ajax();

        $theme = FD::themes();
        $output = $theme->output('apps/project/news/canvas/dialog.empty');

        return $ajax->resolve($output);
    }

    /**
     * Displays confirmation dialog to delete a news
     *
     * @since    1.2
     * @access    public
     */
    public function confirmDelete()
    {
        // Check for request forgeriess
        FD::checkToken();

        // Ensure that the user is logged in.
        FD::requireLogin();

        $ajax = FD::ajax();

        $id = JRequest::getInt('id');
        $projectId = JRequest::getInt('projectId');
        $project = FD::project($projectId);

        if (!$project->isAdmin()) {
            return $ajax->reject();
        }

        $theme = FD::themes();
        $theme->set('project', $project);
        $theme->set('appId', $this->getApp()->id);
        $theme->set('id', $id);
        $output = $theme->output('apps/project/news/canvas/dialog.delete');

        return $ajax->resolve($output);
    }

    /**
     * Retrieves the new article form
     *
     * @since    1.2
     * @access    public
     * @return
     */
    public function save()
    {
        // Check for request forgeriess
        FD::checkToken();

        // Ensure that the user is logged in.
        FD::requireLogin();

        // Determines if this is an edited news.
        $id = $this->input->get('newsId', 0, 'int');

        // Load up the news obj
        $news = FD::table('ProjectNews');
        $news->load($id);

        // Determine the message to display
        $message = !$news->id ? JText::_('APP_PROJECT_NEWS_CREATED_SUCCESSFULLY') : JText::_('APP_PROJECT_NEWS_UPDATED_SUCCESSFULLY');

        // Load the project object
        $project = FD::project($this->input->get('cluster_id', 0, 'int'));

        if (!$project) {
            return $this->redirect($project->getPermalink(false));
        }

        // Get the app id
        $app = $this->getApp();

        if (!$project->isAdmin() && !FD::user()->isSiteAdmin()) {
            $url = $project->getPermalink(false);
            return $this->redirect($url);
        }

        $options = array();
        $options['title'] = $this->input->get('title', '', 'default');
        $options['content'] = $this->input->get('news_content', '', 'raw');
        $options['comments'] = $this->input->get('comments', true, 'bool');
        $options['state'] = SOCIAL_STATE_PUBLISHED;

        // Only bind this if it's a new item
        if (!$news->id) {
            $options['cluster_id'] = $project->id;
            $options['created_by'] = $this->my->id;
            $options['hits'] = 0;
        }

        // Bind the data
        $news->bind($options);

        // Check if there are any errors
        if (!$news->check()) {
            FD::info()->set($news->getError(), SOCIAL_MSG_ERROR);

            $url = FRoute::apps(array('layout' => 'canvas', 'customView' => 'form', 'uid' => $project->getAlias(), 'type' => SOCIAL_TYPE_PROJECT, 'id' => $app->getAlias()), false);

            return $this->redirect($url);
        }

        // If everything is okay, bind the data.
        $news->store();

        // If it is a new item, we want to run some other stuffs here.
        if (!$id) {
            // @points: projects.news.create
            // Add points to the user that updated the project
            $points = FD::points();
            $points->assign('projects.news.create', 'com_easysocial', $this->my->id);
        }

        // Redirect to the appropriate page now
        $url = FRoute::apps(array('layout' => 'canvas', 'customView' => 'item', 'uid' => $project->getAlias(), 'type' => SOCIAL_TYPE_PROJECT, 'id' => $app->getAlias(), 'newsId' => $news->id), false);

        $app = $this->getApp();
        $permalink = $app->getPermalink('canvas', array('customView' => 'item', 'projectId' => $project->id, 'newsId' => $news->id));

        // Notify users about the news.
        $options = array('userId' => $this->my->id, 'permalink' => $permalink,'newsId' => $news->id, 'newsTitle' => $news->title, 'newsContent' => strip_tags($news->content));

        $project->notifyMembers('news.create', $options);

        FD::info()->set($message, SOCIAL_MSG_SUCCESS);

        // Perform a redirection
        $this->redirect($url);
    }

}
