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

class SocialRouterProjects extends SocialRouterAdapter
{
    public function build(&$menu, &$query)
    {
        $segments = array();

        if ($menu && $menu->query['view'] !== 'Projects') {
            $segments[] = $this->translate($query['view']);
        }

        if (!$menu) {
            $segments[] = $this->translate($query['view']);
        }
        unset($query['view']);

        if (isset($query['filter'])) {
            // If filter is all, then we do not want this segment
            if ($query['filter'] !== 'all') {
                $segments[] = $this->translate('Projects_filter_' . $query['filter']);

                if (isset($query['date'])) {
                    $segments[] = $query['date'];
                    unset($query['date']);
                }

                if (isset($query['distance'])) {
                    $segments[] = $query['distance'];
                    unset($query['distance']);
                }
            }

            unset($query['filter']);
        }

        if (isset($query['categoryid'])) {
            $segments[] = $query['categoryid'];
            unset($query['categoryid']);
        }

        if (isset($query['layout'])) {
            $segments[] = $this->translate('Projects_layout_' . $query['layout']);
            unset($query['layout']);
        }

        if (isset($query['step'])) {
            $segments[] = $query['step'];
            unset($query['step']);
        }

        if (isset($query['id'])) {
            $segments[] = $query['id'];
            unset($query['id']);
        }

        if (isset($query['appId'])) {
            $segments[] = $query['appId'];
            unset($query['appId']);
        }

        // If there is no type defined but there is a "app" defined and default display is NOT timeline, then we have to punch in timeline manually
        if (isset($query['app']) && !isset($query['type']) && FD::config()->get('Projects.item.display', 'timeline') !== 'timeline') {
            $segments[] = $this->translate('Projects_type_timeline');
        }

        // If there is no type defined but there is a "filterId" defined and default display is NOT timeline, then we have to punch in timeline manually
        if (isset($query['filterId']) && !isset($query['type']) && FD::config()->get('Projects.item.display', 'timeline') !== 'timeline') {
            $segments[] = $this->translate('Projects_type_timeline');
        }

        // Special handling for timeline and about

        if (isset($query['type'])) {
            $defaultDisplay = FD::config()->get('Projects.item.display', 'timeline');

            // If type is info and there is a step provided, then info has to be added regardless of settings
            if ($query['type'] === 'info' && ($defaultDisplay !== $query['type'] || isset($query['infostep']))) {
                $segments[] = $this->translate('Projects_type_info');

                if (isset($query['infostep'])) {
                    $segments[] = $query['infostep'];
                    unset($query['infostep']);
                }
            }

            // Depending settings, if default is set to timeline and type is timeline, we don't need to add this into the segments
            if ($query['type'] === 'timeline' && $defaultDisplay !== $query['type']) {
                $segments[] = $this->translate('Projects_type_timeline');
            }

            if ($query['type'] === 'filterForm') {
                $segments[] = $this->translate('Projects_type_filterform');

                if (isset($query['filterId'])) {
                    $segments[] = $query['filterId'];
                    unset($query['filterId']);
                }
            }

            unset($query['type']);
        }

        return $segments;
    }

    public function parse(&$segments)
    {
        $vars = array();
        $total = count($segments);

        $vars['view'] = 'Projects';

        if ($total === 2) {
            switch ($segments[1]) {
                // site.com/menu/Projects/all
                case $this->translate('Projects_filter_all'):
                    $vars['filter'] = 'all';
                break;

                // site.com/menu/Projects/nearby
                case $this->translate('Projects_filter_nearby'):
                    $vars['filter'] = 'nearby';
                break;

                // site.com/menu/Projects/featured
                case $this->translate('Projects_filter_featured'):
                    $vars['filter'] = 'featured';
                break;

                // site.com/menu/Projects/mine
                case $this->translate('Projects_filter_mine'):
                    $vars['filter'] = 'mine';
                break;

                // site.com/menu/Projects/invited
                case $this->translate('Projects_filter_invited'):
                    $vars['filter'] = 'invited';
                break;

                // site.com/menu/Projects/create
                case $this->translate('Projects_layout_create'):
                    $vars['layout'] = 'create';
                break;

                // site.com/menu/Projects/week1
                case $this->translate('Projects_filter_week1'):
                    $vars['filter'] = 'week1';
                break;

                // site.com/menu/Projects/week2
                case $this->translate('Projects_filter_week2'):
                    $vars['filter'] = 'week2';
                break;

                // site.com/menu/Projects/past
                case $this->translate('Projects_filter_past'):
                    $vars['filter'] = 'past';
                break;

                // site.com/menu/Projects/date/
                case $this->translate('Projects_filter_date'):
                    $vars['filter'] = 'date';
                break;

                // site.com/menu/Projects/today/
                case $this->translate('Projects_filter_today');
                    $vars['filter'] = 'date';
                break;

                // site.com/menu/Projects/today/
                case $this->translate('Projects_filter_today');
                    $vars['filter'] = 'date';
                break;

                // site.com/menu/Projects/nearby/
                case $this->translate('Projects_filter_nearby');
                    $vars['filter'] = 'nearby';
                break;

                // site.com/menu/Projects/ID-category
                default:
                    $catId = (int) $this->getIdFromPermalink($segments[1]);

                    if ($catId) {
                        $vars['categoryid'] = $catId;
                    } else {
                        $vars['filter'] = $segments[1];
                    }
                break;
            }
        }

        if ($total === 3) {
            switch ($segments[1]) {
                // site.com/menu/Projects/date/[date]
                case $this->translate('Projects_filter_date'):
                    $vars['filter'] = 'date';
                    $vars['date'] = $segments[2];
                break;

                // site.com/menu/Projects/nearby/[distance]
                case $this->translate('Projects_filter_nearby');
                    $vars['filter'] = 'nearby';
                    $vars['distance'] = $segments[2];
                break;

                // site.com/menu/Projects/category/ID-category
                case $this->translate('Projects_layout_category'):
                    $vars['layout'] = 'category';
                    $vars['id'] = $this->getIdFromPermalink($segments[2]);
                break;

                // site.com/menu/Projects/edit/ID-Project
                case $this->translate('Projects_layout_edit'):
                    $vars['layout'] = 'edit';
                    $vars['id'] = $this->getIdFromPermalink($segments[2]);
                break;

                // site.com/menu/Projects/export/ID-Project
                case $this->translate('Projects_layout_export'):
                    $vars['layout'] = 'export';
                    $vars['id'] = (int) $segments[2];
                break;

                // site.com/menu/Projects/item/ID-Project
                case $this->translate('Projects_layout_item'):
                    $vars['layout'] = 'item';
                    $vars['id'] = $this->getIdFromPermalink($segments[2]);
                break;

                // site.com/menu/Projects/steps/ID-Project
                case $this->translate('Projects_layout_steps'):
                    $vars['layout'] = 'steps';
                    $vars['step'] = $segments[2];
                break;

                // site.com/menu/Projects/featured/ID-category
                case $this->translate('Projects_filter_featured'):
                    $vars['filter'] = 'featured';
                    $vars['categoryid'] = $this->getIdFromPermalink($segments[2]);
                break;

                // site.com/menu/Projects/mine/ID-category
                case $this->translate('Projects_filter_mine'):
                    $vars['filter'] = 'mine';
                    $vars['categoryid'] = $this->getIdFromPermalink($segments[2]);
                break;

                // site.com/menu/Projects/recent/ID-category
                case $this->translate('Projects_filter_invited'):
                    $vars['filter'] = 'invited';
                    $vars['categoryid'] = $this->getIdFromPermalink($segments[2]);
                break;

                // site.com/menu/Projects/all/ID-category
                default:
                case $this->translate('Projects_filter_all'):
                    $vars['filter'] = 'all';
                    $vars['categoryid'] = $this->getIdFromPermalink($segments[2]);
                break;
            }
        }

        $typeException = array($this->translate('Projects_type_info'), $this->translate('Projects_type_timeline'), $this->translate('Projects_type_filterform'));

        // Specifically check for both info and timeline. If 4th segment is not info nor timeline, then we assume it is app
        if ($total === 4 && $segments[1] === $this->translate('Projects_layout_item') && !in_array($segments[3], $typeException)) {
            $vars['layout'] = 'item';
            $vars['id'] = $this->getIdFromPermalink($segments[2]);
            $appId = $this->getIdFromPermalink($segments[3]);

            // $vars['type'] = $appId;
            $vars[(int) $appId ? 'appId' : 'app'] = $appId;
        }

        if (($total === 4 || $total === 5) && $segments[1] === $this->translate('Projects_layout_item') && in_array($segments[3], $typeException)) {
            $vars['layout'] = 'item';
            $vars['id'] = $this->getIdFromPermalink($segments[2]);

            if ($segments[3] === $this->translate('Projects_type_info')) {
                $vars['type'] = 'info';

                if (!empty($segments[4])) {
                    $vars['step'] = $segments[4];
                }
            }

            if ($segments[3] === $this->translate('Projects_type_timeline')) {
                $vars['type'] = 'timeline';
            }

            if ($segments[3] === $this->translate('Projects_type_filterform')) {
                $vars['type'] = 'filterForm';

                if (!empty($segments[4])) {
                    $vars['filterId'] = $segments[4];
                }
            }
        }

        return $vars;
    }

    public function getUrl($query, $url)
    {
        static $cache = array();

        // Get a list of menus for the current view.
        $itemMenus = FRoute::getMenus($this->name, 'item');

        // For single group item
        // index.php?option=com_easysocial&view=Projects&layout=item&id=xxxx
        $items = array('item', 'info', 'edit');

        if (isset($query['layout']) && in_array($query['layout'], $items) && isset($query['id']) && !empty($itemMenus)) {

            foreach($itemMenus as $menu) {
                $id = (int) $menu->segments->id;
                $queryId = (int) $query['id'];

                if ($queryId == $id) {

                    // The query cannot contain appId
                    if ($query['layout'] == 'item' && !isset($query['appId'])) {
                        $url = 'index.php?Itemid=' . $menu->id;
                        return $url;
                    }


                    $url .= '&Itemid=' . $menu->id;
                    return $url;
                }
            }
        }

        // For group categories
        $menus = FRoute::getMenus($this->name, 'category');
        $items = array('category');

        if (isset($query['layout']) && in_array($query['layout'], $items) && isset($query['id']) && !empty($itemMenus)) {

            foreach ( $menus as $menu) {
                $id = (int) $menu->segments->id;
                $queryId = (int) $query['id'];

                if ($queryId == $id) {
                    if ($query['layout'] == 'category') {
                        $url = 'index.php?Itemid=' . $menu->id;

                        return $url;
                    }

                    $url .= '&Itemid=' . $menu->id;

                    return $url;
                }

            }
        }

        return false;
    }
}
