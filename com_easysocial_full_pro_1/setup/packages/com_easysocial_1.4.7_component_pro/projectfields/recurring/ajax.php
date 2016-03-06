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

class SocialFieldsProjectRecurring extends SocialFieldItem
{
    public function calculateTotalRecur()
    {
        $in = FD::input();

        $start = $in->getString('start');
        $timezone = $in->getString('timezone');
        $end = $in->getString('end');
        $type = $in->getString('type');
        $daily = $in->getVar('daily', array());
        $allday = $in->getBool('allday', false);

        $projectId = $in->getString('projectId');
        $changed = $in->getInt('changed');
        $showWarningMessages = $in->getInt('showWarningMessages');

        if (!empty($timezone) && $timezone !== 'UTC') {
            $dtz = new DateTimeZone($timezone);

            // This is to reverse the time back to UTC
            $start = JFactory::getDate($start, $dtz)->toSql();
        }

        $projectStart = FD::date($start, false);

        $result = FD::model('Projects')->getRecurringSchedule(array(
            'projectStart' => $projectStart,
            'end' => $end,
            'type' => $type,
            'daily' => $daily
        ));

        $schedule = array();

        $tf = FD::config()->get('projects.timeformat', '12h');

        foreach ($result as $time) {
            $schedule[] = FD::date($time)->format(JText::_($allday ? 'COM_EASYSOCIAL_DATE_DMY' : 'COM_EASYSOCIAL_DATE_DMY' . ($tf == '12h' ? '12H' : '24H')));
        }

        if (empty($schedule) && $type != 'none') {
            FD::ajax()->reject(JText::_('FIELD_PROJECT_RECURRING_NO_RECURRING_PROJECT_WILL_BE_CREATED'));
        }

        $theme = FD::themes();

        $total = count($schedule);
        $limit = FD::config()->get('projects.recurringlimit', 0);

        if (!empty($limit) && $limit != 0 && $total > $limit) {
            $msg = JText::sprintf('FIELD_PROJECT_RECURRING_VALIDATION_MAX_RECURRING_LIMIT', $total, $limit);
            return FD::ajax()->reject($msg);
        }

        $theme->set('schedule', $schedule);

        $theme->set('type', $type);

        $hasChildren = !empty($projectId) && FD::model('Projects')->getTotalProjects(array(
            'state' => SOCIAL_STATE_PUBLISHED,
            'parent_id' => $projectId
        )) > 0;

        $theme->set('hasChildren', $hasChildren);

        if ($type == 'daily') {
            $theme->set('days', array(
                JText::_('SUNDAY'),
                JText::_('MONDAY'),
                JText::_('TUESDAY'),
                JText::_('WEDNESDAY'),
                JText::_('THURSDAY'),
                JText::_('FRIDAY'),
                JText::_('SATURDAY')
            ));
            $theme->set('daily', $daily);
        }

        $theme->set('changed', $changed);

        $theme->set('showWarningMessages', $showWarningMessages);

        $html = $theme->output('fields/project/recurring/summary');

        FD::ajax()->resolve($html);
    }
}
