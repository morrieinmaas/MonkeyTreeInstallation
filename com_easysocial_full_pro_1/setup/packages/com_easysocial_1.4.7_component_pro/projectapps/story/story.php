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

class SocialProjectAppStory extends SocialAppItem
{
    /**
     * Returns the stream favicon object.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     * @return object    The stream favicon object.
     */
    public function getFavIcon()
    {
        $obj            = new stdClass();
        $obj->color     = '#6E9545';
        $obj->icon      = 'fa fa-pencil';
        $obj->label     = 'APP_USER_STORY_UPDATES_STREAM_TOOLTIP';

        return $obj;
    }

    /**
     * Handles onAfterLikeSave project.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     * @param  SocialTableLikes    $likes The like object.
     */
    public function onAfterLikeSave(&$likes)
    {
        if (!$likes->type) {
            return;
        }

        $data = explode('.', $likes->type);

        $element = array_shift($data);

        if ($element != 'story') {
            return;
        }

        $project = array_shift($data);
        $verb = array_shift($data);

        $uid = $likes->uid;

        $stream = FD::table('Stream');
        $stream->load($uid);

        $actor = FD::user($likes->created_by);

        $emailOptions = array(
            'title' => 'APP_PROJECT_STORY_EMAILS_LIKE_ITEM_TITLE',
            'template' => 'apps/project/story/like.item',
            'permalink' => $stream->getPermalink(true, true),
            'actor' => $actor->getName(),
            'actorAvatar' => $actor->getAvatar(SOCIAL_AVATAR_SQUARE),
            'actorLink' => $actor->getPermalink(true, true)
      );

        $systemOptions = array(
            'context_type' => $likes->type,
            'url' => $stream->getPermalink(false, false, false),
            'actor_id' => $likes->created_by,
            'uid' => $likes->uid,
            'aggregate' => true
      );

        // Notify the owner first
        FD::notify('likes.item', array($stream->actor_id), $emailOptions, $systemOptions);

        // Get a list of recipients to be notified for this stream item
        // We exclude the owner of the item and the actor of the like here
        $recipients = $this->getStreamNotificationTargets($likes->uid, $element, $project, $verb, array(), array($stream->actor_id, $likes->created_by));

        $emailOptions['title'] = 'APP_PROJECT_STORY_EMAILS_LIKE_INVOLVED_TITLE';
        $emailOptions['template'] = 'apps/project/story/like.involved';

        // Notify other participating users
        FD::notify('likes.involved', $recipients, $emailOptions, $systemOptions);
    }

    /**
     * Handles onAfterCommentSave project.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     * @param  SocialTableComments    $comment The comment object.
     */
    public function onAfterCommentSave(&$comment)
    {
        $allowed = array('story.project.create', 'links.project.create');

        if (!in_array($comment->element, $allowed)) {
            return;
        }

        list($element, $project, $verb) = explode('.', $comment->element);

        $stream = FD::table('Stream');
        $stream->load($comment->uid);

        $actor = FD::user($comment->created_by);

        $emailOptions = array(
            'title' => 'APP_PROJECT_STORY_EMAILS_COMMENT_ITEM_TITLE',
            'template' => 'apps/project/story/comment.item',
            'comment' => $comment->comment,
            'permalink' => $stream->getPermalink(true, true),
            'posterName' => $actor->getName(),
            'posterAvatar' => $actor->getAvatar(SOCIAL_AVATAR_SQUARE),
            'posterLink' => $actor->getPermalink(true, true)
      );

        $systemOptions  = array(
            'content' => $comment->comment,
            'context_type' => $comment->element,
            'url' => $stream->getPermalink(false, false, false),
            'actor_id' => $comment->created_by,
            'uid' => $comment->uid,
            'aggregate' => true
      );

        // Notify the note owner
        FD::notify('comments.item', array($stream->actor_id), $emailOptions, $systemOptions);

        // Get a list of recipients to be notified for this stream item.
        // We exclude the owner of the item and the actor of the like here
        $recipients = $this->getStreamNotificationTargets($comment->uid, $element, $project, $verb, array(), array($stream->actor_id, $comment->created_by));

        $emailOptions['title'] = 'APP_PROJECT_STORY_EMAILS_COMMENT_ITEM_INVOLVED_TITLE';
        $emailOptions['template'] = 'apps/project/story/comment.involved';

        // Notify participating users
        FD::notify('comments.involved', $recipients, $emailOptions, $systemOptions);
    }

    /**
     * Processes notifications.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     * @param  SocialTableNotification $item   The notification item object.
     */
    public function onNotificationLoad(SocialTableNotification &$item)
    {
        // Process notifications when someone likes your post
        // context_type: stream.project.create, links.create
        // type: likes
        $allowed = array('story.project.create', 'links.create', 'photos.project.share');
        if ($item->type == 'likes' && in_array($item->context_type, $allowed)) {
            $hook = $this->getHook('notification', 'likes');
            $hook->execute($item);

            return;
        }

        // Process notifications when someone posts a comment on your status update
        // context_type: stream.project.create
        // type: comments
        $allowed = array('story.project.create', 'links.project.create', 'photos.project.share');
        if ($item->type == 'comments' && in_array($item->context_type, $allowed)) {

            $hook = $this->getHook('notification', 'comments');
            $hook->execute($item);

            return;
        }

        // Processes notifications when someone posts a new update in a project
        // context_type: story.project.create, links.project.create
        // type: projects
        $allowed = array('story.project.create', 'links.project.create', 'photos.project.share', 'file.project.uploaded');

        if ($item->cmd == 'projects.updates' && (in_array($item->context_type, $allowed))) {

            $hook = $this->getHook('notification', 'updates');
            $hook->execute($item);

            return;
        }
    }

    /**
     * Handles onAfterStorySave trigger.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     * @param  SocialStream          $stream     The stream object.
     * @param  SocialTableStreamItem $streamItem The stream table object.
     * @param  SocialStreamTemplate  $template   The stream template object.
     */
    public function onAfterStorySave(SocialStream &$stream, SocialTableStreamItem &$streamItem, SocialStreamTemplate &$template)
    {
        // Determine if this is for a project
        if (!$template->cluster_id) {
            return;
        }

        // Now we only want to allow specific context
        $context = $template->context_type . '.' . $template->verb;
        $allowed = array('story.create', 'links.create', 'photos.share');

        if (!in_array($context, $allowed)) {
            return;
        }

        $project = FD::project($template->cluster_id);

        $actor = FD::user($streamItem->actor_id);

        // Get a list of project members
        $model = FD::model('Groups');
        $targets = $model->getMembers($project->id, array('exclude' => $actor->id));

        // If there's nothing to send skip this altogether.
        if (!$targets) {
            return;
        }

        // Get the item's permalink
        $permalink = FRoute::stream(array('id' => $streamItem->uid, 'layout' => 'item', 'external' => true), true);

        // Prepare the email params
        $mailParams = array();
        $mailParams['actor'] = $actor->getName();
        $mailParams['posterAvatar'] = $actor->getAvatar(SOCIAL_AVATAR_SQUARE);
        $mailParams['posterLink'] = $actor->getPermalink(true, true);

        $contents = $template->content;

        // break the text and images
        if (strpos($template->content, '<img') !== false) {
            preg_match('#(<img.*?>)#', $template->content, $results);

            $img = "";
            if ($results) {
                $img = $results[0];
            }

            $segments = explode('<img', $template->content);
            $contents = $segments[0];

            if ($img) {
                $contents = $contents . '<br /><div style="text-align:center;">' . $img . "</div>";
            }
        }


        $mailParams['message'] = $contents;

        $mailParams['project'] = $project->getName();
        $mailParams['projectLink'] = $project->getPermalink(true, true);
        $mailParams['permalink'] = FRoute::stream(array('id' => $streamItem->uid, 'layout' => 'item', 'external' => true), true);
        $mailParams['title'] = 'APP_PROJECT_STORY_EMAILS_NEW_POST_IN_PROJECT';
        $mailParams['template'] = 'apps/project/story/new.post';

        // Prepare the system notification params
        $systemParams = array();
        $systemParams['context_type'] = $template->context_type . '.project.' . $template->verb;
        $systemParams['url'] = FRoute::stream(array('id' => $streamItem->uid, 'layout' => 'item', 'sef' => false));
        $systemParams['actor_id'] = $actor->id;
        $systemParams['uid'] = $streamItem->uid;
        $systemParams['context_ids'] = $project->id;
        $systemParams['content'] = $template->content;

        // Try to send the notification
        $state  = FD::notify('projects.updates', $targets, $mailParams, $systemParams);
    }

    /**
     * Trigger to prepare a stream object.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     * @param  SocialStreamItem $item The stream item object.
     */
    public function onPrepareStream(SocialStreamItem &$item)
    {
        // If this is not it's context, we don't want to do anything here.
        if ($item->context != 'story') {
            return;
        }

        // Get the project object
        $project = $item->getCluster();

        if (!$project) {
            return;
        }

        if (!$project->canViewItem()) {
            return;
        }

        // Allow editing of the stream item
        $item->editable = $this->my->isSiteAdmin() || $project->isAdmin() || $item->actor->id == $this->my->id;

        // Get the actor
        $actor = $item->getActor();

        // Decorate the stream
        $item->fonticon = 'fa fa-pencil';
        $item->color = '#6E9545';
        $item->label = FD::_('APP_PROJECT_STORY_STREAM_TOOLTIP', true);
        $item->display = SOCIAL_STREAM_DISPLAY_FULL;

        $this->set('project', $project);
        $this->set('actor', $actor);
        $this->set('stream', $item);

        $item->title = parent::display('streams/title.' . $item->verb);
        $item->content =  parent::display('streams/content.' . $item->verb);

        // Apply likes on the stream
        $likes = FD::likes();
        $likes->get($item->uid, $item->context, $item->verb, SOCIAL_APPS_GROUP_PROJECT, $item->uid);

        $item->likes = $likes;

        // If this update is posted in a project, the comments should be linked to the project item
        $comments = FD::comments($item->uid, $item->context, $item->verb, SOCIAL_APPS_GROUP_PROJECT, array('url' => FRoute::stream(array('layout' => 'item', 'id' => $item->uid))), $item->uid);
        $item->comments = $comments;

        return true;
    }
}
