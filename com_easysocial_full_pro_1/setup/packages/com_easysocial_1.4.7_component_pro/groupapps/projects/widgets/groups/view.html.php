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

class ProjectsWidgetsGroups extends SocialAppsWidgets
{
    public function sidebarBottom($groupId)
    {
        $params = $this->getParams();

        if (!$params->get('widget', true)) {
            return;
        }

        $group = FD::group($groupId);


        if (!$group->getAccess()->get('projects.groupproject', true)) {
            return;
        }

        $my = FD::user();

        $days = $params->get('widget_days', 14);
        $total = $params->get('widget_total', 5);

        $date = FD::date();

        $now = $date->toSql();

        $future = FD::date($date->toUnix() + ($days * 24*60*60))->toSql();

        $options = array();

        $options['start-after'] = $now;

        $options['start-before'] = $future;

        $options['limit'] = $total;

        $options['state'] = SOCIAL_STATE_PUBLISHED;

        $options['ordering'] = 'start';

        $options['group_id'] = $groupId;

        $projects = FD::model('Projects')->getProjects($options);

        if (empty($projects)) {
            return;
        }

        $theme = FD::themes();
        $theme->set('projects', $projects);
        $theme->set('app', $this->app);

        echo $theme->output('themes:/apps/user/projects/widgets/dashboard/upcoming');
    }

    public function groupAdminStart($group)
    {
        $my = FD::user();
        $config = FD::config();

        if (!$config->get('projects.enabled') || !$my->getAccess()->get('projects.create')) {
            return;
        }

        if (!$group->canCreateProject() || !$group->getCategory()->getAcl()->get('projects.groupproject')) {
            return;
        }

        $theme = FD::themes();
        $theme->set('group', $group);
        $theme->set('app', $this->app);

        echo $theme->output('themes:/apps/group/projects/widgets/widget.menu');
    }
}
