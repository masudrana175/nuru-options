<?php
// nuru-utils.php
// Utility functions for Nuru Options plugin

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Retrieves goddess posts for a specific location, timeslot, and day from the Schedule settings.
 *
 * @param string $location   'montreal' or 'laval'.
 * @param string $timeslot   e.g., '10am_3pm'.
 * @param int    $day_index  0-6 for Day 1 to Day 7.
 * @return array An array of WP_Post objects, or empty array if none found.
 */
function get_nuru_slot_day_data($location, $timeslot, $day_index) {
    $valid_locations = array('montreal', 'laval', 'nuru_vip');
    $valid_timeslots = array('10am_3pm', '10am_7pm', '3pm_9pm', '7pm_11pm', '9pm_5am');
    $valid_day_indices = range(0, 6);

    if (!in_array($location, $valid_locations) ||
        !in_array($timeslot, $valid_timeslots) ||
        !in_array($day_index, $valid_day_indices)) {
        error_log("Invalid arguments for get_nuru_slot_day_data: Location '{$location}', Timeslot '{$timeslot}', Day '{$day_index}'");
        return array();
    }

    $options = get_option('nuru_options_settings'); // Fetches from Schedule settings
    $field_id = "{$location}_{$timeslot}_day{$day_index}";

    $stored_data = isset($options[$field_id]) ? $options[$field_id] : '';
    $selected_ids_array = array();

    if (is_array($stored_data)) { // Legacy array of lines/IDs
        foreach ($stored_data as $item) {
            $id = absint(trim($item));
            if ($id > 0) {
                $selected_ids_array[] = $id;
            }
        }
    } elseif (is_string($stored_data) && !empty($stored_data)) { // Current comma-separated string of IDs
        $selected_ids_array = array_filter(array_map('absint', explode(',', $stored_data)));
    }

    $selected_ids_array = array_unique($selected_ids_array);

    if (empty($selected_ids_array)) {
        return array();
    }

   /* $posts = get_posts(array(
        'post_type'      => 'goddess',
        'post__in'       => $selected_ids_array,
        'posts_per_page' => -1,
        'orderby'        => 'post__in',
        'suppress_filters' => true,
    ));
	*/

    return $selected_ids_array; // Returns an array of WP_Post objects
}

/**
 * Retrieves goddess posts for a specific location and timeslot from the "Who's on Now" settings.
 * This version does NOT include day indexing.
 *
 * @param string $location 'montreal' or 'laval'.
 * @param string $timeslot e.g., '10am_3pm'.
 * @return array An array of WP_Post objects, or empty array if none found.
 */
function get_nuru_who_on_now_data($location, $timeslot) {
    $valid_locations = array('montreal', 'laval', 'nuru_vip');
    $valid_timeslots = array('10am_3pm', '10am_7pm', '3pm_9pm', '7pm_11pm', '9pm_5am');

    if (!in_array($location, $valid_locations) ||
        !in_array($timeslot, $valid_timeslots)) {
        error_log("Invalid arguments for get_nuru_who_on_now_data: Location '{$location}', Timeslot '{$timeslot}'");
        return array();
    }

    $options = get_option('nuru_who_on_now_settings'); // Fetches from Who's on Now settings
    // The field ID structure here is `location_timeslot` (no _dayX)
    $field_id = "{$location}_{$timeslot}";

    $stored_data = isset($options[$field_id]) ? $options[$field_id] : '';
    $selected_ids_array = array();

    if (is_string($stored_data) && !empty($stored_data)) {
        $selected_ids_array = array_filter(array_map('absint', explode(',', $stored_data)));
    } elseif (is_array($stored_data)) { // Handle potential legacy arrays during transition if structure ever changed
        foreach ($stored_data as $item) {
            $id = absint(trim($item));
            if ($id > 0) {
                $selected_ids_array[] = $id;
            }
        }
    }

    $selected_ids_array = array_unique($selected_ids_array);

    if (empty($selected_ids_array)) {
        return array();
    }

   /* $posts = get_posts(array(
        'post_type'      => 'goddess',
        'post__in'       => $selected_ids_array,
        'posts_per_page' => -1,
        'orderby'        => 'post__in',
        'suppress_filters' => true,
    ));
*/
    return $selected_ids_array; // Returns an array of WP_Post objects
}

/**
 * Custom Callback to render Select2 fields for goddess posts.
 * This function is generic and can be used for both single-day and multi-day selections.
 *
 * @param array $args Arguments for the field, including 'option_name', 'location', 'slot_slug', 'slot_display', and optionally 'day_index'.
 */
function nuru_options_select2_field_callback($args) {
    $option_name = $args['option_name'];
    $options = get_option($option_name);
    $location = $args['location'];
    $slot_slug = $args['slot_slug'];
    $slot_display = $args['slot_display'];
    
    // Construct field_id based on whether it includes a day or not
    $day_index_present = isset($args['day_index']);
    if ($day_index_present) {
        $day_index = $args['day_index'];
        $field_id = "{$location}_{$slot_slug}_day{$day_index}";
        $label_text = $args['day_name']; // Get day name from args
    } else {
        $field_id = "{$location}_{$slot_slug}";
        $label_text = $slot_display; // Label is just the slot display if no day
    }
    
    // Get stored data and convert to array
    $stored_data = isset($options[$field_id]) ? $options[$field_id] : '';
    $selected_ids_array = array();
    if (is_array($stored_data)) {
        $selected_ids_array = array_filter(array_map('absint', $stored_data));
    } elseif (is_string($stored_data)) {
        $selected_ids_array = array_filter(array_map('absint', explode(',', $stored_data)));
    }
    
    // Fetch ALL posts of type 'goddess'
    $all_posts = get_posts(array(
        'post_type'      => 'goddess',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'suppress_filters' => true,
    ));
    
    // Build options array with all posts, marking selected ones
    $all_options = array();
    if (!empty($all_posts)) {
        foreach ($all_posts as $post) {
            $is_selected = in_array($post->ID, $selected_ids_array);
            $all_options[] = array(
                'id'       => $post->ID,
                'text'     => get_the_title($post->ID) . ' (ID: ' . $post->ID . ')',
                'selected' => $is_selected
            );
        }
    }
    
    $all_options_json = esc_attr(json_encode($all_options));
    $current_value_string = implode(',', $selected_ids_array);
    ?>
    <div class="nuru-select2-item <?php echo $day_index_present ? 'nuru-day-item' : 'nuru-slot-item'; ?>">
        <label for="<?php echo esc_attr($field_id); ?>">
            <?php if ($day_index_present) : ?>
                <h4><?php echo esc_html($label_text); ?></h4>
            <?php else : ?>
                <strong><?php echo esc_html($label_text); ?></strong>
            <?php endif; ?>
        </label>
        <select
            id="<?php echo esc_attr($field_id); ?>"
            name="<?php echo esc_attr($option_name); ?>[<?php echo esc_attr($field_id); ?>][]"
            class="nuru-post-select2"
            style="width: 100%;"
            multiple="multiple"
            data-initial-options="<?php echo $all_options_json; ?>"
        >
            <?php
            // Render all options with selected attribute for selected items
            if (!empty($all_options)) {
                foreach ($all_options as $option) {
                    $selected_attr = $option['selected'] ? ' selected' : '';
                    echo '<option value="' . esc_attr($option['id']) . '"' . $selected_attr . '>'
                        . esc_html($option['text']) .
                    '</option>';
                }
            }
            ?>
        </select>
    </div>
    <?php
}