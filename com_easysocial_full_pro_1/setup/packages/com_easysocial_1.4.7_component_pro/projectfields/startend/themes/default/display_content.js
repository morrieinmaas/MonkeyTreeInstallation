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
EasySocial.require().script('apps/fields/project/startend/display').done(function($) {
    $('[data-display-field="<?php echo $field->id; ?>"]').addController('EasySocial.Controller.Field.Project.Startend.Display', {
        id: <?php echo $field->id; ?>,
        userid: <?php echo $this->my->id; ?>
    });
});