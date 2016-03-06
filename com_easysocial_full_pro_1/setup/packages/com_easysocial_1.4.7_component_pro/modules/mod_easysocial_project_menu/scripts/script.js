EasySocial.require().library('dialog').done(function($)
{
    var pendingList = $('.es-project-menu-pending');

    $('[data-project-menu-approve]').on('click',function() {
        var el = $(this),
            id = el.data('id');

        EasySocial.dialog({
            content: EasySocial.ajax('site/views/projects/confirmApproveGuest', {
                "id": id
            }),
            bindings: {
                '{approveButton} click': function() {
                    EasySocial.ajax('site/controllers/projects/approveGuest', {
                        'id': id
                    })
                    .done(function() {
                        EasySocial.dialog().close();

                        // Remove guest from the pending list
                        el.parents('li').remove();

                        pendingList.find('li').length === 0 && pendingList.remove();
                    });
                }
            }
        });
    });

    $('[data-project-menu-reject]').on('click',function() {
        var el = $(this),
            id = el.data('id');

        EasySocial.dialog({
            content: EasySocial.ajax('site/views/projects/confirmRejectGuest', {
                "id": id
            }),
            bindings: {
                '{approveButton} click': function() {
                    EasySocial.ajax('site/controllers/projects/rejectGuest', {
                        'id': id
                    })
                    .done(function() {
                        EasySocial.dialog().close();

                        // Remove guest from the pending list
                        el.parents('li').remove();

                        pendingList.find('li').length === 0 && pendingList.remove();
                    });
                }
            }
        });
    });
});
