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
?>
<div data-group-projects class="app-groups">

    <div class="es-filterbar row-table">
        <div class="col-cell filterbar-title"><?php echo JText::_('APP_GROUP_PROJECTS_TITLE'); ?></div>

        <?php if ($group->canCreateProject()) { ?>
        <div class="col-cell cell-tight">
            <a href="<?php echo FRoute::projects(array('layout' => 'create', 'group_id' => $group->id));?>" class="btn btn-es-primary btn-sm pull-right">
                <?php echo JText::_('APP_GROUP_PROJECTS_NEW_PROJECT'); ?>
            </a>
        </div>
        <?php } ?>
    </div>

    <div class="app-contents-wrap" data-group-projects-list>
        <?php echo $this->includeTemplate('site/projects/default.list'); ?>
    </div>
</div>
