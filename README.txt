=== Was This Helpful? – Article Feedback ===
Contributors: prototipo88
Tags: was this helpful, article feedback, feedback, WordPress feedback plugin, helpful 
Tested up to: 6.8
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

**Was this helpful?** plugin for WordPress adds a thumbs up/down box to collect quick article feedback on posts and pages.

== Description ==

**Was This Helpful?** is a simple, effective WordPress feedback plugin that adds a "Was this helpful?" box at the bottom of your content to capture article feedback from your visitors. 

Let your readers give quick feedback with a thumbs up or thumbs down — no comments or forms needed.

Designed for speed and simplicity, it helps you identify your most engaging posts and optimize underperforming ones. 

Unlike traditional comment forms, this feedback tool provides a frictionless way for users to respond with a single click — helping you understand content performance and engagement at a glance.

Ideal for blogs, documentation, knowledge bases, and business websites.

== The Problem It Solves ==

Many website owners and bloggers face the same challenges:

- **Low comment engagement**: Readers rarely leave comments, making it hard to know what content works.
- **Unclear content performance**: Analytics show visits, but not whether the article helped readers.
- **Missed opportunities to improve content**: Without feedback, you don’t know which posts need updates or rewrites.

Was This Helpful is an article feedback plugin that solves these problems by providing quick feedback directly on your articles. 

A simple thumbs up/down helps you see:

- Which posts your audience finds useful.
- Which pages need updates or improvements.
- How user satisfaction changes over time.

== Key Benefits== 

**Collect instant feedback**: learn what readers think without requiring them to leave a comment.

**Improve content strategy**: identify helpful vs. unhelpful posts to guide updates and new content.

**Track content performance after updates**: use the reset feedback feature to see how your posts perform after you make changes or optimizations, without losing historical data.

**Boost engagement**: give users a one-click way to interact with your articles.

**Enhance SEO indirectly**: updating low-rated content can improve dwell time and reduce bounce rates.

== Common Use Cases ==

**Blog posts**: measure reader satisfaction and identify which articles resonate.

**Knowledge bases / documentation**: quickly see if tutorials and how-to guides are effective.

**Product or service pages**: understand if descriptions and details answer customer questions.

**Course or learning websites**: track which lessons are clear or need more explanation.

**Business websites**: gather visitor insights without long forms or surveys.

== Core Features ==

- Adds a "Was this helpful?" thumbs up/down box after posts and pages.
- Supports pages, posts, or both.
- Optionally disable the box on specific posts or pages.
- See percentage of positive feedback directly in the admin post list.
- Clean, minimal UI that adapts to your theme.
- Lightweight with optional CSS/JS loading.
- Includes shortcode to manually place the box.
- Reset feedback statistics per post or page without deleting historical feedback.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/riaco-was-this-helpful` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Go to **Settings > Was This Helpful** to configure display options and visibility rules.

== Frequently Asked Questions ==

= Can I choose where the feedback box appears? =
Yes. By default, the box appears after your content on posts and pages. You can also use the `[was_this_helpful]` shortcode to place it manually.

= Can I disable the feedback box on specific posts? =
Absolutely. Just uncheck the display option in the editor sidebar on that post or page.

= How do I see the feedback results? =  
Each post and page in your WordPress admin will show a "Was This Helpful" column, with the number and percentage of positive votes.

= Can I disable the loading of styles or scripts? =
Yes, you can disable the loading of styles or scripts from the plugin settings page.

= How do I enable feedback on custom post types? =
With the PRO version, custom post types appear in your settings page. Just check the ones you want feedback on.

= How do I reset feedback statistics for a post or page? =
In the WordPress admin, go to **Posts** or **Pages**, hover over the post you want to reset, and click **"Reset Helpful Stats"**.  
Resetting changes the reset date and stats are recalculated from that point forward.

= Will resetting feedback delete previous feedback from the database? =
No. Previous feedback entries remain stored in the database for historical purposes.  
However, the **displayed stats** (total and positive counts) only consider feedback **from the last reset date onward**.

= Can I undo a reset? =
Not directly. You can manually adjust the reset date in the post meta if necessary, but the plugin does not currently provide a one-click undo feature.

== Screenshots ==

1. Settings panel in the WordPress admin.
2. Frontend "Was This Helpful?" box with thumbs up/down.
3. Extra settings for assets loading and data deletion
4. Manual placement using shortcode.
5. Quick stats with color scheme
6. Block without editor option
7. Frontend
8. Frontend live

== Changelog ==

= 2.1.0 - 2025-08-06 =
* Added: Reset feature to reset helpful stats for single posts or pages.
* Updated: Plugin name for better identification in WordPress admin.

= 2.0.5 =
* Update: remove unnecessary code in frontend

= 2.0.4 =
* Update: display block even if auto box insertion is disabled in settings
* Fix: block style in editor, and added class wp-block-group to the box

= 2.0.3 =
* Update: add italian translation
* Remove: removed unnecessary files

= 2.0.2 =
* Update: improve security

= 2.0.1 =
* Update: improve security

= 2.0.0 =
* Update: change prefix
* Update: change table name
* Update: change shortcode name

= 1.5.1 =
* Added shortcode page in admin menu
* Added option to maintain/delete data when plugin is removed

= 1.5.0 =
* Refactor code

= 1.4.6 =
* added block to gutenberg editor
* Added button manager (text and icon) for feedback box in settings page. 
* Added stats metabox
* Added color scheme to positive percentage
* Added feedback box color background selection via settings

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
