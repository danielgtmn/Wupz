# Scheduled Backups

Set up automated backups to protect your WordPress site without manual intervention.

## 🕐 Overview

Scheduled backups run automatically using WordPress's built-in cron system, ensuring your site is backed up regularly without requiring manual action.

## ⚙️ Setting Up Scheduled Backups

### 1. Access Schedule Settings
- Navigate to **Wupz → Settings**
- Click the **Schedule** tab
- Configure your backup automation preferences

### 2. Choose Backup Frequency

#### Daily Backups
- **Best for**: Active sites with frequent content updates
- **Frequency**: Every 24 hours
- **Storage**: Requires more disk space
- **Recommended**: Most WordPress sites

#### Weekly Backups
- **Best for**: Static sites or blogs with infrequent updates
- **Frequency**: Once per week
- **Storage**: Lower storage requirements
- **Recommended**: Low-maintenance sites

#### Disabled
- **Use case**: Manual backups only
- **When to choose**: Development sites or when using external backup solutions

### 3. Set Backup Timing

#### Daily Backup Time
```
Recommended times (server time):
- 2:00 AM - Lowest traffic period
- 3:00 AM - After most maintenance windows
- 4:00 AM - Before early morning traffic
```

#### Weekly Backup Day
```
Recommended days:
- Sunday - End of week, before Monday updates
- Saturday - Low business activity
- Monday - Start of week, after weekend changes
```

### 4. Configure Notifications
- **Email Address**: Where to send backup status emails
- **Success Notifications**: Get notified when backups complete
- **Failure Notifications**: Critical alerts for failed backups
- **Cleanup Notifications**: Updates when old backups are removed

## 🔧 WordPress Cron System

### How WordPress Cron Works
- **Trigger**: Activated when users visit your website
- **Background**: Runs scheduled tasks behind the scenes
- **Non-blocking**: Doesn't slow down user experience
- **Reliable**: Built into WordPress core functionality

### Cron Dependencies
```php
// WordPress cron requirements:
1. Website receives regular traffic
2. WP_CRON not disabled in wp-config.php
3. Server allows background processing
4. No external cron interference
```

### Verifying Cron Functionality
Check if WordPress cron is working:

1. **Using WP-CLI**
   ```bash
   wp cron event list
   wp cron test
   ```

2. **Plugin Tools**
   - Install "WP Cron Control" plugin
   - View scheduled events
   - Test cron execution

3. **Manual Testing**
   - Check if other scheduled WordPress features work
   - Verify post scheduling functions
   - Review update check timing

## 📊 Monitoring Scheduled Backups

### Dashboard Indicators

#### Backup Status
- **🟢 Active**: Scheduled backups enabled and working
- **🟡 Pending**: Next backup scheduled but not yet run
- **🔴 Failed**: Last scheduled backup encountered errors
- **⚪ Disabled**: Scheduled backups turned off

#### Recent Activity
```
Dashboard shows:
- Last backup completion time
- Next scheduled backup time
- Recent backup success rate
- Storage usage trends
```

### Email Monitoring

#### Success Email Example
```
Subject: ✅ Scheduled Backup Completed - YourSite.com

Backup Details:
- Created: 2024-01-15 02:00:15
- Type: Scheduled (Daily)
- Size: 45.2 MB
- Components: Database + Files
- Status: Completed successfully

Next backup scheduled: 2024-01-16 02:00:00
```

#### Failure Email Example
```
Subject: ❌ Scheduled Backup Failed - YourSite.com

Error Details:
- Attempted: 2024-01-15 02:00:00
- Error: Insufficient disk space
- Components: Database + Files
- Retry: Will attempt again in 24 hours

Action Required: Check server disk space
```

## 🛠️ Troubleshooting Scheduled Backups

### Common Issues

#### Backups Not Running
**Symptoms**: No new scheduled backups appearing

**Possible Causes**:
- WordPress cron disabled
- Low website traffic
- Server configuration issues
- Plugin conflicts

**Solutions**:
```php
// Check wp-config.php for:
define('DISABLE_WP_CRON', true); // Should be false or commented out

// Enable cron if disabled:
define('DISABLE_WP_CRON', false);
```

#### Inconsistent Backup Times
**Symptoms**: Backups running at random times

**Causes**:
- Cron depends on website traffic
- Server timezone configuration
- Hosting provider limitations

**Solutions**:
- Use server-level cron instead of WordPress cron
- Configure external monitoring
- Contact hosting provider

#### Failed Scheduled Backups
**Symptoms**: Email notifications about backup failures

**Common Causes**:
- Insufficient disk space
- Memory limit exceeded
- Execution time timeout
- File permission issues

**Diagnostic Steps**:
1. Check error logs
2. Verify server resources
3. Test manual backup creation
4. Review exclusion settings

## 🔄 Server-Level Cron (Advanced)

### When to Use Server Cron
- High-traffic sites requiring precise timing
- Sites with disabled WordPress cron
- Enhanced reliability requirements
- External monitoring integration

### Setting Up Server Cron

#### 1. Disable WordPress Cron
Add to `wp-config.php`:
```php
define('DISABLE_WP_CRON', true);
```

#### 2. Create Cron Entry
```bash
# Edit crontab
crontab -e

# Add daily backup at 2 AM
0 2 * * * /usr/bin/php /path/to/wordpress/wp-cron.php
```

#### 3. WordPress Integration
```php
// Trigger Wupz backup via cron
0 2 * * * /usr/bin/php -c /path/to/php.ini /path/to/wordpress/wp-cron.php
```

### Cron Job Examples

#### Daily Backups
```bash
# Every day at 2 AM
0 2 * * * /usr/bin/php /path/to/wp-cron.php

# Every day at 3:30 AM with logging
30 3 * * * /usr/bin/php /path/to/wp-cron.php >> /var/log/wp-cron.log 2>&1
```

#### Weekly Backups
```bash
# Every Sunday at 2 AM
0 2 * * 0 /usr/bin/php /path/to/wp-cron.php

# Every Saturday at midnight
0 0 * * 6 /usr/bin/php /path/to/wp-cron.php
```

## 📈 Optimization Strategies

### Backup Timing Optimization

#### Traffic Analysis
```
Steps to find optimal backup time:
1. Review website analytics
2. Identify lowest traffic periods
3. Consider maintenance windows
4. Test different backup times
5. Monitor server performance
```

#### Resource Management
- **Memory Usage**: Monitor PHP memory during backups
- **CPU Load**: Check server load during backup windows
- **Disk I/O**: Ensure backup doesn't impact site performance
- **Network**: Consider bandwidth usage for large backups

### Retention Strategy

#### Automated Cleanup
```php
Retention examples:
- Daily backups: Keep 7 days (1 week)
- Weekly backups: Keep 4 weeks (1 month)
- Monthly backups: Keep 12 months (1 year)
```

#### Storage Optimization
- Monitor total backup storage usage
- Adjust retention based on available space
- Consider off-site backup storage
- Implement backup rotation strategies

## 📋 Scheduled Backup Checklist

### Weekly Monitoring
- [ ] Check last backup completion status
- [ ] Verify backup file sizes are consistent
- [ ] Review any error notifications
- [ ] Confirm next backup is scheduled

### Monthly Review
- [ ] Analyze backup success rate
- [ ] Review storage usage trends
- [ ] Test backup restoration process
- [ ] Update backup retention settings if needed

### Quarterly Maintenance
- [ ] Review and update backup schedule
- [ ] Test email notification system
- [ ] Verify cron job functionality
- [ ] Update plugin to latest version

## 🎯 Best Practices

### Scheduling Strategy
1. **Consistent Timing**: Use same time daily for predictability
2. **Low-Impact Windows**: Schedule during low-traffic periods
3. **Buffer Time**: Allow enough time before peak traffic
4. **Overlap Avoidance**: Don't conflict with other scheduled tasks

### Monitoring & Alerting
1. **Email Notifications**: Always enable failure notifications
2. **Regular Testing**: Manually verify scheduled backups work
3. **External Monitoring**: Use uptime monitors to verify cron
4. **Log Review**: Check server logs for cron-related issues

### Backup Validation
1. **Size Consistency**: Monitor backup file sizes for anomalies
2. **Content Verification**: Periodically test backup restoration
3. **Integrity Checks**: Verify ZIP file structure
4. **Component Validation**: Ensure database and files are included

---

**Next Steps**: [Troubleshooting](Troubleshooting) | [FAQ](FAQ)

*Need help with cron setup? Check the [Troubleshooting](Troubleshooting) page.* 