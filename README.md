# Wupz - WordPress Backup Plugin

A comprehensive WordPress backup solution that allows you to create, schedule, and manage backups of your WordPress website with ease.

## ✨ Features

- **Manual Backups**: Create instant backups with a single click
- **Scheduled Backups**: Automatic daily or weekly backups using WordPress cron
- **Complete Site Backup**: Backs up both database and files
- **ZIP Archive Format**: All backups are compressed as ZIP files for easy storage
- **Download & Delete**: Easy management of backup files
- **Backup Retention**: Automatically keeps a specified number of backups (default: 5)
- **Progress Tracking**: Real-time backup progress indicator
- **Email Notifications**: Get notified when scheduled backups fail
- **Clean Interface**: Modern, responsive admin interface
- **Security**: Protected backup directory with .htaccess

## 📋 Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- ZipArchive PHP extension
- Sufficient disk space for backups
- File write permissions

## 🚀 Installation

1. **Download the Plugin**
   ```bash
   git clone https://github.com/yourusername/wupz.git
   ```

2. **Upload to WordPress**
   - Upload the `wupz` folder to `/wp-content/plugins/`
   - Or zip the folder and upload via WordPress admin

3. **Activate the Plugin**
   - Go to WordPress Admin → Plugins
   - Find "Wupz" and click "Activate"

4. **Access the Plugin**
   - Go to WordPress Admin → Wupz
   - Configure your backup settings

## ⚙️ Configuration

### General Settings

- **Maximum Backups to Keep**: Set how many backup files to retain (default: 5)
- **Backup Database**: Include database in backups (recommended)
- **Backup Files**: Include wp-content directory in backups

### Schedule Settings

- **Backup Schedule**: Choose between Daily, Weekly, or Disabled
- **Email Notifications**: Get notified when scheduled backups fail

### Advanced Settings

- **Exclude File Patterns**: Specify files/folders to exclude from backups

## 🎯 Usage

### Creating Manual Backups

1. Navigate to **Wupz → Dashboard**
2. Click the **"Create Backup Now"** button
3. Wait for the backup to complete
4. Download or manage your backup from the backup list

### Scheduling Automatic Backups

1. Go to **Wupz → Settings**
2. Set your preferred **Backup Schedule**
3. Configure **Email Notifications** if desired
4. Save your settings

### Managing Backups

- **Download**: Click the download button next to any backup
- **Delete**: Remove old or unwanted backups
- **View Details**: See backup size, creation date, and status

## 📁 What Gets Backed Up

### Database
- All WordPress tables with your site's prefix
- Complete database structure and data
- Exported as SQL format

### Files
- Complete `wp-content` directory
- Themes, plugins, uploads, and custom files
- Excludes cache and temporary files by default

### Exclusions
- `wupz-backups` directory (prevents recursive backups)
- Cache directories (`cache`, `tmp`, `temp`)
- Files larger than 100MB (configurable)
- Log files and temporary files (configurable via settings)

## 🗂️ File Structure

```
wupz/
├── wupz.php                 # Main plugin file
├── includes/
│   ├── backup.php          # Backup functionality
│   ├── schedule.php        # Cron scheduling
│   └── settings.php        # Settings management
├── templates/
│   └── admin-page.php      # Admin interface template
├── assets/
│   ├── style.css           # Admin styles
│   └── script.js           # Admin JavaScript
└── README.md               # This file
```

## 🔧 Technical Details

### Backup Process

1. **Initialization**: Creates secure backup directory with .htaccess protection
2. **Database Export**: Exports all WordPress tables to SQL format
3. **File Compression**: Adds wp-content directory to ZIP archive
4. **Cleanup**: Removes temporary files and old backups
5. **Notification**: Updates backup status and sends notifications if configured

### Security Features

- **Nonce Verification**: All AJAX requests are protected with WordPress nonces
- **Capability Checks**: Only administrators can access backup functions
- **Secure Directory**: Backup directory is protected from direct web access
- **File Validation**: Backup filenames are validated for security

### Performance Considerations

- **Memory Management**: Large files are skipped to prevent memory issues
- **Progress Tracking**: Backup progress is tracked and displayed
- **Background Processing**: Uses WordPress cron for scheduled backups
- **Cleanup**: Automatic cleanup of old backups to manage disk space

## 🛠️ Development

### Extending the Plugin

The plugin is built with extensibility in mind:

```php
// Hook into backup completion
add_action('wupz_backup_completed', 'my_backup_handler');

// Filter backup settings
add_filter('wupz_backup_settings', 'my_settings_filter');

// Customize excluded patterns
add_filter('wupz_exclude_patterns', 'my_exclude_patterns');
```

### Custom Backup Handlers

```php
// Create custom backup
$backup = new Wupz_Backup();
$result = $backup->create_backup();

if ($result['success']) {
    echo 'Backup created: ' . $result['filename'];
} else {
    echo 'Backup failed: ' . $result['message'];
}
```

## 📝 Changelog

### Version 1.0.0
- Initial release
- Manual backup creation
- Scheduled backups (daily/weekly)
- Database and file backup
- ZIP compression
- Backup management interface
- Settings page
- Email notifications

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

### Common Issues

**Backup fails with memory error**
- Increase PHP memory limit in wp-config.php: `ini_set('memory_limit', '512M');`
- Exclude large files via settings

**Backup takes too long**
- Large sites may take several minutes
- Consider excluding unnecessary files
- Check with your hosting provider for timeout limits

**Scheduled backups not working**
- Ensure WordPress cron is functioning
- Check if your hosting provider blocks cron jobs
- Test with WP-Cron Control plugin

**Cannot download backups**
- Check file permissions on backup directory
- Ensure backup files exist and are not corrupted
- Verify server has sufficient resources

### Getting Help

- Check the WordPress.org plugin support forum
- Submit issues on GitHub
- Contact support via email

## 🔮 Roadmap

- Cloud storage integration (Dropbox, Google Drive, Amazon S3)
- Backup restoration functionality
- Advanced scheduling options
- Backup encryption
- Multi-site support
- Import/export settings

---

**Made with ❤️ for the WordPress community** 