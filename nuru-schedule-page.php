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
    $slots = array('10am_3pm', '10am_7pm', '3pm_9pm', '7pm_11pm', '9pm_5am');
    $locations = array('montreal', 'laval');

    // Settings for the "Schedule" page
    register_setting(
        'nuru_options_group', // Settings group
        'nuru_options_settings', // Option name (stores all schedule settings)
		array(
			'sanitize_callback' => 'nuru_options_sanitize_callback',
			'type'              => 'array',
			'show_in_rest'      => false,
			'capability'        => 'edit_pages' // ← ADD THIS
		)
    );

    add_settings_section(
        'nuru_options_section_montreal',
        'Location: Montreal Nuru Massage',
        'nuru_options_section_montreal_callback',
        'nuru-options' // Page slug where this section appears
    );

    add_settings_section(
        'nuru_options_section_laval',
        'Location: Laval Nuru Massage',
        'nuru_options_section_laval_callback',
        'nuru-options' // Page slug where this section appears
    );

    foreach ($locations as $location) {
        foreach ($slots as $slot_slug) {
            $slot_display = str_replace('_', ' to ', $slot_slug);
            add_settings_field(
                "{$location}_{$slot_slug}_days_group",
                "Slot: {$slot_display}",
                'nuru_schedule_days_group_callback', // Specific callback for schedule grid
                'nuru-options', // Page slug
                "nuru_options_section_{$location}",
                array(
                    'location' => $location,
                    'slot_slug' => $slot_slug,
                    'slot_display' => $slot_display,
                    'option_name' => 'nuru_options_settings' // Explicitly pass option name for Schedule
                )
            );
        }
    }
}

// Section callbacks for Schedule page
function nuru_options_section_montreal_callback() {
    echo '<p>Configure the Goddess schedule for Montreal Nuru Massage by selecting posts for each time slot and day.</p>';
}

function nuru_options_section_laval_callback() {
    echo '<p>Configure the Goddess schedule for Laval Nuru Massage by selecting posts for each time slot and day.</p>';
}

/**
 * Custom Callback to render 3 days per row with Select2 for the Schedule page.
 * This calls the generic nuru_options_select2_field_callback for each day.
 *
 * @param array $args Arguments from add_settings_field.
 */
function nuru_schedule_days_group_callback($args) {
    $slot_display = $args['slot_display'];
    $days_labels = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');

    echo "<h3 class='nuru-slot-title'>Slot: {$slot_display}</h3>";
    echo "<div class='nuru-days-grid'>"; // Start of the grid container

    foreach ($days_labels as $day_index => $day_name) {
        $field_args = array_merge($args, [
            'day_index' => $day_index,
            'day_name' => $day_name,
        ]);
        nuru_options_select2_field_callback($field_args); // Call the generic field renderer
    }
    echo "</div>"; // End of the grid container
}

/**
 * Content for the Nuru Schedule admin page.
 */
function nuru_schedule_page_content() {
    ?>
    <div class="wrap nuru-options-wrap">
        <h2>Nuru Schedule Options</h2>
        <form action="options.php" method="post">
            <?php
            settings_fields('nuru_options_group'); // Settings group for Schedule
            do_settings_sections('nuru-options'); // Page slug for Schedule
            submit_button('Save Changes');
            ?>
        </form>
    </div>
    <?php
}