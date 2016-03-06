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

class SocialProjectAppGuestsHookNotificationGuest
{
    /**
     * Processes likes notifications
     *
     * @since   1.2
     * @access  public
     * @param   string
     * @return
     */
    public function execute(&$item)
    {
        // makeadmin, revokeadmin, reject, approve, remove, invite is 1 target only

        // going, maybe, notgoing, request, withdraw is targetting admins, multiple target

        // projects.guest.invite
        // projects.guest.makeadmin
        // projects.guest.revokeadmin
        // projects.guest.reject
        // projects.guest.approve
        // projects.guest.remove
        // projects.guest.going
        // projects.guest.maybe
        // projects.guest.notgoing
        // projects.guest.request
        // projects.guest.withdraw

        // APP_PROJECT_GUESTS_NOTIFICATION_MAKEADMIN_TITLE
        // APP_PROJECT_GUESTS_NOTIFICATION_REVOKEADMIN_TITLE
        // APP_PROJECT_GUESTS_NOTIFICATION_REJECT_TITLE
        // APP_PROJECT_GUESTS_NOTIFICATION_APPROVE_TITLE
        // APP_PROJECT_GUESTS_NOTIFICATION_REMOVE_TITLE
        // APP_PROJECT_GUESTS_NOTIFICATION_GOING_TITLE
        // APP_PROJECT_GUESTS_NOTIFICATION_MAYBE_TITLE
        // APP_PROJECT_GUESTS_NOTIFICATION_NOTGOING_TITLE
        // APP_PROJECT_GUESTS_NOTIFICATION_REQUEST_TITLE
        // APP_PROJECT_GUESTS_NOTIFICATION_WITHDRAW_TITLE
        // APP_PROJECT_GUESTS_NOTIFICATION_INVITE_TITLE

        $segments = explode('.', $item->cmd);

        $action = $segments[2];

        $actor = FD::user($item->actor_id);

        $guest = FD::table('ProjectGuest');
        $guest->load($item->uid);

        $project = FD::project($item->getParams()->get('projectId'));

        $item->title = JText::sprintf('APP_PROJECT_GUESTS_NOTIFICATION_' . strtoupper($action) . '_TITLE', $actor->getName(), $project->getName());

        $item->image = $project->getAvatar();

        return;
    }
}
