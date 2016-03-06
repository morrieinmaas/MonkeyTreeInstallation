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

class EasySocialModProjectsHelper
{
    public static function getProjects(&$params)
    {
        $model  = FD::model('Projects');

        // Determine filter type
        $filter = $params->get('filter');

        // Determine the ordering of the projects
        $ordering = $params->get('ordering', 'start');

        // Default options
        $options = array();

        // Limit the number of projects based on the params
        $options['limit'] = $params->get('display_limit', 5);
        $options['ordering'] = $ordering;
        $options['state'] = SOCIAL_STATE_PUBLISHED;
        $options['type'] = array(SOCIAL_PROJECT_TYPE_PUBLIC, SOCIAL_PROJECT_TYPE_PRIVATE);
        $options['upcoming'] = true;
        $options['ongoing'] = true;
        
        $inclusion = trim($params->get('project_inclusion'));

        if ($inclusion) {
            $options['inclusion'] = explode(',', $inclusion);
        } 

        $projects = array();

        if ($filter == 0) {
            $projects = $model->getProjects($options);
        }

        if ($filter == 1) {
            $category = trim($params->get('category'));

            if (empty($category)) {
                return array();
            }

            // Since category id's are stored as ID:alias, we only want the id portion
            $category = explode(':', $category);

            $options['category'] = $category[0];

            $projects = $model->getProjects($options);
        }

        // Featured modules only
        if ($filter == 2) {
            $options['featured'] = true;

            $projects = $model->getProjects($options);
        }

        // Retrieve projects participated by the current logged in user
        if ($filter == 3) {
            $my = FD::user();

            $options['type'] = 'user';
            $options['guestuid'] = $my->id;
            $projects = $model->getProjects($options);
        }

        return $projects;
    }
}
