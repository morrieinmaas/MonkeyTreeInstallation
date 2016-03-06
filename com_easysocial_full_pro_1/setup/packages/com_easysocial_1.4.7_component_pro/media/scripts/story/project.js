EasySocial.module('story/project', function($) {
    var module = this;

    var lang = EasySocial.options.momentLang;

    EasySocial.require()
    .library('datetimepicker', 'moment/' + lang)
    .view('site/loading/small')
    .language('COM_EASYSOCIAL_STORY_PROJECT_INSUFFICIENT_DATA', 'COM_EASYSOCIAL_STORY_PROJECT_INVALID_START_END_DATETIME')
    .done(function() {
        EasySocial.Controller('Story.Project', {
            defaultOptions: {
                '{base}': '[data-story-project-base]',

                '{category}': '[data-story-project-category]',
                '{form}': '[data-story-project-form]',

                '{timezone}': '[data-project-timezone]',

                '{datetimeForm}': '[data-project-datetime-form]',

                '{datetime}': '[data-project-datetime]',

                '{title}': '[data-project-title]',
                '{description}': '[data-project-description]',

                view: {
                    loading: 'site/loading/small'
                }
            }
        }, function(self) {
            return {
                init: function() {
                },

                '{category} change': function(el, ev) {
                    if(el.val()) {
                        self.form()
                            .show()
                            .html(self.view.loading());

                        self.loadStoryForm(el.val()).done(function(html) {
                            self.form().html(html);

                            var data = self.datetimeForm().htmlData();

                            var yearto;

                            if (!$.isEmpty(data.yearto)) {
                                yearto = parseInt(data.yearto) + 1;
                            } else {
                                yearto = new Date().getFullYear() + 100
                            }

                            $.extend(self.options, {
                                yearfrom: data.yearfrom || 1930,
                                yearto: yearto,
                                allowTime: data.allowtime,
                                allowTimezone: data.allowtimezone,
                                dateFormat: data.dateformat,
                                disallowPast: data.disallowpast,
                                minuteStepping: parseInt(data.minutestepping)
                            });

                            self.datetime().addController('EasySocial.Controller.Story.Projects.Datetime', {
                                '{parent}': self
                            });
                        });
                    } else {
                        self.form()
                            .hide()
                            .html('');
                    }
                },

                loadStoryForm: $.memoize(function(id) {
                    return EasySocial.ajax('apps/user/projects/controllers/projects/loadStoryForm', {
                        id: id
                    });
                }),

                '{story} save': function(element, project, save) {

                    if (save.currentPanel != 'project') {
                        return;
                    }

                    var data = {
                        title: self.title().val(),
                        description: self.description().val(),
                        category: self.category().val()
                    };

                    if (self.options.allowTimezone) {
                        data.timezone = self.timezone().val()
                    }

                    self.datetime().trigger('datetimeExport', [data]);

                    self.options.name = 'project';

                    var task = save.addTask('validateProjectForm');

                    self.save(task, data);
                },

                save: function(task, data) {
                    if ($.isEmpty(data.title)
                        || $.isEmpty(data.category)
                        || $.isEmpty(data.start)) {

                        return task.reject($.language('COM_EASYSOCIAL_STORY_PROJECT_INSUFFICIENT_DATA'));
                    }

                    if (!$.isEmpty(data.start) && !$.isEmpty(data.end) && data.end < data.start) {
                        return task.reject($.language('COM_EASYSOCIAL_STORY_PROJECT_INVALID_START_END_DATETIME'));
                    }

                    task.save.addData(self, data);

                    task.resolve();
                }
            }
        });

        EasySocial.Controller('Story.Projects.Datetime', {
            defaultOptions: {
                type: null,

                '{picker}': '[data-picker]',
                '{toggle}': '[data-picker-toggle]',
                '{datetime}': '[data-datetime]'
            }
        }, function(self) {
            return {
                init: function() {
                    self.options.type = self.element.data('project-datetime');

                    var minDate = new $.moment();

                    if (self.parent.options.disallowPast) {
                        // Minus 1 on the date to allow today
                        minDate.date(minDate.date() - 1);
                    } else {
                        minDate.year(self.parent.options.yearfrom);
                    }

                    self.picker()._datetimepicker({
                        component: "es",
                        useCurrent: false,
                        format: self.parent.options.dateFormat,
                        minDate: minDate,
                        maxDate: new $.moment({y: self.parent.options.yearto}),
                        icons: {
                            time: 'glyphicon glyphicon-time',
                            date: 'glyphicon glyphicon-calendar',
                            up: 'glyphicon glyphicon-chevron-up',
                            down: 'glyphicon glyphicon-chevron-down'
                        },
                        sideBySide: false,
                        pickTime: self.parent.options.allowTime == 1,
                        minuteStepping: self.parent.options.minuteStepping,
                        language: lang
                    });

                    var dateObj = $.moment();

                    dateObj.minute(0);
                    dateObj.second(0);

                    // If this is end, manually add 1 hour
                    if (self.options.type == 'end') {
                        dateObj.hour(dateObj.hour() + 1);
                    }

                    self.datetimepicker('setDate', dateObj);
                },

                datetimepicker: function(name, value) {
                    return self.picker().data('DateTimePicker')[name](value);
                },

                '{toggle} click': function() {
                    self.picker().focus();
                },

                '{picker} dp.change': function(el, ev) {
                    self.setDateValue(ev.date.toDate());

                    self.parent.element.trigger('project' + $.String.capitalize(self.options.type), [ev.date]);
                },

                setDateValue: function(date) {
                    // Convert the date object into sql format and set it into the input
                    self.datetime().val(date.getFullYear() + '-' +
                                        ('00' + (date.getMonth()+1)).slice(-2) + '-' +
                                        ('00' + date.getDate()).slice(-2) + ' ' +
                                        ('00' + date.getHours()).slice(-2) + ':' +
                                        ('00' + date.getMinutes()).slice(-2) + ':' +
                                        ('00' + date.getSeconds()).slice(-2));
                },

                '{parent} projectStart': function(el, ev, date) {
                    if (self.options.type === 'start') {
                        return;
                    }

                    // self.datetimepicker('setMinDate', date ? date : new $.moment({y: self.parent.options.yearfrom}));
                },

                '{parent} projectEnd': function(el, ev, date) {
                    if (self.options.type === 'end') {
                        return;
                    }

                    // self.datetimepicker('setMaxDate', date ? date : new $.moment({y: self.parent.options.yearto}));
                },

                '{self} datetimeExport': function(el, ev, data) {
                    data[self.options.type] = self.datetime().val();
                }
            }
        })

        module.resolve();
    });
});
