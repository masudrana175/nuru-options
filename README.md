# Nuru Options Plugin

A WordPress admin plugin for managing Goddess schedules and real-time availability across multiple Nuru Massage locations.

---

## Features

- **Schedule management** — assign Goddesses to specific time slots for each day of the week, per location
- **Who's on Now** — set which Goddesses are currently available for each time slot, per location
- **Three locations** — Montreal, Laval, and Nuru VIP
- **AJAX saving** — settings save without a full page reload, with inline success/error feedback
- **Select2 dropdowns** — searchable, multi-select dropdowns for easy Goddess selection
- **Sanitized & secure** — nonce-verified AJAX requests, capability checks, and sanitized input

---

## Requirements

- WordPress 5.0+
- PHP 7.4+
- The `goddess` custom post type must be registered and have published posts
- User must have the `edit_pages` capability to access or save settings

---

## Installation

1. Upload the `nuru-options` folder to `/wp-content/plugins/`
2. Activate the plugin through **Plugins → Installed Plugins** in the WordPress admin
3. The **Nuru Options** menu will appear in the admin sidebar

---

## Admin Pages

### Schedule (`Nuru Options → Schedule`)

Configure which Goddesses appear for each **location → time slot → day** combination.

| Location    | Time Slots                                          | Days              |
|-------------|-----------------------------------------------------|-------------------|
| Montreal    | 10am–3pm, 10am–7pm, 3pm–9pm, 7pm–11pm, 9pm–5am    | Mon – Sun         |
| Laval       | same as above                                       | Mon – Sun         |
| Nuru VIP    | same as above                                       | Mon – Sun         |

Each cell in the grid is a searchable multi-select dropdown. Select one or more Goddesses, then click **Save Changes**.

### Who's on Now (`Nuru Options → Who's on Now`)

Set which Goddesses are currently on shift for each **location → time slot** (no day dimension — this is a live/current view).

| Location    | Time Slots                                          |
|-------------|-----------------------------------------------------|
| Montreal    | 10am–3pm, 10am–7pm, 3pm–9pm, 7pm–11pm, 9pm–5am    |
| Laval       | same as above                                       |
| Nuru VIP    | same as above                                       |

---

## Developer Usage

### Retrieving Schedule Data

```php
/**
 * Get goddess post IDs for a given location, time slot, and day.
 *
 * @param string $location   'montreal' | 'laval' | 'nuru_vip'
 * @param string $timeslot   '10am_3pm' | '10am_7pm' | '3pm_9pm' | '7pm_11pm' | '9pm_5am'
 * @param int    $day_index  0 = Monday … 6 = Sunday
 * @return int[] Array of post IDs
 */
$ids = get_nuru_slot_day_data('montreal', '10am_3pm', 0); // Monday, Montreal
```

### Retrieving Who's on Now Data

```php
/**
 * Get goddess post IDs currently on for a given location and time slot.
 *
 * @param string $location  'montreal' | 'laval' | 'nuru_vip'
 * @param string $timeslot  '10am_3pm' | '10am_7pm' | '3pm_9pm' | '7pm_11pm' | '9pm_5am'
 * @return int[] Array of post IDs
 */
$ids = get_nuru_who_on_now_data('nuru_vip', '7pm_11pm');
```

Both functions return an array of integer post IDs. Pass these to `get_posts()` or `WP_Query` to retrieve the full post objects.

### Option Names (direct `get_option` access)

| Option name              | Page              |
|--------------------------|-------------------|
| `nuru_options_settings`  | Schedule          |
| `nuru_who_on_now_settings` | Who's on Now    |

Data is stored as comma-separated post ID strings keyed by field ID:

```
nuru_options_settings[montreal_10am_3pm_day0] = "42,107,88"
nuru_who_on_now_settings[nuru_vip_7pm_11pm]   = "42"
```

---

## File Structure

```
nuru-options/
├── nuru-options.php          # Main plugin file: menus, enqueue, AJAX handlers
├── nuru-schedule-page.php    # Schedule page: settings registration + UI
├── nuru-who-on-now-page.php  # Who's on Now page: settings registration + UI
├── nuru-utils.php            # Public helper functions + Select2 field renderer
├── nuru-options-admin.js     # Admin JavaScript: Select2 init + AJAX save
├── index.php                 # Empty security file
└── css/
    ├── nuru.css              # Plugin admin styles
    └── select2.min.css       # Select2 library styles
```

---

## AJAX Save Flow

1. User clicks **Save Changes**
2. JavaScript collects all `select.nuru-post-select2` values (including empty ones, so clearing a dropdown is respected)
3. A `POST` request is sent to `admin-ajax.php` with the action (`nuru_save_schedule` or `nuru_save_who_on_now`), the field data, and a nonce
4. PHP verifies the nonce, checks `edit_pages` capability, sanitizes input, and calls `update_option()`
5. A success or error notice appears inline without a page reload

---

## Changelog

### 1.1
- Added **Nuru VIP** as a third location on both Schedule and Who's on Now pages
- Replaced full-page form submission with AJAX saving
- Improved admin UI: colour-coded location sections, loading spinner, inline notices
- Cleaned up and consolidated CSS (removed duplicate rules)

### 1.0
- Initial release with Montreal and Laval locations
- Schedule (7-day grid) and Who's on Now pages
- Select2 multi-select dropdowns
