EasySocial.require().script('site/projects/browser').done(function($) {
    $('[data-group-projects-list]').addController('EasySocial.Controller.Projects.Browser', {
        group: '<?php echo $group->id; ?>'
    });
});
