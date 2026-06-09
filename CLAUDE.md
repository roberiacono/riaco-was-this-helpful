# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**RI Was This Helpful** (`riaco-was-this-helpful`) is a WordPress plugin (v2.1.2) that appends a thumbs-up/thumbs-down feedback box to posts and pages. It stores responses in a custom database table and surfaces statistics in the admin.

- PHP package prefix: `RIWTH`
- Text domain: `riaco-was-this-helpful`
- Requires: WordPress 6.2+, PHP 7.4+
- Custom DB table: `{prefix}riwth_helpful_feedback` (columns: `id`, `post_id`, `helpful`, `created_at`; index on `post_id`)

## Build Commands (Gutenberg Block)

The Gutenberg block lives in `helpful-box-block/` and uses `@wordpress/scripts`.

```bash
cd helpful-box-block

# Development watch mode
npm start

# Production build (outputs to helpful-box-block/build/)
npm run build

# Lint JS
npm run lint:js

# Lint CSS
npm run lint:css

# Format
npm run format
```

The built assets (`build/`) are committed. Always run `npm run build` before committing block changes.

## Architecture

### Bootstrap

`riaco-was-this-helpful.php` defines `RIWTH_PLUGIN_VERSION`, `RIWTH_PLUGIN_FILE`, and `RIWTH_DB_VERSION`, loads `class-was-this-helpful.php`, and calls `riwth_was_this_helpful()` to get the singleton.

`RIWTH_Was_This_Helpful` (singleton) wires everything together:
- `define_constants()` — sets `RIWTH_DB_NAME`, `RIWTH_PLUGIN_DIR`, `RIWTH_PLUGIN_URL`, `RIWTH_PLUGIN_DIRNAME`
- `includes()` — requires all class files
- `init_hooks()` — registers activation hook, enqueue hooks, `plugins_loaded` (priority 9: `maybe_upgrade_db`; priority 10: `init`)
- `maybe_upgrade_db()` (on `plugins_loaded`, priority 9) — compares `riwth_db_version` option against `RIWTH_DB_VERSION`; re-runs `dbDelta` when behind so existing installs pick up schema changes (e.g. the `post_id` index) without re-activation
- `init()` (on `plugins_loaded`) — instantiates all classes; admin-only classes are guarded by `is_admin()`; stats classes are gated by `RIWTH_User_Role::can_user_see_stats()`

### Class Responsibilities

| Class | File | Role |
|---|---|---|
| `RIWTH_Functions` | `class-functions.php` | Static helpers: feedback counts (with object cache + transient), `should_display_box()`, `could_display_box()`, color utility |
| `RIWTH_Settings` | `class-settings.php` | Admin menu/submenu, Settings API registration, all option callbacks and sanitizers, `get_intial_settings()` defaults |
| `RIWTH_Box` | `class-box.php` | Hooks `the_content` filter to append the feedback HTML; `feedback_box_code()` builds the HTML (cached via `riwth_feedback_box` transient) |
| `RIWTH_Ajax` | `class-ajax.php` | Handles `wp_ajax_riwth_save_feedback` (public + priv); enforces 30-second per-IP/post rate limit via transient; inserts row, busts caches, returns JSON |
| `RIWTH_Admin_Feedback_List` | `class-admin-feedback-list.php` | Admin "Feedback Records" submenu page — paginated table of raw feedback rows with single/bulk delete and CSV export |
| `RIWTH_Block` | `class-block.php` | Registers the Gutenberg block (`riaco-was-this-helpful/helpful-box-block`) with a server-side render callback that reuses `RIWTH_Box::feedback_box_code()` |
| `RIWTH_Shortcode` | `class-shortcode.php` | Registers `[riwth_helpful_box]` shortcode |
| `RIWTH_Admin_Columns` | `class-admin-columns.php` | Adds "Was This Helpful?" column to post/page list tables; makes it sortable via custom JOIN/ORDER BY SQL |
| `RIWTH_Admin_Bar` | `class-admin-bar.php` | Adds feedback percentage to the WordPress admin bar on singular pages |
| `RIWTH_Metabox` | `class-metabox.php` | "Helpful Settings" sidebar metabox — checkbox to disable the box per-post (`_riwth_disable_box` meta) |
| `RIWTH_Metabox_Stats` | `class-metabox-stats.php` | "Helpful Stats" sidebar metabox — shows live feedback counts; fires `riwth_after_metabox_stats` action |
| `RIWTH_Reset_Stats` | `class-reset-stats.php` | Adds "Reset Helpful Stats" row action; sets `_riwth_reset_date` post meta so counts are computed from that date forward |
| `RIWTH_User_Role` | `class-user-role.php` | `can_user_see_stats()` — checks current user roles against `riwth_display_by_user_role` option |
| `RIWTH_SVG_Icons` | `class-svg-icons.php` | Static registry of SVG icon strings for positive/negative buttons |
| `RIWTH_Admin_Pages_Footer` | `class-admin-pages-footer.php` | Renders the "Made with ♥" pre-footer and the `admin_footer_text` rating line on plugin admin pages |
| `RIWTH_Admin_Review_Notice` | `class-admin-review-notice.php` | Shows a wp.org review nudge (suppressed once `riwth_review_notice_done` option is set) |

### Display Logic

`RIWTH_Functions::should_display_box()` — used for actual front-end rendering — returns `true` only when:
1. A `$post` object exists
2. `_riwth_disable_box` meta is not `'1'`
3. The `riwth_feedback_given` cookie does not include this post ID
4. `could_display_box()` is `true` (filterable via `riwth_should_display_box`)

`RIWTH_Functions::could_display_box()` — used for admin UI visibility — returns `true` when the current post type is in the `riwth_display_on` option array and it's a singular front-end request (or the correct admin screen). The result is filterable via `riwth_could_display_box`.

### Caching Pattern

Feedback counts use a two-layer cache: `wp_cache_get/set` (object cache, group `riwth_feedback`) with a transient fallback. Both are invalidated together on every new submission (`RIWTH_Ajax::save_feedback`) and on stats reset. The HTML of the feedback box itself is cached in the `riwth_feedback_box` transient (365-day TTL); it is busted via the `updated_option` hook in `RIWTH_Settings::maybe_clear_box_transient()` whenever any `riwth_feedback_box_*` option is saved.

### Developer Hooks

**Filters**

| Filter | Args | Where | Purpose |
|---|---|---|---|
| `riwth_feedback_box_html` | `$html, $post_id` | `RIWTH_Box::feedback_box_code()` | Wrap, replace, or extend the box markup |
| `riwth_feedback_box_atts` | `$atts, $post_id` | `RIWTH_Box::feedback_box_code()` | Modify structured box attributes (styles, icons) before HTML is built; keys: `feedback_box_style`, `positive_button_icon`, `positive_button_style`, `negative_button_icon`, `negative_button_style` |
| `riwth_should_display_box` | `$bool, $post_id` | `RIWTH_Functions::should_display_box()` | Override display logic (category, user role, etc.) |
| `riwth_could_display_box` | `$bool, $post_id` | `RIWTH_Functions::could_display_box()` | Override the post-type/screen display gate (also controls metabox and shortcode visibility) |
| `riwth_feedback_given` | `$bool, $post_id` | `RIWTH_Functions::feedback_given()` | Override the cookie-based duplicate-vote check (e.g. server-side tracking for logged-in users) |
| `riwth_initial_settings` | `$settings` | `RIWTH_Settings::get_intial_settings()` | Override default option values |
| `riwth_display_on_fields` | `$fields` | `RIWTH_Settings` | Add post types to "Display on" setting |
| `riwth_custom_columns_post_types` | `$types` | `RIWTH_Admin_Columns` | Extend feedback column to custom post types |
| `riwth_admin_column_content` | `$html, $post_id, $total, $positive` | `RIWTH_Admin_Columns::display_feedback_column()` | Extend or replace the admin column cell HTML |
| `riwth_get_positive_feedback_filter` | `$count, $table, $post_id` | `RIWTH_Functions` | Modify positive-feedback count |
| `riwth_get_total_feedback_filter` | `$count, $table, $post_id` | `RIWTH_Functions` | Modify total-feedback count |
| `riwth_insert_feedback_data` | `$data, $post_id, $helpful` | `RIWTH_Ajax::save_feedback()` | Add extra columns to the feedback DB insert array |
| `riwth_insert_feedback_format` | `$format, $data` | `RIWTH_Ajax::save_feedback()` | Provide matching `%` format array for extra insert columns |
| `riwth_ajax_feedback_sent_return` | `$response` | `RIWTH_Ajax` | Modify the AJAX JSON response after save |
| `riwth_localize_script_data` | `$data` | `RIWTH_Was_This_Helpful::maybe_enqueue_scripts()` | Add extra variables to the `riwth_scripts` JS object |
| `riwth_can_user_see_stats` | `$bool, $user` | `RIWTH_User_Role::can_user_see_stats()` | Override role-based stats visibility |

**Actions**

| Action | Args | Where | Purpose |
|---|---|---|---|
| `riwth_loaded` | — | `RIWTH_Was_This_Helpful::init()` | Fires after all plugin classes are instantiated and their hooks registered |
| `riwth_before_save_feedback` | `$post_id, $helpful` | `RIWTH_Ajax::save_feedback()` | Logging, rate-limiting before DB insert |
| `riwth_feedback_saved` | `$feedback_id, $post_id, $helpful` | `RIWTH_Ajax::save_feedback()` | Notifications, analytics after successful save |
| `riwth_before_save_metabox` | `$post_id` | `RIWTH_Metabox::save_metabox()` | Fires before per-post "disable box" meta is saved |
| `riwth_after_save_metabox` | `$post_id` | `RIWTH_Metabox::save_metabox()` | Fires after per-post "disable box" meta is saved |
| `riwth_before_reset_stats` | `$post_id` | `RIWTH_Reset_Stats::riwth_handle_reset_stats_action()` | Fires before stats are reset (cache cleared, reset date written) |
| `riwth_after_reset_stats` | `$post_id` | `RIWTH_Reset_Stats::riwth_handle_reset_stats_action()` | Fires after stats are reset |
| `riwth_before_show_helpful_box_using_shortcode` | — | `RIWTH_Shortcode` | Side-effects before shortcode renders |
| `riwth_after_metabox_stats` | `$post` | `RIWTH_Metabox_Stats` | Append content to the "Helpful Stats" metabox |

### Frontend JavaScript

`assets/public/js/script.js` (vanilla JS, no jQuery, loaded as `riwth-script`):
- Listens for clicks on `.riwth-helpful-yes` / `.riwth-helpful-no`
- POSTs to `admin-ajax.php` via `fetch` with `action=riwth_save_feedback`, `post_id`, `helpful` (1/0), and nonce
- On success, dispatches the `CustomEvent` named by `response.trigger` (normally `showThankYou`) and sets the `riwth_feedback_given` cookie; event detail carries `{ feedbackId, content }`
- Handles non-success responses (e.g. rate-limit 429) by dispatching `showError`
- Localized data available as `riwth_scripts`: `ajax_url`, `submitting`, `postId`

### Gutenberg Block

`helpful-box-block/src/` contains the block source. The editor (`edit.js`) renders the feedback box HTML from the `helpfulBox` block attribute (populated server-side via `get_feedback_block_for_editor()`). The block has no save function — it is fully server-side rendered. Built output goes to `helpful-box-block/build/`.

## WordPress Options Reference

All options are prefixed `riwth_`. Key ones:

| Option | Default | Notes |
|---|---|---|
| `riwth_display_on` | `['post']` | Array of post types |
| `riwth_display_by_user_role` | `['administrator','editor']` | Controls stats visibility |
| `riwth_load_styles` / `riwth_load_scripts` | `1` | Toggle asset loading |
| `riwth_feedback_box_text` | "Was This Helpful?" | |
| `riwth_feedback_box_color_background` | `#f4f4f5` | |
| `riwth_uninstall_remove_data` | `1` | Drop table on uninstall |
| `riwth_db_version` | — | Tracks applied DB schema version; compared against `RIWTH_DB_VERSION` constant on every load |
