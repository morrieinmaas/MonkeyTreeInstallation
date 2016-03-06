EasySocial.require()
.library('dialog')
.done(function($)
{
    <?php if (FD::version()->getVersion() < 3) { ?>
        $('body').addClass('com_easysocial25');
    <?php } ?>

    window.selectProject = function(obj) {
        $('[data-jfield-project-title]').val(obj.title);

        $('[data-jfield-project-value]').val(obj.alias);

        EasySocial.dialog().close();
    }

    $('[data-jfield-project]').on('click', function() {
        EasySocial.dialog({
            content: EasySocial.ajax('admin/views/projects/browse', {
                'jscallback': 'selectProject'
            })
        });
    });
});
