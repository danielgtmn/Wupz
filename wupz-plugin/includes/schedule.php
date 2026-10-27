<?php
/**
 * Wupz Schedule Class
 * Handles scheduled backups using WordPress cron
 */

if (!defined('ABSPATH')) {
    exit;
}

class Wupz_Schedule {

    const HOOK = 'wupz_scheduled_backup';

    /**
     * Intervals the settings screen and quick schedule can store.
     */
    const ALLOWED_INTERVALS = array('disabled', 'daily', 'weekly');

    /**
     * Avoid registering cron callbacks twice when another screen
     * constructs a second scheduler in the same request.
     */
    private static $initialized = false;

    /**
     * Constructor
     */
    public function __construct() {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;

        add_action(self::HOOK, array($this, 'run_scheduled_backup'));
        add_filter('cron_schedules', array($this, 'add_custom_cron_intervals'));
    }

    /**
     * Add custom cron intervals
     *
     * @param array $schedules Existing schedules
     * @return array Modified schedules
     */
    public function add_custom_cron_intervals($schedules) {
        $schedules['wupz_daily'] = array(
            'interval' => defined('DAY_IN_SECONDS') ? DAY_IN_SECONDS : 86400,
            'display' => __('Daily (Wupz)', 'wupz')
        );

        $schedules['wupz_weekly'] = array(
            'interval' => defined('WEEK_IN_SECONDS') ? WEEK_IN_SECONDS : 604800,
            'display' => __('Weekly (Wupz)', 'wupz')
        );

        return $schedules;
    }

    /**
     * Keep the cron event aligned with the saved schedule.
     *
     * Runs on init, before wp_cron. A matching future event is left alone.
     * Versions before 0.0.4 cleared and recreated the event with time() on
     * every request, so the first load after that upgrade drops the overdue
     * event and schedules a single future run.
     */
    public function ensure_schedule() {
        $this->migrate_schedule_setting();

        $stored_version = get_option('wupz_plugin_version', false);
        $reset_overdue_event = ($stored_version === false || $stored_version === '' || version_compare((string) $stored_version, '0.0.4', '<'));

        if ($reset_overdue_event) {
            wp_clear_scheduled_hook(self::HOOK);
        }

        $settings = get_option('wupz_settings', array());
        $this->apply_schedule(self::resolve_interval(is_array($settings) ? $settings : array()));

        if ((string) $stored_version !== WUPZ_PLUGIN_VERSION) {
            update_option('wupz_plugin_version', WUPZ_PLUGIN_VERSION);
        }
    }

    /**
     * Schedule backup cron job.
     *
     * Kept for callers that used the old init callback. It no longer
     * rebuilds the event on every call.
     */
    public function schedule_backup_cron() {
        $this->ensure_schedule();
    }

    /**
     * Create, replace, or remove the backup event so it matches $interval.
     *
     * Disabled unschedules the hook. An event that already uses this
     * interval (including WordPress core daily/weekly) is not touched, so
     * its next run stays where it is.
     *
     * @param string $interval disabled, daily, or weekly
     * @return bool True when the cron state matches the interval
     */
    public function apply_schedule($interval) {
        if (!self::is_allowed_interval($interval)) {
            $interval = 'weekly';
        }

        if ($interval === 'disabled') {
            if (wp_next_scheduled(self::HOOK)) {
                wp_clear_scheduled_hook(self::HOOK);
            }
            return !wp_next_scheduled(self::HOOK);
        }

        $recurrence = 'wupz_' . $interval;
        $schedules = wp_get_schedules();
        if (!isset($schedules[$recurrence]['interval'])) {
            return false;
        }

        $current = $this->get_event_recurrence(self::HOOK);
        if ($current !== null && self::recurrence_matches_interval($current, $interval)) {
            return true;
        }

        wp_clear_scheduled_hook(self::HOOK);

        $first_run = time() + (int) $schedules[$recurrence]['interval'];
        $scheduled = wp_schedule_event($first_run, $recurrence, self::HOOK);

        return $scheduled !== false && !is_wp_error($scheduled);
    }

    /**
     * Interval that should actually run.
     *
     * backup_schedule is the settings-form value. schedule_interval is the
     * legacy key written by activation and older quick-schedule saves.
     * When the form value is missing, the legacy key is used so existing
     * installs keep the interval they were already on (activation default
     * is weekly).
     *
     * @param array $settings wupz_settings option
     * @return string
     */
    public static function resolve_interval($settings) {
        if (!is_array($settings)) {
            $settings = array();
        }

        if (isset($settings['backup_schedule']) && self::is_allowed_interval($settings['backup_schedule'])) {
            return $settings['backup_schedule'];
        }

        if (isset($settings['schedule_interval']) && self::is_allowed_interval($settings['schedule_interval'])) {
            return $settings['schedule_interval'];
        }

        return 'weekly';
    }

    /**
     * Copy the effective interval onto both option keys.
     */
    public function migrate_schedule_setting() {
        $settings = get_option('wupz_settings', null);
        if (!is_array($settings)) {
            return;
        }

        $resolved = self::resolve_interval($settings);
        $dirty = false;

        if (!isset($settings['backup_schedule']) || $settings['backup_schedule'] !== $resolved) {
            $settings['backup_schedule'] = $resolved;
            $dirty = true;
        }

        if (!isset($settings['schedule_interval']) || $settings['schedule_interval'] !== $resolved) {
            $settings['schedule_interval'] = $resolved;
            $dirty = true;
        }

        if ($dirty) {
            Wupz_Settings::update_settings_raw($settings);
        }
    }

    /**
     * @param string $interval
     * @return bool
     */
    public static function is_allowed_interval($interval) {
        return is_string($interval) && in_array($interval, self::ALLOWED_INTERVALS, true);
    }

    /**
     * Core daily/weekly events are the same length as the Wupz ones.
     * Activation used to register the core name before custom schedules
     * existed for that request, so treat them as already correct.
     *
     * @param string $recurrence
     * @param string $interval
     * @return bool
     */
    private static function recurrence_matches_interval($recurrence, $interval) {
        $equivalent = array(
            'daily' => array('daily', 'wupz_daily'),
            'weekly' => array('weekly', 'wupz_weekly'),
        );

        return isset($equivalent[$interval]) && in_array($recurrence, $equivalent[$interval], true);
    }

    /**
     * Recurrence name of the next scheduled event, or null when absent.
     *
     * @param string $hook
     * @return string|null
     */
    private function get_event_recurrence($hook) {
        if (function_exists('wp_get_scheduled_event')) {
            $event = wp_get_scheduled_event($hook);
            if (!$event) {
                return null;
            }
            return !empty($event->schedule) ? $event->schedule : '';
        }

        $next = wp_next_scheduled($hook);
        if (!$next) {
            return null;
        }

        if (!function_exists('_get_cron_array')) {
            return '';
        }

        $crons = _get_cron_array();
        if (!is_array($crons) || empty($crons[$next][$hook]) || !is_array($crons[$next][$hook])) {
            return '';
        }

        $first = reset($crons[$next][$hook]);
        if (is_array($first) && !empty($first['schedule'])) {
            return $first['schedule'];
        }

        return '';
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
        return wp_next_scheduled(self::HOOK);
    }

    /**
     * Get backup schedule information
     *
     * @return array Schedule information
     */
    public function get_schedule_info() {
        $settings = get_option('wupz_settings', array());
        $schedule_interval = self::resolve_interval(is_array($settings) ? $settings : array());
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
        if (!self::is_allowed_interval($interval)) {
            return;
        }

        $settings = get_option('wupz_settings', array());
        if (!is_array($settings)) {
            $settings = array();
        }

        $settings['backup_schedule'] = $interval;
        $settings['schedule_interval'] = $interval;
        Wupz_Settings::update_settings_raw($settings);

        $this->apply_schedule($interval);
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
