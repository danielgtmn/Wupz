# Troubleshooting

Solutions for common issues you might encounter while using Wupz.

## 🚨 Quick Diagnostic Tools

### Plugin Status Check
1. Go to **Wupz → Dashboard**
2. Look for status indicators:
   - 🟢 All systems operational
   - 🟡 Warnings present
   - 🔴 Critical issues detected

### System Requirements
Check if your server meets requirements:
- **WordPress**: 5.0+ ✅
- **PHP**: 7.4+ ✅
- **ZipArchive**: Available ✅
- **Disk Space**: Sufficient ✅
- **Memory**: Adequate ✅

## 🔧 Installation Issues

### Plugin Won't Activate

#### Error: "Plugin file does not exist"
**Cause**: Incomplete upload or corrupted files

**Solution**:
```bash
# Check plugin directory exists
ls -la /wp-content/plugins/wupz/

# Verify main plugin file
ls -la /wp-content/plugins/wupz/wupz.php

# Re-upload if missing
```

#### Error: "The plugin does not have a valid header"
**Cause**: Corrupted main plugin file

**Solution**:
1. Download fresh copy from releases
2. Re-upload via WordPress admin
3. Verify file integrity after upload

#### Error: "Fatal error: Cannot redeclare"
**Cause**: Plugin conflict or duplicate installation

**Solution**:
```php
// Check for duplicate installations
find /wp-content/plugins/ -name "*wupz*" -type d

// Remove duplicates, keep only one wupz folder
```

### Missing Admin Menu

#### Menu doesn't appear after activation
**Cause**: User permission or caching issues

**Solutions**:
1. **Check User Role**: Must be Administrator
2. **Clear Cache**: Clear all caching plugins
3. **Browser Cache**: Hard refresh (Ctrl+F5)
4. **Reactivate Plugin**: Deactivate and reactivate

## 💾 Backup Creation Issues

### Backup Fails to Start

#### Error: "Backup failed to initialize"
**Symptoms**: Backup never begins, immediate failure

**Diagnostic Steps**:
```php
// Check backup directory permissions
ls -la /wp-content/wupz-backups/

// Should show drwxr-xr-x (755) permissions
// If directory missing, check write permissions on wp-content
```

**Solutions**:
1. **Fix Permissions**:
   ```bash
   chmod 755 /wp-content/wupz-backups/
   chown www-data:www-data /wp-content/wupz-backups/
   ```

2. **Create Directory Manually**:
   ```bash
   mkdir /wp-content/wupz-backups/
   chmod 755 /wp-content/wupz-backups/
   ```

### Memory Limit Errors

#### Error: "Fatal error: Allowed memory size exhausted"
**Cause**: PHP memory limit too low for backup size

**Solutions**:

1. **Increase Memory in wp-config.php**:
   ```php
   ini_set('memory_limit', '512M');
   define('WP_MAX_MEMORY_LIMIT', '512M');
   ```

2. **Server-level PHP.ini**:
   ```ini
   memory_limit = 512M
   ```

3. **Hosting Provider**: Contact support to increase limits

4. **Reduce Backup Size**:
   - Enable file exclusions
   - Set maximum file size limits
   - Backup database only for testing

### Execution Time Limits

#### Error: "Maximum execution time exceeded"
**Cause**: Backup takes longer than PHP allows

**Solutions**:

1. **Increase Time Limit**:
   ```php
   // In wp-config.php
   ini_set('max_execution_time', 300); // 5 minutes
   
   // In .htaccess
   php_value max_execution_time 300
   ```

2. **Server Configuration**:
   ```ini
   ; In php.ini
   max_execution_time = 300
   ```

3. **Backup Optimization**:
   - Exclude large files
   - Use database-only backups for testing
   - Run backups during low-traffic periods

### Disk Space Issues

#### Error: "Insufficient disk space"
**Symptoms**: Backup fails partway through

**Diagnostic**:
```bash
# Check available disk space
df -h /wp-content/

# Check backup directory size
du -sh /wp-content/wupz-backups/
```

**Solutions**:
1. **Free Up Space**: Delete old backups manually
2. **Reduce Retention**: Lower "max backups to keep" setting
3. **Exclude Large Files**: Add exclusion patterns for large files
4. **Upgrade Hosting**: Contact provider about storage limits

## 🗂️ File Permission Issues

### Cannot Create Backup Directory

#### Error: "Permission denied creating backup directory"
**Cause**: WordPress can't write to wp-content

**Diagnostic**:
```bash
# Check wp-content permissions
ls -la /wp-content/

# Should be writable by web server
```

**Solutions**:
```bash
# Fix wp-content permissions
chmod 755 /wp-content/
chown www-data:www-data /wp-content/

# Create backup directory with correct permissions
mkdir /wp-content/wupz-backups/
chmod 755 /wp-content/wupz-backups/
chown www-data:www-data /wp-content/wupz-backups/
```

### Cannot Write Backup Files

#### Error: "Failed to write backup file"
**Cause**: Insufficient permissions on backup directory

**Solution**:
```bash
# Fix backup directory permissions
chmod 755 /wp-content/wupz-backups/
chown -R www-data:www-data /wp-content/wupz-backups/

# Verify permissions
ls -la /wp-content/wupz-backups/
```

## 📦 ZIP Archive Issues

### ZipArchive Extension Missing

#### Error: "ZipArchive class not found"
**Cause**: PHP ZipArchive extension not installed

**Check Extension**:
```php
<?php
if (class_exists('ZipArchive')) {
    echo 'ZipArchive is available';
} else {
    echo 'ZipArchive is NOT available';
}
?>
```

**Solutions**:
1. **Contact Hosting Provider**: Request ZipArchive extension
2. **Install on VPS/Dedicated**:
   ```bash
   # Ubuntu/Debian
   sudo apt-get install php-zip
   
   # CentOS/RHEL
   sudo yum install php-zip
   ```

### Corrupted ZIP Files

#### Error: "Backup file appears corrupted"
**Symptoms**: Cannot extract backup ZIP file

**Diagnostic**:
```bash
# Test ZIP file integrity
unzip -t backup-file.zip

# Check file size
ls -lh backup-file.zip
```

**Solutions**:
1. **Retry Backup**: Create new backup
2. **Check Disk Space**: Ensure sufficient space during creation
3. **Memory Limits**: Increase PHP memory if needed
4. **Exclude Large Files**: Reduce backup size

## 🕐 Scheduled Backup Issues

### Scheduled Backups Not Running

#### Symptoms: No automatic backups appearing
**Cause**: WordPress cron not functioning

**Diagnostic**:
```bash
# Check if WP_CRON is disabled
grep -r "DISABLE_WP_CRON" /path/to/wordpress/
```

**Solutions**:
1. **Enable WordPress Cron**:
   ```php
   // Remove or comment out in wp-config.php
   // define('DISABLE_WP_CRON', true);
   ```

2. **Test Cron Functionality**:
   - Install "WP Cron Control" plugin
   - Check cron event list
   - Test cron execution

3. **Server-Level Cron**:
   ```bash
   # Add to crontab
   0 2 * * * /usr/bin/php /path/to/wordpress/wp-cron.php
   ```

### Irregular Backup Times

#### Symptoms: Backups running at random times
**Cause**: WordPress cron depends on site traffic

**Solutions**:
1. **Ensure Regular Traffic**: Cron needs visitors to trigger
2. **Server Cron**: Use server-level cron for precise timing
3. **Monitoring**: Use external uptime monitors to trigger cron

## 📧 Email Notification Issues

### Emails Not Sending

#### No notifications received
**Cause**: WordPress mail function issues

**Diagnostic**:
```php
// Test WordPress mail function
wp_mail('test@example.com', 'Test Subject', 'Test message');
```

**Solutions**:
1. **SMTP Plugin**: Install WP Mail SMTP plugin
2. **Server Configuration**: Check server mail settings
3. **Hosting Provider**: Verify email sending is enabled
4. **Alternative Email**: Try different notification email address

### Emails Going to Spam

#### Notifications end up in spam folder
**Cause**: Poor server reputation or configuration

**Solutions**:
1. **SMTP Authentication**: Use authenticated SMTP
2. **SPF Records**: Configure DNS SPF records
3. **Alternative Service**: Use email service like SendGrid
4. **Whitelist Sender**: Add to email whitelist

## 🔍 Database Backup Issues

### Database Export Fails

#### Error: "Failed to export database"
**Cause**: Database connection or permission issues

**Diagnostic**:
```php
// Test database connection
$connection = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
if (!$connection) {
    echo 'Database connection failed';
}
```

**Solutions**:
1. **Database Permissions**: Ensure user has export privileges
2. **Connection Limits**: Check max connections setting
3. **Large Tables**: May need increased timeouts for large databases
4. **Alternative Method**: Use phpMyAdmin for manual export

### Large Database Issues

#### Timeouts with large databases
**Symptoms**: Backup fails on database export step

**Solutions**:
1. **Increase Limits**:
   ```php
   ini_set('max_execution_time', 600); // 10 minutes
   ini_set('memory_limit', '1024M');   // 1GB
   ```

2. **Optimize Database**: Remove spam, revisions, unused data
3. **Selective Backup**: Exclude non-essential tables
4. **Professional Tools**: Consider enterprise backup solutions

## 🌐 Download Issues

### Cannot Download Backup Files

#### Error: "Download failed" or file not found
**Cause**: File permissions or security restrictions

**Solutions**:
1. **Check File Exists**:
   ```bash
   ls -la /wp-content/wupz-backups/
   ```

2. **Fix Permissions**:
   ```bash
   chmod 644 /wp-content/wupz-backups/*.zip
   ```

3. **Browser Issues**: Try different browser or clear cache
4. **Security Plugins**: Temporarily disable security plugins

### Slow Download Speeds

#### Downloads are very slow
**Cause**: Server configuration or file size

**Solutions**:
1. **File Size**: Check if backup is unusually large
2. **Server Resources**: Download during low-traffic periods
3. **Alternative Method**: Use FTP/SFTP to download directly
4. **Compression**: Ensure proper ZIP compression

## 📊 Performance Issues

### Backup Process is Very Slow

#### Backups take extremely long time
**Symptoms**: Progress bar moves very slowly

**Diagnostic Steps**:
1. **Check File Count**: Large numbers of small files slow process
2. **Monitor Resources**: Check CPU and memory usage
3. **Analyze Content**: Identify large files or directories

**Solutions**:
1. **Exclude Directories**:
   ```
   cache/
   uploads/large-files/
   *.mp4
   *.avi
   ```

2. **Optimize WordPress**:
   - Clean up uploads directory
   - Remove unnecessary plugins
   - Optimize database

3. **Server Optimization**:
   - Increase PHP limits
   - Use SSD storage
   - Optimize server configuration

## 🛠️ Advanced Debugging

### Enable Debug Logging

Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check logs at: `/wp-content/debug.log`

### Plugin Debug Mode

Access detailed error information:
1. Go to **Wupz → Settings**
2. Enable "Debug Mode" (if available)
3. Check detailed error messages in dashboard

### Server Error Logs

Check server error logs:
```bash
# Common log locations
tail -f /var/log/apache2/error.log
tail -f /var/log/nginx/error.log
tail -f /var/log/php/error.log
```

## 📞 Getting Additional Help

### Before Contacting Support

Gather this information:
- WordPress version
- PHP version
- Plugin version
- Error messages (exact text)
- Server configuration details
- Steps to reproduce issue

### Support Channels

1. **GitHub Issues**: [Create new issue](https://github.com/danielgtmn/wupz/issues)
2. **Documentation**: Check other wiki pages
3. **WordPress Forums**: Search for similar issues
4. **Hosting Support**: For server-related issues

### Providing Debug Information

When requesting help, include:
```
WordPress Version: 6.4.1
PHP Version: 8.1.10
Wupz Version: 1.0.0
Error Message: [Exact error text]
Server: [Hosting provider name]
Attempted Solutions: [What you've tried]
```

---

**Related Pages**: [Installation](Installation) | [Configuration](Configuration) 

*Still need help? Create an issue on [GitHub Issues](https://github.com/danielgtmn/wupz/issues).* 