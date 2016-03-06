EasySocial.require()
.script('apps/project/discussions', 'prism')
.done(function($) {

    $('[data-project-discussion-item]')
        .implement(EasySocial.Controller.Projects.Item.Discussion);
});
