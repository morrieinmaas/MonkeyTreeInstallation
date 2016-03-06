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

class SocialFieldsProjectType extends SocialFieldItem
{
    /**
     * Displays the field for creation.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @param   array                   $post       The posted data.
     * @param   SocialTableStepSession  $session    The session table.
     * @return  string                              The html codes for this field.
     */
    public function onRegister(&$post, &$session)
    {
        // Support for group project
        // If this is a group project, we do not allow user to change the type as the type follows the group
        $reg = FD::registry();
        $reg->load($session->values);

        if ($reg->exists('group_id')) {
            return;
        }

        $value = isset($post['project_type']) ? $post['project_type'] : $this->params->get('default');

        $this->set('value', $value);

        return $this->display();
    }

    /**
     * Displays the field for edit.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @param   array           $post       The posted data.
     * @param   SocialCluster   $cluster    The cluster object.
     * @param   array           $errors     The errors array.
     * @return  string                      The html codes for this field.
     */
    public function onEdit(&$post, &$cluster, $error)
    {
        // Support for group project
        // If this is a group project, we do not allow user to change the type as the type follows the group
        if ($cluster->isGroupProject()) {
            return;
        }

        $value = isset($post['project_type']) ? $post['project_type'] : $cluster->type;

        $this->set('value', $value);

        return $this->display();
    }

    /**
     * Displays the sample html codes when the field is added into the profile.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @return  string  The html output.
     */
    public function onSample()
    {
        $this->set('value', 1);

        return $this->display();
    }

    /**
     * Executes before the project is created
     *
     * @since   1.3
     * @access  public
     * @param   string
     * @return
     */
    public function onRegisterBeforeSave(&$post, &$cluster)
    {
        if ($cluster->isGroupProject()) {
            // Currently, the type always follow group type
            // There is a separate checking where user must be group member to join the project
            $cluster->type = FD::group($cluster->getMeta('group_id'))->type;

            unset($post['project_type']);

            return;
        }

        $type = isset($post['project_type']) ? $post['project_type'] : $this->params->get('default');

        $cluster->type = $type;

        unset($post['project_type']);
    }

    /**
     * Executes before the project is created.
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @param   array           $post       The posted data.
     * @param   SocialCluster   $cluster    The cluster object.
     */
    public function onEditBeforeSave(&$post, &$cluster)
    {
        if ($cluster->isGroupProject()) {
            // Currently, the type always follow group type
            // There is a separate checking where user must be group member to join the project
            $cluster->type = FD::group($cluster->getMeta('group_id'))->type;

            unset($post['project_type']);

            return;
        }

        $type = isset($post['project_type']) ? $post['project_type'] : $cluster->type;

        $cluster->type = $type;

        unset($post['project_type']);
    }
}
