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

class GuestsControllerProjects extends SocialAppsController
{
    /**
     * Filters the output of members
     *
     * @since    1.3
     * @access    public
     * @return
     */
    public function filterGuests()
    {
        // Check for request forgeriess
        FD::checkToken();

        // Ensure that the user is logged in.
        FD::requireLogin();

        // Get the project object
        $id = $this->input->get('id', 0, 'int');
        $project = FD::project($id);

        if (!$project || !$id) {
            return $this->ajax->reject();
        }

        // Check whether the viewer can really view the contents
        if (!$project->canViewItem()) {
            return $this->ajax->reject();
        }

        // Get the current filter
        $filter  = $this->input->get('filter', '', 'word');
        $options = array();

        if ($filter == 'admin') {
            $options['admin'] = true;
        }

        if ($filter == 'going') {
            $options['state'] = SOCIAL_PROJECT_GUEST_GOING;
        }

        if ($filter == 'maybe') {
            $options['state'] = SOCIAL_PROJECT_GUEST_MAYBE;
        }

        if ($filter == 'notgoing') {
            $options['state'] = SOCIAL_PROJECT_GUEST_NOT_GOING;
        }

        if ($filter == 'pending') {
            $options['state'] = SOCIAL_PROJECT_GUEST_PENDING;
        }

        $model = FD::model('Projects');
        $guests  = $model->getGuests($project->id, $options);
        $pagination = $model->getPagination();

        $myGuest = $project->getGuest();

        // Load the contents
        $theme = FD::themes();

        $theme->set('pagination', $pagination);
        $theme->set('project', $project);
        $theme->set('guests', $guests);

        $theme->set('myGuest', $myGuest);

        $contents    = $theme->output('apps/project/guests/projects/default.list');

        return $this->ajax->resolve($contents, count($guests));
    }

}
