EasySocial.module('admin/projects/users', function($) {
    var module = this;

    EasySocial
        .require()
        .language('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST')
        .done(function($) {
            EasySocial.Controller('Projects.Users', {
                defaultOptions: {
                    projectid: null,

                    '{inviteGuest}': '[data-project-invite-guest]',
                    '{removeGuest}': '[data-project-remove-guest]',
                    '{approveGuest}': '[data-project-approve-guest]',
                    '{promoteGuest}': '[data-project-promote-guest]',
                    '{demoteGuest}': '[data-project-demote-guest]'
                }
            }, function(self) {
                return {
                    init: function() {
                    },

                    '{inviteGuest} click': function(el, ev) {
                        var guests = {};

                        window.inviteGuests = function(guest) {
                            if (guest.state) {
                                guests[guest.id] = guest
                            } else {
                                delete guests[guest.id];
                            }
                        };

                        var confirmInviteGuests = function() {
                            EasySocial.dialog({
                                content: EasySocial.ajax('admin/views/projects/confirmInviteGuests', {
                                    guests: guests,
                                    projectid: self.options.projectid
                                }),
                                bindings: {
                                    '{submitButton} click': function() {
                                        this.inviteGuestsForm().submit();
                                    }
                                }
                            });
                        };

                        EasySocial.dialog({
                            content: EasySocial.ajax('admin/views/projects/inviteGuests'),
                            bindings: {
                                '{submitButton} click': function() {
                                    confirmInviteGuests();
                                }
                            }
                        });
                    },

                    '{removeGuest} click': function(el, ev) {
                        if(document.adminForm.boxchecked.value == 0) {
                            alert($.language('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST'));
                        } else {
                            $.Joomla('submitform', ['removeGuests']);
                        }
                    },

                    '{approveGuest} click': function(el, ev) {
                        if(document.adminForm.boxchecked.value == 0) {
                            alert($.language('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST'));
                        } else {
                            $.Joomla('submitform', ['approveGuests']);
                        }
                    },

                    '{promoteGuest} click': function(el, ev) {
                        if(document.adminForm.boxchecked.value == 0) {
                            alert($.language('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST'));
                        } else {
                            $.Joomla('submitform', ['promoteGuests']);
                        }
                    },

                    '{demoteGuest} click': function(el, ev) {
                        if(document.adminForm.boxchecked.value == 0) {
                            alert($.language('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST'));
                        } else {
                            $.Joomla('submitform', ['demoteGuests']);
                        }
                    }
                }
            });

            module.resolve();
        });
});
