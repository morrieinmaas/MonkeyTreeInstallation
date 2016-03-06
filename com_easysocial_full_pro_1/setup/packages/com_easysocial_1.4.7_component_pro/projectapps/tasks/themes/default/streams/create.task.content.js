EasySocial.ready(function($) {

    $('[data-task-<?php echo $stream->uid; ?>-checkbox]').on('change', function() {
        var taskId = $(this).val(),
            parentItem = $(this).parents('li');

        if ($(this).is(':checked')) {
            EasySocial.ajax('apps/project/tasks/controllers/tasks/resolve', {
                "id": taskId,
                "projectId": "<?php echo $project->id; ?>"
            })
            .done(function() {
                $(parentItem).addClass('completed');
            });
        } else {
            EasySocial.ajax('apps/project/tasks/controllers/tasks/unresolve', {
                "id": taskId,
                "projectId": "<?php echo $project->id; ?>"
            })
            .done(function() {
                $(parentItem).removeClass('completed');
            });
        }
    });
});
