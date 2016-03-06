EasySocial.require()
.script('apps/project/guests')
.done(function($)
{
    $('[data-project-guests]').implement(EasySocial.Controller.Projects.Item.Guests);
})
