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

FD::import('admin:/tables/clustercategory');

class SocialTableProjectCategory extends SocialTableClusterCategory
{
    /**
     * Preprocess before calling parent::store();
     *
     * @author  Jason Rey <jasonrey@stackideas.com>
     * @since   1.3
     * @access  public
     * @param   boolean $updateNulls    True to update fields even if they are null.
     * @return  boolean                 True on success.
     */
    public function store($updateNulls = false)
    {
        $this->type = SOCIAL_TYPE_PROJECT;

        return parent::store($updateNulls);
    }

    /**
     * Returns the total number of projects in this category.
     *
     * @author Jason Rey <jasonrey@stackideas.com>
     * @since  1.3
     * @access public
     * @return integer    The number of projects in this category.
     */
    public function getTotalProjects($options = array())
    {
        static $total = array();

        $defaultOptions = array(
            'state' => SOCIAL_STATE_PUBLISHED,
            'type' => array(SOCIAL_PROJECT_TYPE_PUBLIC, SOCIAL_PROJECT_TYPE_PRIVATE),
            'category' => $this->id
        );

        $options = array_merge($defaultOptions, $options);

        ksort($options);

        $key = serialize($options);

        if (!isset($total[$this->id][$key])) {
            $total[$this->id][$key] = FD::model('projects')->getTotalProjects($options);
        }

        return $total[$this->id][$key];
    }
}
