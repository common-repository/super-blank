<?php

namespace SuperBlank\Endpoints;

if (!defined('ABSPATH')) {
    exit;
}

use WP_REST_Response;
use WP_Error;
use WP_Query;

class HandleStepSeven
{

    public function __construct()
    {

        add_action('wp_ajax_super_blank_step7', [$this, 'handle_step']);
    }

    public function handle_step()
    {

        // Checked POST nonce is not empty.
        if (empty($_POST['nonce'])) wp_die('0');

        $nonce = sanitize_key(wp_unslash($_POST['nonce']));

        if (!wp_verify_nonce($nonce, 'install_super_blank')) {

            echo wp_json_encode(new WP_Error('error_data', 'Invalid nonce', array('status' => 403)));

            wp_die();
        }

        /**
         * Execution code here
         */

        // Import wp_options.json if exists
        $this->importOptions();

        // Disable ads
        $this->disableWpFormsAdsAndNotifications();

        // Update elementor kit
        $this->updateElementorKit();

        // Set Homepage
        $this->setHomePage();

        // Disable other ads and notifications
        $this->disableOtherAdsAndNotifications();

        // Success
        echo wp_json_encode(new WP_REST_Response([
            'success' => true,
            'message' => 'Website settings...'
        ], 200));

        wp_die();
    }

    public function importOptions()
    {

        $options_file = SUPER_BLANK_PLUGIN_PATH . 'settings/wp_options.json';

        if (!file_exists($options_file)) return;

        $import_file = $options_file;

        global $wp_filesystem;

        if (empty($wp_filesystem)) {

            require_once ABSPATH . '/wp-admin/includes/file.php';
            WP_Filesystem();
        }

        // Get the options.
        $fileContent = $wp_filesystem->get_contents($import_file);
        $options      = json_decode($fileContent, 1);

        // Change url
        $old_site_url = 'super-blank-local-storage';

        $new_site_url = home_url();

        foreach ($options as $name => $value) {

            $optionsValue = maybe_unserialize($value);

            update_option(sanitize_key($name), superBlankSanitizeNestedArray($optionsValue, $old_site_url, $new_site_url));
        }
    }

    public function disableWpFormsAdsAndNotifications()
    {

        if (!is_plugin_active('wpforms-lite/wpforms.php')) return;

        $wpforms_settings = get_option('wpforms_settings', array());
        $wpforms_settings['hide-announcements'] = '1';
        $wpforms_settings['hide-admin-bar'] = '1';
        $wpforms_settings['email-summaries-disable'] = '1';
        $wpforms_settings['uninstall-data'] = '1';
        $wpforms_settings['modern-markup'] = '1';
        $wpforms_settings['modern-markup-is-set'] = '1';

        update_option('wpforms_settings', $wpforms_settings);

        update_option('wpforms_activation_redirect', true);

        $all_users = get_users(['fields' => 'ID']);

        foreach ($all_users as $user_id) {

            $dismissed = get_user_meta($user_id, 'wpforms_dismissed', true);

            if (!is_array($dismissed)) {

                $dismissed = array();
            }

            $dismissed['edu-admin-notice-bar'] = true;
            $dismissed['edu-admin-did-you-know-overview'] = true;
            $dismissed['edu-builder-lite-connect-top-bar'] = true;

            update_user_meta($user_id, 'wpforms_dismissed', $dismissed);
        }

        $pointers = get_option('wpforms_pointers', []);
        $pointers['dismiss'] = ['wpforms_education_pointers_payments'];

        update_option('wpforms_pointers', $pointers);

        $site_url = get_site_url();
        $domain = wp_parse_url($site_url, PHP_URL_HOST);
        $forms = wpforms()->form->get();

        if (!is_iterable($forms)) return;

        foreach ($forms as $form) {

            $updated = false;
            $form_data = wpforms_decode($form->post_content);

            if (isset($form_data['settings']['notifications']) && is_array($form_data['settings']['notifications'])) {

                foreach ($form_data['settings']['notifications'] as &$notification) {

                    $notification['sender_name'] = 'Super Bland User';
                    $notification['sender_address'] = "no-reply@$domain";
                    $updated = true;
                }

                if ($updated) {

                    wpforms()->form->update($form->ID, $form_data);
                }
            }
        }
    }

    public function updateElementorKit()
    {

        if (class_exists('\Elementor\Plugin')) {

            $kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();

            if ($kit->get_id()) {

                delete_option('elementor_active_kit');
            }

            $created_default_kit = \Elementor\Plugin::$instance->kits_manager->create_default();

            update_option('elementor_active_kit', intval($created_default_kit));
        }
    }

    public function setHomePage()
    {

        $query = new WP_Query([
            'post_type' => 'page',
            'title'     => 'Home',
            'posts_per_page' => 1
        ]);

        if ($query->have_posts()) {

            $query->the_post();
            $homePageId = get_the_ID();
            wp_reset_postdata();

            if (get_option('show_on_front') !== false) {

                update_option('show_on_front', 'page');
            }

            if (get_option('page_on_front') !== false) {

                update_option('page_on_front', $homePageId);
            }
        }
    }

    public function disableOtherAdsAndNotifications()
    {

        // Elementor history
        $versions = [];

        if (defined('ELEMENTOR_VERSION')) {

            $versions[ELEMENTOR_VERSION] = time();
        }

        update_option('elementor_install_history', $versions);

        // Users settings
        $users = get_users(array(
            'fields' => array('ID'),
            'number' => -1
        ));

        // Loop through each user
        foreach ($users as $user) {

            if (!is_object($user) || !isset($user->ID)) continue;

            $userId = $user->ID;

            update_user_meta($userId, 'show_welcome_panel', 0);

            $hiddenMetaboxes = [
                'dashboard_site_health',
                'dashboard_right_now',
                'dashboard_activity',
                'dashboard_quick_press',
                'dashboard_primary',
                'wpforms_reports_widget_lite',
            ];

            update_user_meta($userId, 'metaboxhidden_dashboard', $hiddenMetaboxes);

            update_user_option($userId, 'elementor_enable_ai', 0);

            $elementorNotices = [
                'image_optimizer_hint'
            ];

            update_user_meta($userId, 'elementor_dismissed_editor_notices', $elementorNotices);

            $elementor_admin_notices = [
                'site_mailer_promotion' => 'true',
                'design_not_appearing' => 'true',
            ];

            update_user_meta($userId, 'elementor_admin_notices', $elementor_admin_notices);
        }
    }
}
