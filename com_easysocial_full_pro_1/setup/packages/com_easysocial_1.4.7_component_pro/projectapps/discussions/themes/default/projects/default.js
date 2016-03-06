EasySocial.require()
.script('apps/project/discussions')
.done(function($)
{
    $('[data-project-discussions]').implement(EasySocial.Controller.Projects.Item.Discussions);

})
