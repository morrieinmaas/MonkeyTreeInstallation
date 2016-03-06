EasySocial.module('site/projects/createRecurring', function($) {
    var module = this;

    EasySocial.Controller('Projects.CreateRecurring', {
        defaultOptions: {
            schedule: [],

            projectId: null,

            '{progress}': '[data-progress-bar]',

            '{form}': '[data-form]'
        }
    }, function(self) {
        return {
            init: function() {
                self.start();
            },

            counter: 0,

            start: function() {
                if (self.options.schedule[self.counter] === undefined) {
                    return self.completed();
                }

                self.create(self.options.schedule[self.counter])
                    .done(function() {
                        self.counter++;

                        var percentage = Math.ceil((self.counter / self.options.schedule.length) * 100);

                        self.progress().css({
                            width: percentage + '%'
                        });

                        self.start();
                    })
                    .fail(function(msg) {
                        console.log(msg);
                    });
            },

            create: function(datetime) {
                return EasySocial.ajax('site/controllers/projects/createRecurring', {
                    projectId: self.options.projectId,
                    datetime: datetime
                });
            },

            completed: function() {
                self.progress().parent().removeClass('progress-info').addClass('progress-success');
                self.form().submit();
            }
        }
    })

    module.resolve();
});
