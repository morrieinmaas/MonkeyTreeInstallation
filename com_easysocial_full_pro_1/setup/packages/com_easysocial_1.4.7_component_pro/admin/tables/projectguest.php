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

FD::import('admin:/tables/clusternode');

/**
 * Object mapping for `#__social_clusters_nodes` table.
 *
 * @author  Jason Rey <jasonrey@stackideas.com>
 * @since   1.3
 */
class SocialTableProjectGuest extends SocialTableClusterNode
{
    /**
     * Alias method to route to FD::user()->getName();
     * @return  string   The guest's name.
     */
    public function getName()
    {
        if (empty($this->type) || empty($this->uid)) {
            return '';
        }

        return call_user_func(array('Foundry', $this->type), $this->uid)->getName();
    }

    public function isParticipant()
    {
        // This is to check if the current user is part of the project regardless of the state of the user
        return !empty($this->cluster_id);
    }

    public function isGuest()
    {
        // Guest with pending state or invited state is NOT consider a guest
        return !is_null($this->cluster_id) && !$this->isPending() && !$this->isUndecided();
    }

    /**
     * Alias method for isGuest to ensure compatibility of projects with groups.
     * @return boolean True if user is a guest of the project.
     */
    public function isMember()
    {
        return $this->isGuest();
    }

    public function isInvited()
    {
        return $this->state == SOCIAL_PROJECT_GUEST_INVITED;
    }

    public function isUndecided()
    {
        return $this->state == SOCIAL_PROJECT_GUEST_INVITED;
    }

    public function isGoing()
    {
        return $this->state == SOCIAL_PROJECT_GUEST_GOING;
    }

    public function isMaybe()
    {
        return $this->state == SOCIAL_PROJECT_GUEST_MAYBE;
    }

    public function isNotGoing()
    {
        return $this->state == SOCIAL_PROJECT_GUEST_NOT_GOING;
    }

    public function isPending()
    {
        return $this->state == SOCIAL_PROJECT_GUEST_PENDING;
    }

    /**
     * Performs trigger when user responds to an project.
     *
     * @since   1.3
     * @access  public
     * @param   string
     * @return
     */
    public function triggerResponse($method)
    {
        // Perform project trigger for this action
        // @onProjectGuestResponse
        FD::apps()->load('user');

        $user = FD::user($this->uid);
        $args = array($method, &$user);

        $dispatcher = FD::dispatcher();
        $dispatcher->trigger('user', 'onProjectGuestResponse', $args);
    }

    /**
     * Creates a new notification item
     *
     * @since   1.3
     * @access  public
     * @param   string  $action The action associated with the notification.
     */
    public function createNotification($action)
    {
        // To prproject unexpected callees in creating notification
        $allowed = array('going', 'maybe', 'notgoing', 'makeadmin', 'revokeadmin', 'approve', 'reject', 'request', 'withdraw', 'remove');

        if (!in_array($action, $allowed)) {
            return;
        }

        // Actor is the guest for:
        // going
        // maybe
        // notgoing
        // request
        // withdraw

        $actor = FD::user($this->uid);

        // Actor is the current logged in user for:
        // makeadmin
        // revokeadmin
        // reject
        // approve
        // remove

        if (in_array($action, array('makeadmin', 'revokeadmin', 'reject', 'approve', 'remove'))) {
            $actor = FD::user();
        }

        $project = FD::project($this->cluster_id);

        // COM_EASYSOCIAL_EMAILS_PROJECT_GUEST_MAKEADMIN_SUBJECT
        // COM_EASYSOCIAL_EMAILS_PROJECT_GUEST_REVOKEADMIN_SUBJECT
        // COM_EASYSOCIAL_EMAILS_PROJECT_GUEST_REJECT_SUBJECT
        // COM_EASYSOCIAL_EMAILS_PROJECT_GUEST_APPROVE_SUBJECT
        // COM_EASYSOCIAL_EMAILS_PROJECT_GUEST_REMOVE_SUBJECT
        // COM_EASYSOCIAL_EMAILS_PROJECT_GUEST_GOING_SUBJECT
        // COM_EASYSOCIAL_EMAILS_PROJECT_GUEST_MAYBE_SUBJECT
        // COM_EASYSOCIAL_EMAILS_PROJECT_GUEST_NOTGOING_SUBJECT
        // COM_EASYSOCIAL_EMAILS_PROJECT_GUEST_REQUEST_SUBJECT
        // COM_EASYSOCIAL_EMAILS_PROJECT_GUEST_WITHDRAW_SUBJECT

        // site/project/guest.makeadmin
        // site/project/guest.revokeadmin
        // site/project/guest.reject
        // site/project/guest.approve
        // site/project/guest.remove
        // site/project/guest.going
        // site/project/guest.maybe
        // site/project/guest.notgoing
        // site/project/guest.request
        // site/project/guest.withdraw

        $emailOptions = (object) array(
            'title' => 'COM_EASYSOCIAL_EMAILS_PROJECT_GUEST_' . strtoupper($action) . '_SUBJECT',
            'template' => 'site/project/guest.' . strtolower($action),
            'project' => $project->getName(),
            'projectAvatar' => $project->getAvatar(),
            'projectLink' => $project->getPermalink(false, true),
            'response' => $action,
            'actor' => $actor->getName(),
            'actorLink' => $actor->getPermalink(false, true),
            'actorAvatar' => $actor->getAvatar()
        );

        $systemOptions = (object) array(
            'uid' => $this->id,
            'title' => '',
            'actor_id' => $actor->id,
            'context_ids' => $this->cluster_id,
            'context_type' => SOCIAL_TYPE_PROJECT,
            'type' => 'projects',
            'url' => $project->getPermalink(false, false, 'item', false),
            'image' => $project->getAvatar(),
            'response' => $action,
            'projectId' => $project->id
        );

        // Special case for request
        // If action is request, we use a different permalink instead so that the generated system notification is pointing to the guest app
        // Request email also needs to point to the guest app
        if ($action === 'request') {
            // Get the guest app first
            $guestApp = FD::table('App');
            $guestApp->load(array(
                'type' => SOCIAL_TYPE_APPS,
                'group' => SOCIAL_TYPE_PROJECT,
                'element' => 'guests'
            ));

            $emailOptions->link = FRoute::projects(array(
                'layout' => 'item',
                'id' => $project->getAlias(),
                'appId' => $guestApp->getAlias(),
                'type' => 'pending',
                'external' => true
            ));

            $systemOptions->url = FRoute::projects(array(
                'layout' => 'item',
                'id' => $project->getAlias(),
                'appId' => $guestApp->getAlias(),
                'type' => 'pending',
                'sef' => false
            ));
        }

        $targets = array();

        // If action is makeadmin, revokeadmin, reject, approve, remove, then supposed to notify the guest instead
        if (in_array($action, array('makeadmin', 'revokeadmin', 'reject', 'approve', 'remove'))) {
            $targets[] = $this->uid;
        } else {
            // Notify the project owner and admins
            $targets[] = $project->getCreator()->id;

            $admins = $project->getAdmins();

            foreach ($admins as $admin) {
                $targets[] = $admin->uid;
            }

            // Remove self, unique it, and reset the targets array
            $targets = array_values(array_unique(array_diff($targets, array(FD::user()->id))));
        }

        // Send notification now
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
        FD::notify('projects.guest.' . $action, $targets, $emailOptions, $systemOptions);
    }

    /**
     * Removes a system notification item.
     *
     * @since   1.3
     * @access  public
     * @param   string  $action The action associated with the notification.
     */
    public function removeNotification($action)
    {
        // Query by actor_id if actions are these listed because these notifications is 1 actor to multiple targets
        // going
        // maybe
        // notgoing
        // request
        // withdraw

        if (in_array($action, array('going', 'maybe', 'notgoing', 'request', 'withdraw'))) {
            $db = FD::db();
            $sql = $db->sql();

            $sql->delete('#__social_notifications');

            $sql->where('actor_type', SOCIAL_TYPE_USER);
            $sql->where('actor_id', $this->uid);
            $sql->where('cmd', 'projects.guest.' . $action);

            $db->setQuery($sql);
            $db->query();
        }

        // These actions doesn't need to remove notification
        // makeadmin
        // revokeadmin
        // reject
        // approve
        // remove
    }

    /**
     * Creates project stream item for project guest related action.
     * For project item stream item, see SocialProject::createStream();
     *
     * @since   1.3
     * @access  public
     * @param   string  $action     The action associated with the stream.
     */
    public function createStream($action)
    {
        // To prproject unexpected callees on creating stream.
        $allowed = array('going', 'notgoing', 'makeadmin');

        if (!in_array($action, $allowed)) {
            return false;
        }

        $project = $this->getProject();

        // Load up the stream library
        $stream = FD::stream();

        // Get the stream template
        $tpl = $stream->getTemplate();

        // Set the verb
        $tpl->setVerb($action);

        // Set the context
        // Since this is a "user" action, we set the context id to the guest node id, and the context type to guests
        $tpl->setContext($this->id, 'guests');

        // Set the privacy rule
        $tpl->setAccess('core.view');

        // Set the cluster
        $tpl->setCluster($project->id, $project->cluster_type, $project->type);

        // Set the actor
        $tpl->setActor($this->uid, $this->type);

        // Add stream template.
        $stream->add($tpl);
    }

    /**
     * Removes project stream item for project guest related action.
     * For project item stream item, see SocialProject::createStream();
     *
     * @since   1.3
     * @access  public
     * @param   string  $action     The action associated with the stream.
     */
    public function removeStream($action)
    {
        // To prproject unexpected callees on creating stream.
        $allowed = array('going', 'notgoing', 'makeadmin');

        if (!in_array($action, $allowed)) {
            return false;
        }

        $db = FD::db();
        $sql = $db->sql();

        $sql->select('#__social_stream');
        $sql->column('id');
        $sql->where('cluster_id', $this->cluster_id);
        $sql->where('cluster_type', SOCIAL_TYPE_PROJECT);
        $sql->where('context_type', 'guests');
        $sql->where('verb', $action);
        $sql->where('actor_id', $this->uid);
        $sql->where('actor_type', $this->type);

        $db->setQuery($sql);
        $ids = $db->loadColumn();

        if (empty($ids)) {
            return;
        }

        $sql->clear();

        $sql->delete('#__social_stream_item');
        $sql->where('uid', $ids, 'IN');

        $db->setQuery($sql);
        $db->query();

        $sql->clear();

        $sql->delete('#__social_stream');
        $sql->where('id', $ids, 'IN');

        $db->setQuery($sql);
        $db->query();
    }

    /**
     * Allows caller to make a user as an admin
     *
     * @since   1.3
     * @access  public
     * @param   string
     * @return
     */
    public function makeAdmin()
    {
        $state = parent::makeAdmin();

        $this->createNotification('makeadmin');

        // Create a stream for this
        $this->createStream('makeadmin', $this->uid, $this->type);

        return $state;
    }

    public function revokeAdmin()
    {
        $state = parent::revokeAdmin();

        $this->createNotification('revokeadmin');

        // Don't need to remove makeadmin notification

        // Delete makeAdmin stream
        $this->removeStream('makeadmin');

        return $state;
    }

    /**
     * Set the status to attending
     *
     * @since   1.3
     * @access  public
     * @return
     */
    public function going()
    {
        // If already is going, then don't do anything
        if ($this->isGoing()) {
            return true;
        }

        $this->state = SOCIAL_PROJECT_GUEST_GOING;

        $state = $this->store();

        if (!$state) {
            return false;
        }

        // Perform project trigger for this action
        $this->triggerResponse(__FUNCTION__);

        // Remove other state notification
        $this->removeNotification('notgoing');
        $this->removeNotification('maybe');

        // Send notification to project owner that the user will be attending the project
        $this->createNotification('going');

        // Remove notgoing response stream
        $this->removeStream('notgoing');

        // Create stream
        $this->createStream('going');

        // Assign points for the user
        FD::points()->assign('projects.going', 'com_easysocial', $this->uid);

        return $state;
    }

    /**
     * Set the status to maybe
     *
     * @since   1.3
     * @access  public
     * @return  boolean True if successful.
     */
    public function maybe()
    {
        // If already is maybe, then don't do anything
        if ($this->isMaybe()) {
            return true;
        }

        $this->state = SOCIAL_PROJECT_GUEST_MAYBE;

        $state = $this->store();

        if (!$state) {
            return false;
        }

        // Perform project trigger for this action
        $this->triggerResponse(__FUNCTION__);

        // Remove other state notification
        $this->removeNotification('notgoing');
        $this->removeNotification('going');


        // Send notification to project owner that the user set the status to "maybe"
        $this->createNotification('maybe');

        // Remove other response streams avoid spanning multiple stream items
        $this->removeStream('going');
        $this->removeStream('notgoing');

        return $state;
    }

    /**
     * Set the status to not going
     *
     * @since   1.3
     * @access  public
     * @return  boolean True if successful.
     */
    public function notGoing()
    {
        // If already is not going, then don't do anything
        if ($this->isNotGoing()) {
            return true;
        }

        // Allow settings to decide if guest do not want to go, directly remove them from the project.
        // That part is controlled in controller/projects

        $this->state = SOCIAL_PROJECT_GUEST_NOT_GOING;

        $state = $this->store();

        if (!$state) {
            return false;
        }

        // Perform project trigger for this action
        $this->triggerResponse(__FUNCTION__);

        // Remove other state notification
        $this->removeNotification('going');
        $this->removeNotification('maybe');

        // Send notification to project owner that the user set the status to "not going"
        $this->createNotification('notgoing');

        // Remove "going" stream to avoid spanning multiple stream items
        $this->removeStream('going');

        // Create stream
        $this->createStream('notgoing');

        // Assign points for the user
        FD::points()->assign('projects.notgoing', 'com_easysocial', $this->uid);

        return $state;
    }

    /**
     * Same as going but different notification.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     * @return boolean  True if success.
     */
    public function approve()
    {
        // If the user request to join the project, means that he/she wants to go, hence if admin approve a pending request, the state should be going
        $this->state = SOCIAL_PROJECT_GUEST_GOING;

        $state = $this->store();

        if (!$state) {
            return false;
        }

        // Notification
        $this->createNotification('approve');

        // Create stream
        $this->createStream('going');

        return $state;
    }

    /**
     * Rejects a guest request to join the project.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     * @return boolean  True if success.
     */
    public function reject()
    {
        $state = $this->delete();

        if (!$state) {
            return false;
        }

        // Notification
        $this->createNotification('reject');

        return $state;
    }

    public function request()
    {
        // If already is pending, then don't do anything
        if ($this->isPending()) {
            return true;
        }

        $this->state = SOCIAL_PROJECT_GUEST_PENDING;

        $state = $this->store();

        if (!$state) {
            return false;
        }

        // Notification
        $this->createNotification('request');

        return $state;
    }

    public function withdraw()
    {
        $state = $this->delete();

        if (!$state) {
            return false;
        }

        $this->removeNotification('going');
        $this->removeNotification('maybe');

        $this->createNotification('withdraw');

        $this->removeStream('going');
        $this->removeStream('notgoing');
        $this->removeStream('request');

        return $state;
    }

    public function remove()
    {
        $state = $this->delete();

        if (!$state) {
            return false;
        }

        // Notification
        $this->createNotification('remove');

        // Remove stream
        $this->removeStream('going');
        $this->removeStream('notgoing');
        $this->removeStream('makeadmin');

        return $state;
    }

    public function getProject()
    {
        return FD::project($this->cluster_id);
    }

    public function __call($method, $args)
    {
        $user = FD::user($this->uid);

        if (!is_callable(array($user, $method))) {
            throw new Exception("No such method", 1);
        }

        return call_user_func_array(array($user, $method), $args);
    }
}
