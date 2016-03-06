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

FD::import('admin:/includes/fields/dependencies');

class SocialFieldsGroupProjectcreate extends SocialFieldItem
{
    public function onRegister(&$post, &$session)
    {
        // Get access
        $access = FD::access($session->uid, SOCIAL_TYPE_CLUSTERS);

        if (!$access->get('projects.groupproject', true)) {
            return;
        }

        $value = !empty($post['projectcreate']) ? $post['projectcreate'] : '[]';

        $value = FD::makeArray($value);

        $this->set('value', $value);

        return $this->display();
    }

    public function onEdit(&$post, &$group, $errors)
    {
        // Get access
        $access = $group->getAccess();

        if (!$access->get('projects.groupproject', true)) {
            return;
        }

        $value = !empty($post['projectcreate']) ? $post['projectcreate'] : $group->getParams()->get('projectcreate', '[]');

        $value = FD::makeArray($value);

        $this->set('value', $value);

        return $this->display();
    }

    public function onSample()
    {
        return $this->display();
    }

    public function onRegisterBeforeSave(&$post, &$group)
    {
        return $this->onBeforeSave($post, $group);
    }

    public function onEditBeforeSave(&$post, &$group)
    {
        return $this->onBeforeSave($post, $group);
    }

    public function onBeforeSave(&$post, &$group)
    {
        $value = !empty($post['projectcreate']) ? $post['projectcreate'] : '[]';

        $params = $group->getParams();
        $params->set('projectcreate', FD::makeArray($value));

        $group->params = $params->toString();

        unset($post['projectcreate']);
    }
}
