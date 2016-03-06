EasySocial.require()
.library('dialog')
.done(function($)
{
    <?php if (FD::version()->getVersion() < 3) { ?>
        $('body').addClass('com_easysocial25');
    <?php } ?>

    window.selectProjectCategory  = function(obj) {
        $('[data-jfield-projectcategory-title]').val(obj.title);

        $('[data-jfield-projectcategory-value]').val(obj.id + ':' + obj.alias);

        EasySocial.dialog().close();
    }

    $('[data-jfield-projectcategory]').on('click', function() {
        EasySocial.dialog({
            content: EasySocial.ajax('admin/views/projects/browseCategory', {
                'jscallback': 'selectProjectCategory'
            })
        });
    });
});
