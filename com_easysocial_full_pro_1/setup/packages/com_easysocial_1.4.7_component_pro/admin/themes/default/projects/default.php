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
<form action="" method="post" name="adminForm" class="esForm" id="adminForm" data-table-grid>
    <div class="app-filter filter-bar form-inline">
        <div class="form-group">
            <?php echo $this->html('filter.search', $search); ?>
        </div>

        <?php if ($this->tmpl != 'component') { ?>
        <div class="form-group">
            <strong><?php echo JText::_('COM_EASYSOCIAL_FILTER_BY'); ?> :</strong>
            <div>
                <?php echo $this->html('filter.published', 'state', $state); ?>
                <select class="form-control input-sm" name="type" id="filterType" data-table-grid-filter>
                    <option value="all"<?php echo $type == 'all' ? ' selected="selected"' : '';?>><?php echo JText::_('COM_EASYSOCIAL_FILTER_PROJECT_TYPE'); ?></option>
                    <option value="1"<?php echo $type == 1 ? ' selected="selected"' : '';?>><?php echo JText::_('COM_EASYSOCIAL_PROJECTS_OPEN_PROJECT'); ?></option>
                    <option value="2"<?php echo $type == 2 ? ' selected="selected"' : '';?>><?php echo JText::_('COM_EASYSOCIAL_PROJECTS_CLOSED_PROJECT'); ?></option>
                    <option value="3"<?php echo $type == 3 ? ' selected="selected"' : '';?>><?php echo JText::_('COM_EASYSOCIAL_PROJECTS_INVITE_PROJECT'); ?></option>
                </select>
            </div>
        </div>
        <?php } ?>

        <div class="form-group pull-right">
            <div><?php echo $this->html('filter.limit', $limit); ?></div>
        </div>
    </div>

    <div class="panel-table">
        <table class="app-table table table-eb table-striped">
            <thead>
                <tr>
                    <th width="1%" class="center">
                        <input type="checkbox" name="toggle" data-table-grid-checkall />
                    </th>

                    <th>
                        <?php echo $this->html('grid.sort', 'a.title', JText::_('COM_EASYSOCIAL_TABLE_COLUMN_TITLE'), $ordering, $direction); ?>
                    </th>

                    <?php if ($this->tmpl != 'component') { ?>
                    <th class="center" width="15%">
                        <?php echo $this->html('grid.sort', 'b.title', JText::_('COM_EASYSOCIAL_TABLE_COLUMN_CATEGORY'), $ordering, $direction); ?>
                    </th>

                    <th class="center" width="5%">
                        <?php echo $this->html( 'grid.sort' , 'a.featured' , JText::_('COM_EASYSOCIAL_TABLE_COLUMN_FEATURED') , $ordering , $direction ); ?>
                    </th>

                    <th width="5%" class="center">
                        <?php echo $this->html('grid.sort', 'a.state', JText::_('COM_EASYSOCIAL_TABLE_COLUMN_STATUS'), $ordering, $direction); ?>
                    </th>

                    <th class="center" width="10%">
                        <?php echo JText::_('COM_EASYSOCIAL_TABLE_COLUMN_TYPE');?>
                    </th>

                    <th class="center" width="5%">
                        <?php echo $this->html('grid.sort', 'a.created_by', JText::_('COM_EASYSOCIAL_TABLE_COLUMN_CREATED_BY'), $ordering, $direction); ?>
                    </th>

                    <th width="5%" class="center">
                        <?php echo JText::_('COM_EASYSOCIAL_TABLE_COLUMN_USERS'); ?>
                    </th>
                    <?php } ?>

                    <th class="center" width="10%">
                        <?php echo $this->html('grid.sort', 'a.created', JText::_('COM_EASYSOCIAL_TABLE_COLUMN_CREATED'), $ordering, $direction); ?>
                    </th>

                    <th width="5%" class="center">
                        <?php echo $this->html('grid.sort', 'a.id', JText::_('COM_EASYSOCIAL_TABLE_COLUMN_ID'), $ordering, $direction); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($projects)) { ?>
                <?php $i = 0;?>
                <?php foreach ($projects as $project) { ?>
                    <tr class="row<?php echo $i; ?>" data-grid-row data-id="<?php echo $project->id; ?>">
                        <td align="center">
                            <?php echo $this->html('grid.id', $i, $project->id); ?>
                        </td>

                        <td>
                            <a href="<?php echo FRoute::url(array('view' => 'projects', 'layout' => 'form', 'id' => $project->id));?>"
                                data-project-insert
                                data-id="<?php echo $project->id;?>"
                                data-avatar="<?php echo $project->getAvatar();?>"
                                data-title="<?php echo $this->html('string.escape', $project->getName());?>"
                                data-alias="<?php echo $project->getAlias();?>"
                            >
                                <?php echo JText::_($project->title); ?>
                            </a>

                            &mdash;
                            <?php if ($project->isOver()) { ?>
                                <?php echo JText::_('COM_EASYSOCIAL_PROJECTS_OVER_PROJECT'); ?>
                            <?php } ?>

                            <?php if ($project->isOngoing()) { ?>
                                <?php echo JText::_('COM_EASYSOCIAL_PROJECTS_ONGOING_PROJECT'); ?>
                            <?php } ?>

                            <?php if ($project->isUpcoming()) { ?>
                                <?php echo JText::_('COM_EASYSOCIAL_PROJECTS_UPCOMING_PROJECT'); ?>
                            <?php } ?>

                            <?php if ($project->isRecurringProject()) { ?>
                                <?php echo JText::_('COM_EASYSOCIAL_PROJECTS_RECURRING_PROJECT'); ?>
                            <?php } ?>
                        </td>

                        <?php if ($this->tmpl != 'component') { ?>
                        <td class="center">
                            <a href="<?php echo FRoute::url(array('view' => 'projects', 'layout' => 'category', 'id' => $project->category_id)); ?>" target="_blank"><?php echo JText::_($project->getCategory()->title); ?></a>
                        </td>

                        <td class="center">
                            <?php echo $this->html('grid.featured', $project, 'projects', 'featured'); ?>
                        </td>

                        <td class="center">
                            <?php echo $this->html('grid.published', $project, 'projects', 'state', array(2 => 'approve'), array(2 => 'COM_EASYSOCIAL_GRID_TOOLTIP_APPROVE_ITEM'), array(2 => 'pending')); ?>
                        </td>

                        <td class="center">
                            <?php if ($project->isOpen()){ ?>
                                <?php echo JText::_('COM_EASYSOCIAL_PROJECTS_OPEN_PROJECT'); ?>
                            <?php } ?>

                            <?php if ($project->isClosed()){ ?>
                                <?php echo JText::_('COM_EASYSOCIAL_PROJECTS_CLOSED_PROJECT'); ?>
                            <?php } ?>

                            <?php if ($project->isInviteOnly()){ ?>
                                <?php echo JText::_('COM_EASYSOCIAL_PROJECTS_INVITE_PROJECT'); ?>
                            <?php } ?>
                        </td>

                        <td class="center">
                            <a href="<?php echo FRoute::url(array('view' => 'users', 'layout' => 'form', 'id' => $project->getCreator()->id)); ?>" target="_blank"><?php echo $project->getCreator()->getName(); ?></a>
                        </td>

                        <td class="center">
                            <?php echo $project->getTotalGuests(); ?>
                        </td>
                        <?php } ?>

                        <td class="center">
                            <?php echo $project->created; ?>
                        </td>

                        <td class="center">
                            <?php echo $project->id;?>
                        </td>
                    </tr>
                <?php $i++; ?>
                <?php } ?>
            <?php } else { ?>
                <tr class="is-empty">
                    <td colspan="10" class="center empty">
                        <?php echo JText::_('COM_EASYSOCIAL_PROJECTS_NO_PROJECT_CREATED_YET');?>
                    </td>
                </tr>
            <?php } ?>
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="10" class="center">
                        <div class="footer-pagination"><?php echo $pagination->getListFooter(); ?></div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php echo JHTML::_('form.token'); ?>
    <input type="hidden" name="ordering" value="<?php echo $ordering;?>" data-table-grid-ordering />
    <input type="hidden" name="direction" value="<?php echo $direction;?>" data-table-grid-direction />
    <input type="hidden" name="boxchecked" value="0" data-table-grid-box-checked />
    <input type="hidden" name="task" value="" data-table-grid-task />
    <input type="hidden" name="option" value="com_easysocial" />
    <input type="hidden" name="view" value="projects" />
    <input type="hidden" name="controller" value="projects" />
</form>
