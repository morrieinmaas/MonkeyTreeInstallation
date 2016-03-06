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

class FilesViewProjects extends SocialAppsView
{
    public function display($projectId = null, $docType = null)
    {
        // Load up the project
        $project = FD::project($projectId);

        // Only allow project members access here.
        if (!$project->getGuest()->isGuest()) {
            return $this->redirect($project->getPermalink(false));
        }

        // Load up the explorer library.
        $explorer = FD::explorer($project->id, SOCIAL_TYPE_PROJECT);

        // Get total number of files that are already uploaded in the project
        $model = FD::model('Files');
        $total = (int) $model->getTotalFiles($project->id, SOCIAL_TYPE_PROJECT);

        // Get the access object
        $access = $project->getAccess();

        // Determines if the project exceeded their limits
        $allowUpload = $access->get('files.max') == 0 || $total < $access->get('files.max') ? true : false;
        $uploadLimit = $access->get('files.maxsize');

        $this->set('uploadLimit', $uploadLimit);
        $this->set('allowUpload', $allowUpload);
        $this->set('explorer', $explorer);
        $this->set('project', $project);

        echo parent::display('projects/default');
    }
}
