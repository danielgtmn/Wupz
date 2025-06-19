# Installation Guide

This guide will walk you through installing Wupz on your WordPress website.

## 📋 Requirements

Before installing Wupz, ensure your server meets these requirements:

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **PHP Extensions**: ZipArchive extension enabled
- **Disk Space**: Sufficient space for backup files
- **Permissions**: File write permissions for `/wp-content/`

## 🔍 Checking Requirements

### PHP Version
```php
<?php
echo 'PHP Version: ' . phpversion();
?>
```

### ZipArchive Extension
```php
<?php
if (class_exists('ZipArchive')) {
    echo 'ZipArchive is available';
} else {
    echo 'ZipArchive is NOT available';
}
?>
```

## 📥 Download Methods

### Method 1: GitHub Releases (Recommended)

1. Go to [Wupz Releases](https://github.com/danielgtmn/wupz/releases)
2. Download the latest `wupz-x.x.x.zip` file
3. The ZIP file contains the complete, ready-to-install plugin

### Method 2: Clone Repository (Developers)

```bash
git clone https://github.com/danielgtmn/wupz.git
cd wupz
```

## 🚀 Installation Methods

### Method 1: WordPress Admin Upload (Recommended)

1. **Access WordPress Admin**
   - Login to your WordPress dashboard
   - Navigate to **Plugins → Add New**

2. **Upload Plugin**
   - Click **Upload Plugin** button
   - Choose the downloaded `wupz-x.x.x.zip` file
   - Click **Install Now**

3. **Activate Plugin**
   - Click **Activate Plugin** after installation
   - Or go to **Plugins → Installed Plugins** and activate "Wupz"

### Method 2: FTP/SFTP Upload

1. **Extract ZIP File**
   - Extract the downloaded ZIP file on your computer
   - You should see a `wupz` folder

2. **Upload via FTP**
   ```
   Upload the 'wupz' folder to: /wp-content/plugins/
   ```

3. **Set Permissions**
   ```bash
   chmod 755 /wp-content/plugins/wupz
   chmod 644 /wp-content/plugins/wupz/*.php
   ```

4. **Activate Plugin**
   - Go to WordPress Admin → Plugins
   - Find "Wupz" and click "Activate"

### Method 3: WP-CLI Installation

```bash
# Download and install
wp plugin install https://github.com/danielgtmn/wupz/releases/latest/download/wupz.zip

# Activate
wp plugin activate wupz
```

## ✅ Verify Installation

After installation, verify everything is working:

1. **Check Plugin List**
   - Go to **Plugins → Installed Plugins**
   - Ensure "Wupz" appears and is active

2. **Access Plugin Dashboard**
   - Look for **Wupz** in your WordPress admin menu
   - Click it to access the backup dashboard

3. **Check Backup Directory**
   - Navigate to `/wp-content/wupz-backups/`
   - A `.htaccess` file should be created automatically

## 🔧 Post-Installation Setup

### 1. Initial Configuration
- Go to **Wupz → Settings**
- Configure your backup preferences
- Set backup retention limits
- Enable/disable components (database, files)

### 2. Test Backup Creation
- Go to **Wupz → Dashboard**
- Click **"Create Backup Now"**
- Verify the backup completes successfully

### 3. Schedule Setup (Optional)
- Configure automatic backup schedules
- Set up email notifications
- Test scheduled backup functionality

## 🚨 Troubleshooting Installation

### Plugin Won't Activate
- Check PHP version compatibility
- Verify ZipArchive extension is enabled
- Check for plugin conflicts

### Missing Admin Menu
- Clear browser cache
- Check user permissions (must be Administrator)
- Deactivate and reactivate the plugin

### Permission Errors
```bash
# Fix file permissions
find /wp-content/plugins/wupz -type f -exec chmod 644 {} \;
find /wp-content/plugins/wupz -type d -exec chmod 755 {} \;
```

### Memory Issues
Add to `wp-config.php`:
```php
ini_set('memory_limit', '512M');
define('WP_MAX_MEMORY_LIMIT', '512M');
```

## 🔄 Updating the Plugin

### Automatic Updates (Future)
- Updates will be available through WordPress admin when submitted to repository

### Manual Updates
1. Deactivate current version
2. Delete old plugin files
3. Upload new version
4. Activate updated plugin
5. Verify settings are preserved

---

**Next Steps**: [Configuration Guide](Configuration)

*Need help? Check the [Troubleshooting](Troubleshooting) page.* 