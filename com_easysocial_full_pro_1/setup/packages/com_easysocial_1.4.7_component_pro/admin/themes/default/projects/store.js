EasySocial.require().script('admin/projects/store').done(function($) {
    $('[data-recurring-projects]').addController('EasySocial.Controller.Projects.Update', {
        postdata: <?php echo $data; ?>,
        updateids: <?php echo $updateids; ?>,
        schedule: <?php echo $schedule; ?>,
        projectId: <?php echo $project->id; ?>
    });
})
