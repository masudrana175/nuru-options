<?php
// nuru-schedule-page.php

if (!defined('ABSPATH')) {
    exit;
}

function nuru_schedule_settings_init() {
    $slots     = array('10am_3pm', '10am_7pm', '3pm_9pm', '7pm_11pm', '9pm_5am');
    $locations = array('montreal', 'laval', 'nuru_vip');

    register_setting('nuru_options_group', 'nuru_options_settings', array(
        'sanitize_callback' => 'nuru_options_sanitize_callback',
        'type'              => 'array',
        'show_in_rest'      => false,
        'capability'        => 'edit_pages',
    ));

    add_settings_section('nuru_options_section_montreal',     '', '__return_false', 'nuru-options');
    add_settings_section('nuru_options_section_laval',        '', '__return_false', 'nuru-options');
    add_settings_section('nuru_options_section_nuru_vip',     '', '__return_false', 'nuru-options');
    add_settings_section('nuru_options_section_vip_exclusive','', '__return_false', 'nuru-options');

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

    // VIP Exclusive single field
    add_settings_field(
        'nuru_vip_exclusive',
        'VIP Exclusive Goddesses',
        'nuru_vip_exclusive_field_callback',
        'nuru-options',
        'nuru_options_section_vip_exclusive',
        array('option_name' => 'nuru_options_settings')
    );
}

/**
 * Renders a collapsible slot with a 7-day grid of Select2 dropdowns.
 */
function nuru_schedule_days_group_callback($args) {
    $slot_display = $args['slot_display'];
    $days_labels  = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');

    // Count total goddess assignments across all 7 days for this slot
    $option_data = get_option('nuru_options_settings', array());
    $total_count = 0;
    foreach (array_keys($days_labels) as $day_index) {
        $fid = $args['location'] . '_' . $args['slot_slug'] . '_day' . $day_index;
        if (!empty($option_data[$fid])) {
            $total_count += count(array_filter(explode(',', $option_data[$fid])));
        }
    }
    ?>
    <div class="nuru-collapsible">
        <button type="button" class="nuru-collapsible-header">
            <span class="nuru-slot-label">
                <span class="dashicons dashicons-clock nuru-slot-icon"></span>
                <?php echo esc_html($slot_display); ?>
                <?php if ($total_count > 0): ?>
                    <span class="nuru-badge"><?php echo $total_count; ?></span>
                <?php endif; ?>
            </span>
            <span class="nuru-chevron dashicons dashicons-arrow-down-alt2"></span>
        </button>
        <div class="nuru-collapsible-body" style="display:none;">
            <div class="nuru-days-grid">
                <?php
                foreach ($days_labels as $day_index => $day_name) {
                    nuru_options_select2_field_callback(array_merge($args, array(
                        'day_index' => $day_index,
                        'day_name'  => $day_name,
                    )));
                }
                ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Renders the VIP Exclusive Goddesses single multi-select field.
 */
function nuru_vip_exclusive_field_callback($args) {
    $option_name  = $args['option_name'];
    $options      = get_option($option_name, array());
    $field_id     = 'nuru_vip_exclusive';
    $stored_data  = isset($options[$field_id]) ? $options[$field_id] : '';

    $selected_ids = array();
    if (is_string($stored_data) && !empty($stored_data)) {
        $selected_ids = array_filter(array_map('absint', explode(',', $stored_data)));
    }

    $all_posts = get_posts(array(
        'post_type'        => 'goddess',
        'posts_per_page'   => -1,
        'orderby'          => 'title',
        'order'            => 'ASC',
        'suppress_filters' => true,
    ));
    ?>
    <div class="nuru-vip-exclusive-field">
        <select id="<?php echo esc_attr($field_id); ?>"
                name="<?php echo esc_attr($option_name); ?>[<?php echo esc_attr($field_id); ?>][]"
                class="nuru-post-select2"
                style="width:100%;"
                multiple="multiple">
            <?php foreach ($all_posts as $post): ?>
                <option value="<?php echo esc_attr($post->ID); ?>"
                        <?php echo in_array($post->ID, $selected_ids) ? 'selected' : ''; ?>>
                    <?php echo esc_html(get_the_title($post->ID)) . ' (ID: ' . $post->ID . ')'; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description" style="margin-top:8px;">
            These Goddesses are exclusive to Nuru VIP and will appear separately from the regular schedule.
        </p>
    </div>
    <?php
}

/**
 * Renders the Schedule admin page.
 */
function nuru_schedule_page_content() {
    $locations = array(
        array(
            'key'     => 'montreal',
            'label'   => 'Montreal Nuru Massage',
            'desc'    => 'Configure the schedule for Montreal across all time slots and days.',
            'section' => 'nuru_options_section_montreal',
            'css'     => 'nuru-loc-montreal',
        ),
        array(
            'key'     => 'laval',
            'label'   => 'Laval Nuru Massage',
            'desc'    => 'Configure the schedule for Laval across all time slots and days.',
            'section' => 'nuru_options_section_laval',
            'css'     => 'nuru-loc-laval',
        ),
        array(
            'key'     => 'nuru_vip',
            'label'   => 'Nuru VIP',
            'desc'    => 'Configure the schedule for Nuru VIP across all time slots and days.',
            'section' => 'nuru_options_section_nuru_vip',
            'css'     => 'nuru-loc-vip',
        ),
    );

    // Pre-compute VIP Exclusive badge count
    $vip_options      = get_option('nuru_options_settings', array());
    $vip_excl_stored  = isset($vip_options['nuru_vip_exclusive']) ? $vip_options['nuru_vip_exclusive'] : '';
    $vip_excl_count   = 0;
    if (!empty($vip_excl_stored)) {
        $vip_excl_count = count(array_filter(array_map('absint', explode(',', $vip_excl_stored))));
    }
    ?>
    <div class="wrap nuru-options-wrap">

        <!-- Sticky top bar -->
        <div class="nuru-sticky-bar">
            <div class="nuru-sticky-bar-left">
                <span class="dashicons dashicons-calendar-alt nuru-bar-icon"></span>
                <span class="nuru-bar-title">Nuru Schedule</span>
            </div>
            <button type="submit" form="nuru-schedule-form" class="nuru-save-btn" id="nuru-schedule-save">
                <span class="nuru-btn-text">Save Changes</span>
                <span class="nuru-btn-spinner" style="display:none;">
                    <span class="spinner is-active" style="float:none;vertical-align:middle;margin:0 4px 0 0;"></span>Saving&hellip;
                </span>
            </button>
        </div>

        <!-- Inline save notice -->
        <div id="nuru-schedule-notice" class="nuru-ajax-notice" style="display:none;" role="alert"></div>

        <form id="nuru-schedule-form" class="nuru-ajax-form"
              data-action="nuru_save_schedule"
              data-option="nuru_options_settings"
              data-notice="#nuru-schedule-notice"
              data-savebtn="#nuru-schedule-save">
            <?php wp_nonce_field('nuru_ajax_nonce', 'nuru_nonce'); ?>

            <!-- Location slot cards -->
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
                        <?php do_settings_fields('nuru-options', $loc['section']); ?>
                    </tbody></table>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- VIP Exclusive Goddesses card -->
            <div class="nuru-location-card nuru-loc-vip nuru-vip-exclusive-card">
                <div class="nuru-location-header">
                    <div class="nuru-location-title-wrap">
                        <h2 class="nuru-location-title">
                            <span class="dashicons dashicons-star-filled nuru-vip-star"></span>
                            VIP Exclusive Goddesses
                            <?php if ($vip_excl_count > 0): ?>
                                <span class="nuru-badge" style="font-size:.6em;vertical-align:middle;"><?php echo $vip_excl_count; ?></span>
                            <?php endif; ?>
                        </h2>
                        <p class="nuru-location-desc">
                            Goddesses exclusively available to Nuru VIP members — shown separately from the regular schedule.
                        </p>
                    </div>
                </div>
                <div class="nuru-location-body nuru-vip-exclusive-body">
                    <table class="form-table nuru-form-table" role="presentation"><tbody>
                        <?php do_settings_fields('nuru-options', 'nuru_options_section_vip_exclusive'); ?>
                    </tbody></table>
                </div>
            </div>

        </form>
    </div>
    <?php
}
