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

FD::import('admin:/includes/apps/apps');

/**
 * Projects application for EasySocial.
 *
 * @since   1.3
 * @author  Mark Lee <mark@stackideas.com>
 */
class SocialUserAppProjects extends SocialAppItem
{
    /**
     * Responsible to return the favicon object.
     *
     * @since   1.3
     * @access  public
     */
    public function getFavIcon()
    {
        $obj = new stdClass();
        $obj->color = '#f06050';
        $obj->icon = 'fa-calendar';
        $obj->label = 'APP_USER_PROJECTS_STREAM_TOOLTIP';

        return $obj;
    }

    /**
     * Prepares the stream item.
     *
     * @since   1.3
     * @access  public
     */
    public function onPrepareStream(SocialStreamItem &$item, $includePrivacy = true)
    {
        // We only want to process related items
        if ($item->cluster_type !== SOCIAL_TYPE_PROJECT || empty($item->cluster_id)) {
            return;
        }

        // If the project is a invite-only project, then we do not want the stream to show on user's stream.
        // The stream still has to be generated because the same stream item is displayed on both user and project's stream.

        $project = FD::project($item->cluster_id);

        // Only show Social sharing in public project
        if ($project->type != SOCIAL_PROJECT_TYPE_PUBLIC) {
            $item->sharing = false;
        }

        // If the project is pending and is a new item, this means this project is created from the story form, and we want to show a message stating that the project is in pending
        if ($project->isPending() && !empty($item->isNew)) {
            $item->title = JText::_('APP_USER_PROJECTS_STREAM_PROJECT_PENDING_APPROVAL');
            $item->display = SOCIAL_STREAM_DISPLAY_MINI;
            return;
        }

        // If project is not published, we do not want to render the stream.
        if (!$project->isPublished()) {
            return;
        }

        if (!$project->isGroupProject()) {
            if ($project->isInviteOnly() && !$project->getGuest()->isGuest()) {
                return;
            }
        }

        if (!in_array($item->context, array('projects', 'guests', 'tasks', 'discussions'))) {
            return;
        }

        $item->display = SOCIAL_STREAM_DISPLAY_FULL;
        $item->color = '#f06050';
        $item->fonticon = 'fa fa-calendar';
        $item->label = FD::_('APP_USER_PROJECTS_STREAM_TOOLTIP', true);

        if ($project->isGroupProject()) {
            $item->label = FD::_('APP_USER_PROJECTS_GROUP_PROJECT_STREAM_TOOLTIP', true);
        }

        if ($item->context === 'projects' || $item->context === 'guests') {
            // Context are split into projects and guests
            // "projects" context are stream items that are related to project item
            // "guests" context are stream items that are related to guests of the project

            // From projects
            // stream_feature
            // stream_create
            // stream_update

            // From guests
            // stream_makeadmin
            // stream_going
            // stream_notgoing
            if (!$this->getParams()->get('stream_' . $item->verb, true)) {
                return;
            }

            // Project stream items should just be mini
            // $item->display = SOCIAL_STREAM_DISPLAY_MINI;

            // This goes to user/projects/streams in accordance to verb
            // $this->processStream($item);

            $this->set('project', $project);
            $this->set('actor', $item->actor);

            if ($project->isGroupProject()) {
                $this->set('group', $project->getGroup());
            }

            // streams/create.title
            // streams/feature.title
            // streams/makeadmin.title
            // streams/going.title
            // streams/notgoing.title
            // streams/update.title
            $item->title = parent::display('streams/projects/' . $item->verb . '.title');
            $item->content = parent::display('streams/projects/content');

            // APP_USER_PROJECTS_STREAM_OPENGRAPH_CREATE
            // APP_USER_PROJECTS_STREAM_OPENGRAPH_FEATURE
            // APP_USER_PROJECTS_STREAM_OPENGRAPH_MAKEADMIN
            // APP_USER_PROJECTS_STREAM_OPENGRAPH_UPDATE
            // APP_USER_PROJECTS_STREAM_OPENGRAPH_GOING
            // APP_USER_PROJECTS_STREAM_OPENGRAPH_NOTGOING
            // Append the opengraph tags
            $item->addOgDescription(JText::sprintf('APP_USER_PROJECTS_STREAM_OPENGRAPH_' . strtoupper($item->verb), $item->actor->getName(), $project->getName()));

            return;
        }

        if ($item->context === 'discussions') {
            $this->processDiscussionStream($item, $includePrivacy);
            return;
        }

        if ($item->context === 'tasks') {
            $this->processTaskStream($item, $includePrivacy);
            return;
        }
    }

    private function processDiscussionStream(SocialStreamItem &$item, $includePrivacy)
    {
        $app = FD::table('App');
        $app->load(array('group' => SOCIAL_TYPE_PROJECT, 'type' => SOCIAL_TYPE_APPS, 'element' => 'discussions'));

        $project = FD::project($item->cluster_id);

        $params = FD::registry($item->params);

        $discussion = FD::table('Discussion');
        $discussion->load($item->contextId);

        $permalink = FRoute::apps(array(
            'layout' => 'canvas',
            'customView' => 'item',
            'uid' => $project->getAlias(),
            'type' => SOCIAL_TYPE_PROJECT,
            'id' => $app->getAlias(),
            'discussionId' => $discussion->id
        ));

        $this->set('actor', $item->actor);
        $this->set('permalink', $permalink);
        $this->set('discussion', $discussion);
        $this->set('project', $project);

        $files = $discussion->hasFiles();

        $this->set('files', $files);

        // Do not allow user to repost discussions
        $item->repost = false;

        $content = '';

        if ($item->verb === 'create') {

            $content = $this->formatContent($discussion);
            $this->set('content', $content);
        }

        if ($item->verb === 'reply' || $item->verb === 'answered') {
            $reply = FD::table('Discussion');
            $reply->load($params->get('reply')->id);

            $reply->author = FD::user($reply->created_by);

            $content = $this->formatContent($reply);

            $this->set('reply', $reply);

            $this->set('content', $content);
        }

        if ($item->verb === 'answered') {
            // We want it to be SOCIAL_STREAM_DISPLAY_MINI but we also want the accepted answer to show as well.
            // Hence we leave the display to full but we disable comments, likes, sharing and repost
            $item->comments = false;
            $item->likes = false;
            $item->sharing = false;
        }

        if ($item->verb === 'locked') {
            $item->display = SOCIAL_STREAM_DISPLAY_MINI;
        }

        $item->title = parent::display('streams/discussions/' . $item->verb . '.title');
        $item->content = parent::display('streams/discussions/' . $item->verb . '.content');

        // Append the opengraph tags
        $item->addOgDescription(JText::sprintf('APP_USER_PROJECTS_STREAM_DISCUSSION_OPENGRAPH_' . strtoupper($item->verb), $item->actor->getName(), $project->getName()));
    }


    /**
     * Internal method to format the discussions
     *
     * @since   1.3
     * @access  public
     * @param   string
     * @return
     */
    private function formatContent( $item )
    {
        // Get the app params so that we determine which stream should be appearing
        $app = $this->getApp();
        $params = $app->getParams();

        $content = $item->content;

        $content = FD::string()->parseBBCode( $content , array( 'code' => true , 'escape' => false ) );

        // Remove [file] from contents
        $content = $item->removeFiles( $content );

        $maxlength = $params->get('stream_discussion_maxlength', 250);
        if ($maxlength) {
            // lets do a simple content truncation here.
            $content = strip_tags($content);
            $content = strlen($content) > $maxlength ? JString::substr($content, 0, $maxlength ) . JText::_('COM_EASYSOCIAL_ELLIPSES') : $content ;
        }

        return $content;
    }

    private function processTaskStream(SocialStreamItem &$item, $includePrivacy)
    {
        $app = FD::table('App');
        $app->load(array('group' => SOCIAL_TYPE_PROJECT, 'type' => SOCIAL_TYPE_APPS, 'element' => 'tasks'));

        $project = FD::project($item->cluster_id);

        $params = FD::registry($item->params);

        // Get the milestone
        $milestone = FD::table('Milestone');
        $milestone->bind($params->get('milestone'));

        $permalink = FRoute::apps(array(
            'layout' => 'canvas',
            'customView' => 'item',
            'uid' => $project->getAlias(),
            'type' => SOCIAL_TYPE_PROJECT,
            'id' => $app->getAlias(),
            'milestoneId' => $milestone->id
        ));

        // Do not allow reposting on milestone items
        $item->repost = false;

        if ($item->verb == 'createTask') {
            $items = $params->get('tasks');
            $tasks = array();

            foreach ($items as $i) {
                $task = FD::table('Task');

                // We don't do bind here because we need to latest state from the database
                // THe cached params might be an old data.
                $task->load($i->id);

                $tasks[] = $task;
            }

            $this->set('tasks', $tasks);
            $this->set('total', count($tasks));
        }

        $this->set('project', $project);
        $this->set('stream', $item);

        $this->set('milestone', $milestone);
        $this->set('permalink', $permalink);

        $this->set('actor', $item->actor);

        // streams/tasks/createTask.title
        // streams/tasks/createTask.content
        // streams/tasks/createMilestone.title
        // streams/tasks/createMilestone.content

        $item->title = parent::display('streams/tasks/' . $item->verb . '.title');
        $item->content = parent::display('streams/tasks/' . $item->verb . '.content');

        if ($item->verb === 'createMilestone') {
            // Append the opengraph tags
            $item->addOgDescription(JText::sprintf('APP_USER_PROJECTS_TASKS_STREAM_OPENGRAPH_CREATED_MILESTONE', $item->actor->getName(), $milestone->title, $project->getName()));
        }

        if ($item->verb === 'createTask') {
            // Append the opengraph tags
            $item->addOgDescription(JText::sprintf(FD::string()->computeNoun('APP_USER_PROJECTS_TASKS_STREAM_OPENGRAPH_ADDED_TASK', count($tasks)), $item->actor->getName(), count($tasks), $milestone->title, $project->getName()));
        }
    }

    /**
     * Prepares what should appear on user's story form.
     *
     * @since  1.3
     * @access public
     */
    public function onPrepareStoryPanel($story)
    {
        // We only allow project creation on dashboard, which means if the story target and current logged in user is different, then we don't show this
        // Empty target is also allowed because it means no target.
        if (!empty($story->target) && $story->target != FD::user()->id) {
            return;
        }

        $params = $this->getParams();

        // Determine if we should attach ourselves here.
        if (!$params->get('story_project', true)) {
            return;
        }

        // Ensure that the user has access to create projects
        if (!$this->my->getAccess()->get('projects.create')) {
            return;
        }

        // Ensure that projects is enabled
        if (!FD::config()->get('projects.enabled')) {
            return;
        }

        // Create plugin object
        $plugin = $story->createPlugin('project', 'panel');

        // Get the theme class
        $theme = FD::themes();

        // Get the available project category
        $categories = FD::model('ProjectCategories')->getCreatableCategories(FD::user()->getProfile()->id);

        $theme->set('categories', $categories);

        $plugin->button->html = $theme->output('apps/user/projects/story/panel.button');
        $plugin->content->html = $theme->output('apps/user/projects/story/panel.content');

        $script = FD::get('Script');
        $plugin->script = $script->output('apps:/user/projects/story');

        return $plugin;
    }

    public function onBeforeStorySave(&$template, &$stream, &$content)
    {
        $params = $this->getParams();

        // Determine if we should attach ourselves here.
        if (!$params->get('story_project', true)) {
            return;
        }

        $in = FD::input();

        $title = $in->getString('project_title');
        $description = $in->getString('project_description');
        $categoryid = $in->getInt('project_category');
        $start = $in->getString('project_start');
        $end = $in->getString('project_end');
        $timezone = $in->getString('project_timezone');

        // If no category id, then we don't proceed
        if (empty($categoryid)) {
            return;
        }

        // Perhaps in the future we use FD::model('Project')->createProject() instead.
        // For now just hardcode it here to prproject field triggering and figuring out how to punch data into the respective field data because the form is not rendered through field trigger.

        $my = FD::user();

        $project = FD::project();

        $project->title = $title;

        $project->description = $description;

        // Set a default params for this project first
        $project->params = '{"photo":{"albums":true},"news":true,"discussions":true,"allownotgoingguest":false,"allowmaybe":true,"guestlimit":0}';

        $project->type = SOCIAL_PROJECT_TYPE_PUBLIC;
        $project->creator_uid = $my->id;
        $project->creator_type = SOCIAL_TYPE_USER;
        $project->category_id = $categoryid;
        $project->cluster_type = SOCIAL_TYPE_PROJECT;
        $project->alias = FD::model('Projects')->getUniqueAlias($title);
        $project->created = FD::date()->toSql();
        $project->key = md5($project->created . $my->password . uniqid());

        $project->state = SOCIAL_CLUSTER_PENDING;

        if ($my->isSiteAdmin() || !$my->getAccess()->get('projects.moderate')) {
            $project->state = SOCIAL_CLUSTER_PUBLISHED;
        }

        // Trigger apps
        FD::apps()->load(SOCIAL_TYPE_USER);

        $dispatcher  = FD::dispatcher();
        $triggerArgs = array(&$project, &$my, true);

        // @trigger: onProjectBeforeSave
        $dispatcher->trigger(SOCIAL_TYPE_USER, 'onProjectBeforeSave', $triggerArgs);

        $state = $project->save();

        // Notifies admin when a new project is created
        if ($project->state === SOCIAL_CLUSTER_PENDING || !$my->isSiteAdmin()) {
            FD::model('Projects')->notifyAdmins($project);
        }

        // Set the meta for start end timezone
        $meta = $project->meta;
        $meta->cluster_id = $project->id;
        $meta->start = FD::date($start)->toSql();
        $meta->end = FD::date($end)->toSql();
        $meta->timezone = $timezone;

        $meta->store();

        // Recreate the project object
        $project = FD::project($project->id);

        // Create a new owner object
        $project->createOwner($my->id);

        // @trigger: onProjectAfterSave
        $triggerArgs = array(&$project, &$my, true);
        $dispatcher->trigger(SOCIAL_TYPE_USER, 'onProjectAfterSave' , $triggerArgs);

        // Due to inconsistency, we don't use SOCIAL_TYPE_PROJECT.
        // Instead we use "projects" because app elements are named with 's', namely users, groups, projects.
        $template->context_type = 'projects';

        $template->context_id = $project->id;
        $template->cluster_access = $project->type;
        $template->cluster_type = $project->cluster_type;
        $template->cluster_id = $project->id;

        $params = array(
            'project' => $project
        );

        $template->setParams(FD::json()->encode($params));
    }

    public function onAfterLikeSave($likes)
    {
        $segments = explode('.', $likes->type);

        if (count($segments) !== 3 || $segments[1] !== SOCIAL_TYPE_PROJECT) {
            return;
        }

        list($element, $group, $verb) = explode('.', $likes->type);

        // Get the actor
        $actor = FD::user($likes->created_by);

        if ($element === 'projects') {
            // Verbs
            // feature
            // create
            // update

            $project = FD::project($likes->uid);

            $stream = FD::table('Stream');
            $stream->load($likes->stream_id);

            $owner = FD::user($stream->actor_id);

            // APP_USER_PROJECTS_EMAILS_FEATURE_LIKE_ITEM_SUBJECT
            // APP_USER_PROJECTS_EMAILS_CREATE_LIKE_ITEM_SUBJECT
            // APP_USER_PROJECTS_EMAILS_UPDATE_LIKE_ITEM_SUBJECT
            // APP_USER_PROJECTS_EMAILS_FEATURE_LIKE_INVOLVED_SUBJECT
            // APP_USER_PROJECTS_EMAILS_CREATE_LIKE_INVOLVED_SUBJECT
            // APP_USER_PROJECTS_EMAILS_UPDATE_LIKE_INVOLVED_SUBJECT

            // apps/user/projects/feature.like.item
            // apps/user/projects/create.like.item
            // apps/user/projects/update.like.item
            // apps/user/projects/feature.like.involved
            // apps/user/projects/create.like.involved
            // apps/user/projects/update.like.involved

            $emailOptions = array(
                'title' => 'APP_USER_PROJECTS_EMAILS_' . strtoupper($verb) . '_LIKE_ITEM_SUBJECT',
                'template' => 'apps/user/projects/' . $verb . '.like.item',
                'permalink' => FRoute::stream(array('layout' => 'item', 'id' => $likes->stream_id, 'external' => true)),
                'actor' => $actor->getName(),
                'actorAvatar' => $actor->getAvatar(SOCIAL_AVATAR_SQUARE),
                'actorLink' => $actor->getPermalink(true, true)
            );

            $systemOptions = array(
                'context_type' => $likes->type,
                'url' => FRoute::stream(array('layout' => 'item', 'id' => $likes->stream_id, 'sef' => false)),
                'actor_id' => $likes->created_by,
                'uid' => $likes->uid,
                'aggregate' => true
            );

            // Notify the owner first
            if ($likes->created_by != $owner->id) {
                FD::notify('likes.item', array($owner->id), $emailOptions, $systemOptions);
            }

            // Get a list of recipients to be notified for this stream item
            // We exclude the guest and the actor of the like here
            $recipients = $this->getStreamNotificationTargets($likes->uid, $element, $group, $verb, array(), array($owner->id, $likes->created_by));

            $emailOptions['title'] = 'APP_USER_PROJECTS_EMAILS_' . strtoupper($verb) . '_LIKE_INVOLVED_SUBJECT';
            $emailOptions['template'] = 'apps/user/projects/' . $verb . '.like.involved';

            // Notify other participating users
            FD::notify('likes.involved', $recipients, $emailOptions, $systemOptions);
        }

        if ($element === 'guests') {
            // Verbs
            // makeadmin
            // going
            // notgoing

            $guest = FD::table('ProjectGuest');
            $guest->load($likes->uid);

            $project = FD::project($guest->cluster_id);

            $stream = FD::table('Stream');
            $stream->load($likes->stream_id);

            $owner = FD::user($stream->actor_id);

            // APP_USER_PROJECTS_GUESTS_EMAILS_MAKEADMIN_LIKE_ITEM_SUBJECT
            // APP_USER_PROJECTS_GUESTS_EMAILS_GOING_LIKE_ITEM_SUBJECT
            // APP_USER_PROJECTS_GUESTS_EMAILS_NOTGOING_LIKE_ITEM_SUBJECT
            // APP_USER_PROJECTS_GUESTS_EMAILS_MAKEADMIN_LIKE_INVOLVED_SUBJECT
            // APP_USER_PROJECTS_GUESTS_EMAILS_GOING_LIKE_INVOLVED_SUBJECT
            // APP_USER_PROJECTS_GUESTS_EMAILS_NOTGOING_LIKE_INVOLVED_SUBJECT

            // apps/user/projects/guest.makeadmin.like.item
            // apps/user/projects/guest.going.like.item
            // apps/user/projects/guest.notgoing.like.item
            // apps/user/projects/guest.makeadmin.like.involved
            // apps/user/projects/guest.going.like.involved
            // apps/user/projects/guest.notgoing.like.involved

            $emailOptions = array(
                'title' => 'APP_USER_PROJECTS_GUESTS_EMAILS_' . strtoupper($verb) . '_LIKE_ITEM_SUBJECT',
                'template' => 'apps/user/projects/guest.' . $verb . '.like.item',
                'permalink' => FRoute::stream(array('layout' => 'item', 'id' => $likes->stream_id, 'external' => true)),
                'actor' => $actor->getName(),
                'actorAvatar' => $actor->getAvatar(SOCIAL_AVATAR_SQUARE),
                'actorLink' => $actor->getPermalink(true, true)
            );

            $systemOptions = array(
                'context_type' => $likes->type,
                'url' => FRoute::stream(array('layout' => 'item', 'id' => $likes->stream_id, 'sef' => false)),
                'actor_id' => $likes->created_by,
                'uid' => $likes->uid,
                'aggregate' => true
            );

            // Notify the owner first
            if ($likes->created_by != $owner->id) {
                FD::notify('likes.item', array($owner->id), $emailOptions, $systemOptions);
            }

            // Get a list of recipients to be notified for this stream item
            // We exclude the guest and the actor of the like here
            $recipients = $this->getStreamNotificationTargets($likes->uid, $element, $group, $verb, array(), array($owner->id, $likes->created_by));

            $emailOptions['title'] = 'APP_USER_PROJECTS_GUESTS_EMAILS_' . strtoupper($verb) . '_LIKE_INVOLVED_SUBJECT';
            $emailOptions['template'] = 'apps/user/projects/guest.' . $verb . '.like.involved';

            // Notify other participating users
            FD::notify('likes.involved', $recipients, $emailOptions, $systemOptions);
        }

        if ($element === 'discussions') {

            // Uses app/project/discussions onAfterLikeSave logic and language strings since it is the same

            // Get the discussion object since it's tied to the stream
            $discussion = FD::table('Discussion');
            $discussion->load($likes->uid);

            // APP_PROJECT_DISCUSSIONS_EMAILS_CREATE_LIKE_ITEM_SUBJECT
            // APP_PROJECT_DISCUSSIONS_EMAILS_CREATE_LIKE_INVOLVED_SUBJECT
            // APP_PROJECT_DISCUSSIONS_EMAILS_REPLY_LIKE_ITEM_SUBJECT
            // APP_PROJECT_DISCUSSIONS_EMAILS_REPLY_LIKE_INVOLVED_SUBJECT

            // apps/project/discussions/create.like.item
            // apps/project/discussions/create.like.involved
            // apps/project/discussions/reply.like.item
            // apps/project/discussions/reply.like.involved

            $emailOptions = array(
                'title' => 'APP_PROJECT_DISCUSSIONS_EMAILS_' . strtoupper($verb) . '_LIKE_ITEM_SUBJECT',
                'template' => 'apps/project/discussions/' . $verb . '.like.item',
                'permalink' => FRoute::stream(array('layout' => 'item', 'id' => $likes->stream_id, 'external' => true)),
                'actor' => $actor->getName(),
                'actorAvatar' => $actor->getAvatar(SOCIAL_AVATAR_SQUARE),
                'actorLink' => $actor->getPermalink(true, true)
            );

             $systemOptions  = array(
                'context_type' => $likes->type,
                'url' => FRoute::stream(array('layout' => 'item', 'id' => $likes->stream_id, 'sef' => false)),
                'actor_id' => $likes->created_by,
                'uid' => $likes->uid,
                'aggregate' => true
            );

             // Notify the owner first
             if ($likes->created_by != $discussion->created_by) {
                 FD::notify('likes.item', array($discussion->created_by), $emailOptions, $systemOptions);
             }

             // Get a list of recipients to be notified for this stream item
             // We exclude the owner of the discussion and the actor of the like here
             $recipients = $this->getStreamNotificationTargets($likes->uid, $element, $group, $verb, array(), array($discussion->created_by, $likes->created_by));

             $emailOptions['title'] = 'APP_PROJECT_DISCUSSIONS_EMAILS_' . strtoupper($verb) . '_LIKE_INVOLVED_SUBJECT';
             $emailOptions['template'] = 'apps/project/discussions/' . $verb . '.like.involved';

             // Notify other participating users
             FD::notify('likes.involved', $recipients, $emailOptions, $systemOptions);
        }

        if ($element === 'tasks') {
            // Uses app/project/tasks onAfterLikeSave logic and language strings since it is the same

            $identifier = $verb == 'createMilestone' ? 'milestone' : 'task';

            // Get the milestone/task table
            $table = FD::table($identifier);
            $table->load($likes->uid);

            // Get the owner
            $owner = FD::user($table->owner_id);

            // Get the project
            $project = FD::project($table->uid);

            $recipients = $this->getStreamNotificationTargets($likes->uid, $element, $group, $verb, array(), array($owner->id, $likes->created_by));

            // APP_PROJECT_TASKS_EMAILS_LIKE_YOUR_MILESTONE_SUBJECT
            // APP_PROJECT_TASKS_EMAILS_LIKE_YOUR_TASK_SUBJECT
            // APP_PROJECT_TASKS_EMAILS_LIKE_A_MILESTONE_SUBJECT
            // APP_PROJECT_TASKS_EMAILS_LIKE_A_TASK_SUBJECT

            // apps/project/tasks/like.milestone
            // apps/project/tasks/like.task
            // apps/project/tasks/like.milestone.involved
            // apps/project/tasks/like.task.involved

            $emailOptions = array(
                'title' => 'APP_PROJECT_TASKS_EMAILS_LIKE_YOUR_' . strtoupper($identifier) . '_SUBJECT',
                'template' => 'apps/project/tasks/like.' . $identifier,
                'permalink' => FRoute::stream(array('layout' => 'item', 'id' => $likes->stream_id, 'external' => true)),
                'actor' => $actor->getName(),
                'actorAvatar' => $actor->getAvatar(SOCIAL_AVATAR_SQUARE),
                'actorLink' => $actor->getPermalink(true, true)
            );

            $systemOptions = array(
                'context_type' => $likes->type,
                'url' => FRoute::stream(array('layout' => 'item', 'id' => $likes->stream_id, 'sef' => false)),
                'actor_id' => $likes->created_by,
                'uid' => $likes->uid,
                'aggregate' => true
            );

            // Notify the owner first
            if ($likes->created_by != $owner->id) {
                FD::notify('likes.item', array($owner->id), $emailOptions, $systemOptions);
            }

            // Get a list of recipients to be notified for this stream item
            // We exclude the owner of the note and the actor of the like here
            $recipients = $this->getStreamNotificationTargets($likes->uid, $element, $group, $verb, array(), array($owner->id, $likes->created_by));

            $emailOptions['title'] = 'APP_PROJECT_TASKS_EMAILS_LIKE_A_' . strtoupper($identifier) . '_SUBJECT';
            $emailOptions['template'] = 'apps/project/tasks/like.' . $identifier . '.involved';

            // Notify other participating users
            FD::notify('likes.involved', $recipients, $emailOptions, $systemOptions);
        }
    }

    public function onAfterCommentSave($comment)
    {
        $segments = explode('.', $comment->element);

        if (count($segments) !== 3 || $segments[1] !== SOCIAL_TYPE_PROJECT) {
            return;
        }

        list($element, $group, $verb) = explode('.', $comment->element);

        // Get the actor
        $actor = FD::user($comment->created_by);

        if ($element === 'projects') {
            $project = FD::project($comment->uid);

            $stream = FD::table('Stream');
            $stream->load($comment->stream_id);

            $owner = FD::user($stream->actor_id);

            // APP_USER_PROJECTS_EMAILS_FEATURE_COMMENT_ITEM_SUBJECT
            // APP_USER_PROJECTS_EMAILS_CREATE_COMMENT_ITEM_SUBJECT
            // APP_USER_PROJECTS_EMAILS_UPDATE_COMMENT_ITEM_SUBJECT
            // APP_USER_PROJECTS_EMAILS_FEATURE_COMMENT_INVOLVED_SUBJECT
            // APP_USER_PROJECTS_EMAILS_CREATE_COMMENT_INVOLVED_SUBJECT
            // APP_USER_PROJECTS_EMAILS_UPDATE_COMMENT_INVOLVED_SUBJECT

            // apps/user/projects/feature.comment.item
            // apps/user/projects/create.comment.item
            // apps/user/projects/update.comment.item
            // apps/user/projects/feature.comment.involved
            // apps/user/projects/create.comment.involved
            // apps/user/projects/update.comment.involved

            $emailOptions = array(
                'title' => 'APP_USER_PROJECTS_EMAILS_' . strtoupper($verb) . '_COMMENT_ITEM_SUBJECT',
                'template' => 'apps/user/projects/' . $verb . '.comment.item',
                'permalink' => $stream->getPermalink(true, true),
                'actor' => $actor->getName(),
                'actorAvatar' => $actor->getAvatar(SOCIAL_AVATAR_SQUARE),
                'actorLink' => $actor->getPermalink(true, true),
                'comment' => $comment->comment
            );

             $systemOptions  = array(
                'context_type' => $comment->element,
                'content' => $comment->comment,
                'url' => $stream->getPermalink(false, false, false),
                'actor_id' => $comment->created_by,
                'uid' => $comment->uid,
                'aggregate' => true
            );

             // Notify the owner first
             if ($comment->created_by != $owner->id) {
                FD::notify('comments.item', array($owner->id), $emailOptions, $systemOptions);
             }

             // Get a list of recipients to be notified for this stream item
             // We exclude the owner of the discussion and the actor of the comment here
             $recipients = $this->getStreamNotificationTargets($comment->uid, $element, $group, $verb, array(), array($owner->id, $comment->created_by));

             $emailOptions['title'] = 'APP_USER_PROJECTS_EMAILS_' . strtoupper($verb) . '_COMMENT_INVOLVED_SUBJECT';
             $emailOptions['template'] = 'apps/user/projects/' . $verb . '.comment.involved';

             // Notify other participating users
             FD::notify('comments.involved', $recipients, $emailOptions, $systemOptions);
        }

        if ($element === 'guests') {
            $guest = FD::table('ProjectGuest');
            $guest->load($comment->uid);

            $project = FD::project($guest->cluster_id);

            $stream = FD::table('Stream');
            $stream->load($comment->stream_id);

            $owner = FD::user($stream->actor_id);

            // APP_USER_PROJECTS_GUESTS_EMAILS_MAKEADMIN_COMMENT_ITEM_SUBJECT
            // APP_USER_PROJECTS_GUESTS_EMAILS_GOING_COMMENT_ITEM_SUBJECT
            // APP_USER_PROJECTS_GUESTS_EMAILS_NOTGOING_COMMENT_ITEM_SUBJECT
            // APP_USER_PROJECTS_GUESTS_EMAILS_MAKEADMIN_COMMENT_INVOLVED_SUBJECT
            // APP_USER_PROJECTS_GUESTS_EMAILS_GOING_COMMENT_INVOLVED_SUBJECT
            // APP_USER_PROJECTS_GUESTS_EMAILS_NOTGOING_COMMENT_INVOLVED_SUBJECT

            // apps/user/projects/guest.makeadmin.comment.item
            // apps/user/projects/guest.going.comment.item
            // apps/user/projects/guest.notgoing.comment.item
            // apps/user/projects/guest.makeadmin.comment.involved
            // apps/user/projects/guest.going.comment.involved
            // apps/user/projects/guest.notgoing.comment.involved

            $emailOptions = array(
                'title' => 'APP_USER_PROJECTS_GUESTS_EMAILS_' . strtoupper($verb) . '_COMMENT_ITEM_SUBJECT',
                'template' => 'apps/user/projects/guest.' . $verb . '.comment.item',
                'permalink' => $stream->getPermalink(true, true),
                'actor' => $actor->getName(),
                'actorAvatar' => $actor->getAvatar(SOCIAL_AVATAR_SQUARE),
                'actorLink' => $actor->getPermalink(true, true),
                'comment' => $comment->comment
            );

             $systemOptions  = array(
                'context_type' => $comment->element,
                'content' => $comment->comment,
                'url' => $stream->getPermalink(false, false, false),
                'actor_id' => $comment->created_by,
                'uid' => $comment->uid,
                'aggregate' => true
            );

             // Notify the owner first
             if ($comment->created_by != $owner->id) {
                FD::notify('comments.item', array($owner->id), $emailOptions, $systemOptions);
             }

             // Get a list of recipients to be notified for this stream item
             // We exclude the owner of the discussion and the actor of the comment here
             $recipients = $this->getStreamNotificationTargets($comment->uid, $element, $group, $verb, array(), array($owner->id, $comment->created_by));

             $emailOptions['title'] = 'APP_USER_PROJECTS_GUESTS_EMAILS_' . strtoupper($verb) . '_COMMENT_INVOLVED_SUBJECT';
             $emailOptions['template'] = 'apps/user/projects/guest.' . $verb . '.comment.involved';

             // Notify other participating users
             FD::notify('comments.involved', $recipients, $emailOptions, $systemOptions);
        }

        if ($element === 'discussions') {

            // Uses app/project/discussions onAfterCommentSave logic and language strings since it is the same

            $stream = FD::table('Stream');
            $stream->load($comment->stream_id);

            // Get the discussion object since it's tied to the stream
            $discussion = FD::table('Discussion');
            $discussion->load($comment->uid);

            // APP_PROJECT_DISCUSSIONS_EMAILS_CREATE_COMMENT_ITEM_SUBJECT
            // APP_PROJECT_DISCUSSIONS_EMAILS_CREATE_COMMENT_INVOLVED_SUBJECT
            // APP_PROJECT_DISCUSSIONS_EMAILS_REPLY_COMMENT_ITEM_SUBJECT
            // APP_PROJECT_DISCUSSIONS_EMAILS_REPLY_COMMENT_INVOLVED_SUBJECT

            // apps/project/discussions/create.comment.item
            // apps/project/discussions/create.comment.involved
            // apps/project/discussions/reply.comment.item
            // apps/project/discussions/reply.comment.involved

            $emailOptions = array(
                'title' => 'APP_PROJECT_DISCUSSIONS_EMAILS_' . strtoupper($verb) . '_COMMENT_ITEM_SUBJECT',
                'template' => 'apps/project/discussions/' . $verb . '.comment.item',
                'permalink' => $stream->getPermalink(true, true),
                'actor' => $actor->getName(),
                'actorAvatar' => $actor->getAvatar(SOCIAL_AVATAR_SQUARE),
                'actorLink' => $actor->getPermalink(true, true),
                'comment' => $comment->comment
            );

             $systemOptions  = array(
                'context_type' => $comment->element,
                'content' => $comment->comment,
                'url' => $stream->getPermalink(false, false, false),
                'actor_id' => $comment->created_by,
                'uid' => $comment->uid,
                'aggregate' => true
            );

             // Notify the owner first
             if ($comment->created_by != $discussion->created_by) {
                FD::notify('comments.item', array($discussion->created_by), $emailOptions, $systemOptions);
             }

             // Get a list of recipients to be notified for this stream item
             // We exclude the owner of the discussion and the actor of the comment here
             $recipients = $this->getStreamNotificationTargets($comment->uid, $element, $group, $verb, array(), array($discussion->created_by, $comment->created_by));

             $emailOptions['title'] = 'APP_PROJECT_DISCUSSIONS_EMAILS_' . strtoupper($verb) . '_COMMENT_INVOLVED_SUBJECT';
            $emailOptions['template'] = 'apps/project/discussions/' . $verb . '.comment.involved';

             // Notify other participating users
             FD::notify('comments.involved', $recipients, $emailOptions, $systemOptions);
        }

        if ($element === 'tasks') {
            // Uses app/project/tasks onAfterCommentSave logic and language strings since it is the same

            $identifier = $verb == 'createMilestone' ? 'milestone' : 'task';

            // Get the milestone/task table
            $table = FD::table($identifier);
            $table->load($comment->uid);

            // Get the owner
            $owner = FD::user($table->owner_id);

            // Get the project
            $project = FD::project($table->uid);

            $recipients = $this->getStreamNotificationTargets($comment->uid, $element, $group, $verb, array(), array($owner->id, $comment->created_by));

            // APP_PROJECT_TASKS_EMAILS_COMMENTED_ON_YOUR_MILESTONE_SUBJECT
            // APP_PROJECT_TASKS_EMAILS_COMMENTED_ON_YOUR_TASK_SUBJECT
            // APP_PROJECT_TASKS_EMAILS_COMMENTED_ON_A_MILESTONE_SUBJECT
            // APP_PROJECT_TASKS_EMAILS_COMMENTED_ON_A_TASK_SUBJECT

            // apps/project/tasks/comment.milestone
            // apps/project/tasks/comment.task
            // apps/project/tasks/comment.milestone.involved
            // apps/project/tasks/comment.task.involved

            $emailOptions = array(
                'title' => 'APP_PROJECT_TASKS_EMAILS_COMMENTED_ON_YOUR_' . strtoupper($identifier) . '_SUBJECT',
                'template' => 'apps/project/tasks/comment.' . $identifier,
                'permalink' => FRoute::stream(array('layout' => 'item', 'id' => $comment->stream_id, 'external' => true)),
                'actor' => $actor->getName(),
                'actorAvatar' => $actor->getAvatar(SOCIAL_AVATAR_SQUARE),
                'actorLink' => $actor->getPermalink(true, true),
                'comment' => $comment->comment
            );

            $systemOptions = array(
                'context_type' => $comment->element,
                'content' => $comment->element,
                'url' => FRoute::stream(array('layout' => 'item', 'id' => $comment->stream_id, 'sef' => false)),
                'actor_id' => $comment->created_by,
                'uid' => $comment->uid,
                'aggregate' => true
            );

            // Notify the owner first
            if ($comment->created_by != $owner->id) {
                FD::notify('comments.item', array($owner->id), $emailOptions, $systemOptions);
            }

            // Get a list of recipients to be notified for this stream item
            // We exclude the owner of the note and the actor of the like here
            $recipients = $this->getStreamNotificationTargets($comment->uid, $element, $group, $verb, array(), array($owner->id, $comment->created_by));

            $emailOptions['title'] = 'APP_PROJECT_TASKS_EMAILS_COMMENTED_ON_A_' . strtoupper($identifier) . '_SUBJECT';
            $emailOptions['template'] = 'apps/project/tasks/comment.' . $identifier . '.involved';

            // Notify other participating users
            FD::notify('comments.involved', $recipients, $emailOptions, $systemOptions);
        }
    }

    public function onBeforeGetStream(array &$options, $view = '')
    {
        if ($view != 'dashboard') {
            return;
        }

        $allowedContext = array('projects','story','photos', 'tasks', 'discussions', 'guests');

        if (is_array($options['context']) && in_array('projects', $options['context'])){
            // we need to make sure the stream return only cluster stream.
            $options['clusterType'] = SOCIAL_TYPE_PROJECT;
        } else if ($options['context'] === 'projects') {
            $options['context']     = $allowedContext;
            $options['clusterType'] = SOCIAL_TYPE_PROJECT;
        }
    }

    public function onStreamVerbExclude(&$exclude)
    {
        $params = $this->getParams();

        $excludeVerb = array();

        // From projects
        // stream_feature
        // stream_create
        // stream_update

        if (!$params->get('stream_feature', true)) {
            $excludeVerb[] = 'feature';
        }

        if (!$params->get('stream_create', true)) {
            $excludeVerb[] = 'create';
        }

        if (!$params->get('stream_update', true)) {
            $excludeVerb[] = 'update';
        }

        if (!empty($excludeVerb)) {
            $exclude['projects'] = $excludeVerb;
        }

        $excludeVerb = array();

        // From guests
        // stream_makeadmin
        // stream_going
        // stream_notgoing

        if (!$params->get('stream_makeadmin', true)) {
            $excludeVerb[] = 'makeadmin';
        }

        if (!$params->get('stream_going', true)) {
            $excludeVerb[] = 'going';
        }

        if (!$params->get('stream_notgoing', true)) {
            $excludeVerb[] = 'notgoing';
        }

        if (!empty($excludeVerb)) {
            $exclude['guests'] = $excludeVerb;
        }
    }
}
