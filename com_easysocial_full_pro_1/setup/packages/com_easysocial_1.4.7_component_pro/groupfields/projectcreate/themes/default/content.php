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
<div>
    <div class="checkbox">
        <label for="projectcreate-owner" class="option">
        <input type="checkbox" disabled="disabled" checked="checked" id="projectcreate-owner" />
        <?php echo JText::_('FIELDS_GROUP_PROJECTCREATE_OPTION_OWNER'); ?>
        </label>
    </div>
    <div class="checkbox">
        <label for="projectcreate-admin" class="option">
        <input type="checkbox" id="projectcreate-admin" name="projectcreate[]" value="admin" <?php if (!empty($value) && in_array('admin', $value)) { ?>checked="checked"<?php } ?> />
        <?php echo JText::_('FIELDS_GROUP_PROJECTCREATE_OPTION_ADMIN'); ?>
        </label>
    </div>
    <div class="checkbox">
        <label for="projectcreate-user" class="option">
        <input type="checkbox" id="projectcreate-user" name="projectcreate[]" value="member" <?php if (!empty($value) && in_array('member', $value)) { ?>checked="checked"<?php } ?> />
        <?php echo JText::_('FIELDS_GROUP_PROJECTCREATE_OPTION_MEMBER'); ?>
        </label>
    </div>
</div>
