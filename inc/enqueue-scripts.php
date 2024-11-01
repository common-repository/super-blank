<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('superBlankCustomFrontendStyle')) {
    /**
     * Enqueue frontend scripts.
     * 
     * @return void
     */
    function superBlankCustomFrontendStyle() {

        wp_enqueue_style('super-blank-frontend-css', SUPER_BLANK_PLUGIN_URL . 'assets/css/frontend-styles.css', [], SUPER_BLANK_PLUGIN_VERSION, 'all');
    }
}

add_action('wp_enqueue_scripts', 'superBlankCustomFrontendStyle');


if (!function_exists('superBlankCustomAdminStyle')) {
    /**
     * Enqueue admin scripts.
     * 
     * @return void
     */
    function superBlankCustomAdminStyle() {

        wp_enqueue_style('super-blank-admin-css', SUPER_BLANK_PLUGIN_URL . 'assets/css/admin-styles.css', [], SUPER_BLANK_PLUGIN_VERSION, 'all');

        wp_enqueue_script('super-blank-admin-js', SUPER_BLANK_PLUGIN_URL . 'assets/js/scripts.js', [], SUPER_BLANK_PLUGIN_VERSION, true);

        // Localize the script with new data
		wp_localize_script(
			'super-blank-admin-js',
			'superBlankLocalizer',
			[
				'plugin_version' => SUPER_BLANK_PLUGIN_VERSION,
				'nonce' => wp_create_nonce('install_super_blank'),
				'ajax_url' => admin_url('admin-ajax.php'),
				'site_url' => home_url(),
				'productionMode' => SUPER_BLANK_PRODUCTION,
                'menuData' => [
                    'name' => 'Primary',
                    'slug' => 'primary',
                    'locations' => [
                        'primary',
                        'mobile_menu'
                    ]
                ]
            ]
		);
    }
}

add_action('admin_enqueue_scripts', 'superBlankCustomAdminStyle');