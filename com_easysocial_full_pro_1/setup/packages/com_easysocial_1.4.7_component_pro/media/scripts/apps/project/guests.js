EasySocial.module('apps/project/guests', function($) {

    var module  = this;


    EasySocial.Controller('Projects.Item.Guests', {
        defaultOptions: {
            '{filters}': '[data-project-guests-filter]',
            '{content}': '[data-project-guests-content]'
        }
    }, function(self) {
        return {
            init : function()
            {
                self.options.id = self.element.data('id');

                self.items = self.addPlugin('Item');
            },

            '{filters} click': function(el, event)
            {
                event.preventDefault();

                el.route();

                // Remove active
                self.filters().removeClass('active');

                // Set current to active
                el.addClass('active');

                // Get the filter
                var filter  = el.data('filter');

                // Set the loading class
                self.content().html('&nbsp;');
                self.content().addClass('is-loading');

                EasySocial.ajax('apps/project/guests/controllers/projects/filterGuests', {
                    'id': self.options.id,
                    'filter': filter
                }).done(function(contents, total) {
                    self.content().removeClass('is-loading');

                    if (total == 0) {
                        self.content().addClass('is-empty');
                    } else {
                        self.content().removeClass('is-empty');
                    }
                    self.content().html(contents);
                });
            },

            '{self} emptyGuest': function() {
                self.content().addClass('is-empty');
            }
        }
    });

    EasySocial.Controller(
        'Projects.Item.Guests.Item',
        {
            defaultOptions:
            {
                '{item}': '[data-project-guest-item]',
                '{promote}': '[data-guest-promote]',
                '{demote}': '[data-guest-demote]',
                '{approve}': '[data-guest-approve]',
                '{reject}': '[data-guest-reject]',
                '{remove}': '[data-guest-remove]'
            }
        },
        function( self )
        {
            return {
                init : function()
                {
                },

                getItem: function(el)
                {
                    var item = self.item.of(el);

                    return item;
                },

                '{approve} click' : function(el)
                {
                    var item = self.getItem(el),
                        guestId = item.data('guestId');

                    EasySocial.dialog({
                        content: EasySocial.ajax('site/views/projects/confirmApproveGuest', {
                            'id': guestId
                        }),
                        bindings: {
                            '{approveButton} click': function() {
                                EasySocial.ajax('site/controllers/projects/approveGuest', {
                                    'id': guestId
                                })
                                .done(function() {
                                    EasySocial.dialog().close();

                                    // Remove guest from the pending list
                                    item.remove();

                                    self.item().length === 0 && self.element.trigger('emptyGuest');
                                });
                            }
                        }
                    });
                },

                '{reject} click' : function(el)
                {
                    var item = self.getItem(el),
                        guestId = item.data('guestId');

                    EasySocial.dialog({
                        content: EasySocial.ajax('site/views/projects/confirmRejectGuest', {
                            'id': guestId
                        }),
                        bindings: {
                            '{rejectButton} click': function() {
                                EasySocial.ajax('site/controllers/projects/rejectGuest', {
                                    'id': guestId
                                })
                                .done(function() {
                                    EasySocial.dialog().close();

                                    // Remove guest from the pending list
                                    item.remove();

                                    self.item().length === 0 && self.element.trigger('emptyGuest');
                                });
                            }
                        }
                    });
                },

                '{promote} click' : function(el)
                {
                    var item = self.getItem(el),
                        guestId = item.data('guestId');


                    EasySocial.dialog({
                        content: EasySocial.ajax('site/views/projects/confirmPromoteGuest', {
                            'id': guestId
                        }),
                        bindings: {
                            '{promoteButton} click': function() {
                                EasySocial.ajax('site/controllers/projects/promoteGuest', {
                                    'id': guestId
                                }).done(function() {
                                    EasySocial.dialog().close();

                                    // Add the admin label
                                    item.removeClass('is-member')
                                        .addClass('is-admin');
                                });
                            }
                        }
                    })
                },

                '{demote} click' : function(el)
                {
                    var item = self.getItem(el),
                        guestId = item.data('guestId');

                    EasySocial.dialog({
                        content: EasySocial.ajax('site/views/projects/confirmDemoteGuest', {
                            'id': guestId
                        }),
                        bindings: {
                            '{demoteButton} click' : function() {
                                EasySocial.ajax('site/controllers/projects/demoteGuest', {
                                    'id': guestId
                                })
                                .done(function() {
                                    EasySocial.dialog().close();

                                    // If the current tab is admin, then we remove instead
                                    if (self.parent.filters('.active').data('filter') == 'admin') {
                                        item.remove();

                                        self.item().length === 0 && self.element.trigger('emptyGuest');
                                    } else {
                                        // Remove the admin label
                                        item.removeClass('is-admin').addClass('is-member');
                                    }
                                });

                            }
                        }
                    });
                },

                '{remove} click' : function(el, event)
                {
                    var item = self.getItem(el),
                        guestId = item.data('guestId');

                    EasySocial.dialog({
                        content: EasySocial.ajax('site/views/projects/confirmRemoveGuest', {
                            'id': guestId
                        }),
                        bindings: {
                            '{removeButton} click': function() {
                                EasySocial.ajax('site/controllers.projects/removeGuest', {
                                    'id': guestId
                                })
                                .done(function() {
                                    EasySocial.dialog().close();

                                    // Remove guest from the list
                                    item.remove();

                                    self.item().length === 0 && self.element.trigger('emptyGuest');
                                });
                            }
                        }
                    });
                }
            }
        }
    );


    module.resolve();
});

