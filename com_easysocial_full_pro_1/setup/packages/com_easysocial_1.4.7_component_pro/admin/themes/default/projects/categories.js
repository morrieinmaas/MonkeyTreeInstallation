EasySocial.require().script('admin/grid/grid').done(function($) {
    $('[data-table-grid]').implement('EasySocial.Controller.Grid');

    <?php if( $this->tmpl == 'component' ){ ?>
        
        $('[data-category-insert]').on('click', function(project) {
            project.preventDefault();

            // Supply all the necessary info to the caller
            var id      = $( this ).data( 'id' ),
                avatar  = $( this ).data( 'avatar' ),
                title   = $( this ).data( 'title' ),
                alias   = $(this).data( 'alias' );

                obj     = {
                            "id"    : id,
                            "title" : title,
                            "avatar" : avatar,
                            "alias" : alias
                          };

            window.parent["<?php echo JRequest::getCmd( 'jscallback' );?>" ]( obj );
        });
        
    <?php } else { ?>
        $.Joomla('submitbutton', function(task) {
            if (task == 'deleteCategory') {
                EasySocial.dialog({
                    content: EasySocial.ajax('admin/views/projects/deleteCategoryDialog'),
                    bindings: {
                        '{deleteButton} click': function() {
                            $.Joomla('submitform', [task]);
                        }
                    }
                });

                return false;
            }

            if (task == 'categoryForm') {
                window.location = "<?php echo FRoute::url(array('view' => 'projects', 'layout' => 'categoryForm')); ?>";

                return false;
            }

            $.Joomla('submitform', [task]);
        });
    <?php } ?>
});