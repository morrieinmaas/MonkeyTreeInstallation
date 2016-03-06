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

class ProjectsViewGroups extends SocialAppsView
{
    public function display($groupId = null, $docType = null)
    {
        $group = FD::group($groupId);

        // Check access
        if (!$group->getAccess()->get('projects.groupproject', true)) {
            return $this->redirect($group->getPermalink(false));
        }

        // Check if the viewer is allowed here.
        if (!$group->canViewItem()) {
            return $this->redirect($group->getPermalink(false));
        }

        $params = $this->app->getParams();

        // Retrieve project's model
        $model = FD::model('Projects');

        // Get the start date
        $start = $this->input->get('start', 0, 'int');

        // Should we include past projects?
        $includePast = $this->input->get('includePast', 0, 'int');

        // Ordering of the projects
        $ordering = $this->input->get('ordering', 'start', 'string');

        // Get featured projects
        $featuredOptions = array(
            'state' => SOCIAL_STATE_PUBLISHED,
            'featured' => true,
            'ordering' => 'start',
            'limit' => 5,
            'limitstart' => $start,
            'group_id' => $group->id,
            'type' => 'all',
            'ordering' => $ordering
        );

        $featuredProjects = $model->getProjects($featuredOptions);

        // Default options
        $options = array(
            'state' => SOCIAL_STATE_PUBLISHED,
            'featured' => false,
            'ordering' => 'start',
            'limit' => 5,
            'limitstart' => $start,
            'group_id' => $group->id,
            'type' => 'all',
            'ordering' => $ordering
        );

        if (empty($includePast)) {
            $options['ongoing'] = true;
            $options['upcoming'] = true;
        }

        // Get the projects
        $projects = $model->getProjects($options);

        $pagination = $model->getPagination();

        $pagination->setVar('option', 'com_easysocial');
        $pagination->setVar('view', 'groups');
        $pagination->setVar('layout', 'item');
        $pagination->setVar('id', $group->getAlias());
        $pagination->setVar('appId', $this->app->getAlias());

        $hrefs = array();
        $routeOptions = array(
            'layout' => 'item',
            'id' => $group->getAlias(),
            'appId' => $this->app->getAlias()
        );

        // We use start as key because order is always start by default, and it is the page default link
        $hrefs = array(
            'start' => array(
                'nopast' => FRoute::groups($routeOptions),
                'past' => FRoute::groups(array_merge($routeOptions, array('includePast' => 1)))
            ),
            'created' => array(
                'nopast' => FRoute::groups(array_merge($routeOptions, array('ordering' => 'created'))),
                'past' => FRoute::groups(array_merge($routeOptions, array('ordering' => 'created', 'includePast' => 1)))
            )
        );

        $this->set('hrefs', $hrefs);

        // Parameters to work with site/project/default.list
        $this->set('featuredProjects', $featuredProjects);
        $this->set('projects', $projects);
        $this->set('pagination', $pagination);
        $this->set('group', $group);
        $this->set('filter', 'all');
        $this->set('delayed', false);
        $this->set('showSorting', true);
        $this->set('showDistanceSorting', false);
        $this->set('showPastFilter', true);
        $this->set('showDistance', false);
        $this->set('hasLocation', false);
        $this->set('includePast', false);
        $this->set('ordering', $ordering);
        $this->set('includePast', $includePast);
        $this->set('activeCategory', false);

        $this->set('isGroupOwner', true);

        $guestApp = FD::table('App');
        $guestApp->load(array('type' => SOCIAL_TYPE_APPS, 'group' => SOCIAL_TYPE_PROJECT, 'element' => 'guests'));
        $this->set('guestApp', $guestApp);

        echo parent::display('groups/default');
    }
}
