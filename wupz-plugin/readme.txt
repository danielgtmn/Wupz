=== Wupz Backup ===
Contributors: danielgietmann
Tags: backup, database, files, scheduled, restore
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 0.0.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive WordPress backup solution that allows manual and scheduled backups of your database and files.

== Description ==

Wupz Backup is a powerful and easy-to-use WordPress backup solution that helps you protect your website with automated and manual backups.

**Key Features:**

* **Manual Backups** - Create instant backups with a single click
* **Scheduled Backups** - Automatic daily or weekly backups
* **Database Export** - Complete MySQL database backup with structure and data
* **File Backup** - Backup your WordPress files and uploads
* **System Status Check** - Monitor your system's backup readiness
* **Email Notifications** - Get notified when scheduled backups fail
* **Backup Management** - Easy download and deletion of backup files
* **Security Focused** - Follows WordPress security best practices

**System Requirements:**

* WordPress 5.0 or higher
* PHP 8.1 or higher
* ZipArchive PHP extension
* Sufficient disk space for backups
* Write permissions for backup directory

**How It Works:**

1. Install and activate the plugin
2. Configure your backup preferences in Settings
3. Create manual backups or set up automated schedules
4. Download or manage your backups from the admin panel

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wupz-backup` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Wupz menu item in your WordPress admin to configure and create backups.

== Frequently Asked Questions ==

= Where are backups stored? =

Backups are stored in the `/wp-content/wupz-backups/` directory on your server. This directory is protected with .htaccess rules to prevent direct access.

= Can I schedule automatic backups? =

Yes. Choose Daily or Weekly under Wupz → Settings. Disabled removes the scheduled event. WordPress cron runs the backup once per interval. The next run is one interval after you save the setting (or after you update to 0.0.4), then it repeats. It does not run on every page view.

= What gets included in backups? =

By default, backups include your complete WordPress database and all files in the wp-content directory (themes, plugins, uploads, etc.). You can configure what to include in the settings.

= How do I restore a backup? =

Currently, the plugin focuses on creating backups. For restoration, you can download the backup files and restore them manually through your hosting control panel or contact your hosting provider.

= Can I exclude certain files from backups? =

Yes. Exclude patterns in the plugin settings are applied while wp-content is archived. A trailing slash matches a directory name anywhere under wp-content (cache/, tmp/, temp/). Use * as a wildcard (*.log, *.tmp). Files larger than the maximum file size (100 MB by default) are skipped. The backup directory itself is always skipped.

= Will this work with my hosting provider? =

The plugin is designed to work with most standard WordPress hosting environments. It requires basic file system permissions and the ZipArchive PHP extension.

== Screenshots ==

1. Main backup dashboard showing system status and backup controls
2. Backup list with download and delete options
3. Settings page for configuring backup preferences
4. System status check showing environment compatibility

== Changelog ==

= 0.0.4 =
* Requires PHP 8.1
* Fixed scheduled backups running on every visit instead of once per daily or weekly interval
* The Backup Schedule setting, including Disabled, now controls WP-Cron. The legacy schedule_interval option is migrated and kept in sync
* Delete local backup after S3 upload now follows the storage setting
* Exclude patterns and maximum file size are applied during file backup
* S3 retention only lists and deletes Wupz backup objects (wupz-backup-YYYY-MM-DD_HH-MM-SS.zip)
* Documentation links point at the GitHub repository

= 0.0.2 =
* Added system status check functionality
* Improved WordPress coding standards compliance
* Enhanced security with proper input validation and escaping
* Better error handling and logging
* Updated file system operations to use WordPress APIs

= 0.0.1 =
* Initial release
* Manual backup creation
* Scheduled backup functionality
* Database and file backup support
* Basic backup management interface

== Upgrade Notice ==

= 0.0.4 =
Scheduled backups now follow the saved Daily, Weekly, or Disabled setting and no longer run on every page load. After updating, check Wupz → Settings → Backup Schedule. The next automatic backup is one interval after the update, then repeats. If a previous settings save stored Disabled (the old form could show that even while backups were still running), automatic backups stay off until you choose Daily or Weekly. Default exclude patterns now also skip *.log and *.tmp, which the settings screen already listed. S3 cleanup no longer counts or deletes other objects in the bucket.

= 0.0.2 =
This version includes important security improvements and system status monitoring. Please update to ensure optimal security and functionality.

== Support ==

For support, please use the GitHub repository: https://github.com/danielgtmn/Wupz

== Privacy ==

This plugin does not collect or transmit any personal data. All backup operations are performed locally on your server. Email notifications (if enabled) only send backup status information to the site administrator's email address. 