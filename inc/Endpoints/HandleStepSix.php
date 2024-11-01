<?php

namespace SuperBlank\Endpoints;

if (!defined('ABSPATH')) {
    exit;
}

use WP_REST_Response;
use WP_Error;
use SuperBlank\Super_Blank_Source_Local;

class HandleStepSix
{

    private $pagesElementor = [
        [
            'title' => 'Home',
            'sections' => [
                [
                    'name' => 'Home V1 Hero',
                    'path' => SUPER_BLANK_PLUGIN_PATH . 'elementor-sections-php/home-v1-hero.php',
                ],
                [
                    'name' => 'Home V1 Statistics',
                    'path' => SUPER_BLANK_PLUGIN_PATH . 'elementor-sections-php/home-v1-statistics.php',
                ],
                [
                    'name' => 'Home V1 Services',
                    'path' => SUPER_BLANK_PLUGIN_PATH . 'elementor-sections-php/home-v1-services.php',
                ],
                [
                    'name' => 'Home V1 About',
                    'path' => SUPER_BLANK_PLUGIN_PATH . 'elementor-sections-php/home-v1-about.php',
                ],
                [
                    'name' => 'Home V1 Reviews',
                    'path' => SUPER_BLANK_PLUGIN_PATH . 'elementor-sections-php/home-v1-reviews.php',
                ],
                [
                    'name' => 'Home V1 CTA',
                    'path' => SUPER_BLANK_PLUGIN_PATH . 'elementor-sections-php/home-v1-cta.php',
                ],
            ]
        ],
        [
            'title' => 'About',
            'sections' => [
                [
                    'name' => 'About V1 Hero',
                    'path' => SUPER_BLANK_PLUGIN_PATH . 'elementor-sections-php/about-v1-hero.php',
                ],
                [
                    'name' => 'About V1 About',
                    'path' => SUPER_BLANK_PLUGIN_PATH . 'elementor-sections-php/about-v1-about.php',
                ],
                [
                    'name' => 'CTA',
                    'path' => SUPER_BLANK_PLUGIN_PATH . 'elementor-sections-php/home-v1-cta.php',
                ],
            ]
        ],
        [
            'title' => 'Contact',
            'sections' => [
                [
                    'name' => 'Contact V1 Hero',
                    'path' => SUPER_BLANK_PLUGIN_PATH . 'elementor-sections-php/contact-v1-hero.php',
                ],
                [
                    'name' => 'Contact V1 Form',
                    'path' => SUPER_BLANK_PLUGIN_PATH . 'elementor-sections-php/contact-v1-form.php',
                    'filter' => 'super_blank_pre_process_template_content'
                ],
                [
                    'name' => 'Contact V1 Map',
                    'path' => SUPER_BLANK_PLUGIN_PATH . 'elementor-sections-php/contact-v1-map.php'
                ],
            ]
        ],
    ];

    private $pagesGutenberg = [
        [
            'title' => 'Home',
            'pattern_file' => SUPER_BLANK_PLUGIN_PATH . 'patterns/home-v1.php'
        ],
        [
            'title' => 'About',
            'pattern_file' => SUPER_BLANK_PLUGIN_PATH . 'patterns/about-v1.php'
        ],
        [
            'title' => 'Contact',
            'pattern_file' => SUPER_BLANK_PLUGIN_PATH . 'patterns/contact-v1.php'
        ]
    ];

    public function __construct()
    {

        add_action('wp_ajax_super_blank_step6', [$this, 'handle_step']);
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

        // Create pages with elementor
        $this->createPagesElementor();

        // Success
        echo wp_json_encode(new WP_REST_Response([
            'success' => true,
            'message' => 'Pages creation...'
        ], 200));

        wp_die();
    }

    public function createPagesElementor()
    {

        if (!is_iterable($this->pagesElementor)) return;

        foreach ($this->pagesElementor as $page) {

            if (!is_iterable($page['sections'])) continue;

            $this->createPageImportTemplates($page['sections'], $page['title']);
        }
    }

    public function createPageImportTemplates($pageSections, $pageTitle)
    {

        $importer = new Super_Blank_Source_Local();

        $combinedContent = [];

        foreach ($pageSections as $section) {

            // Now there is only one filter
            if (isset($section['filter'])) {

                add_filter('super_blank_pre_process_template_content', function ($content) {

                    $args = array(
                        'post_type' => 'wpforms',
                        'posts_per_page' => 1,
                        'orderby' => 'ID',
                        'order' => 'ASC',
                        'fields' => 'ids'
                    );

                    $query = new \WP_Query($args);

                    if ($query->have_posts()) {

                        return superBlankFindAndReplaceWpFormsId($content, (string) absint($query->posts[0]));
                    } else {

                        return $content;
                    }
                }, 10, 1);
            }

            $result = $importer->importTemplateUsingPHP($section['path']);

            if (is_wp_error($result)) continue;

            $templateId = $result['template_id'];

            // Get the template content
            $templateContent = $importer->get_data([
                'template_id' => $templateId,
            ]);

            if (is_wp_error($templateContent)) continue;

            // Add the template content to our combined content
            $combinedContent = array_merge($combinedContent, $templateContent['content']);

            // Delete the imported template (optional, remove if you want to keep it)
            wp_delete_post($templateId, true);
        }

        // Create a new page
        $current_user_id = get_current_user_id();

        $pageId = wp_insert_post([
            'post_title'    => $pageTitle,
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_content'  => '',
            'post_author'   => $current_user_id,
        ]);

        if (is_int($pageId)) {

            // Set the page to use Elementor
            update_post_meta($pageId, '_elementor_edit_mode', 'builder');

            // Add the combined Elementor content to the page
            update_post_meta($pageId, '_elementor_data', wp_slash(wp_json_encode($combinedContent)));

            // Astra template settings
            update_post_meta($pageId, 'ast-site-content-layout', 'full-width-container');
            update_post_meta($pageId, 'site-post-title', 'disabled');
            update_post_meta($pageId, 'ast-title-bar-display', 'disabled');
            update_post_meta($pageId, 'ast-featured-img', 'disabled');
            update_post_meta($pageId, 'site-sidebar-layout', 'no-sidebar');

            // Add the page to menu
            $primaryMenuId = get_option('super_blank_primary_menu_id');

            if (empty($primaryMenuId)) return;

            if (!$this->isPageInMenu($primaryMenuId, $pageId)) {

                wp_update_nav_menu_item($primaryMenuId, 0, array(
                    'menu-item-title' => $pageTitle,
                    'menu-item-object-id' => $pageId,
                    'menu-item-object' => 'page',
                    'menu-item-status' => 'publish',
                    'menu-item-type' => 'post_type',
                ));
            }
        }
    }

    private function isPageInMenu($menuId, $pageId)
    {

        $menu_items = wp_get_nav_menu_items($menuId);

        if (!$menu_items || !is_array($menu_items)) {
            return false;
        }

        foreach ($menu_items as $menu_item) {
            if ($menu_item->object === 'page' && (int)$menu_item->object_id === (int)$pageId) {
                return true;
            }
        }

        return false;
    }

    public function createPagesGutenberg()
    {

        $current_user_id = get_current_user_id();

        foreach ($this->pagesGutenberg as $page) {

            if (!file_exists($page['pattern_file'])) continue;

            ob_start();
            include $page['pattern_file'];
            $home_content = ob_get_clean();

            // Create post object
            $page = array(
                'post_title'    => wp_strip_all_tags($page['title']),
                'post_content'  => $home_content,
                'post_status'   => 'publish',
                'post_author'   => $current_user_id,
                'post_type'     => 'page',
            );

            // Insert the page into the database
            wp_insert_post($page);
        }
    }
}
