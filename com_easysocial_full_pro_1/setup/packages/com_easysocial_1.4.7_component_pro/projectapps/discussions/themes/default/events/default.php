<?php
/**
* @package        EasySocial
* @copyright    Copyright (C) 2010 - 2014 Stack Ideas Sdn Bhd. All rights reserved.
* @license        GNU/GPL, see LICENSE.php
* EasySocial is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined('_JEXEC') or die('Unauthorized Access');
?>
<div class="app-discussions" data-project-discussions data-id="<?php echo $project->id; ?>">
    <div class="es-filterbar row-table">
        <div class="col-cell filterbar-title"><?php echo JText::_('APP_PROJECT_DISCUSSIONS_SUBTITLE'); ?></div>

        <?php if ($project->getGuest()->isGuest() || $this->my->isSiteAdmin()) { ?>
        <div class="col-cell cell-tight">
            <a href="<?php echo FRoute::apps(array('layout' => 'canvas', 'uid' => $project->getAlias(), 'type' => SOCIAL_TYPE_PROJECT, 'id' => $app->getAlias(), 'customView' => 'create')); ?>" class="btn btn-es-primary btn-sm pull-right">
                <?php echo JText::_('APP_PROJECT_DISCUSSIONS_CREATE_DISCUSSION'); ?>
            </a>
        </div>
        <?php } ?>
    </div>

    <div class="app-contents-wrap">
        <ul class="fd-nav es-filter-nav clearfix">
            <li>
                <a class="active" href="javascript:void(0);" data-project-discussions-filter data-filter="all"><?php echo JText::_('APP_PROJECT_DISCUSSIONS_FILTER_ALL'); ?></a>
            </li>
            <li>
                <a href="javascript:void(0);" data-project-discussions-filter data-filter="unanswered"><?php echo JText::_('APP_PROJECT_DISCUSSIONS_FILTER_UNANSWERED'); ?></a>
            </li>
            <li>
                <a href="javascript:void(0);" data-project-discussions-filter data-filter="resolved"><?php echo JText::_('APP_PROJECT_DISCUSSIONS_FILTER_RESOLVED'); ?></a>
            </li>
            <li>
                <a href="javascript:void(0);" data-project-discussions-filter data-filter="locked"><?php echo JText::_('APP_PROJECT_DISCUSSIONS_FILTER_LOCKED'); ?></a>
            </li>
        </ul>

        <div class="app-contents<?php echo !$discussions ? ' is-empty' : ''; ?>" data-project-discussion-contents>

            <?php echo $this->loadTemplate('apps/project/discussions/projects/default.list', array('discussions' => $discussions, 'project' => $project, 'app' => $app, 'pagination' => $pagination, 'params' => $params)); ?>

        </div>
    </div>

</div>
