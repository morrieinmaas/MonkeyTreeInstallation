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

class SocialProjectAppStoryHookNotificationUpdates
{
    /**
     * Processes comment notifications
     *
     * @since   1.2
     * @access  public
     * @param   string
     * @return
     */
    public function execute(SocialTableNotification &$item)
    {
        // Get the project item
        $project = FD::project($item->context_ids);

        // Get the actor
        $actor = FD::user($item->actor_id);

        // Format the title
        if ($item->context_type == 'story.project.create') {
            $item->title = JText::sprintf('APP_PROJECT_STORY_USER_POSTED_IN_PROJECT', $actor->getName(), $project->getName());
            $item->image = $project->getAvatar();

            // Ensure that the content is properly formatted
            $item->content = JString::substr(strip_tags($item->content), 0, 80) . JText::_('COM_EASYSOCIAL_ELLIPSES');

            return $item;
        }

        if ($item->context_type == 'links.project.create') {

            $model = FD::model( 'Stream' );
            $links = $model->getAssets($item->uid, SOCIAL_TYPE_LINKS);

            if (!$links) {
                return;
            }

            $link = FD::makeObject($links[0]->data);

            $item->image = $link->image;
            $item->content = $link->link;
            $item->title = JText::sprintf('APP_PROJECT_STORY_USER_SHARED_LINK_IN_PROJECT', $actor->getName(), $project->getName());
        }

        // Someone shared a file in a project
        if ($item->context_type == 'file.project.uploaded') {

            // Get the file object
            $file = FD::table('File');
            $file->load($item->context_ids);

            $project = FD::project($item->uid);

            $item->title = JText::sprintf('APP_PROJECT_STORY_USER_SHARED_FILE_IN_PROJECT', $actor->getName(), $project->getName());
            $item->content = $file->name;

            if ($file->hasPreview()) {
                $item->image = $file->getPreviewURI();
            }

            return;
        }


        // Someone shared a photo in a project
        if ($item->context_type == 'photos.project.share') {

            // Based on the stream id, we need to get the stream item id.
            $stream = FD::table('Stream');
            $stream->load($item->uid);

            // Get child items
            $streamItems = $stream->getItems();

            // Since we got all the child of stream, we can get the correct count
            $count = count($streamItems);

            if ($count && $count == 1) {

                $photo = FD::table('Photo');
                $photo->load($streamItems[0]->id);

                $item->title = JText::sprintf('APP_PROJECT_STORY_USER_SHARED_SINGLE_PHOTO_IN_PROJECT', $actor->getName(), $project->getName());
                $item->image = $photo->getSource();
                $item->content = '';

                return;
            }

            $item->title = JText::sprintf('APP_PROJECT_STORY_USER_SHARED_MULTIPLE_PHOTOS_IN_PROJECT', $actor->getName(), $count, $project->getName());
            $item->content = '';

            return;
        }

        return $item;
    }
}
