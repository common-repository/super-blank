<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('superBlankCustomAdminMenu')) {
    /**
     * Add admin menu.
     * 
     * @return void
     */
    function superBlankCustomAdminMenu()
    {

        $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><rect width="20" height="20" fill="#808080"/></svg>';
        $icon_base64 = base64_encode($icon_svg);

        add_menu_page(
            'Super Blank',
            'Super Blank',
            'manage_options',
            'super-blank-page',
            'superBlankPageContent',
            'data:image/svg+xml;base64,' . $icon_base64
        );
    }
}

add_action('admin_menu', 'superBlankCustomAdminMenu');

if (!function_exists('superBlankPageContent')) {
    /**
     * Admin page content.
     * 
     * @return void
     */
    function superBlankPageContent()
    {
?>
        <div class="wrap super-blank-admin-page">

            <div class="super-blank-wrap">

                <span class="super-blank-heading"><?php esc_html_e('Super Blank will erase your website and...', 'super-blank'); ?></span>

                <p class="super-blank-description">
                    <?php esc_html_e('Add pages (Home, About, Contact), configure header and footer, install Astra theme & WForms plugin.', 'super-blank'); ?>
                </p>

                <div id="status-message" class="super-blank-warning">
                    <?php esc_html_e('This will delete and replace your entire website!', 'super-blank'); ?>
                </div>

                <div class="super-blank-button">
                    <a href="#" class="button" id="super-blank-install"><?php esc_html_e('Let’s Do This', 'super-blank'); ?></a>
                </div>
            </div>
        </div>
<?php
    }
}
