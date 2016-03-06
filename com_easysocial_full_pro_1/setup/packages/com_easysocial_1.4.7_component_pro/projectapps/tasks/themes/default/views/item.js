EasySocial.require()
.library('sparkline')
.script('apps/project/tasks')
.done(function($) {
    // Apply controller
    $('[data-tasks-item]').implement('EasySocial.Controller.Projects.Apps.Tasks', {
        "redirect": "<?php echo FRoute::projects(array('layout' => 'item', 'id' => $project->getAlias())); ?>"
    });

    $('[data-chart-milestone]').sparkline('html', {
        type: "pie",
        width: "120px",
        height: "120px",
        sliceColors: ["#2b94c5", "#BE1F23"],
        tooltipFormatter: function(sparkline, options, field) {
            var message = field.offset == 1 ? '<?php echo JText::_('APP_PROJECT_TASKS_CHART_OPEN_TASKS', true); ?>' : '<?php echo JText::_('APP_PROJECT_TASKS_CHART_CLOSED_TASKS', true); ?>';

            return '<span style="color: ' + field.color + '">&#9679;</span> <strong>' + field.value + '</strong> ' + message;
        }
    });
});
