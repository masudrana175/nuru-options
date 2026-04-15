<?php
// nuru-who-on-now-page.php

if (!defined('ABSPATH')) {
    exit;
}

function nuru_who_on_now_settings_init() {
    $slots     = array('10am_3pm', '10am_7pm', '3pm_9pm', '7pm_11pm', '9pm_5am');
    $locations = array('montreal', 'laval', 'nuru_vip');

    register_setting('nuru_who_on_now_group', 'nuru_who_on_now_settings', array(
        'sanitize_callback' => 'nuru_options_sanitize_callback',
        'type'              => 'array',
        'show_in_rest'      => false,
        'capability'        => 'edit_pages',
    ));

    add_settings_section('nuru_who_on_now_section_montreal', '', '__return_false', 'nuru-options-who-on-now');
    add_settings_section('nuru_who_on_now_section_laval',    '', '__return_false', 'nuru-options-who-on-now');
    add_settings_section('nuru_who_on_now_section_nuru_vip', '', '__return_false', 'nuru-options-who-on-now');

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

/**
 * Renders the Who's on Now admin page.
 */
function nuru_who_on_now_page_content() {
    $locations = array(
        array(
            'key'     => 'montreal',
            'label'   => 'Montreal Nuru Massage',
            'desc'    => "Select which Goddesses are currently on shift for Montreal.",
            'section' => 'nuru_who_on_now_section_montreal',
            'css'     => 'nuru-loc-montreal',
        ),
        array(
            'key'     => 'laval',
            'label'   => 'Laval Nuru Massage',
            'desc'    => "Select which Goddesses are currently on shift for Laval.",
            'section' => 'nuru_who_on_now_section_laval',
            'css'     => 'nuru-loc-laval',
        ),
        array(
            'key'     => 'nuru_vip',
            'label'   => 'Nuru VIP',
            'desc'    => "Select which Goddesses are currently on shift for Nuru VIP.",
            'section' => 'nuru_who_on_now_section_nuru_vip',
            'css'     => 'nuru-loc-vip',
        ),
    );
    ?>
    <div class="wrap nuru-options-wrap">

        <!-- Sticky top bar -->
        <div class="nuru-sticky-bar">
            <div class="nuru-sticky-bar-left">
                <span class="dashicons dashicons-groups nuru-bar-icon"></span>
                <span class="nuru-bar-title">Who's on Now</span>
            </div>
            <button type="submit" form="nuru-who-on-now-form" class="nuru-save-btn" id="nuru-who-on-now-save">
                <span class="nuru-btn-text">Save Changes</span>
                <span class="nuru-btn-spinner" style="display:none;">
                    <span class="spinner is-active" style="float:none;vertical-align:middle;margin:0 4px 0 0;"></span>Saving&hellip;
                </span>
            </button>
        </div>

        <!-- Inline save notice -->
        <div id="nuru-who-on-now-notice" class="nuru-ajax-notice" style="display:none;" role="alert"></div>

        <form id="nuru-who-on-now-form" class="nuru-ajax-form"
              data-action="nuru_save_who_on_now"
              data-option="nuru_who_on_now_settings"
              data-notice="#nuru-who-on-now-notice"
              data-savebtn="#nuru-who-on-now-save">
            <?php wp_nonce_field('nuru_ajax_nonce', 'nuru_nonce'); ?>

            <?php foreach ($locations as $loc): ?>
            <div class="nuru-location-card <?php echo esc_attr($loc['css']); ?>">
                <div class="nuru-location-header">
                    <div class="nuru-location-title-wrap">
                        <h2 class="nuru-location-title"><?php echo esc_html($loc['label']); ?></h2>
                        <p class="nuru-location-desc"><?php echo esc_html($loc['desc']); ?></p>
                    </div>
                    <div class="nuru-location-actions">
                        <button type="button" class="nuru-toggle-all button-link" data-open="0">
                            Expand All
                        </button>
                    </div>
                </div>
                <div class="nuru-location-body">
                    <table class="form-table nuru-form-table" role="presentation"><tbody>
                        <?php do_settings_fields('nuru-options-who-on-now', $loc['section']); ?>
                    </tbody></table>
                </div>
            </div>
            <?php endforeach; ?>

        </form>
    </div>
    <?php
}
