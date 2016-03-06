EasySocial.require()
.script('apps/project/tasks')
.done(function($) {
    $('[data-tasks-milestones]').implement(EasySocial.Controller.Projects.Apps.Tasks.Milestones.Browse)
});
