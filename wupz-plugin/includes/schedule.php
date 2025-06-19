<?php
/**
 * Wupz Schedule Class
 * Handles scheduled backups using WordPress cron
 */

if (!defined('ABSPATH')) {
    exit;
}

class Wupz_Schedule {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wupz_scheduled_backup', array($this, 'run_scheduled_backup'));
        add_action('init', array($this, 'schedule_backup_cron'));
        add_filter('cron_schedules', array($this, 'add_custom_cron_intervals'));
    }
    
    /**
     * Add custom cron intervals
     * 
     * @param array $schedules Existing schedules
     * @return array Modified schedules
     */
    public function add_custom_cron_intervals($schedules) {
        // Add custom intervals
        $schedules['wupz_daily'] = array(
            'interval' => 24 * 60 * 60, // 24 hours
            'display' => __('Daily (Wupz)', 'wupz')
        );
        
        $schedules['wupz_weekly'] = array(
            'interval' => 7 * 24 * 60 * 60, // 7 days
            'display' => __('Weekly (Wupz)', 'wupz')
        );
        
        return $schedules;
    }
    
    /**
     * Schedule backup cron job
     */
    public function schedule_backup_cron() {
        $settings = get_option('wupz_settings', array());
        $schedule_interval = isset($settings['schedule_interval']) ? $settings['schedule_interval'] : 'weekly';
        
        // Clear existing scheduled event
        wp_clear_scheduled_hook('wupz_scheduled_backup');
        
        // Schedule new event if auto-backup is enabled
        if ($schedule_interval && $schedule_interval !== 'disabled') {
            $cron_schedule = 'wupz_' . $schedule_interval;
            
            // Make sure the schedule exists
            $schedules = wp_get_schedules();
            if (isset($schedules[$cron_schedule])) {
                wp_schedule_event(time(), $cron_schedule, 'wupz_scheduled_backup');
            }
        }
    }
    
    /**
     * Run scheduled backup
     */
    public function run_scheduled_backup() {
        $backup = new Wupz_Backup();
        $result = $backup->create_backup();
        
        // Update last backup status
        $last_backup_info = array(
            'timestamp' => time(),
            'status' => $result['success'] ? 'completed' : 'failed',
            'type' => 'scheduled'
        );
        
        if ($result['success']) {
            $last_backup_info['filename'] = $result['filename'];
            $last_backup_info['size'] = $result['size'];
        } else {
            $last_backup_info['error'] = $result['message'];
            
            // Log error for debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Only logs in debug mode
                error_log('Wupz Scheduled Backup Failed: ' . $result['message']);
            }
            
            // Send email notification to admin if backup fails
            $this->send_backup_failure_notification($result['message']);
        }
        
        update_option('wupz_last_backup', $last_backup_info);
    }
    
    /**
     * Send backup failure notification email
     * 
     * @param string $error_message Error message
     */
    private function send_backup_failure_notification($error_message) {
        $settings = get_option('wupz_settings', array());
        
        // Check if email notifications are enabled
        if (!isset($settings['email_notifications']) || !$settings['email_notifications']) {
            return;
        }
        
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        $site_url = get_site_url();
        
        /* translators: %s: Site name */
        $subject = sprintf(__('[%s] Backup Failed', 'wupz'), $site_name);
        
        /* translators: %1$s: Site URL, %2$s: Error message, %3$s: Admin URL */
        $message = sprintf(
            __("Hello,\n\nThe scheduled backup for your WordPress site %1\$s has failed.\n\nError message: %2\$s\n\nPlease check your Wupz backup settings and try running a manual backup.\n\nYou can access the Wupz backup plugin at: %3\$s\n\nRegards,\nWupz Backup Plugin", 'wupz'),
            $site_url,
            $error_message,
            admin_url('admin.php?page=wupz')
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Get next scheduled backup time
     * 
     * @return int|false Timestamp of next backup or false if not scheduled
     */
    public function get_next_scheduled_backup() {
        return wp_next_scheduled('wupz_scheduled_backup');
    }
    
    /**
     * Get backup schedule information
     * 
     * @return array Schedule information
     */
    public function get_schedule_info() {
        $settings = get_option('wupz_settings', array());
        $schedule_interval = isset($settings['schedule_interval']) ? $settings['schedule_interval'] : 'weekly';
        $next_backup = $this->get_next_scheduled_backup();
        
        return array(
            'interval' => $schedule_interval,
            'next_backup' => $next_backup,
            'next_backup_formatted' => $next_backup ? wp_date('Y-m-d H:i:s', $next_backup) : __('Not scheduled', 'wupz'),
            'is_scheduled' => $next_backup !== false
        );
    }
    
    /**
     * Update backup schedule
     * 
     * @param string $interval Schedule interval (daily, weekly, or disabled)
     */
    public function update_schedule($interval) {
        $settings = get_option('wupz_settings', array());
        $settings['schedule_interval'] = $interval;
        update_option('wupz_settings', $settings);
        
        // Reschedule cron job
        $this->schedule_backup_cron();
    }
    
    /**
     * Check if backup is currently running
     * 
     * @return bool True if backup is running
     */
    public function is_backup_running() {
        $lock_file = WUPZ_BACKUP_DIR . '.backup_lock';
        
        if (file_exists($lock_file)) {
            $lock_time = filemtime($lock_file);
            
            // If lock file is older than 30 minutes, consider it stale
            if (time() - $lock_time > 1800) {
                wp_delete_file($lock_file);
                return false;
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Create backup lock file
     */
    public function create_backup_lock() {
        $lock_file = WUPZ_BACKUP_DIR . '.backup_lock';
        
        // Use WP_Filesystem for file operations
        global $wp_filesystem;
        if (!$wp_filesystem) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
        
        $wp_filesystem->put_contents($lock_file, current_time('mysql'));
    }
    
    /**
     * Remove backup lock file
     */
    public function remove_backup_lock() {
        $lock_file = WUPZ_BACKUP_DIR . '.backup_lock';
        if (file_exists($lock_file)) {
            wp_delete_file($lock_file);
        }
    }
} 