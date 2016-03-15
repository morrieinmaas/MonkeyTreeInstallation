EasySocial.require().script('admin/projects/approveRecurring').done(function($) {
    $('[data-recurring-projects]').addController('EasySocial.Controller.Projects.ApproveRecurring', {
        postdatas: <?php echo $postdatas; ?>,
        schedules: <?php echo $schedules; ?>,
        projectids: <?php echo $projectids; ?>
    });
})
