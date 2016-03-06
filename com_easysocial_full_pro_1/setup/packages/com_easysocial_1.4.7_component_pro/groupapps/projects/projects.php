<?php
/**
* @package      EasySocial
* @copyright    Copyright (C) 2010 - 2014 Stack Ideas Sdn Bhd. All rights reserved.
* @license      GNU/GPL, see LICENSE.php
* EasySocial is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined('_JEXEC') or die('Unauthorized Access');

FD::import('admin:/includes/apps/apps');

class SocialGroupAppProjects extends SocialAppItem
{
    public function getFavIcon()
    {
        $obj = new stdClass();
        $obj->color = '#f06050';
        $obj->icon = 'fa-calendar';
        $obj->label = 'APP_GROUP_PROJECTS_STREAM_TOOLTIP';

        return $obj;
    }

    // Stream is prepared on app/project/projects

    public function onPrepareStoryPanel($story)
    {
        if ($story->clusterType != SOCIAL_TYPE_GROUP) {
            return;
        }

        $params = $this->getParams();

        // Determine if we should attach ourselves here.
        if (!$params->get('story_project', true)) {
            return;
        }

        // If projects is disabled, we shouldn't display this
        if (!FD::config()->get('projects.enabled')) {
            return;
        }

        // Ensure that the group category has access to create projects
        $group = FD::group($story->cluster);
        $access = $group->getAccess();

        if (!$access->get('projects.groupproject')) {
            return;
        }

        // Create plugin object
        $plugin = $story->createPlugin('project', 'panel');

        // Get the theme class
        $theme = FD::themes();

        // Get the available project category
        $categories = FD::model('ProjectCategories')->getCreatableCategories(FD::user()->getProfile()->id);

        $theme->set('categories', $categories);

        $plugin->button->html = $theme->output('apps/user/projects/story/panel.button');
        $plugin->content->html = $theme->output('apps/user/projects/story/panel.content');

        $script = FD::get('Script');
        $plugin->script = $script->output('apps:/user/projects/story');

        return $plugin;
    }

    public function onBeforeStorySave(&$template, &$stream, &$content)
    {
        $params = $this->getParams();

        // Determine if we should attach ourselves here.
        if (!$params->get('story_project', true)) {
            return;
        }

        $in = FD::input();

        $title = $in->getString('project_title');
        $description = $in->getString('project_description');
        $categoryid = $in->getInt('project_category');
        $start = $in->getString('project_start');
        $end = $in->getString('project_end');
        $timezone = $in->getString('project_timezone');

        // If no category id, then we don't proceed
        if (empty($categoryid)) {
            return;
        }

        // Perhaps in the future we use FD::model('Project')->createProject() instead.
        // For now just hardcode it here to prproject field triggering and figuring out how to punch data into the respective field data because the form is not rendered through field trigger.

        $my = FD::user();

        $project = FD::project();

        $project->title = $title;

        $project->description = $description;

        // Set a default params for this project first
        $project->params = '{"photo":{"albums":true},"news":true,"discussions":true,"allownotgoingguest":false,"allowmaybe":true,"guestlimit":0}';

        // project type will always follow group type
        $project->type = FD::group($template->cluster_id)->type;
        $project->creator_uid = $my->id;
        $project->creator_type = SOCIAL_TYPE_USER;
        $project->category_id = $categoryid;
        $project->cluster_type = SOCIAL_TYPE_PROJECT;
        $project->alias = FD::model('Projects')->getUniqueAlias($title);
        $project->created = FD::date()->toSql();
        $project->key = md5($project->created . $my->password . uniqid());

        $project->state = SOCIAL_CLUSTER_PENDING;

        if ($my->isSiteAdmin() || !$my->getAccess()->get('projects.moderate')) {
            $project->state = SOCIAL_CLUSTER_PUBLISHED;
        }

        // Trigger apps
        FD::apps()->load(SOCIAL_TYPE_USER);

        $dispatcher  = FD::dispatcher();
        $triggerArgs = array(&$project, &$my, true);

        // @trigger: onProjectBeforeSave
        $dispatcher->trigger(SOCIAL_TYPE_USER, 'onProjectBeforeSave', $triggerArgs);

        $state = $project->save();

        // Notifies admin when a new project is created
        if ($project->state === SOCIAL_CLUSTER_PENDING || !$my->isSiteAdmin()) {
            FD::model('Projects')->notifyAdmins($project);
        }

        // Set the meta for start end timezone
        $meta = $project->meta;
        $meta->cluster_id = $project->id;
        $meta->start = FD::date($start)->toSql();
        $meta->end = FD::date($end)->toSql();
        $meta->timezone = $timezone;

        // Set the group id
        $meta->group_id = $template->cluster_id;

        $meta->store();

        // Recreate the project object
        SocialProject::$instances[$project->id] = null;
        $project = FD::project($project->id);

        // Create a new owner object
        $project->createOwner($my->id);

        // @trigger: onProjectAfterSave
        $triggerArgs = array(&$project, &$my, true);
        $dispatcher->trigger(SOCIAL_TYPE_USER, 'onProjectAfterSave' , $triggerArgs);

        // Due to inconsistency, we don't use SOCIAL_TYPE_PROJECT.
        // Instead we use "projects" because app elements are named with 's', namely users, groups, projects.
        $template->context_type = 'projects';

        $template->context_id = $project->id;
        $template->cluster_access = $project->type;
        $template->cluster_type = $project->cluster_type;
        $template->cluster_id = $project->id;

        $params = array(
            'project' => $project
        );

        $template->setParams(FD::json()->encode($params));
    }

    public function onBeforeGetStream(&$options, $view = '')
    {
        if ($view != 'groups') {
            return;
        }

        $layout = JRequest::getVar('layout', '');
        if ($layout == 'category') {
            // if this is viewing group category page, we ignore the projects stream for groups.
            return;
        }

        // Check if there are any group projects
        $groupProjects = FD::model('Projects')->getProjects(array(
            'group_id' => $options['clusterId'],
            'state' => SOCIAL_STATE_PUBLISHED,
            'idonly' => true
        ));

        if (count($groupProjects) == 0) {
            return;
        }

        // Support in getting project stream as well
        if (!is_array($options['clusterType'])) {
            $options['clusterType'] = array($options['clusterType']);
        }

        if (!in_array(SOCIAL_TYPE_PROJECT, $options['clusterType'])) {
            $options['clusterType'][] = SOCIAL_TYPE_PROJECT;
        }

        if (!is_array($options['clusterId'])) {
            $options['clusterId'] = array($options['clusterId']);
        }

        $options['clusterId'] = array_merge($options['clusterId'], $groupProjects);
    }

    /**
     * Determines if this app should be visible in the group page
     *
     * @since   5.0
     * @access  public
     * @param   string
     * @return
     */
    public function appListing($view, $groupId, $type)
    {
        $group = FD::group($groupId);

        if (!$this->config->get('projects.enabled')) {
            return false;
        }

        return $group->getAccess()->get('projects.groupproject', true);
    }
}
