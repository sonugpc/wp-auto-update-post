# Auto Renew Post Dates

A WordPress plugin that automatically updates the published date of posts and custom post types on a configurable schedule, keeping your content fresh for search engine reindexing.

## Why

Search engines like Google use the post modified date to decide how frequently to revisit a page. This plugin lets you touch that date on a regular interval without editing the content, so posts stay indexed as recently updated.

## Features

- Enable auto-renew per post/CPT via a sidebar meta box in the editor
- **Bulk enable** on all published posts across all post types from the settings page
- Schedule updates every 7 days, 15 days, or a custom interval (1–30 days)
- Optional custom time-of-day for when the date update fires
- Admin settings page listing all enrolled posts with their next scheduled push date
- Inline frequency editing from the settings page without leaving the screen

## Requirements

- WordPress 5.0+
- PHP 7.4+

## Installation

1. Upload the `wp-auto-update-post` folder to `/wp-content/plugins/`.
2. Activate the plugin through **Plugins > Installed Plugins** in the WordPress admin.
3. Go to **Auto Renew** in the admin sidebar to configure settings.

## Usage

### Enable on a single post

1. Open any post or CPT in the editor.
2. Find the **Auto Renew Post Date** box in the right sidebar.
3. Check **Enable Auto Renew**, choose a frequency, and save the post.

### Bulk enable on all posts

1. Go to **Auto Renew** in the admin sidebar.
2. Select a frequency under **Enable on All Posts (All Post Types)**.
3. Click **Enable Auto Renew on All Posts**.

### Manage enrolled posts

The settings page lists every enrolled post with its post type, frequency, and next scheduled push date. You can edit the frequency inline and click **Update**, or click **Delete** to remove a post from the schedule.

## How it works

A WordPress cron event (`auto_renew_cron_hook`) runs hourly. For each enrolled post it checks whether the configured interval has elapsed since the last push. If it has, it calls `wp_update_post()` with the current date and time (optionally at a custom time), which updates `post_date` and `post_date_gmt` and triggers a standard WordPress save — enough for Google Search Console to pick up the change on the next crawl.

## Changelog

### 1.1
- Added bulk enable for all post types (CPTs included)
- Meta box now appears on all public post types, not just Posts
- Fixed CSRF vulnerability on delete/clear settings actions
- Fixed autosave wiping the enabled state per post
- Fixed AJAX frequency update (nonce was never sent in the request)
- Fixed admin page load corrupting the cron schedule anchor
- Fixed cron silently re-enabling posts that were manually disabled
- Unified two separate cron queries into one for efficiency
- Standardised internal meta key for last push date

### 1.0
- Initial release

## Author

Sonu Gupta
