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

class EasySocialModProjectsMenuHelper
{
    public static function getApps(&$params, $project)
    {
        // Load list of apps for this project
        $appsModel = FD::model('Apps');

        // Retrieve apps
        $apps = $appsModel->getProjectApps($project->id);

        return $apps;
    }

    public static function getPendingMembers(&$params, $project)
    {
        $options = array();
        $options['state'] = SOCIAL_GROUPS_MEMBER_PENDING;

        $model = FD::model('Projects');
        $users = $model->getMembers($project->id, $options);

        return $users;
    }
}
