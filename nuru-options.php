<?php
/**
 * Plugin Name: Nuru option
 * Plugin URI: https://www.nuruplayground.com/
 * Description: A plugin to manage Nuru Massage location and slot options with post selection.
 * Version: 1.1
 * Author: None
 * Author URI: https://www.nuruplayground.com/
 */

if (!defined('ABSPATH')) {
    exit;
}

define('NURU_OPTIONS_PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once NURU_OPTIONS_PLUGIN_DIR . 'nuru-utils.php';
require_once NURU_OPTIONS_PLUGIN_DIR . 'nuru-schedule-page.php';
require_once NURU_OPTIONS_PLUGIN_DIR . 'nuru-who-on-now-page.php';

// --- 1. Enqueue Scripts and Styles ---
function nuru_options_enqueue_admin_scripts($hook) {
    if (strpos($hook, 'nuru-options') === false) {
        return;
    }

    wp_enqueue_style('select2', plugin_dir_url(__FILE__) . 'css/select2.min.css', array(), '4.0.13');
    wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '4.0.13', true);

    // Fetch goddess posts for Select2 options
    $posts = get_posts(array(
        'post_type'      => 'goddess',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'fields'         => array('ID', 'post_title'),
    ));

    $options = array();
    foreach ($posts as $p) {
        $lang_details = apply_filters('wpml_post_language_details', null, $p->ID);
        $lang_code    = $lang_details['language_code'] ?? '';
        $options[]    = array(
            'id'   => $p->ID,
            'text' => $p->post_title . ($lang_code ? ' (' . $lang_code . ')' : ''),
        );
    }

    wp_enqueue_script('nuru-admin-js', plugin_dir_url(__FILE__) . 'nuru-options-admin.js', array('jquery', 'select2'), '1.2', true);
    wp_enqueue_style('nuru-css', plugin_dir_url(__FILE__) . 'css/nuru.css', array(), '1.2');

    wp_localize_script('nuru-admin-js', 'nuru_options_data', array(
        'posts'    => $options,
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('nuru_ajax_nonce'),
    ));
}
add_action('admin_enqueue_scripts', 'nuru_options_enqueue_admin_scripts');

// --- 2. Admin Menu ---
function nuru_options_add_admin_menu() {
    add_menu_page(
        'Nuru Options',
        'Nuru Options',
        'edit_pages',
        'nuru-options',
        'nuru_schedule_page_content',
        'dashicons-align-wide',
        60
    );

    add_submenu_page(
        'nuru-options',
        'Nuru Schedule',
        'Schedule',
        'edit_pages',
        'nuru-options',
        'nuru_schedule_page_content'
    );

    add_submenu_page(
        'nuru-options',
        "Nuru Who's on Now",
        "Who's on Now",
        'edit_pages',
        'nuru-options-who-on-now',
        'nuru_who_on_now_page_content'
    );
}
add_action('admin_menu', 'nuru_options_add_admin_menu');

// --- 3. Register Settings ---
function nuru_options_register_all_settings() {
    nuru_schedule_settings_init();
    nuru_who_on_now_settings_init();
}
add_action('admin_init', 'nuru_options_register_all_settings');

// --- 4. Sanitization Callback ---
function nuru_options_sanitize_callback($input) {
    $new_input = array();
    if (!is_array($input)) {
        return $new_input;
    }
    foreach ($input as $key => $value) {
        $sanitized_ids = array();

        if (is_array($value)) {
            foreach ($value as $item) {
                $id = absint(trim($item));
                if ($id > 0) {
                    $sanitized_ids[] = $id;
                }
            }
        } else {
            foreach (explode(',', (string) $value) as $id_string) {
                $id = absint(trim($id_string));
                if ($id > 0) {
                    $sanitized_ids[] = $id;
                }
            }
        }

        $new_input[sanitize_key($key)] = implode(',', array_unique($sanitized_ids));
    }
    return $new_input;
}

// --- 5. Capability Filters ---
add_filter('option_page_capability_nuru_who_on_now_group', function () {
    return 'edit_pages';
});
add_filter('option_page_capability_nuru_options_group', function () {
    return 'edit_pages';
});

// --- 6. AJAX Save Handlers ---

/**
 * AJAX handler for saving Schedule settings.
 */
function nuru_ajax_save_schedule() {
    check_ajax_referer('nuru_ajax_nonce', 'nuru_nonce');

    if (!current_user_can('edit_pages')) {
        wp_send_json_error(array('message' => 'You do not have permission to save these settings.'));
    }

    $raw_input = isset($_POST['nuru_options_settings']) ? wp_unslash($_POST['nuru_options_settings']) : array();
    $sanitized = nuru_options_sanitize_callback((array) $raw_input);
    update_option('nuru_options_settings', $sanitized);

    wp_send_json_success(array('message' => 'Schedule settings saved successfully.'));
}
add_action('wp_ajax_nuru_save_schedule', 'nuru_ajax_save_schedule');

/**
 * AJAX handler for saving Who's on Now settings.
 */
function nuru_ajax_save_who_on_now() {
    check_ajax_referer('nuru_ajax_nonce', 'nuru_nonce');

    if (!current_user_can('edit_pages')) {
        wp_send_json_error(array('message' => 'You do not have permission to save these settings.'));
    }

    $raw_input = isset($_POST['nuru_who_on_now_settings']) ? wp_unslash($_POST['nuru_who_on_now_settings']) : array();
    $sanitized = nuru_options_sanitize_callback((array) $raw_input);
    update_option('nuru_who_on_now_settings', $sanitized);

    wp_send_json_success(array('message' => "Who's on Now settings saved successfully."));
}
add_action('wp_ajax_nuru_save_who_on_now', 'nuru_ajax_save_who_on_now');
