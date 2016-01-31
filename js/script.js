(function($) {
    $(document).ready(function() {
        $("#usergroups-select").change(function() {
            var action = $(this).parents("form").attr('action');
            if(action.match(/\?/i)) {
                action = action + '&user-group=' + $(this).val();
            } else {
                action = action + '?user-group=' + $(this).val();
            }

            window.location = action;
        });
    });
})(jQuery);
