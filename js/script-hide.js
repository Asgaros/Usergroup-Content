(function($) {
    $(document).ready(function() {
        $('#menu-posts').removeClass('wp-menu-open wp-has-current-submenu').addClass('wp-not-current-submenu');
        $('#menu-users').addClass('wp-has-current-submenu wp-menu-open menu-top menu-top-first').removeClass('wp-not-current-submenu');
        $('#menu-users a.wp-has-submenu').addClass('wp-has-current-submenu wp-menu-open menu-top');
        $('#menu-posts a.wp-has-submenu').removeClass('wp-has-current-submenu wp-menu-open menu-top');
        
        $('.inline-edit-col input[name=slug]').parents('label').hide();

        // Adding color picker
        $('.custom-color').wpColorPicker();
    });
})(jQuery);
