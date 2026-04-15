<?php
/**
 * Plugin Name: Nuru option
 * Plugin URI: https://www.nuruplayground.com/
 * Description: A plugin to manage Nuru Massage location and slot options with post selection.
 * Version: 1.0
 * Author: None
 * Author URI: https://www.nuruplayground.com/
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin directory path for easier includes
define('NURU_OPTIONS_PLUGIN_DIR', plugin_dir_path(__FILE__));

// --- Include separate files for functionality ---
require_once NURU_OPTIONS_PLUGIN_DIR . 'nuru-utils.php';
require_once NURU_OPTIONS_PLUGIN_DIR . 'nuru-schedule-page.php';
require_once NURU_OPTIONS_PLUGIN_DIR . 'nuru-who-on-now-page.php';

// --- 1. Enqueue Scripts and Styles for Select2 ---
function nuru_options_enqueue_admin_scripts($hook) {
    // Only load on our specific admin pages
    if (strpos($hook, 'nuru-options') === false) {
        return;
    }

    // Enqueue Select2 styles
    wp_enqueue_style('select2', plugin_dir_url( __FILE__ ) .'css/select2.min.css?ver=4.0.13', array(), '4.1.0-rc.0');
	
    wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/js/select2.min.js', array('jquery'), '4.1.0-rc.0', true);

    // Fetch goddess posts and localize for both schedule and who's on now pages
    $posts = get_posts( array(
        'post_type'      => 'goddess',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'fields'         => array( 'ID', 'post_title' ),
    ) );

    $options = array();
    foreach ( $posts as $p ) {
		$lang_details = apply_filters( 'wpml_post_language_details', null, $p->ID );
    	$lang_code    = $lang_details['language_code'] ?? '';
        $options[] = array(
            'id'   => $p->ID,
            'text' => $p->post_title . ' ' . '('.$lang_code.')' ,
        );
    }

    wp_enqueue_script( 'nuru-admin-js', plugin_dir_url( __FILE__ ) . 'nuru-options-admin.js', array( 'jquery', 'select2' ), '1.1', true );
    wp_enqueue_style( 'nuru-css', plugin_dir_url( __FILE__ ) . 'css/nuru.css', array( ), '1.1' );

    wp_localize_script( 'nuru-admin-js', 'nuru_options_data', array(
        'posts' => $options, // send goddess posts to JS
    ) );
}
add_action('admin_enqueue_scripts', 'nuru_options_enqueue_admin_scripts');

// --- 2. Add Nuru Options main menu and submenus ---
function nuru_options_add_admin_menu() {
    // Main top-level menu item
    add_menu_page(
        'Nuru Options',           // Page title
        'Nuru Options',           // Menu title
        'edit_pages',         // Capability
        'nuru-options',           // Menu slug for Schedule page (default)
        'nuru_schedule_page_content', // Callback function from nuru-schedule-page.php
        'dashicons-align-wide',   // Icon URL or Dashicon class
        60                        // Position in the menu order
    );

    // Submenu for "Schedule"
    add_submenu_page(
        'nuru-options',           // Parent slug
        'Nuru Schedule',          // Page title
        'Schedule',               // Menu title
        'edit_pages',         // Capability
        'nuru-options',           // Menu slug (same as parent to make it the default)
        'nuru_schedule_page_content' // Callback function from nuru-schedule-page.php
    );

    // Submenu for "Who's on Now"
    add_submenu_page(
        'nuru-options',           // Parent slug
        'Nuru Who\'s on Now',     // Page title
        'Who\'s on Now',          // Menu title
        'edit_pages',         // Capability
        'nuru-options-who-on-now', // New unique menu slug
        'nuru_who_on_now_page_content' // Callback function from nuru-who-on-now-page.php
    );
}
add_action('admin_menu', 'nuru_options_add_admin_menu');

// --- 3. Register Settings (Delegated to separate files, but hooked here) ---
function nuru_options_register_all_settings() {
    nuru_schedule_settings_init(); // From nuru-schedule-page.php
    nuru_who_on_now_settings_init(); // From nuru-who-on-now-page.php
}
add_action('admin_init', 'nuru_options_register_all_settings');

// --- 4. Sanitization Callback (Reusable) ---
/**
 * Sanitization callback for Nuru Options settings.
 * This now processes comma-separated post IDs from the hidden input.
 * It also handles legacy data from previous versions.
 *
 * @param array $input The raw input from the form.
 * @return array The sanitized and processed options.
 */
function nuru_options_sanitize_callback($input) {
    $new_input = array();
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
            $post_ids_raw = explode(',', $value);
            foreach ($post_ids_raw as $id_string) {
                $id = absint(trim($id_string));
                if ($id > 0) {
                    $sanitized_ids[] = $id;
                }
            }
        }
        $new_input[$key] = implode(',', array_unique($sanitized_ids));
    }
    return $new_input;
}

add_filter('option_page_capability_nuru_who_on_now_group', function() {
    return 'edit_pages';
});
add_filter('option_page_capability_nuru_options_group', function() {
    return 'edit_pages';
});