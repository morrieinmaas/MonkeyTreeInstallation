EasySocial.require().script('admin/grid/grid', 'admin/users/form', 'validate', 'admin/projects/users').done(function($) {
    $('[data-table-grid]').addController('EasySocial.Controller.Grid');

    var form = $('[data-projects-form]');

    form.implement('EasySocial.Controller.Users.Form', {
        mode: 'adminedit'
    });

    <?php if (!$isNew) { ?>

    form.find('[data-tabnav]').click(function(project) {
        var name = $(this).data('for');

        form.find('[data-active-tab]').val(name);
    });

    $('[data-projects-form-guests]').addController('EasySocial.Controller.Projects.Users', {
        projectid: <?php echo $project ? $project->id : 0; ?>
    });

    <?php } ?>

    $.Joomla('submitbutton', function(task) {
        if (task === 'cancel') {
            window.location = "<?php echo FRoute::url(array('view' => 'projects')); ?>";

            return false;
        }

        var dfd = [];

        dfd.push(form.validate());

        $.when.apply(null, dfd)
            .done(function() {
                <?php if ($isNew || !$project->hasRecurringProjects()) { ?>

                $.Joomla('submitform', [task]);

                <?php } else { ?>

                EasySocial.dialog({
                    content: EasySocial.ajax('admin/views/projects/applyRecurringDialog'),
                    bindings: {
                        '{applyThisButton} click': function() {
                            $('input[name="applyRecurring"]').val(0);
                            $.Joomla('submitform', [task]);
                        },

                        '{applyAllButton} click': function() {
                            $('input[name="applyRecurring"]').val(1);
                            $.Joomla('submitform', [task]);
                        },

                        '{cancelButton} click': function() {
                            EasySocial.dialog().close();
                        }
                    }
                });

                <?php } ?>
            })
            .fail(function() {
                EasySocial.dialog({
                    content: EasySocial.ajax('admin/views/users/showFormError')
                });
            });
    });
});
