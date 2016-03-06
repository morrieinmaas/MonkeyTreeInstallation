EasySocial.ready(function($) {
    $('input[name="project_allday"]').on('change', function() {
        $(window).trigger('easysocial.fields.allday.change', [$(this).val()]);
    });
});
