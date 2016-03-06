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
defined( '_JEXEC' ) or die( 'Unauthorized Access' );

/**
 * Attendees widget for project
 *
 * @since    1.3
 * @access   public
 */
class GuestsWidgetsProjects extends SocialAppsWidgets
{
    /**
     * Display users attending this project
     *
     * @since    1.3
     * @access   public
     * @param    string
     * @return
     */
    public function sidebarBottom($projectId)
    {
        // Load up the project object
        $project = FD::project($projectId);

        $params = $this->app->getParams();

        if ($params->get('show_guests', true)) {
            echo $this->getGuests($project);
        }

        if ($params->get('show_online', true)) {
            echo $this->getOnlineUsers($project);
        }

        if ($params->get('show_friends', true)) {
            echo $this->getFriends($project);
        }
    }

    /**
     * Displays the total attendees
     *
     * @since   1.3
     * @access  public
     * @param   string
     * @return
     */
    public function afterCategory($projectId)
    {
        $theme = FD::themes();

        // Get the project object
        $project = FD::project($projectId);

        $permalink  = FRoute::projects(array('layout'=> 'item', 'id' => $project->getAlias(), 'appId' => $this->app->getAlias()));

        $theme->set('miniheader', false);
        $theme->set('permalink', $permalink);
        $theme->set('project', $project);

        echo $theme->output('themes:/apps/project/guests/widgets/widget.header');
    }

    /**
     * Displays the attendees in mini header
     *
     * @since   1.3
     * @access  public
     * @param   string
     * @return
     */
    public function miniProjectStatsEnd($projectId)
    {
        $theme = FD::themes();

        // Get the project object
        $project = FD::project($projectId);

        $permalink  = FRoute::projects(array('layout'=> 'item', 'id' => $project->getAlias(), 'appId' => $this->app->getAlias()));

        $theme->set('miniheader', true);
        $theme->set('permalink', $permalink);
        $theme->set('project', $project);

        echo $theme->output('themes:/apps/project/guests/widgets/widget.header');
    }

    private function getGuests($project)
    {
        $theme = FD::themes();

        $totalGoing = count($project->going);

        $ids = array();
        $goingGuests = array();

        if ($totalGoing > 0) {
            // Guests are already in $project->guests property
            // Going guests are also in $project->going property
            // Use php random to pick the id out from $project->going, then map it back to $project->guests
            $ids = (array) array_rand($project->going, min($totalGoing, 20));

            foreach ($ids as $id) {
                $guest = $project->guests[$project->going[$id]];
                $goingGuests[] = $guest;
            }
        }

        $theme->set('project', $project);
        $theme->set('totalGoing', $totalGoing);
        $theme->set('goingGuests', $goingGuests);

        $params = $project->getParams();

        $allowMaybe = $params->get('allowmaybe', true);

        $theme->set('allowMaybe', $allowMaybe);

        if ($allowMaybe) {
            $totalMaybe = count($project->maybe);

            $theme->set('totalMaybe', $totalMaybe);

            if ($totalMaybe > 0) {
                $ids = (array) array_rand($project->maybe, min($totalMaybe, 20));

                $maybeGuests = array();

                foreach ($ids as $id) {
                    $guest = $project->guests[$project->maybe[$id]];
                    $maybeGuests[] = $guest;
                }

                $theme->set('maybeGuests', $maybeGuests);
            }
        }

        $allowNotGoing = $params->get('allownotgoingguest', true);

        $theme->set('allowNotGoing', $allowNotGoing);

        if ($allowNotGoing) {
            $totalNotGoing = count($project->notgoing);

            $theme->set('totalNotGoing', $totalNotGoing);

            if ($totalNotGoing > 0) {
                $ids = (array) array_rand($project->notgoing, min($totalNotGoing, 20));

                $notGoingGuests = array();

                foreach ($ids as $id) {
                    $guest = $project->guests[$project->notgoing[$id]];
                    $notGoingGuests[] = $guest;
                }

                $theme->set('notGoingGuests', $notGoingGuests);
            }
        }

        $link = FRoute::projects(array(
            'id' => $project->getAlias(),
            'appId' => $this->app->getAlias(),
            'layout' => 'item'
        ));

        $theme->set('link', $link);

        echo $theme->output('themes:/apps/project/guests/widgets/widget.guests');
    }

    private function getFriends($project)
    {
        $theme = FD::themes();

        $my = FD::user();

        $options = array();
        $options['userId'] = $my->id;
        $options['randomize'] = true;
        $options['limit'] = 5;
        $options['published'] = true;

        $model = FD::model('Projects');
        $friends = $model->getFriendsInProject($project->id, $options);

        $theme->set('friends', $friends);

        return $theme->output('themes:/apps/project/guests/widgets/widget.friends');
    }

    private function getOnlineUsers($project)
    {
        $model = FD::model('Projects');
        $users = $model->getOnlineGuests($project->id);

        $theme = FD::themes();
        $theme->set('users', $users);

        return $theme->output('themes:/apps/project/guests/widgets/widget.online');
    }
}
