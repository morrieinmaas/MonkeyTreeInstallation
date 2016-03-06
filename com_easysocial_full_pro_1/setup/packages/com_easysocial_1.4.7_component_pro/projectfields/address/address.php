<?php
/**
* @package		EasySocial
* @copyright	Copyright (C) 2010 - 2014 Stack Ideas Sdn Bhd. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* EasySocial is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined('_JEXEC') or die('Unauthorized Access');

FD::import('fields:/user/address/address');

class SocialFieldsProjectAddress extends SocialFieldsUserAddress
{
    public function onRegisterBeforeSave(&$post, &$project)
    {
        parent::onRegisterBeforeSave($post, $project);

        $this->beforeSave($post, $project);
    }

    public function onEditBeforeSave(&$post, &$project)
    {
        parent::onEditBeforeSave($post, $project);

        $this->beforeSave($post, $project);
    }

    public function beforeSave(&$post, &$project)
    {
        $address = $post[$this->inputName];

        $project->latitude = $address->latitude;
        $project->longitude = $address->longitude;
        $project->address = $address->toString();
    }
}
