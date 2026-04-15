<?php
// nuru-schedule-page.php
// Handles settings and content for the Nuru Schedule admin page

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Register settings, sections, and fields for the Schedule page.
 */
function nuru_schedule_settings_init() {
    $slots     = array('10am_3pm', '10am_7pm', '3pm_9pm', '7pm_11pm', '9pm_5am');
    $locations = array('montreal', 'laval', 'nuru_vip');

    register_setting(
        'nuru_options_group',
        'nuru_options_settings',
        array(
            'sanitize_callback' => 'nuru_options_sanitize_callback',
            'type'              => 'array',
            'show_in_rest'      => false,
            'capability'        => 'edit_pages',
        )
    );

    add_settings_section(
        'nuru_options_section_montreal',
        'Location: Montreal Nuru Massage',
        'nuru_options_section_montreal_callback',
        'nuru-options'
    );

    add_settings_section(
        'nuru_options_section_laval',
        'Location: Laval Nuru Massage',
        'nuru_options_section_laval_callback',
        'nuru-options'
    );

    add_settings_section(
        'nuru_options_section_nuru_vip',
        'Location: Nuru VIP',
        'nuru_options_section_nuru_vip_callback',
        'nuru-options'
    );

    foreach ($locations as $location) {
        foreach ($slots as $slot_slug) {
            $slot_display = str_replace('_', ' to ', $slot_slug);
            add_settings_field(
                "{$location}_{$slot_slug}_days_group",
                "Slot: {$slot_display}",
                'nuru_schedule_days_group_callback',
                'nuru-options',
                "nuru_options_section_{$location}",
                array(
                    'location'    => $location,
                    'slot_slug'   => $slot_slug,
                    'slot_display'=> $slot_display,
                    'option_name' => 'nuru_options_settings',
                )
            );
        }
    }
}

function nuru_options_section_montreal_callback() {
    echo '<p>Configure the Goddess schedule for Montreal Nuru Massage by selecting posts for each time slot and day.</p>';
}

function nuru_options_section_laval_callback() {
    echo '<p>Configure the Goddess schedule for Laval Nuru Massage by selecting posts for each time slot and day.</p>';
}

function nuru_options_section_nuru_vip_callback() {
    echo '<p>Configure the Goddess schedule for Nuru VIP by selecting posts for each time slot and day.</p>';
}

/**
 * Renders a 7-day grid of Select2 dropdowns for a given slot.
 */
function nuru_schedule_days_group_callback($args) {
    $slot_display = $args['slot_display'];
    $days_labels  = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');

    echo "<h3 class='nuru-slot-title'>Slot: {$slot_display}</h3>";
    echo "<div class='nuru-days-grid'>";

    foreach ($days_labels as $day_index => $day_name) {
        $field_args = array_merge($args, array(
            'day_index' => $day_index,
            'day_name'  => $day_name,
        ));
        nuru_options_select2_field_callback($field_args);
    }

    echo "</div>";
}

/**
 * Content for the Nuru Schedule admin page.
 */
function nuru_schedule_page_content() {
    ?>
    <div class="wrap nuru-options-wrap">
        <h1 class="nuru-page-title">
            <span class="dashicons dashicons-calendar-alt"></span>
            Nuru Schedule Options
        </h1>

        <div id="nuru-schedule-notice" class="notice nuru-ajax-notice" style="display:none;" role="alert"></div>

        <form id="nuru-schedule-form" class="nuru-ajax-form" data-action="nuru_save_schedule" data-option="nuru_options_settings">
            <?php wp_nonce_field('nuru_ajax_nonce', 'nuru_nonce'); ?>
            <?php do_settings_sections('nuru-options'); ?>
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
