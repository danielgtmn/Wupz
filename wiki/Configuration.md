# Configuration Guide

Learn how to configure Wupz to meet your specific backup needs.

## 🎛️ Accessing Settings

1. Login to WordPress Admin
2. Navigate to **Wupz → Settings**
3. Configure options across different tabs

## ⚙️ General Settings

### Backup Retention
- **Maximum Backups to Keep**: Set how many backup files to retain
  - Default: `5`
  - Recommended: `3-10` (depending on available disk space)
  - When limit is reached, oldest backups are automatically deleted

### Backup Components
- **☑️ Backup Database**: Include WordPress database in backups
  - Highly recommended for complete site restoration
  - Includes all posts, pages, settings, users, etc.

- **☑️ Backup Files**: Include wp-content directory
  - Contains themes, plugins, uploads, and custom files
  - Can be large - monitor disk space usage

### File Size Limits
- **Maximum File Size**: Skip files larger than specified size
  - Default: `100 MB`
  - Prevents memory issues with very large files
  - Adjust based on your server's capabilities

## 📅 Schedule Settings

### Backup Frequency
Choose your automated backup schedule:

- **🔴 Disabled**: No automatic backups
- **🟡 Daily**: Backup every day at specified time
- **🟢 Weekly**: Backup once per week on specified day

### Backup Time
- **Hour**: Set the time when daily backups run
  - Default: `2:00 AM` (server time)
  - Choose low-traffic hours to minimize impact
  - Consider your hosting provider's backup windows

### Day of Week (Weekly backups)
- **Day**: Choose which day for weekly backups
  - Default: `Sunday`
  - Consider your content update schedule

## 📧 Email Notifications

### Notification Settings
- **☑️ Enable Email Notifications**: Get notified about backup events
- **Email Address**: Where to send notifications
  - Default: WordPress admin email
  - Can specify multiple emails (comma-separated)

### Notification Types
- **✅ Backup Success**: Notify when backups complete successfully
- **❌ Backup Failure**: Notify when backups fail
- **🗑️ Cleanup**: Notify when old backups are deleted

## 🚫 Exclusion Settings

### File Patterns to Exclude
Specify files and folders to skip during backup:

#### Default Exclusions
```
cache/
tmp/
temp/
*.log
*.tmp
wupz-backups/
```

#### Custom Exclusion Examples
```
# Exclude specific plugin data
wp-content/plugins/cache-plugin/cache/

# Exclude large media files
*.mp4
*.avi
*.mov

# Exclude development files
node_modules/
.git/
.env
```

### Directory Exclusions
- **Cache Directories**: Automatically excluded
- **Backup Directory**: `wupz-backups/` is always excluded
- **Temporary Files**: System temp files skipped

## 🔐 Security Settings

### Backup Directory Protection
- **☑️ Create .htaccess**: Protect backup directory from web access
  - Automatically creates security rules
  - Prevents direct download of backup files
  - Recommended: Keep enabled

### File Validation
- **☑️ Validate Backup Files**: Check backup integrity
  - Verifies ZIP file structure
  - Ensures backups are not corrupted
  - Minimal performance impact

## 💾 Storage Settings

### Backup Location
- **Directory**: Where backups are stored
  - Default: `/wp-content/wupz-backups/`
  - Must be writable by WordPress
  - Should be outside document root for security

### Disk Space Management
- **Monitor Usage**: Keep track of backup storage usage
- **Free Space Check**: Ensures sufficient space before backup
- **Cleanup Strategy**: Automatic removal of old backups

## 📊 Performance Settings

### Memory Management
- **Memory Limit**: PHP memory limit for backup operations
  - Increase if backups fail with memory errors
  - Add to wp-config.php: `ini_set('memory_limit', '512M');`

### Execution Time
- **Time Limit**: Maximum execution time for backups
  - Large sites may need longer time limits
  - Configure via php.ini or wp-config.php

### Batch Processing
- **Chunk Size**: Process files in smaller batches
  - Reduces memory usage
  - Prevents timeouts on large sites
  - May increase total backup time

## 🧪 Testing Configuration

### Test Backup Creation
1. Save your settings
2. Go to **Wupz → Dashboard**
3. Click **"Create Backup Now"**
4. Monitor progress and check for errors

### Verify Scheduled Backups
1. Set schedule to "Daily"
2. Use WordPress cron testing tools
3. Check backup creation at scheduled time
4. Review email notifications

### Validate Backup Quality
1. Download a test backup
2. Extract ZIP file locally
3. Verify database SQL file is present
4. Check that expected files are included

## 🔧 Advanced Configuration

### WordPress Constants
Add to `wp-config.php` for advanced control:

```php
// Increase memory for backups
ini_set('memory_limit', '512M');

// Extend execution time
ini_set('max_execution_time', 300);

// Custom backup directory
define('WUPZ_BACKUP_DIR', '/custom/backup/path/');

// Disable email notifications
define('WUPZ_DISABLE_EMAILS', true);
```

### Hooks and Filters
For developers - customize behavior:

```php
// Modify backup settings
add_filter('wupz_backup_settings', 'custom_backup_settings');

// Custom exclusion patterns
add_filter('wupz_exclude_patterns', 'custom_exclusions');

// Backup completion hook
add_action('wupz_backup_completed', 'custom_backup_handler');
```

## 📝 Configuration Examples

### Small Blog Setup
```
- Max Backups: 3
- Schedule: Weekly (Sunday, 2 AM)
- Components: Database + Files
- Notifications: Failures only
- Exclusions: Default
```

### Large Site Setup
```
- Max Backups: 5
- Schedule: Daily (3 AM)
- Components: Database + Files
- Max File Size: 50 MB
- Notifications: All events
- Exclusions: Cache, logs, large media
```

### Development Site
```
- Max Backups: 2
- Schedule: Disabled
- Components: Database only
- Notifications: Disabled
- Exclusions: node_modules, .git
```

---

**Next Steps**: [Usage Guide](Usage)

*Need help with configuration? Check the [Troubleshooting](Troubleshooting) page.* 