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

class SocialProjectAppProjects extends SocialAppItem
{
    public function getFavIcon()
    {
        $obj = new stdClass();
        $obj->color = '#f06050';
        $obj->icon = 'fa-calendar';
        $obj->label = 'APP_PROJECT_PROJECTS_STREAM_TOOLTIP';

        return $obj;
    }

    public function onPrepareStream(SocialStreamItem &$item, $includePrivacy = true)
    {
        // We only want to process related items
        if ($item->cluster_type !== SOCIAL_TYPE_PROJECT || empty($item->cluster_id)) {
            return;
        }

        // Context are split into projects and nodes
        // "projects" context are stream items that are related to project item
        // "guests" context are stream items that are related to guests of the project
        // Only process "projects" context here
        // "guests" context are processed in the app/project/guests app

        if ($item->context !== 'projects') {
            return;
        }

        $project = FD::project($item->cluster_id);

        // Only show Social sharing in public group
        if (!$project->isOpen()) {
            $item->sharing = false;
        }

        if (!$project->canViewItem()) {
            return;
        }

        if (!$this->getParams()->get('stream_' . $item->verb, true)) {
            return;
        }

        $item->display = SOCIAL_STREAM_DISPLAY_FULL;

        $item->color = '#f06050';
        $item->fonticon = 'fa fa-calendar';
        $item->label = FD::_('APP_PROJECT_PROJECTS_STREAM_TOOLTIP', true);

        $actor = $item->actor;

        $this->set('project', $project);
        $this->set('actor', $actor);

        // streams/create.title
        // streams/feature.title
        // streams/update.title
        $item->title = parent::display('streams/' . $item->verb . '.title');
        $item->content = '';

        if ($project->isGroupProject()) {
            $this->set('group', $project->getGroup());

            $item->content = parent::display('streams/content');
        }

        // APP_PROJECT_PROJECTS_STREAM_OPENGRAPH_UPDATE
        // APP_PROJECT_PROJECTS_STREAM_OPENGRAPH_CREATE
        // APP_PROJECT_PROJECTS_STREAM_OPENGRAPH_FEATURE
        // Append the opengraph tags
        $item->addOgDescription(JText::sprintf('APP_PROJECT_PROJECTS_STREAM_OPENGRAPH_' . strtoupper($item->verb), $actor->getName(), $project->getName()));
    }

    public function onNotificationLoad(SocialTableNotification &$item)
    {
        $allowedCmd = array('likes.item', 'likes.involved', 'comments.item', 'comments.involved');

        if (!in_array($item->cmd, $allowedCmd)) {
            return;
        }

        $allowedContext = array('projects.project.create', 'projects.project.feature', 'projects.project.update');

        if (!in_array($item->context_type, $allowedContext)) {
            return;
        }

        $project = FD::project($item->uid);
        $actor = FD::user($item->actor_id);

        $hook = $this->getHook('notification', $item->type);
        $hook->execute($item);

        return;
    }

    public function onAfterCommentSave($comment)
    {
        $segments = explode('.', $comment->element);

        if (count($segments) !== 3 || $segments[1] !== SOCIAL_TYPE_PROJECT) {
            return;
        }

        list($element, $group, $verb) = explode('.', $comment->element);

        if ($element !== 'projects') {
            return;
        }

        // Get the actor
        $actor = FD::user($comment->created_by);

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

    public function onAfterLikeSave($likes)
    {
        $segments = explode('.', $likes->type);

        if (count($segments) !== 3 || $segments[1] !== SOCIAL_TYPE_PROJECT) {
            return;
        }

        list($element, $group, $verb) = explode('.', $likes->type);

        if ($element !== 'projects') {
            return;
        }

        // Get the actor
        $actor = FD::user($likes->created_by);

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
}
