EasySocial.require().script('apps/fields/project/recurring/content').done(function($) {

    $('[data-field-<?php echo $field->id; ?>]').addController('EasySocial.Controller.Field.Project.Recurring', {
        value: <?php echo FD::json()->encode($original); ?>,
        allday: <?php echo $allday ? 1 : 0; ?>,
        showWarningMessages: <?php echo $showWarningMessages; ?>,
        projectId: <?php echo isset($projectId) ? $projectId : 'null'; ?>
    });

});
