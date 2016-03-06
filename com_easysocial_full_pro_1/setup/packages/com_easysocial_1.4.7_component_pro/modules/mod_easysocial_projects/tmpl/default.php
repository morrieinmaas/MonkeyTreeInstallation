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
<div id="fd" class="es mod-es-projects module-register<?php echo $suffix;?> es-responsive">

    <ul class="es-projects-list fd-reset-list">
        <?php foreach ($projects as $project) { ?>

        <li>
            <?php if ($params->get('display_avatar' , true)) { ?>
            <div class="es-project-avatar es-avatar es-avatar-sm es-avatar-border-sm">
                <img src="<?php echo $project->getAvatar();?>" alt="<?php echo $modules->html('string.escape' , $project->getName());?>" />
            </div>
            <?php } ?>

            <div class="es-project-object">
                <a href="<?php echo $project->getPermalink();?>" class="project-title"><?php echo $project->getName();?></a>
            </div>

            <div class="es-project-meta">
                <?php echo $project->getStartEndDisplay(array('end' => false)); ?>
            </div>

            <div class="es-project-meta">
                <?php if ($params->get('display_category' , true)) { ?>
                <span>
                    <a href="<?php echo FRoute::projects(array('layout' => 'category' , 'id' => $project->getCategory()->getAlias()));?>" alt="<?php echo $modules->html('string.escape' , $project->getCategory()->get('title'));?>" class="project-category">
                        <i class="fa fa-database"></i> <?php echo $modules->html('string.escape' , $project->getCategory()->get('title'));?>
                    </a>
                </span>
                <?php } ?>

                <?php if ($params->get('display_member_counter', true)) { ?>
                <span class="hit-counter">
                    <i class="fa fa-users"></i> <?php echo JText::sprintf(FD::string()->computeNoun('MOD_EASYSOCIAL_PROJECTS_GUEST_COUNT' , $project->getTotalGuests()) , $project->getTotalGuests()); ?>
                </span>
                <?php } ?>
            </div>

            <?php echo $project->showRsvpButton(); ?>

        </li>
        <?php } ?>
    </ul>

    <div class="fd-small">
        <a href="<?php echo FRoute::projects(); ?>"><?php echo JText::_('MOD_EASYSOCIAL_PROJECTS_ALL_PROJECT'); ?></a>
    </div>
</div>
