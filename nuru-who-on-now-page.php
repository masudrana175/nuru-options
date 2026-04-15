<?php
// nuru-who-on-now-page.php
// Handles settings and content for the Nuru "Who's on Now" admin page

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Register settings, sections, and fields for the "Who's on Now" page.
 */
function nuru_who_on_now_settings_init() {
    $slots     = array('10am_3pm', '10am_7pm', '3pm_9pm', '7pm_11pm', '9pm_5am');
    $locations = array('montreal', 'laval', 'nuru_vip');

    register_setting(
        'nuru_who_on_now_group',
        'nuru_who_on_now_settings',
        array(
            'sanitize_callback' => 'nuru_options_sanitize_callback',
            'type'              => 'array',
            'show_in_rest'      => false,
            'capability'        => 'edit_pages',
        )
    );

    add_settings_section(
        'nuru_who_on_now_section_montreal',
        "Location: Montreal &mdash; Who's on Now",
        'nuru_who_on_now_section_montreal_callback',
        'nuru-options-who-on-now'
    );

    add_settings_section(
        'nuru_who_on_now_section_laval',
        "Location: Laval &mdash; Who's on Now",
        'nuru_who_on_now_section_laval_callback',
        'nuru-options-who-on-now'
    );

    add_settings_section(
        'nuru_who_on_now_section_nuru_vip',
        "Location: Nuru VIP &mdash; Who's on Now",
        'nuru_who_on_now_section_nuru_vip_callback',
        'nuru-options-who-on-now'
    );

    foreach ($locations as $location) {
        foreach ($slots as $slot_slug) {
            $slot_display = str_replace('_', ' to ', $slot_slug);
            add_settings_field(
                "{$location}_{$slot_slug}_who_on_now",
                "Slot: {$slot_display}",
                'nuru_options_select2_field_callback',
                'nuru-options-who-on-now',
                "nuru_who_on_now_section_{$location}",
                array(
                    'location'    => $location,
                    'slot_slug'   => $slot_slug,
                    'slot_display'=> $slot_display,
                    'option_name' => 'nuru_who_on_now_settings',
                )
            );
        }
    }
}

function nuru_who_on_now_section_montreal_callback() {
    echo '<p>Select which Goddesses are currently available for Montreal Nuru Massage for each time slot.</p>';
}

function nuru_who_on_now_section_laval_callback() {
    echo '<p>Select which Goddesses are currently available for Laval Nuru Massage for each time slot.</p>';
}

function nuru_who_on_now_section_nuru_vip_callback() {
    echo "<p>Select which Goddesses are currently available for Nuru VIP for each time slot.</p>";
}

/**
 * Content for the Nuru "Who's on Now" admin page.
 */
function nuru_who_on_now_page_content() {
    ?>
    <div class="wrap nuru-options-wrap">
        <h1 class="nuru-page-title">
            <span class="dashicons dashicons-groups"></span>
            Who's on Now Options
        </h1>

        <div id="nuru-who-on-now-notice" class="notice nuru-ajax-notice" style="display:none;" role="alert"></div>

        <form id="nuru-who-on-now-form" class="nuru-ajax-form" data-action="nuru_save_who_on_now" data-option="nuru_who_on_now_settings">
            <?php wp_nonce_field('nuru_ajax_nonce', 'nuru_nonce'); ?>
            <?php do_settings_sections('nuru-options-who-on-now'); ?>
            <p class="submit">
                <button type="submit" class="button button-primary button-large nuru-save-btn">
                    <span class="nuru-btn-text">Save Changes</span>
                    <span class="nuru-btn-spinner" style="display:none;">
                        <span class="spinner is-active" style="float:none; margin:0 5px 0 0;"></span>Saving&hellip;
                    </span>
                </button>
            </p>
        </form>
    </div>
    <?php
}
