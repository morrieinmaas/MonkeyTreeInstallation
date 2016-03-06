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

$timeformatOptions = array(
	$settings->makeOption('Display Time Format 12H', '12h'),
	$settings->makeOption('Display Time Format 24H', '24h'),
	'help' => true,
	'class' => 'form-control input-sm input-medium'
);
$defaultDisplay = array(
	$settings->makeOption('Default Display Timeline', 'timeline'),
	$settings->makeOption('Default Display Info', 'info'),
	'help' => true,
	'class' => 'form-control input-sm'
);

$startOfWeekOptions = array(
	$settings->makeOption('MON', 1, false),
	$settings->makeOption('TUE', 2, false),
	$settings->makeOption('WED', 3, false),
	$settings->makeOption('THU', 4, false),
	$settings->makeOption('FRI', 5, false),
	$settings->makeOption('SAT', 6, false),
	$settings->makeOption('SUN', 0, false),
);
echo $settings->renderPage(
	$settings->renderColumn(
		$settings->renderSection(
			$settings->renderHeader('General'),
			$settings->renderSetting('Enable Projects', 'projects.enabled', 'boolean', array('help' => true)),
			$settings->renderSetting('Recurring Limit', 'projects.recurringlimit', 'input', array('help' => true, 'default' => 0, 'class' => 'input-sm')),
			$settings->renderSetting('Enable iCal Export', 'projects.ical', 'boolean', array('help' => true)),
			$settings->renderSetting('Allow Invite Non Friends', 'projects.invite.nonfriends', 'boolean', array('help' => true)),
			$settings->renderSetting('Display Time Format', 'projects.timeformat', 'list', $timeformatOptions),
			$settings->renderSetting('Include Featured Project', 'projects.listing.includefeatured', 'boolean', array('help' => true)),
			$settings->renderSetting('Default Display', 'projects.item.display', 'list', $defaultDisplay)
		),
		$settings->renderSection(
			$settings->renderHeader('Group Projects'),
			$settings->renderSetting('Include Group Project', 'projects.listing.includegroup', 'boolean', array('help' => true))
		)
	),
	$settings->renderColumn(
		$settings->renderSection(
			$settings->renderHeader('Stream'),
			$settings->renderSetting('Creation Stream', 'projects.stream.create', 'boolean', array('help' => true))
		),
		$settings->renderSection(
			$settings->renderHeader('Calendar'),
			$settings->renderSetting('Start Of Week', 'projects.startofweek', 'list', $startOfWeekOptions)
		)
	)
);
