<?php
// nuru-utils.php
// Utility functions for Nuru Options plugin

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Retrieves goddess post IDs for a specific location, timeslot, and day (Schedule).
 */
function get_nuru_slot_day_data($location, $timeslot, $day_index) {
    $valid_locations   = array('montreal', 'laval', 'nuru_vip');
    $valid_timeslots   = array('10am_3pm', '10am_7pm', '3pm_9pm', '7pm_11pm', '9pm_5am');
    $valid_day_indices = range(0, 6);

    if (!in_array($location, $valid_locations) ||
        !in_array($timeslot, $valid_timeslots) ||
        !in_array($day_index, $valid_day_indices)) {
        error_log("Invalid arguments for get_nuru_slot_day_data: Location '{$location}', Timeslot '{$timeslot}', Day '{$day_index}'");
        return array();
    }

    $options  = get_option('nuru_options_settings');
    $field_id = "{$location}_{$timeslot}_day{$day_index}";

    $stored_data       = isset($options[$field_id]) ? $options[$field_id] : '';
    $selected_ids_array = array();

    if (is_array($stored_data)) {
        foreach ($stored_data as $item) {
            $id = absint(trim($item));
            if ($id > 0) { $selected_ids_array[] = $id; }
        }
    } elseif (is_string($stored_data) && !empty($stored_data)) {
        $selected_ids_array = array_filter(array_map('absint', explode(',', $stored_data)));
    }

    return array_unique($selected_ids_array);
}

/**
 * Retrieves goddess post IDs for a specific location and timeslot (Who's on Now).
 */
function get_nuru_who_on_now_data($location, $timeslot) {
    $valid_locations = array('montreal', 'laval', 'nuru_vip');
    $valid_timeslots = array('10am_3pm', '10am_7pm', '3pm_9pm', '7pm_11pm', '9pm_5am');

    if (!in_array($location, $valid_locations) ||
        !in_array($timeslot, $valid_timeslots)) {
        error_log("Invalid arguments for get_nuru_who_on_now_data: Location '{$location}', Timeslot '{$timeslot}'");
        return array();
    }

    $options  = get_option('nuru_who_on_now_settings');
    $field_id = "{$location}_{$timeslot}";

    $stored_data        = isset($options[$field_id]) ? $options[$field_id] : '';
    $selected_ids_array = array();

    if (is_string($stored_data) && !empty($stored_data)) {
        $selected_ids_array = array_filter(array_map('absint', explode(',', $stored_data)));
    } elseif (is_array($stored_data)) {
        foreach ($stored_data as $item) {
            $id = absint(trim($item));
            if ($id > 0) { $selected_ids_array[] = $id; }
        }
    }

    return array_unique($selected_ids_array);
}

/**
 * Renders a Select2 multi-select field.
 *
 * When 'day_index' is present in $args  → renders a day item (inside schedule grid).
 * When 'day_index' is absent            → renders a collapsible slot item (Who's on Now).
 */
function nuru_options_select2_field_callback($args) {
    $option_name      = $args['option_name'];
    $options          = get_option($option_name);
    $location         = $args['location'];
    $slot_slug        = $args['slot_slug'];
    $slot_display     = $args['slot_display'];
    $day_index_present = isset($args['day_index']);

    if ($day_index_present) {
        $day_index  = $args['day_index'];
        $field_id   = "{$location}_{$slot_slug}_day{$day_index}";
        $label_text = $args['day_name'];
    } else {
        $field_id   = "{$location}_{$slot_slug}";
        $label_text = $slot_display;
    }

    // Resolve stored selections
    $stored_data        = isset($options[$field_id]) ? $options[$field_id] : '';
    $selected_ids_array = array();
    if (is_array($stored_data)) {
        $selected_ids_array = array_filter(array_map('absint', $stored_data));
    } elseif (is_string($stored_data)) {
        $selected_ids_array = array_filter(array_map('absint', explode(',', $stored_data)));
    }

    // Build options list
    $all_posts = get_posts(array(
        'post_type'        => 'goddess',
        'posts_per_page'   => -1,
        'orderby'          => 'title',
        'order'            => 'ASC',
        'suppress_filters' => true,
    ));

    $all_options = array();
    foreach ($all_posts as $post) {
        $all_options[] = array(
            'id'       => $post->ID,
            'text'     => get_the_title($post->ID) . ' (ID: ' . $post->ID . ')',
            'selected' => in_array($post->ID, $selected_ids_array),
        );
    }

    $name_attr = esc_attr($option_name) . '[' . esc_attr($field_id) . '][]';
    $id_attr   = esc_attr($field_id);

    if ($day_index_present) {
        // ---- Schedule day cell ----
        ?>
        <div class="nuru-day-item">
            <h4><?php echo esc_html($label_text); ?></h4>
            <select id="<?php echo $id_attr; ?>" name="<?php echo $name_attr; ?>"
                    class="nuru-post-select2" style="width:100%;" multiple="multiple">
                <?php foreach ($all_options as $opt): ?>
                    <option value="<?php echo esc_attr($opt['id']); ?>"<?php echo $opt['selected'] ? ' selected' : ''; ?>>
                        <?php echo esc_html($opt['text']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    } else {
        // ---- Who's on Now slot — collapsible ----
        $has_selection = !empty($selected_ids_array);
        ?>
        <div class="nuru-slot-item nuru-collapsible">
            <button type="button" class="nuru-collapsible-header">
                <span class="nuru-slot-label">
                    <span class="dashicons dashicons-clock nuru-slot-icon"></span>
                    <?php echo esc_html($slot_display); ?>
                    <?php if ($has_selection): ?>
                        <span class="nuru-badge"><?php echo count($selected_ids_array); ?></span>
                    <?php endif; ?>
                </span>
                <span class="nuru-chevron dashicons dashicons-arrow-down-alt2"></span>
            </button>
            <div class="nuru-collapsible-body" style="display:none;">
                <div class="nuru-select-wrap">
                    <select id="<?php echo $id_attr; ?>" name="<?php echo $name_attr; ?>"
                            class="nuru-post-select2" style="width:100%;" multiple="multiple">
                        <?php foreach ($all_options as $opt): ?>
                            <option value="<?php echo esc_attr($opt['id']); ?>"<?php echo $opt['selected'] ? ' selected' : ''; ?>>
                                <?php echo esc_html($opt['text']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <?php
    }
}
