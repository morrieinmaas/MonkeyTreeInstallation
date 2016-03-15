EasySocial.require().script('admin/grid/grid').done(function($) {
    $('[data-table-grid]').addController('EasySocial.Controller.Grid');

    <?php if( $this->tmpl != 'component' ){ ?>
    $.Joomla('submitbutton', function(task) {
        var selected = [];

        $('[data-table-grid]').find('[data-table-grid-id]:checked').each(function(i, el) {
            selected.push($(el).val());
        });

        if (task === 'create') {
            EasySocial.dialog({
                content: EasySocial.ajax('admin/views/projects/createDialog'),
                bindings: {
                    '{continueButton} click': function() {
                        var categoryId = this.category().val();

                        window.location = 'index.php?option=com_easysocial&view=projects&layout=form&category_id=' + categoryId;
                    }
                }
            });

            return false;
        }

        if (task == 'makeFeatured' || task == 'removeFeatured') {
            $('[data-table-grid-task]').val(task);

            $('[data-table-grid]').submit();

            return false;
        }

        if (task === 'delete') {
            EasySocial.dialog({
                content: EasySocial.ajax('admin/views/projects/deleteDialog'),
                bindings: {
                    '{deleteButton} click': function() {
                        $.Joomla('submitform', [task]);
                    }
                }
            });

            return false;
        }

        if (task === 'switchOwner') {
            EasySocial.dialog({
                content: EasySocial.ajax('admin/views/projects/switchOwner', {
                    ids: selected
                })
            });

            return false;
        }

        if (task === 'switchCategory') {
            EasySocial.dialog({
                content: EasySocial.ajax('admin/views/projects/switchCategory', {
                    ids: selected
                })
            });

            return false;
        }

        $.Joomla('submitform', [task]);
    });

    window.switchOwner = function(user, projectIds) {
        EasySocial.dialog({
            content: EasySocial.ajax('admin/views/projects/confirmSwitchOwner', {
                ids: projectIds,
                userId: user.id
            })
        });
    }
    <?php } else { ?>

        $('[data-project-insert]').on('click', function(project){
            project.preventDefault();

            // Supply all the necessary info to the caller
            var id = $(this).data('id'),
                avatar = $(this).data('avatar'),
                title = $(this).data('title'),
                alias = $(this).data('alias');

                obj     = {
                            "id"    : id,
                            "title" : title,
                            "avatar" : avatar,
                            "alias" : alias
                          };

            window.parent["<?php echo JRequest::getCmd( 'jscallback' );?>" ]( obj );
        });

    <?php } ?>
});
