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

FD::import('admin:/includes/cluster/cluster');

class Socialproject extends SocialCluster
{
    /**
     * Defines the cluster type.
     * @var string
     */
    public $cluster_type = SOCIAL_TYPE_project;

    /**
     * Stores the instances of projects.
     * @var array
     */
    static $instances = array();

    /**
     * Stores the guest states key that exists as property within this class.
     * @var array
     */
    static $guestStates = array('invited', 'going', 'pending', 'maybe', 'notgoing');

    /**
     * Stores the guest state of invited.
     * @var array
     */
    public $invited = array();

    /**
     * Stores the guest state of going.
     * @var array
     */
    public $going = array();

    /**
     * Stores the guest state of pending.
     * @var array
     */
    public $pending = array();

    /**
     * Stores the guest state of maybe.
     * @var array
     */
    public $maybe = array();

    /**
     * Stores the guest state of notgoing.
     * @var array
     */
    public $notgoing = array();

    /**
     * Stores all the guests of this project in SocialTableprojectGuest class.
     * @var array
     */
    public $guests = array();

    /**
     * Stores all the admin id (mapped to $this->guests) of this project.
     * @var array
     */
    public $admins = array();

    /**
     * Stores the meta table of this project.
     * @var SocialTableprojectMeta
     */
    public $meta = null;

    /**
     * Construct and initialise this project class per single project class.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @param   array     $params The parameters to init.
     */
    public function __construct($params = array())
    {
        // Create the user parameters object
        $this->_params = FD::registry();

        // Initialize user's property locally.
        $this->initParams($params);

        $this->table = FD::table('project');
        $this->table->bind($this);

        $this->meta = FD::Table('projectMeta');
        $this->meta->load(array('cluster_id' => $this->id));
    }

    /**
     * Core function to initialise this class.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @param   Mixed        $ids The ids to load.
     * @return  Mixed        The project class or array of project classes.
     */
    public static function factory($ids = null)
    {
        $items = self::loadprojects($ids);

        return $items;
    }

    /**
     * Custom save function to handle additional meta to save into projects_meta table.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @return  Boolean True if successful.
     */
    public function save()
    {
        // Let parent save first to ensure there is a cluster id
        $state = parent::save();

        if (!$state) {
            return $state;
        }

        // Then now we store the meta.
        $this->meta->cluster_id = $this->id;
        $this->meta->store();

        return $state;
    }

    /**
     * Method to get extended project meta data.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @param   string $key     The key of the meta.
     * @param   Mixed  $default The default value of the meta.
     * @return  Mixed           The data of the meta.
     */
    public function getMeta($key, $default = null)
    {
        return $this->meta->get($key, $default);
    }

    /**
     * Method to set extended project meta data.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @param   string $key   The key of the meta.
     * @param   Mixed  $value The value of the meta.
     * @return  Boolean True if successful.
     */
    public function setMeta($key, $value)
    {
        return $this->meta->set($key, $value);
    }

    /**
     * Retrieves the description about an project
     *
     * @since   1.3
     * @access  public
     * @param   string
     * @return
     */
    public function getDescription()
    {
        return nl2br($this->description);
    }

    /**
     * Delete notifications related to this cluster
     *
     * @since   1.3
     * @access  public
     * @return  boolean True if successful.
     */
    public function deleteNotifications()
    {
        $model = FD::model('Clusters');
        $state = $model->deleteClusterNotifications($this->id, $this->cluster_type, SOCIAL_TYPE_project);

        return $state;
    }

    /**
     * Allows caller to remove a member from the group
     *
     * @since   1.3
     * @access  public
     * @param   integer $userId The user id to delete.
     * @return  boolean         True if successful.
     */
    public function deleteMember($userId)
    {
        $state  = $this->deleteNode($userId, SOCIAL_TYPE_USER);

        return $state;
    }

    /**
     * Logics for deleting an project
     *
     * @since   1.3
     * @access  public
     * @return  boolean True if successful.
     */
    public function delete()
    {
        // @trigger onBeforeDelete
        $dispatcher = FD::dispatcher();

        // @points: groups.remove
        // Deduct points when a group is deleted
        FD::points()->assign('projects.remove', 'com_easysocial', $this->getCreator()->id);

        // remove the access log for this action
        FD::access()->removeLog('projects.limit', $this->getCreator()->id, $this->id, SOCIAL_TYPE_project);

        // Set the arguments
        $args = array(&$this);

        // @trigger onBeforeStorySave
        $dispatcher->trigger(SOCIAL_TYPE_project, 'onBeforeDelete', $args);

        $dispatcher->trigger(SOCIAL_TYPE_USER, 'onprojectBeforeDelete', $args);

        // Delete any relations from the calendar table
        $this->deleteFromCalendar();

        // Delete all members from the cluster nodes.
        $this->deleteNodes();

        // Delete custom fields data for this cluster.
        $this->deleteCustomFields();

        // Delete photos albums for this cluster.
        $this->deletePhotoAlbums();

        // Delete stream items for this group
        $this->deleteStream();

        // Delete all group news
        $this->deleteNews();

        // delete all user notification associated with this group.
        $this->deleteNotifications();

        // Delete from the cluster
        $state = parent::delete();

        $args[] = $state;

        // @trigger onAfterDelete
        $dispatcher->trigger(SOCIAL_TYPE_project, 'onAfterDelete', $args);

        $dispatcher->trigger(SOCIAL_TYPE_USER, 'onprojectAfterDelete', $args);

        return $state;
    }

    /**
     * Deletes an project entry from the calendar
     *
     * @since   1.3
     * @access  public
     * @param   string
     * @return
     */
    public function deleteFromCalendar()
    {
        $db = FD::db();
        $sql = $db->sql();

        $sql->delete('#__social_apps_calendar');
        $sql->where('uid', $this->id);
        $sql->where('type', SOCIAL_TYPE_project);

        $db->setQuery($sql);
        return $db->Query();
    }

    /**
     * Method to feature an project.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @return  boolean True if successful.
     */
    public function feature()
    {
        $this->table->featured = true;

        $state = $this->table->store();

        if ($state) {
            $this->featured = true;

            $this->createStream('feature');

            // Notify the owner only if the person who carries out the feature action is not the owner
            if ($this->creator_type == SOCIAL_TYPE_USER && $this->creator_uid != FD::user()->id) {

            }
        }

        return $state;
    }

    /**
     * Method to unfeature an project.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @return  boolean True if successful.
     */
    public function unfeature()
    {
        $this->table->featured = false;

        $state = $this->table->store();

        if ($state) {
            $this->featured = false;

            $this->removeStream('feature');
        }

        return $state;
    }

    /**
     * Loads and prepares the project classes.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @param   Mixed   $ids    The ids to load.
     * @return  Mixed           The project class or array of project classes.
     */
    public static function loadprojects($ids = null)
    {
        if (is_object($ids)) {
            $obj = new self;
            $obj->bind($ids);

            self::$instances[$ids->id] = $obj;

            return self::$instances[$ids->id];
        }

        $argumentIsArray = is_array($ids);

        $ids = FD::makeArray($ids);

        if (empty($ids)) {
            return false;
        }

        $model = FD::model('projects');

        $projects = $model->getMeta($ids);

        if (empty($projects)) {
            return false;
        }

        $result = array();

        foreach ($projects as $project) {
            if (!$project) {
                continue;
            }

            if (isset(self::$instances[$project->id])) {
                $result[] = self::$instances[$project->id];
                continue;
            }

            $project->cover = self::getCoverObject($project);

            $guests = $model->getGuests($project->id);

            $project->guests = array();

            foreach ($guests as $guest) {
                $project->guests[$guest->uid] = $guest;

                if ($guest->isAdmin()) {
                    $project->admins[] = $guest->uid;
                }

                if (!isset($project->{self::$guestStates[$guest->state]}) || !is_array($project->{self::$guestStates[$guest->state]})){
                    $project->{self::$guestStates[$guest->state]} = array();
                }

                // Guests states array only stores the id, and this needs to be mapped to the instance->guests property to get the guest table object.
                $project->{self::$guestStates[$guest->state]}[] = $guest->uid;
            }

            $obj = new Socialproject($project);

            self::$instances[$project->id] = $obj;

            $result[] = self::$instances[$project->id];
        }

        if (empty($result)) {
            return false;
        }

        if (!$argumentIsArray && count($result) === 1) {
            return $result[0];
        }

        return $result;
    }

    public function bind($data)
    {
        $this->table->bind($data);

        $keyToArray = array_merge(array('avatars', 'admins'), self::$guestStates);

        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                if (in_array($key, $keyToArray) && is_object($value)) {
                    $value = FD::makeArray($value);
                }

                $this->$key = $value;
            }
        }
    }

    /**
     * Returns the total guests in this project.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @return  integer  The number of guests in this project.
     */
    public function getTotalGuests()
    {
        // Pending and undecided is not consider a guest
        return count($this->guests) - count($this->pending) - count($this->invited);
    }

    /**
     * Returns the total admins in this project.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @return  integer  The number of admins in this project.
     */
    public function getTotalAdmins()
    {
        return count($this->admins);
    }

    /**
     * Returns the total guests that is going to this project.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @return  integer  The number of guests that is going to this project.
     */
    public function getTotalGoing()
    {
        return count($this->going);
    }

    /**
     * Returns the total guests that might be going to this project.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @return  integer  The number of guests that might be going to this project.
     */
    public function getTotalMaybe()
    {
        return count($this->maybe);
    }

    /**
     * Returns the total guests that is not going to this project.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @return  integer  The number of guests that is not going to this project.
     */
    public function getTotalNotGoing()
    {
        return count($this->notgoing);
    }

    /**
     * Returns the total guests that is invited but haven't make a decision in this project.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @return  integer  The number of guests that is invited but haven't make a decision in this project.
     */
    public function getTotalUndecided()
    {
        return count($this->invited);
    }

    /**
     * Returns the permalink to the page of this project, depending on the layout.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @param   boolean   $xhtml    True if permalink is required to be in xhtml format.
     * @param   boolean   $external True if permalink is required to be in external format.
     * @param   string    $layout   The layout of the project page.
     * @param   boolean   $sef      True if permalink is required to be a SEF link.
     * @return  string              The permalink of this project.
     */
    public function getPermalink($xhtml = true, $external = false, $layout = 'item', $sef = true)
    {
        $options = array('id' => $this->getAlias(), 'layout' => $layout, 'external' => $external, 'sef' => $sef);

        return FRoute::projects($options, $xhtml);
    }

    /**
     * Determines if the project is an open project.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @return  bool    True if is an open project.
     */
    public function isOpen()
    {
        return $this->type == SOCIAL_project_TYPE_PUBLIC;
    }

    /**
     * Determines if the project is a close project.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @return  bool    True if is a closed project.
     */
    public function isClosed()
    {
        return $this->type == SOCIAL_project_TYPE_PRIVATE;
    }

    /**
     * Determines if the user is attending
     *
     * @since   1.3
     * @access  public
     * @param   string
     * @return
     */
    public function isAttending($id = null)
    {
        $user = FD::user($id);

        if (!isset($this->guests[$user->id])) {
            return false;
        }

        $obj = $this->guests[$user->id];

        return $obj->isGoing();
    }

    /**
     * Determines if the user is attending
     *
     * @since   1.3
     * @access  public
     * @param   string
     * @return
     */
    public function isNotAttending($id = null)
    {
        $user = FD::user($id);

        if (!isset($this->guests[$user->id])) {
            return false;
        }

        $obj = $this->guests[$user->id];

        return $obj->isNotGoing();
    }

    /**
     * Alias method for isClosed
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @return  bool    True if is a private project.
     */
    public function isPrivate()
    {
        return $this->type == SOCIAL_project_TYPE_PRIVATE;
    }

    /**
     * Determines if the project is an invite-only project.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @return  bool    True if is invite-only project.
     */
    public function isInviteOnly()
    {
        return $this->type == SOCIAL_project_TYPE_INVITE;
    }

    /**
     * Returns the SocialDate object of the project start datetime.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @return  SocialDate The SocialDate object of the project start datetime.
     */
    public function getprojectStart()
    {
        return $this->meta->getStart();
    }

    /**
     * Returns the SocialDate object of the project end datetime.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @return  SocialDate The SocialDate object of the project end datetime.
     */
    public function getprojectEnd()
    {
        return $this->meta->getEnd();
    }

    /**
     * Returns the SocialDate object of the project timezone.
     *
     * @author  Nik Faris <nikfaris@stackideas.com>
     * @since   1.4
     * @access  public
     * @return  SocialDate The SocialDate object of the project timezone.
     */
    public function getprojectTimezone()
    {
        return $this->meta->getTimezone();
    }

    /**
     * Check if this project has an end date.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     * @return boolean   True if project has an end date.
     */
    public function hasprojectEnd()
    {
        return $this->meta->hasEnd();
    }

    /**
     * Determines if the project is over.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @return  boolean   True if the project is over.
     */
    public function isOver()
    {
        // Get the project end date
        $end = $this->getprojectEnd();

        // Get the current date
        $now = FD::date();

        // If now > end, means it is over.
        $over = $now->toUnix() > $end->toUnix();

        return $over;
    }

    /**
     * Determines if the project is an upcoming project. Optionally check by days.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @param   integer    $days Days to check. Optional.
     * @return  boolean          True if it is an upcoming project.
     */
    public function isUpcoming($daysToCheck = null)
    {
        $start = $this->getprojectStart();

        $now = FD::date();

        $upcoming = $now->toUnix() < $start->toUnix();

        // If not upcoming, then no point checking whether it is within the days or not.
        if (!$upcoming || is_null($daysToCheck)) {
            return $upcoming;
        }

        $daysToproject = $this->timeToproject() / (60*60*24);

        return $daysToproject < $daysToCheck;
    }

    /**
     * Determines if the project is currently ongoing.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @return  boolean True if the project is currently ongoing.
     */
    public function isOngoing()
    {
        // Regardless of projectstart or projectend, as long as it is not upcoming and not over, then it is ongoing.
        return !$this->isUpcoming() && !$this->isOver();
    }

    /**
     * Return the amount of time to project from now.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @param   string  $format The format of the time to return.
     * @return  integer         The time based on the format to the project.
     */
    public function timeToproject($format = 'seconds')
    {
        $start = $this->getprojectStart();

        $now = FD::date();

        // Get the total seconds first.
        $seconds = $start->toFormat('U') - $now->toFormat('U');

        $units = array(
            'seconds' => 1,
            'minutes' => 60,
            'hours' => 60 * 60,
            'days' => 60 * 60 * 24,
            'weeks' => 60 * 60 * 24 * 7,
            'months' => 60 * 60 * 24 * 30,
            'years' => 60 * 60 * 24 * 365
        );

        return floor($seconds / (isset($units[$format]) ? $units[$format] : 1));
    }

    /**
     * Returns the table object of the category of this project.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @return  SocialTableprojectCategory    The project category table object.
     */
    public function getCategory()
    {
        $table = FD::table('projectCategory');
        $table->load($this->category_id);

        return $table;
    }

    /**
     * Alias method for getCreator
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     * @return  SocialTableprojectGuest    The project guest table object of the creator of this project.
     */
    public function getOwner()
    {
        return $this->getCreator();
    }

    /**
     * Returns an array of SocialTableprojectGuest object who are the admins.
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @return  array   Array of admins in SocialTableprojectGuest object.
     */
    public function getAdmins()
    {
        $admins = array();

        foreach ($this->admins as $uid) {
            if (isset($this->guests[$uid])) {
                $admins[$uid] = $this->guests[$uid];
            }
        }

        return $admins;
    }

    /**
     * Returns the projectGuest object with the given user id.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     * @param  integer                  $uid    The user id.
     * @return SocialTableprojectGuest            The project guest table object.
     */
    public function getGuest($uid = null)
    {
        if (empty($uid)) {
            $uid = FD::user()->id;
        }

        if (!isset($this->guests[$uid])) {
            $guest = FD::table('projectGuest');
            $guest->uid = $uid;
            $guest->type = SOCIAL_TYPE_USER;

            return $guest;
        }

        return $this->guests[$uid];
    }

    /**
     * Approves the project.
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @return  boolean True if successfull.
     */
    public function approve()
    {
        $this->state = SOCIAL_CLUSTER_PUBLISHED;

        $state = $this->save();

        if (!$state) {
            return false;
        }

        $dispatcher = FD::dispatcher();

        // Set the arguments
        $args = array(&$this);

        // @trigger onprojectAfterApproved
        $dispatcher->trigger(SOCIAL_TYPE_project, 'onAfterApproved', $args);
        $dispatcher->trigger(SOCIAL_TYPE_USER, 'onprojectAfterApproved', $args);

        // Send email.
        FD::language()->loadSite();

        $params = array(
            'title' => $this->getName(),
            'name' => $this->getCreator()->getName(),
            'avatar' => $this->getAvatar(),
            'url' => $this->getPermalink(false, true),
            'editUrl' => $this->getPermalink(false, true, 'edit'),
            'discussion' => $this->getParams()->get('discussions', true)
        );

        $title = JText::sprintf('COM_EASYSOCIAL_EMAILS_project_APPROVED', $this->getName());

        $mailer = FD::mailer();

        $tpl = $mailer->getTemplate();

        $recipient = $this->getCreator();

        $tpl->setRecipient($recipient->getName(), $recipient->email);

        $tpl->setTitle($title);

        $tpl->setTemplate('site/project/approved', $params);

        $tpl->setPriority(SOCIAL_MAILER_PRIORITY_IMMEDIATE);

        $mailer->create($tpl);

        // Create stream.
        if (FD::config()->get('projects.stream.create')) {
            // It is possible that the stream might have already been created if this project is created from story form.

            $stream = FD::table('Stream');
            $state = $stream->load(array('context_type' => 'projects', 'verb' => 'create', 'cluster_id' => $this->id));

            // If no stream found then only we create the stream item
            if (!$state || empty($stream->id)) {
                $this->createStream('create', $this->creator_uid, $this->creator_type);
            }
        }

        return true;
    }

    /**
     * Rejects the project.
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @return  boolean True if successfull.
     */
    public function reject()
    {
        $this->state = SOCIAL_CLUSTER_UNPUBLISHED;

        $state = $this->save();

        if (!$state) {
            return false;
        }

        // Send email.

        FD::language()->loadSite();

        $params = array(
            'title' => $this->getName(),
            'name' => $this->getCreator()->getName()
        );

        $title = JText::sprintf('COM_EASYSOCIAL_EMAILS_project_REJECTED', $this->getName());

        $mailer = FD::mailer();

        $tpl = $mailer->getTemplate();

        $recipient = $this->getCreator();

        $tpl->setRecipient($recipient->getName(), $recipient->email);

        $tpl->setTitle($title);

        $tpl->setTemplate('site/project/rejected', $params);

        $tpl->setPriority(SOCIAL_MAILER_PRIORITy_IMMEDIATE);

        $mailer->create($tpl);

        return true;
    }

    /**
     * Creates project stream item for project item related action.
     * For guest response stream item, see SocialTableprojectGuest::createStream();
     *
     * @since   1.3
     * @access  public
     * @param   string  $action     The action associated with the stream.
     * @param   integer $actorId    The actor id.
     * @param   string  $actorType  The actor type.
     */
    public function createStream($action, $actorId = null, $actorType = SOCIAL_TYPE_USER)
    {
        // To prproject unexpected callees on creating stream.
        $allowed = array('create', 'update', 'feature');

        if (!in_array($action, $allowed)) {
            return false;
        }

        if (is_null($actorId)) {
            $actorId = FD::user()->id;
            $actorType = SOCIAL_TYPE_USER;
        }

        // Load up the stream library
        $stream = FD::stream();

        // Get the stream template
        $tpl = $stream->getTemplate();

        // Set the verb
        $tpl->setVerb($action);

        // Set the context
        // Due to inconsistency, we don't use SOCIAL_TYPE_project.
        // Instead we use "projects" because app elements are named with 's', namely users, groups, projects.
        $tpl->setContext($this->id, 'projects');

        // Set the privacy rule
        $tpl->setAccess('core.view');

        // Set the cluster
        $tpl->setCluster($this->id, $this->cluster_type, $this->type);

        // Set the actor
        $tpl->setActor($actorId, $actorType);

        // Add stream template.
        $stream->add($tpl);
    }

    /**
     * Removes project stream item for project item related action.
     * For guest response stream item, see SocialTableprojectGuest::removeStream();
     *
     * @since   1.3
     * @access  public
     * @param   string  $action     The action associated with the stream.
     * @return  boolean             True if successful.
     */
    public function removeStream($action)
    {
        // To prproject unexpected callees deleting stream.
        $allowed = array('feature');

        if (!in_array($action, $allowed)) {
            return false;
        }

        $stream = FD::table('Stream');
        $state = $stream->load(array(
            'cluster_id' => $this->id,
            'cluster_type' => $this->cluster_type,
            'context_type' => 'projects',
            'verb' => $action
        ));

        if (!$state) {
            return false;
        }

        return $stream->delete();
    }

    /**
     * Notify members of the project
     *
     * @since   1.3
     * @access  public
     * @param   string
     * @return
     */
    public function notifyMembers($action, $data = array())
    {
        $model = FD::model('projects');

        // Determines if the targets has been provided
        $targets = isset($data['targets']) ? $data['targets'] : false;

        if ($targets === false) {
            $exclude = isset($data['userId']) ? $data['userId'] : '';
            $options = array('exclude' => $exclude, 'state' => SOCIAL_project_GUEST_GOING, 'users' => true);
            $targets = $model->getGuests($this->id, $options);
        }

        // If there is nothing to send, just skip this altogether
        if (!$targets) {
            return;
        }

        if ($action == 'video.create') {
            $actor = ES::user($data['userId']);

            $params = new stdClass();
            $params->actor = $actor->getName();
            $params->userName = $actor->getName();
            $params->userLink = $actor->getPermalink(false, true);
            $params->groupName = $this->getName();
            $params->groupAvatar = $this->getAvatar();
            $params->groupLink = $this->getPermalink(false, true);
            $params->videoTitle = $data['title'];
            $params->videoDescription = $data['description'];
            $params->videoLink = $data['permalink'];

            $options = new stdClass();
            $options->title = 'COM_EASYSOCIAL_EMAILS_GROUP_VIDEO_CREATED_SUBJECT';
            $options->template = 'site/project/video.create';
            $options->params = $params;

            // Set the system alerts
            $system = new stdClass();
            $system->uid = $this->id;
            $system->title = '';
            $system->actor_id = $actor->id;
            $system->context_ids = $data['id'];
            $system->context_type = 'projects';
            $system->type = SOCIAL_TYPE_project;
            $system->url = $params->videoLink;
            $system->image = $this->getAvatar();

            ES::notify('projects.video.create', $targets, $options, $system);
        }

        if ($action == 'task.completed')
        {
            $actor                  = FD::user($data['userId']);
            $params                 = new stdClass();
            $params->actor          = $actor->getName();
            $params->userName       = $actor->getName();
            $params->userLink       = $actor->getPermalink(false, true);
            $params->userAvatar     = $actor->getAvatar(SOCIAL_AVATAR_LARGE);
            $params->projectName      = $this->getName();
            $params->projectAvatar    = $this->getAvatar();
            $params->projectLink      = $this->getPermalink(false, true);
            $params->milestoneName  = $data['milestone'];
            $params->title          = $data['title'];
            $params->content        = $data['content'];
            $params->permalink      = $data['permalink'];

            // Send notification e-mail to the target
            $options            = new stdClass();
            $options->title     = 'COM_EASYSOCIAL_EMAILS_project_TASK_COMPLETED_SUBJECT';
            $options->template  = 'site/project/task.completed';
            $options->params    = $params;

            // Set the system alerts
            $system                 = new stdClass();
            $system->uid            = $this->id;
            $system->title          = '';
            $system->actor_id       = $actor->id;
            $system->context_ids    = $data['id'];
            $system->context_type   = 'project';
            $system->type           = 'projects';
            $system->url            = $params->permalink;
            $system->image          = $this->getAvatar();

            FD::notify('projects.task.completed', $targets, $options, $system);
        }

        if ($action == 'task.create')
        {
            $actor                  = FD::user($data['userId']);
            $params                 = new stdClass();
            $params->actor          = $actor->getName();
            $params->userName       = $actor->getName();
            $params->userLink       = $actor->getPermalink(false, true);
            $params->userAvatar     = $actor->getAvatar(SOCIAL_AVATAR_LARGE);
            $params->projectName      = $this->getName();
            $params->projectAvatar    = $this->getAvatar();
            $params->projectLink      = $this->getPermalink(false, true);
            $params->milestoneName  = $data['milestone'];
            $params->title          = $data['title'];
            $params->content        = $data['content'];
            $params->permalink      = $data['permalink'];

            // Send notification e-mail to the target
            $options            = new stdClass();
            $options->title     = 'COM_EASYSOCIAL_EMAILS_project_NEW_TASK_SUBJECT';
            $options->template  = 'site/group/task.create';
            $options->params    = $params;

            // Set the system alerts
            $system                 = new stdClass();
            $system->uid            = $this->id;
            $system->title          = '';
            $system->actor_id       = $actor->id;
            $system->context_ids    = $data['id'];
            $system->context_type   = 'project';
            $system->type           = 'projects';
            $system->url            = $params->permalink;
            $system->image          = $this->getAvatar();

            FD::notify('projects.task.created', $targets, $options, $system);
        }

        if ($action == 'milestone.create')
        {
            $actor                  = FD::user($data['userId']);
            $params                 = new stdClass();
            $params->actor          = $actor->getName();
            $params->userName       = $actor->getName();
            $params->userLink       = $actor->getPermalink(false, true);
            $params->userAvatar     = $actor->getAvatar(SOCIAL_AVATAR_LARGE);
            $params->projectName      = $this->getName();
            $params->projectAvatar    = $this->getAvatar();
            $params->projectLink      = $this->getPermalink(false, true);
            $params->title          = $data['title'];
            $params->content        = $data['content'];
            $params->permalink      = $data['permalink'];

            // Send notification e-mail to the target
            $options            = new stdClass();
            $options->title     = 'COM_EASYSOCIAL_EMAILS_project_NEW_MILESTONE_SUBJECT';
            $options->template  = 'site/project/milestone.create';
            $options->params    = $params;

            // Set the system alerts
            $system                 = new stdClass();
            $system->uid            = $this->id;
            $system->title          = '';
            $system->actor_id       = $actor->id;
            $system->context_ids    = $data['id'];
            $system->context_type   = 'project';
            $system->type           = 'projects';
            $system->url            = $params->permalink;
            $system->image          = $this->getAvatar();

            FD::notify('projects.milestone.created', $targets, $options, $system);
        }

        if ($action == 'discussion.reply')
        {
            $actor                  = FD::user($data['userId']);
            $params                 = new stdClass();
            $params->actor          = $actor->getName();
            $params->userName       = $actor->getName();
            $params->userLink       = $actor->getPermalink(false, true);
            $params->userAvatar     = $actor->getAvatar(SOCIAL_AVATAR_LARGE);
            $params->projectName      = $this->getName();
            $params->projectAvatar    = $this->getAvatar();
            $params->projectLink      = $this->getPermalink(false, true);
            $params->title          = $data['title'];
            $params->content        = $data['content'];
            $params->permalink      = $data['permalink'];

            // Send notification e-mail to the target
            $options            = new stdClass();
            $options->title     = 'COM_EASYSOCIAL_EMAILS_project_NEW_REPLY_SUBJECT';
            $options->template  = 'site/project/discussion.reply';
            $options->params    = $params;

            // Set the system alerts
            $system                 = new stdClass();
            $system->uid            = $this->id;
            $system->title          = '';
            $system->actor_id       = $actor->id;
            $system->target_id      = $this->id;
            $system->context_type   = 'project';
            $system->type           = 'projects';
            $system->url            = $params->permalink;
            $system->context_ids    = $data['discussionId'];

            FD::notify('projects.discussion.reply', $targets, $options, $system);
        }

        if ($action == 'discussion.answered')
        {
            $actor                  = FD::user($data['userId']);
            $params                 = new stdClass();
            $params->actor          = $actor->getName();
            $params->userName       = $actor->getName();
            $params->userLink       = $actor->getPermalink(false, true);
            $params->userAvatar     = $actor->getAvatar(SOCIAL_AVATAR_LARGE);
            $params->projectName      = $this->getName();
            $params->projectAvatar    = $this->getAvatar();
            $params->projectLink      = $this->getPermalink(false, true);
            $params->title          = $data['title'];
            $params->content        = $data['content'];
            $params->permalink      = $data['permalink'];

            // Send notification e-mail to the target
            $options            = new stdClass();
            $options->title     = 'COM_EASYSOCIAL_EMAILS_project_DISCUSSION_ANSWERED_SUBJECT';
            $options->template  = 'site/project/discussion.answered';
            $options->params    = $params;

            // Set the system alerts
            $system                 = new stdClass();
            $system->uid            = $this->id;
            $system->title          = '';
            $system->actor_id       = $actor->id;
            $system->target_id      = $this->id;
            $system->context_type   = 'project';
            $system->type           = 'projects';
            $system->url            = $params->permalink;
            $system->context_ids    = $data['discussionId'];

            FD::notify('projects.discussion.answered', $targets, $options, $system);
        }

        if ($action == 'discussion.create')
        {
            $actor                  = FD::user($data['userId']);
            $params                 = new stdClass();
            $params->actor          = $actor->getName();
            $params->userName       = $actor->getName();
            $params->userLink       = $actor->getPermalink(false, true);
            $params->userAvatar     = $actor->getAvatar(SOCIAL_AVATAR_LARGE);
            $params->projectName      = $this->getName();
            $params->projectAvatar    = $this->getAvatar();
            $params->projectLink      = $this->getPermalink(false, true);
            $params->title          = $data['discussionTitle'];
            $params->content        = $data['discussionContent'];
            $params->permalink      = $data['permalink'];

            // Send notification e-mail to the target
            $options            = new stdClass();
            $options->title     = 'COM_EASYSOCIAL_EMAILS_project_NEW_DISCUSSION_SUBJECT';
            $options->template  = 'site/project/discussion.create';
            $options->params    = $params;

            // Set the system alerts
            $system                 = new stdClass();
            $system->uid            = $this->id;
            $system->title          = '';
            $system->actor_id       = $actor->id;
            $system->target_id      = $this->id;
            $system->context_type   = 'project';
            $system->type           = 'projects';
            $system->url            = $params->permalink;
            $system->context_ids    = $data['discussionId'];

            FD::notify('projects.discussion.create', $targets, $options, $system);
        }

        if ($action == 'file.uploaded')
        {
            $actor = FD::user($data['userId']);

            $params = new stdClass();

            // Set the actor
            $params->actor = $actor->getName();
            $params->actorLink = $actor->getPermalink(false, true);
            $params->actorAvatar = $actor->getAvatar(SOCIAL_AVATAR_LARGE);

            // Set the project attributes.
            $params->project = $this->getName();
            $params->projectAvatar = $this->getAvatar();
            $params->projectLink   = $this->getPermalink(false, true);

            // Set the file attributes
            $params->fileTitle = $data['fileName'];
            $params->fileSize = $data['fileSize'];
            $params->permalink = $data['permalink'];

            // Send notification e-mail to the target
            $options            = new stdClass();
            $options->title     = 'COM_EASYSOCIAL_EMAILS_project_NEW_FILE_SUBJECT';
            $options->template  = 'site/project/file.uploaded';
            $options->params    = $params;

            // Set the system alerts
            $system = new stdClass();
            $system->uid = $this->id;
            $system->actor_id = $actor->id;
            $system->target_id = $this->id;
            $system->context_type = 'file.project.uploaded';
            $system->context_ids = $data['fileId'];
            $system->type = 'projects';
            $system->url = $params->permalink;

            FD::notify('projects.updates', $targets, $options, $system);
        }

        if ($action == 'news.create') {

            $actor = FD::user($data['userId']);

            $params                 = new stdClass();
            $params->actor          = $actor->getName();
            $params->project          = $this->getName();
            $params->userName       = $actor->getName();
            $params->userLink       = $actor->getPermalink(false, true);
            $params->userAvatar     = $actor->getAvatar(SOCIAL_AVATAR_LARGE);
            $params->projectName      = $this->getName();
            $params->projectAvatar    = $this->getAvatar();
            $params->projectLink      = $this->getPermalink(false, true);
            $params->newsTitle      = $data['newsTitle'];
            $params->newsContent    = $data['newsContent'];
            $params->permalink      = $data['permalink'];

            // Send notification e-mail to the target
            $options            = new stdClass();
            $options->title     = 'COM_EASYSOCIAL_EMAILS_project_NEW_ANNOUNCEMENT_SUBJECT';
            $options->template  = 'site/project/news';
            $options->params    = $params;

            // Set the system alerts
            $system = new stdClass();
            $system->uid = $this->id;
            $system->actor_id = $actor->id;
            $system->target_id = $this->id;
            $system->context_type = 'projects';
            $system->context_ids = $data['newsId'];
            $system->type = 'projects';
            $system->url = $params->permalink;

            FD::notify('projects.news', $targets, $options, $system);
        }
    }

    /**
     * Gets project guest filter.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     * @param  integer    $userId The user id to filter against.
     * @return array              Array of SocialTableStreamFilter objects.
     */
    public function getFilters($userId)
    {
        return FD::model('projects')->getFilters($this->id, $userId);
    }

    /**
     * Invites a user to the project and does the appropriate follow actions.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     * @param  integer    $target The invited user id.
     * @param  integer    $actor  The actor user id.
     * @return boolean            True if successful.
     */
    public function invite($target, $actor = null)
    {
        $actor = FD::user($actor);
        $target = FD::user($target);

        $guest = FD::table('projectGuest');

        $guest->cluster_id = $this->id;
        $guest->uid = $target->id;
        $guest->type = SOCIAL_TYPE_USER;
        $guest->state = SOCIAL_project_GUEST_INVITED;
        $guest->invited_by = $actor->id;

        $guest->store();

        FD::points()->assign('projects.guest.invite', 'com_easysocial', $actor->id);

        $emailOptions = (object) array(
            'title' => 'COM_EASYSOCIAL_EMAILS_project_GUEST_INVITED_SUBJECT',
            'template' => 'site/project/guest.invited',
            'project' => $this->getName(),
            'projectName' => $this->getName(),
            'projectAvatar' => $this->getAvatar(),
            'projectLink' => $this->getPermalink(false, true),
            'invitorName' => $actor->getName(),
            'invitorLink' => $actor->getPermalink(false, true),
            'invitorAvatar' => $actor->getAvatar()
        );

        $systemOptions = (object) array(
            'uid' => $this->id,
            'actor_id' => $actor->id,
            'target_id' => $target->id,
            'context_type' => 'projects',
            'type' => 'projects',
            'url' => $this->getPermalink(true, false, 'item', false),
            'projectId' => $this->id
        );

        FD::notify('projects.guest.invited', array($target->id), $emailOptions, $systemOptions);

        return true;
    }

    /**
     * Returns the available seats left based on the guestLimit param - total guest.
     * @return integer  If guest limit is not unlimited, then returns the number of seats left. If guest limit is unlimited, then return -1;
     */
    public function seatsLeft()
    {
        $max = $this->getParams()->get('guestlimit', 0);

        if (empty($max)) {
            return -1;
        }

        // We do not want to count 'notgoing'
        $total = $this->getTotalGuests() - $this->getTotalNotGoing();

        return $max - $total;
    }

    /**
     * Below functions are only here in order to ensure compatibility with groups object behaviour.
     * These functions will redirect to the appropriate calls.
     */

    public function isAdmin($userid = null)
    {
        if (empty($userid)) {
            $userid = FD::user()->id;
        }

        $guest = $this->getGuest($userid);

        return $guest->isAdmin();
    }

    /**
     * Determines if the user is an owner of the project
     *
     * @since   1.3
     * @access  public
     * @param   string
     * @return
     */
    public function isOwner($id = null)
    {
        if (is_null($id)) {
            $id = FD::user()->id;
        }

        $guest = $this->getGuest($id);

        return $guest->isOwner();
    }

    public function isMember($userid = null)
    {
        if (empty($userid)) {
            $userid = FD::user()->id;
        }

        $guest = $this->getGuest($userid);

        return $guest->isGuest();
    }

    public function canViewItem($userid = null)
    {
        if (empty($userid)) {
            $userid = FD::user()->id;
        }

        $guest = $this->getGuest($userid);

        if (! $this->isGroupproject()) {
            if (!FD::user()->isSiteAdmin() && !$this->isOpen() && !$guest->isGuest()) {
                return false;
            }
        } else {
            if (!FD::user()->isSiteAdmin() && !$this->getGroup()->isMember()) {
                return false;
            }
        }

        return true;
    }

    public function isPendingMember($userid = null) {
        if (is_null($userid)) {
            $userid = FD::user()->id;
        }

        $guest = $this->getGuest($userid);

        return $guest->isPending();
    }

    /**
     * Retrieves a list of apps for an project
     *
     * @since   1.3
     * @access  public
     * @param   string
     * @return
     */
    public function getApps()
    {
        static $apps    = null;

        if( !$apps )
        {
            $model  = FD::model( 'Apps' );
            $data   = $model->getprojectApps( $this->id );

            $apps   = $data;
        }

        return $apps;
    }

    /**
     * Creates the owner node. This is an override on the parent class createOwner method to use projectGuest table object instead, and assign it into the guest property by default.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.2
     * @access public
     * @param  int  $userId The owner id.
     * @return bool         True if successful.
     */
    public function createOwner($userId = null)
    {
        if (empty($userId)) {
            $userId = FD::user()->id;
        }

        $guest = FD::table('projectGuest');

        $state = $guest->load(array('cluster_id' => $this->id, 'uid' => $userId, 'type' => SOCIAL_TYPE_USER));

        $guest->cluster_id = $this->id;
        $guest->uid = $userId;
        $guest->type = SOCIAL_TYPE_USER;
        $guest->state = SOCIAL_STATE_PUBLISHED;
        $guest->admin = true;
        $guest->owner = true;

        $guest->store();

        $this->guests[$userId] = $guest;

        $this->admins[] = $userId;

        $this->going[] = $userId;

        return $guest;
    }

    /**
     * Checks if this project is an all day project.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3.7
     * @access public
     * @return boolean   True if this project is an all day project.
     */
    public function isAllDay()
    {
        return $this->meta->isAllDay();
    }

    /**
     * Checks if this project is an all day project.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3.7
     * @access public
     * @return boolean   True if this project is an all day project.
     */
    public function getReminder()
    {
        return $this->meta->getReminder();
    }

    /**
     * As the logic is getting more complicated, we move it here so that it does not cloud the theme files.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3.7
     * @access public
     * @return string    The output of the start end of the project.
     */
    public function getStartEndDisplay($options = array())
    {
        // Get the 12h/24h settings
        $timeformat = FD::config()->get('projects.timeformat', '12h');

        $start = $this->getprojectStart();
        $end = $this->getprojectEnd();
        $timezone = $this->getprojectTimezone();

        $startString = $start->toSql(true);
        $endString = $end->toSql(true);

        list($startYMD, $startHMS) = explode(' ', $startString);
        list($endYMD, $endHMS) = explode(' ', $endString);

        // Available options
        // start = true/false (force show/hide start)
        // end = true/false (force show/hide end)
        // startdate = true/false (force show/hide startdate)
        // starttime = true/false (force show/hide starttime)
        // enddate = true/false (force show/hide enddate)
        // endtime = true/false (force show/hide endtime)

        // Each checking blocks has its own "default"

        $default = array(
            'start' => true,
            'end' => true,
            'startdate' => true,
            'starttime' => true,
            'enddate' => true,
            'endtime' => true
        );

        // If there is a timezone set for this project, display it
        if ($timezone) {
            $default['timezone'] = true;
        }

        // If start and end is the same, means there is no end, then we do not want to show end by default
        if ($startString == $endString) {

            $default['end'] = false;
        }

        // If start and end is on the same day, then we do not want to show the end date
        if ($startYMD == $endYMD) {
            $default['enddate'] = false;
        }

        if ($this->isAllDay()) {
            // If it is an all day project, then we do not want to show time by default
            $default['starttime'] = false;
            $default['endtime'] = false;

            // If it is all day then we only check the date part
            if ($startYMD == $endYMD) {
                // If it is on the same day then we do not want to show end by default

                $default['end'] = false;
            }
        }

        $options = array_merge($default, $options);

        // If startdate/starttime or enddate/endtime are both explicitly false, then we switch off that particular display
        if (!$options['startdate'] && !$options['starttime']) {
            $options['start'] = false;
        }
        if (!$options['enddate'] && !$options['endtime']) {
            $options['end'] = false;
        }

        // If start/end are both explicitly false, means there is nothing to display, then it is the callee's fault
        if ((!$options['start'] && !$options['end'])) {
            return;
        }

        // Determine the format
        $startFormat = JText::_('COM_EASYSOCIAL_DATE_' . ($options['startdate'] ? 'DMY' : '') . ($options['starttime'] ? ($timeformat == '12h' ? '12H' : '24H') : ''));
        $endFormat = JText::_('COM_EASYSOCIAL_DATE_' . ($options['enddate'] ? 'DMY' : '') . ($options['endtime'] ? ($timeformat == '12h' ? '12H' : '24H') : ''));

        $output = '';

        if ($options['start']) {
            $output .= $start->format($startFormat, true);
        }

        if ($options['end']) {
            if (!empty($output)) {
                $output .= ' - ';
            }

            $output .= $end->format($endFormat, true);
        }

        if (isset($options['timezone']) && $options['timezone']) {
            $output .= ' - ' . $timezone;
        }

        return $output;
    }

    /**
     * Checks if this project is a group project.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3.9
     * @access public
     * @return boolean   True if this project is a group project.
     */
    public function isGroupproject()
    {
        return $this->meta->isGroupproject();
    }

    /**
     * Returns the group that this project belongs to if it is a group project.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3.9
     * @access public
     * @return Mixed    SocialGroup if this project is a group project, false if it is not.
     */
    public function getGroup()
    {
        if (!$this->isGroupproject()) {
            return false;
        }

        return FD::group($this->getMeta('group_id'));
    }

    public function isRecurringproject()
    {
        return !empty($this->parent_id) && $this->parent_type == SOCIAL_TYPE_project;
    }

    public function hasRecurringprojects()
    {
        static $data = array();

        if (!isset($data[$this->id])) {
            $data[$this->id] = FD::model('projects')->getTotalprojects(array(
                'state' => SOCIAL_STATE_PUBLISHED,
                'parent_id' => $this->id
            )) > 0;
        }

        return $data[$this->id];
    }

    public function getRecurringprojects()
    {
        return FD::model('projects')->getprojects(array(
            'state' => SOCIAL_STATE_PUBLISHED,
            'parent_id' => $this->id
        ));
    }

    /**
     * display a rsvp button. used in module and widget.
     *
     * @author Sam <sam@stackideas.com>
     * @since  1.4
     * @access public
     * @return string
     */
    public function showRsvpButton($usePopbox = false)
    {
        // if over already, dont show button.
        if ($this->isOver()) {
            return '';
        }

        $guest = $this->getGuest();

        $defaultBtn = ' btn-default';
        $defaultBtnLabel = JText::_('COM_EASYSOCIAL_projectS_RSVP_TO_THIS_project');

        if ($guest->isGoing()) {
            $defaultBtn = ' btn-es-success';
            $defaultBtnLabel = JText::_('COM_EASYSOCIAL_projectS_GUEST_GOING');
        } else if ($guest->isMaybe()) {
            $defaultBtn = ' btn-es-info';
            $defaultBtnLabel = JText::_('COM_EASYSOCIAL_projectS_GUEST_MAYBE');
        } else if ($guest->isNotGoing()) {
            $defaultBtn = ' btn-es-danger';
            $defaultBtnLabel = JText::_('COM_EASYSOCIAL_projectS_GUEST_NOTGOING');
        }

        $theme = ES::themes();
        $theme->set('project', $this);
        $theme->set('guest', $guest);
        $theme->set('defaultBtn', $defaultBtn);
        $theme->set('defaultBtnLabel', $defaultBtnLabel);

        $filename = 'site/projects/rsvp.button';
        if ($usePopbox) {
            $filename .= '.popbox';
        }

        $output = $theme->output($filename);
        return $output;
    }
}
