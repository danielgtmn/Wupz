# Usage Guide

Learn how to use Wupz effectively to backup and protect your WordPress website.

## �� Dashboard Overview

Access the main dashboard via **WordPress Admin → Wupz**

### Dashboard Components
- **📊 Backup Statistics**: Overview of backup status
- **🚀 Create Backup Button**: Start manual backup process
- **📋 Recent Backups List**: View and manage existing backups
- **⚡ Quick Actions**: Download, delete, and restore options

## 🛠️ Creating Manual Backups

### Step-by-Step Process

1. **Navigate to Dashboard**
   - Go to **Wupz → Dashboard**
   - Review current backup statistics

2. **Start Backup Creation**
   - Click **"Create Backup Now"** button
   - Backup process begins immediately

3. **Monitor Progress**
   - Progress bar shows backup completion status
   - Real-time updates on current operation
   - Estimated time remaining (for large sites)

4. **Backup Completion**
   - Success message displayed
   - New backup appears in backup list
   - Email notification sent (if configured)

### What Happens During Backup

```
1. 🔍 Initializing backup process
2. 📁 Creating backup directory structure
3. 🗄️ Exporting database to SQL format
4. 📦 Compressing wp-content files to ZIP
5. 🔗 Combining database and files
6. ✅ Finalizing backup archive
7. 🧹 Cleaning up temporary files
8. 📧 Sending notifications (if enabled)
```

## 📋 Managing Backups

### Backup List Interface

Each backup entry shows:
- **📅 Creation Date**: When backup was created
- **📦 File Size**: Total size of backup archive
- **🏷️ Backup Type**: Manual or Scheduled
- **⚙️ Actions**: Download, Delete, View Details

### Available Actions

#### Download Backup
1. Click **Download** button next to desired backup
2. ZIP file downloads to your computer
3. Contains complete site backup (database + files)

#### Delete Backup
1. Click **Delete** button next to backup
2. Confirm deletion in popup dialog
3. Backup file permanently removed from server

#### View Backup Details
- Click backup filename or **Details** button
- Shows detailed information:
  - Backup components included
  - Individual file sizes
  - Creation time and duration
  - Success/error status

## 🕒 Scheduled Backups

### Setting Up Automatic Backups

1. **Configure Schedule**
   - Go to **Wupz → Settings → Schedule**
   - Choose frequency: Daily or Weekly
   - Set preferred time and day

2. **Enable Notifications**
   - Configure email settings
   - Choose notification types
   - Test email delivery

3. **Monitor Scheduled Backups**
   - Check **Wupz → Dashboard** regularly
   - Review automated backup entries
   - Watch for failure notifications

### Scheduled Backup Behavior

- **WordPress Cron**: Uses WordPress built-in cron system
- **Background Processing**: Runs without user interaction
- **Automatic Cleanup**: Removes old backups per retention settings
- **Error Handling**: Notifies on failures, retries on temporary issues

## 📧 Understanding Notifications

### Email Notification Types

#### Success Notifications
```
Subject: ✅ Wupz Backup Completed Successfully
Content:
- Backup creation time
- Backup file size
- Components included
- Download link (if applicable)
```

#### Failure Notifications
```
Subject: ❌ Wupz Backup Failed
Content:
- Error description
- Troubleshooting suggestions
- Contact information
- System requirements check
```

#### Cleanup Notifications
```
Subject: 🗑️ Wupz Backup Cleanup
Content:
- Number of backups removed
- Current backup count
- Storage space freed
```

## 🔍 Backup Content Details

### Database Backup
- **Format**: SQL dump file
- **Content**: All WordPress tables with your site prefix
- **Structure**: Complete table structure and data
- **Compatibility**: Standard MySQL/MariaDB format

### File Backup
- **Location**: Complete wp-content directory
- **Includes**:
  - All themes and plugins
  - Media uploads
  - Custom files and folders
- **Excludes**:
  - Cache directories
  - Temporary files
  - Log files (configurable)

## 📥 Backup Restoration Process

### Manual Restoration Steps

1. **Download Backup**
   - Get backup ZIP file from Wupz dashboard
   - Extract ZIP file on your computer

2. **Database Restoration**
   ```sql
   -- Via phpMyAdmin or command line
   mysql -u username -p database_name < backup_database.sql
   ```

3. **File Restoration**
   ```bash
   # Extract and upload wp-content files
   unzip backup_files.zip
   rsync -av wp-content/ /path/to/wordpress/wp-content/
   ```

4. **Verify Restoration**
   - Check website functionality
   - Test admin access
   - Verify recent content

### Automated Restoration (Future Feature)
- One-click restoration from dashboard
- Database restoration with safety checks
- File restoration with backup verification
- Rollback capabilities

## 📊 Monitoring and Maintenance

### Regular Monitoring Tasks

#### Weekly Checks
- ✅ Verify recent backups completed successfully
- ✅ Check backup file sizes for consistency
- ✅ Review email notifications
- ✅ Test download of latest backup

#### Monthly Maintenance
- 🔍 Review backup retention settings
- 🧹 Clean up very old backup files manually
- 📊 Analyze backup storage usage
- ⚙️ Update plugin to latest version

### Performance Monitoring

#### Backup Duration Tracking
- Monitor how long backups take to complete
- Watch for increasing backup times
- Adjust settings if backups take too long

#### Storage Usage Analysis
```
Dashboard shows:
- Total backup storage used
- Average backup size
- Storage usage trend
- Available disk space
```

## 🚨 Troubleshooting Common Issues

### Backup Fails to Start
- Check PHP memory limits
- Verify file permissions
- Ensure adequate disk space
- Review error logs

### Backup Incomplete
- Increase PHP execution time limits
- Check for file permission issues
- Review exclusion settings
- Monitor server resources

### Download Issues
- Clear browser cache
- Check backup file exists on server
- Verify file permissions
- Try alternative download method

## 💡 Best Practices

### Backup Strategy
1. **Regular Schedule**: Set up automatic daily or weekly backups
2. **Before Updates**: Always backup before plugin/theme updates
3. **Before Changes**: Backup before major content changes
4. **Off-site Storage**: Download important backups to external storage

### Security Considerations
1. **Secure Storage**: Keep backups in secure, protected directory
2. **Regular Testing**: Periodically test backup restoration
3. **Access Control**: Limit backup access to administrators only
4. **External Copies**: Store critical backups off-server

### Performance Optimization
1. **Optimal Timing**: Schedule backups during low-traffic periods
2. **Resource Management**: Monitor server resources during backup
3. **Exclusion Tuning**: Exclude unnecessary files to reduce backup size
4. **Retention Management**: Keep appropriate number of backups

---

**Next Steps**: [Scheduled Backups](Scheduled-Backups.md) | [Troubleshooting](Troubleshooting.md)

*Need immediate help? Check the [Troubleshooting](Troubleshooting.md) page for quick answers.*
