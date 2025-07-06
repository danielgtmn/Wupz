=== Wupz Backup ===
Contributors: danielgietmann
Tags: backup, database, files, scheduled, restore
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.0.2
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
* PHP 7.4 or higher
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

Yes! You can set up daily or weekly automatic backups in the plugin settings. The plugin uses WordPress cron functionality for scheduling.

= What gets included in backups? =

By default, backups include your complete WordPress database and all files in the wp-content directory (themes, plugins, uploads, etc.). You can configure what to include in the settings.

= How do I restore a backup? =

Currently, the plugin focuses on creating backups. For restoration, you can download the backup files and restore them manually through your hosting control panel or contact your hosting provider.

= Can I exclude certain files from backups? =

Yes, you can specify file patterns to exclude in the plugin settings (e.g., cache files, temporary files, logs).

= Will this work with my hosting provider? =

The plugin is designed to work with most standard WordPress hosting environments. It requires basic file system permissions and the ZipArchive PHP extension.

== Screenshots ==

1. Main backup dashboard showing system status and backup controls
2. Backup list with download and delete options
3. Settings page for configuring backup preferences
4. System status check showing environment compatibility

== Changelog ==

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

= 0.0.2 =
This version includes important security improvements and system status monitoring. Please update to ensure optimal security and functionality.

== Support ==

For support and detailed documentation, please visit our official documentation site: https://docs.wups.org

== Privacy ==

This plugin does not collect or transmit any personal data. All backup operations are performed locally on your server. Email notifications (if enabled) only send backup status information to the site administrator's email address. 