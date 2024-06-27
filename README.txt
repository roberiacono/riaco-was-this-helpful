=== Helpful Feedback ===
Contributors: prototipo88
Tags: feedback, user interaction, comments
Requires at least: 6.2
Tested up to: 6.5.4
Requires PHP: 7.0
Stable tag: 1.4.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a "Was this helpful?" box at the end of posts with thumb-up/thumb-down buttons for feedback.

== Description ==

This plugin adds a simple "Was this helpful?" feedback box at the end of your posts/pages, allowing users to provide feedback on whether they found a post or page helpful. It includes the following features:

- Display a "Was This Helpful" box on posts and pages.
- Select which post types (posts and pages) the feedback box should appear on.
- Option to disable the feedback box on specific posts or pages.
- Sort posts and pages by percentage of positive feedback

**PRO Version Features:**

- Display the "Was This Helpful" box on custom post types.
- Additional settings for advanced customization.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/ri-was-this-helpful` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Settings->Was This Helpful screen to configure the plugin.

== Frequently Asked Questions ==

= Can I choose where the feedback box appears? =
Yes, you can choose to display the feedback box on the bottom of posts and pages. In the PRO version, you can choose to display the feedback box on the bottom of custom post types.

= Can I disable the feedback box on specific posts? =
Yes, you can disable the feedback box on individual posts or pages using the settings in the post editor.

= How do I view the feedback? =
You can view the percentage of positive feedback in the admin posts/pages list. A new column "Was This Helpful" will show the percentage of positive feedback and the total number of feedback entries.

= Can I disable the loading of styles or scripts? =
Yes, you can disable the loading of styles or scripts from the plugin settings page.

= How do I enable feedback on custom post types? =
With the PRO version installed, the custom post types will automatically be available in the settings page. Simply select the desired custom post types to enable feedback.

== Changelog ==

= 1.4.6 =
* added block to gutenberg editor
* Added button manager (text and icon) for feedback box in settings page. 
* Added stats metabox

= 1.4.5 =
* Added display stats and functionalities by user role

= 1.4.4 =
* Added filters to retrieve feedback with conditional

= 1.4.3 =
* Added option to disable the feedback box on individual posts or pages

= 1.4.2 =
* Refactor RI_WTH_Functions::should_display_box to include posts and pages

= 1.4.1 =
* Added support to choose to display feedback box on posts and pages

= 1.4 =
* Added quick stats on admin bar

= 1.3 =
* Refactor code in OOP.
* Added sorting by positive feedback in posts dashboard

= 1.2 =
* Added localization support.
* Separated scripts and styles into their own files.
* Added settings page to control the loading of scripts and styles.
* Improved feedback display in the admin post list.

= 1.1 =
* Added thank you message and animation.

= 1.0 =
* Initial release.

== Settings ==

1. **Show On Post Types:** Choose which post types (posts, pages) the feedback box should appear on.
2. **Disable On Specific Posts:** Option to disable the feedback box on individual posts or pages.

**PRO Version Settings:**

1. **Show On Custom Post Types:** Choose which custom post types the feedback box should appear on.

== License ==

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
