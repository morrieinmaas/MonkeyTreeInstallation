EasySocial.module('admin/projects/approveRecurring', function($) {
    var module = this;

    EasySocial.Controller('Projects.ApproveRecurring', {
        defaultOptions: {
            postdatas: {},
            schedules: {},
            projectids: [],

            '{progress}': '[data-progress-bar]',

            '{form}': '[data-form]'
        }
    }, function(self) {
        return {
            init: function() {
                // Calculate the total things to do
                var length = 0;

                $.each(self.options.schedules, function(i, s) {
                    length += s.length;
                });

                self.total = length;

                self.startCreate();
            },

            total: 0,
            doneCounter: 0,
            projectCounter: 0,
            createCounter: 0,

            updateProgressBar: function() {
                var percentage = Math.ceil((self.doneCounter / self.total) * 100);

                self.progress().css({
                    width: percentage + '%'
                });
            },

            startCreate: function() {
                if (self.options.projectids[self.projectCounter] === undefined) {
                    return self.completed();
                }

                self.create()
                    .done(function() {
                        self.doneCounter++;

                        self.createCounter++;

                        if (self.options.schedules[self.options.projectids[self.projectCounter]][self.createCounter] === undefined) {
                            self.projectCounter++;
                            self.createCounter = 0;
                        }

                        self.updateProgressBar();

                        self.startCreate();
                    })
                    .fail(function(msg, errors) {
                        console.log(msg, errors);
                    });
            },

            create: function() {
                var projectId = self.options.projectids[self.projectCounter],
                    datetime = self.options.schedules[projectId][self.createCounter],
                    postdata = self.options.postdatas[projectId];

                return EasySocial.ajax('admin/controllers/projects/createRecurring', {
                    projectId: projectId,
                    datetime: datetime,
                    postdata: postdata
                });
            },

            completed: function() {
                self.progress().parent().removeClass('progress-info').addClass('progress-success');
                self.form().submit();
            }
        }
    });

    module.resolve();
});
