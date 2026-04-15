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
    $slots = array('10am_3pm', '10am_7pm', '3pm_9pm', '7pm_11pm', '9pm_5am');
    $locations = array('montreal', 'laval');

    // Settings for the "Who's on Now" page
    register_setting(
        'nuru_who_on_now_group', // New settings group
        'nuru_who_on_now_settings', // New option name
		array(
			'sanitize_callback' => 'nuru_options_sanitize_callback',
			'type'              => 'array',
			'show_in_rest'      => false,
			'capability'        => 'edit_pages' // ← ADD THIS
		)
    );

    add_settings_section(
        'nuru_who_on_now_section_montreal',
        'Location: Montreal - Who\'s on Now',
        'nuru_who_on_now_section_montreal_callback',
        'nuru-options-who-on-now' // Page slug for this section
    );

    add_settings_section(
        'nuru_who_on_now_section_laval',
        'Location: Laval - Who\'s on Now',
        'nuru_who_on_now_section_laval_callback',
        'nuru-options-who-on-now' // Page slug for this section
    );

    // Fields for "Who's on Now" with location and time slots, but NO days
    foreach ($locations as $location) {
        foreach ($slots as $slot_slug) {
            $slot_display = str_replace('_', ' to ', $slot_slug);
            add_settings_field(
                "{$location}_{$slot_slug}_who_on_now", // Unique field ID: location_timeslot
                "Slot: {$slot_display}",
                'nuru_options_select2_field_callback', // Use the generic field callback
                'nuru-options-who-on-now', // Page slug
                "nuru_who_on_now_section_{$location}", // Section for Who's on Now
                array(
                    'location' => $location,
                    'slot_slug' => $slot_slug,
                    'slot_display' => $slot_display,
                    'option_name' => 'nuru_who_on_now_settings' // Explicitly pass the NEW option name
                )
            );
        }
    }
}

// Section callbacks for Who's on Now page
function nuru_who_on_now_section_montreal_callback() {
    echo '<p>Select which Goddesses are currently available for Montreal Nuru Massage for each time slot.</p>';
}

function nuru_who_on_now_section_laval_callback() {
    echo '<p>Select which Goddesses are currently available for Laval Nuru Massage for each time slot.</p>';
}

/**
 * Content for the Nuru "Who's on Now" admin page.
 */
function nuru_who_on_now_page_content() {
    ?>
    <div class="wrap nuru-options-wrap">
        <h2>Who's on Now Options</h2>

        <style>
            
        </style>

        <form action="options.php" method="post">
            <?php
            settings_fields('nuru_who_on_now_group'); // New settings group for Who's on Now
            do_settings_sections('nuru-options-who-on-now'); // New page slug for Who's on Now
            submit_button('Save Changes');
            ?>
        </form>
    </div>
    <?php
}