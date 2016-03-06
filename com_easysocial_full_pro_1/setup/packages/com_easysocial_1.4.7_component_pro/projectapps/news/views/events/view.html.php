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

class NewsViewProjects extends SocialAppsView
{
    /**
     * Displays the application output in the canvas.
     *
     * @since    1.3
     * @access   public
     * @param    int        The project id
     */
    public function display($id = null, $docType = null)
    {
        // Load up the project
        $project = FD::project($id);

        // Check if the viewer is really allowed to view news
        if (($project->isInviteOnly() && $project->isClosed()) && !$project->getGuest()->isGuest() && !$this->my->isSiteAdmin()) {
            FD::info()->set(false, JText::_('COM_EASYSOCIAL_PROJECTS_ONLY_GUEST_ARE_ALLOWED'), SOCIAL_MSG_ERROR);

            return $this->redirect($project->getPermalink(false));
        }

        $params = $this->app->getParams();

        // Set the max length of the item
        $options = array('limit' => (int) $params->get('total', 10));

        $model = FD::model('Clusters');
        $items = $model->getNews($project->id, $options);
        $pagination = $model->getPagination();

        // Format the item's content.
        $this->format($items, $params);

        $pagination->setVar('option', 'com_easysocial');
        $pagination->setVar('view', 'projects');
        $pagination->setVar('layout', 'item');
        $pagination->setVar('id', $project->getAlias());
        $pagination->setVar('appId', $this->app->getAlias());

        $this->set('params', $params);
        $this->set('pagination', $pagination);
        $this->set('project', $project);
        $this->set('items', $items);

        echo parent::display('canvas/default');
    }

    private function format(&$items, $params)
    {
        $length = $params->get('content_length');

        if ($length == 0) {
            return;
        }

        foreach ($items as &$item) {
            $item->content = JString::substr(strip_tags($item->content), 0, $length) . ' ' . JText::_('COM_EASYSOCIAL_ELLIPSES');
        }
    }
}
