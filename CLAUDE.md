# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**RI Was This Helpful** (`riaco-was-this-helpful`) is a WordPress plugin (v2.1.2) that appends a thumbs-up/thumbs-down feedback box to posts and pages. It stores responses in a custom database table and surfaces statistics in the admin.

- PHP package prefix: `RIWTH`
- Text domain: `riaco-was-this-helpful`
- Requires: WordPress 6.2+, PHP 7.4+
- Custom DB table: `{prefix}riwth_helpful_feedback` (columns: `id`, `post_id`, `helpful`, `created_at`)

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

`riaco-was-this-helpful.php` defines `RIWTH_PLUGIN_VERSION` and `RIWTH_PLUGIN_FILE`, loads `class-was-this-helpful.php`, and calls `riwth_was_this_helpful()` to get the singleton.

`RIWTH_Was_This_Helpful` (singleton) wires everything together:
- `define_constants()` — sets `RIWTH_DB_NAME`, `RIWTH_PLUGIN_DIR`, `RIWTH_PLUGIN_URL`, `RIWTH_PLUGIN_DIRNAME`
- `includes()` — requires all class files
- `init_hooks()` — registers activation hook, enqueue hooks, and `plugins_loaded`
- `init()` (on `plugins_loaded`) — instantiates all classes; admin-only classes are guarded by `is_admin()`; stats classes are gated by `RIWTH_User_Role::can_user_see_stats()`

### Class Responsibilities

| Class | File | Role |
|---|---|---|
| `RIWTH_Functions` | `class-functions.php` | Static helpers: feedback counts (with object cache + transient), `should_display_box()`, `could_display_box()`, color utility |
| `RIWTH_Settings` | `class-settings.php` | Admin menu/submenu, Settings API registration, all option callbacks and sanitizers, `get_intial_settings()` defaults |
| `RIWTH_Box` | `class-box.php` | Hooks `the_content` filter to append the feedback HTML; `feedback_box_code()` builds the HTML (cached via `riwth_feedback_box` transient) |
| `RIWTH_Ajax` | `class-ajax.php` | Handles `wp_ajax_riwth_save_feedback` (public + priv); inserts row, busts caches, returns JSON |
| `RIWTH_Block` | `class-block.php` | Registers the Gutenberg block (`riaco-was-this-helpful/helpful-box-block`) with a server-side render callback that reuses `RIWTH_Box::feedback_box_code()` |
| `RIWTH_Shortcode` | `class-shortcode.php` | Registers `[riwth_helpful_box]` shortcode |
| `RIWTH_Admin_Columns` | `class-admin-columns.php` | Adds "Was This Helpful?" column to post/page list tables; makes it sortable via custom JOIN/ORDER BY SQL |
| `RIWTH_Admin_Bar` | `class-admin-bar.php` | Adds feedback percentage to the WordPress admin bar on singular pages |
| `RIWTH_Metabox` | `class-metabox.php` | "Helpful Settings" sidebar metabox — checkbox to disable the box per-post (`_riwth_disable_box` meta) |
| `RIWTH_Metabox_Stats` | `class-metabox-stats.php` | "Helpful Stats" sidebar metabox — shows live feedback counts; fires `riwth_after_metabox_stats` action |
| `RIWTH_Reset_Stats` | `class-reset-stats.php` | Adds "Reset Helpful Stats" row action; sets `_riwth_reset_date` post meta so counts are computed from that date forward |
| `RIWTH_User_Role` | `class-user-role.php` | `can_user_see_stats()` — checks current user roles against `riwth_display_by_user_role` option |
| `RIWTH_SVG_Icons` | `class-svg-icons.php` | Static registry of SVG icon strings for positive/negative buttons |
| `RIWTH_Admin_Pages_Footer` | `class-admin-pages-footer.php` | Renders the PRO upsell footer on plugin admin pages |
| `RIWTH_Admin_Review_Notice` | `class-admin-review-notice.php` | Shows a wp.org review nudge (suppressed once `riwth_review_notice_done` option is set) |

### Display Logic

`RIWTH_Functions::should_display_box()` — used for actual front-end rendering — returns `true` only when:
1. A `$post` object exists
2. `_riwth_disable_box` meta is not `'1'`
3. The `riwth_feedback_given` cookie does not include this post ID
4. `could_display_box()` is true

`RIWTH_Functions::could_display_box()` — used for admin UI visibility — returns `true` when the current post type is in the `riwth_display_on` option array and it's a singular front-end request (or the correct admin screen).

### Caching Pattern

Feedback counts use a two-layer cache: `wp_cache_get/set` (object cache, group `riwth_feedback`) with a transient fallback. Both are invalidated together on every new submission (`RIWTH_Ajax::save_feedback`) and on stats reset. The HTML of the feedback box itself is cached in `riwth_feedback_box` transient; it is busted when settings are saved (in `feedback_box_border_button_rounded_callback`).

### Pro Extension Points

The free plugin exposes several filters for a PRO add-on:
- `riwth_display_on_fields` — add custom post types to "Display on" setting
- `riwth_custom_columns_post_types` — extend admin columns to custom post types
- `riwth_get_positive_feedback_filter` / `riwth_get_total_feedback_filter` — modify DB query results
- `riwth_ajax_feedback_sent_return` — modify AJAX response payload
- `riwth_after_metabox_stats` action — append content to the stats metabox

### Frontend JavaScript

`assets/public/js/script.js` (jQuery, loaded as `riwth-script`):
- Listens for clicks on `.riwth-helpful-yes` / `.riwth-helpful-no`
- POSTs to `admin-ajax.php` with `action=riwth_save_feedback`, `post_id`, `helpful` (1/0), and nonce
- On success, fires the DOM event from `response.trigger` (normally `showThankYou`) and sets the `riwth_feedback_given` cookie
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
