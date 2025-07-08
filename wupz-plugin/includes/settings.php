<?php
/**
 * Wupz Settings Class
 * Handles plugin settings and configuration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Wupz_Settings {
    
    /**
     * Default settings
     */
    private static $defaults = [
        'max_backups' => 5,
        'backup_database' => true,
        'backup_files' => true,
        'backup_schedule' => 'disabled',
        'backup_time' => '02:00',
        'backup_day' => 'sunday',
        'email_notifications' => false,
        'notification_email' => '',
        'max_file_size' => 104857600, // 100MB in bytes
        'exclude_patterns' => [
            'cache/',
            'tmp/',
            'temp/',
            '*.log',
            '*.tmp',
            'wupz-backups/'
        ]
    ];

    private static $storage_defaults = [
        's3_enabled' => false,
        's3_access_key' => '',
        's3_secret_key' => '',
        's3_bucket' => '',
        's3_region' => '',
        's3_endpoint' => '',
        's3_delete_local' => false
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_init', array($this, 'init_storage_settings'));
        add_action('admin_init', array($this, 'handle_force_update_check'));
    }

    /**
     * Handle force update check
     */
    public function handle_force_update_check() {
        if (
            isset($_GET['action']) && 
            $_GET['action'] === 'wupz_check_for_updates' && 
            isset($_GET['_wpnonce']) && 
            wp_verify_nonce(sanitize_key($_GET['_wpnonce']), 'wupz_check_for_updates')
        ) {
            Wupz_Updater::clear_update_transient();
            
            $redirect_url = remove_query_arg(['action', '_wpnonce'], wp_get_referer());
            $redirect_url = add_query_arg('update-checked', 'true', $redirect_url);
            
            wp_safe_redirect($redirect_url);
            exit;
        }
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        // Register settings
        register_setting('wupz_settings_group', 'wupz_settings', array($this, 'sanitize_settings'));
        
        // Add settings sections
        add_settings_section(
            'wupz_general_section',
            __('General Settings', 'wupz'),
            array($this, 'general_section_callback'),
            'wupz_settings'
        );
        
        add_settings_section(
            'wupz_schedule_section',
            __('Schedule Settings', 'wupz'),
            array($this, 'schedule_section_callback'),
            'wupz_settings'
        );
        
        add_settings_section(
            'wupz_advanced_section',
            __('Advanced Settings', 'wupz'),
            array($this, 'advanced_section_callback'),
            'wupz_settings'
        );
        
        // Add settings fields
        $this->add_settings_fields();
    }

    /**
     * Initialize storage settings
     */
    public function init_storage_settings() {
        // Register settings
        register_setting('wupz_storage_settings_group', 'wupz_storage_settings', array($this, 'sanitize_storage_settings'));

        // Add settings sections
        add_settings_section(
            'wupz_s3_section',
            __('S3 Storage Settings', 'wupz'),
            array($this, 's3_section_callback'),
            'wupz_storage_settings'
        );

        // Add settings fields
        $this->add_storage_settings_fields();
    }
    
    /**
     * Add settings fields
     */
    private function add_settings_fields() {
        // General settings
        add_settings_field(
            'max_backups',
            __('Maximum Backups to Keep', 'wupz'),
            array($this, 'max_backups_callback'),
            'wupz_settings',
            'wupz_general_section'
        );
        
        add_settings_field(
            'backup_database',
            __('Backup Database', 'wupz'),
            array($this, 'backup_database_callback'),
            'wupz_settings',
            'wupz_general_section'
        );
        
        add_settings_field(
            'backup_files',
            __('Backup Files', 'wupz'),
            array($this, 'backup_files_callback'),
            'wupz_settings',
            'wupz_general_section'
        );
        
        // Schedule settings
        add_settings_field(
            'backup_schedule',
            __('Backup Schedule', 'wupz'),
            array($this, 'backup_schedule_callback'),
            'wupz_settings',
            'wupz_schedule_section'
        );
        
        add_settings_field(
            'email_notifications',
            __('Email Notifications', 'wupz'),
            array($this, 'email_notifications_callback'),
            'wupz_settings',
            'wupz_schedule_section'
        );
        
        // Advanced settings
        add_settings_field(
            'exclude_patterns',
            __('Exclude File Patterns', 'wupz'),
            array($this, 'exclude_patterns_callback'),
            'wupz_settings',
            'wupz_advanced_section'
        );
    }

    /**
     * Add storage settings fields
     */
    private function add_storage_settings_fields() {
        // S3 settings
        add_settings_field(
            's3_enabled',
            __('Enable S3 Backup', 'wupz'),
            array($this, 's3_enabled_callback'),
            'wupz_storage_settings',
            'wupz_s3_section'
        );

        add_settings_field(
            's3_access_key',
            __('S3 Access Key ID', 'wupz'),
            array($this, 's3_access_key_callback'),
            'wupz_storage_settings',
            'wupz_s3_section'
        );

        add_settings_field(
            's3_secret_key',
            __('S3 Secret Access Key', 'wupz'),
            array($this, 's3_secret_key_callback'),
            'wupz_storage_settings',
            'wupz_s3_section'
        );

        add_settings_field(
            's3_bucket',
            __('S3 Bucket Name', 'wupz'),
            array($this, 's3_bucket_callback'),
            'wupz_storage_settings',
            'wupz_s3_section'
        );

        add_settings_field(
            's3_region',
            __('S3 Region', 'wupz'),
            array($this, 's3_region_callback'),
            'wupz_storage_settings',
            'wupz_s3_section'
        );

        add_settings_field(
            's3_endpoint',
            __('S3 Endpoint', 'wupz'),
            array($this, 's3_endpoint_callback'),
            'wupz_storage_settings',
            'wupz_s3_section'
        );

        add_settings_field(
            's3_delete_local',
            __('Delete Local Backup', 'wupz'),
            array($this, 's3_delete_local_callback'),
            'wupz_storage_settings',
            'wupz_s3_section'
        );
    }
    
    /**
     * General section callback
     */
    public function general_section_callback() {
        echo '<p>' . esc_html__('Configure general backup settings.', 'wupz') . '</p>';
    }
    
    /**
     * Schedule section callback
     */
    public function schedule_section_callback() {
        echo '<p>' . esc_html__('Configure automatic backup scheduling.', 'wupz') . '</p>';
    }
    
    /**
     * Advanced section callback
     */
    public function advanced_section_callback() {
        echo '<p>' . esc_html__('Advanced configuration options.', 'wupz') . '</p>';
    }
    
    /**
     * S3 section callback
     */
    public function s3_section_callback() {
        echo '<p>' . esc_html__('Configure settings for backing up to an S3 bucket.', 'wupz') . '</p>';
    }

    /**
     * Max backups field callback
     */
    public function max_backups_callback() {
        $settings = wp_parse_args(get_option('wupz_settings', array()), self::$defaults);
        $value = $settings['max_backups'];
        
        echo '<input type="number" name="wupz_settings[max_backups]" value="' . esc_attr($value) . '" min="0" max="50" />';
        echo '<p class="description">' . esc_html__('Number of backup files to keep. Set to 0 to keep all backups.', 'wupz') . '</p>';
    }
    
    /**
     * Backup database field callback
     */
    public function backup_database_callback() {
        $settings = wp_parse_args(get_option('wupz_settings', array()), self::$defaults);
        $value = $settings['backup_database'];
        
        echo '<label>';
        echo '<input type="checkbox" name="wupz_settings[backup_database]" value="1" ' . checked(1, $value, false) . ' />';
        echo ' ' . esc_html__('Include database in backups', 'wupz');
        echo '</label>';
    }
    
    /**
     * Backup files field callback
     */
    public function backup_files_callback() {
        $settings = wp_parse_args(get_option('wupz_settings', array()), self::$defaults);
        $value = $settings['backup_files'];
        
        echo '<label>';
        echo '<input type="checkbox" name="wupz_settings[backup_files]" value="1" ' . checked(1, $value, false) . ' />';
        echo ' ' . esc_html__('Include files in backups', 'wupz');
        echo '</label>';
        echo '<p class="description">' . esc_html__('Includes the wp-content directory and its subdirectories.', 'wupz') . '</p>';
    }
    
    /**
     * Schedule interval field callback
     */
    public function backup_schedule_callback() {
        $settings = wp_parse_args(get_option('wupz_settings', array()), self::$defaults);
        $value = $settings['backup_schedule'];
        
        $intervals = array(
            'disabled' => __('Disabled', 'wupz'),
            'daily' => __('Daily', 'wupz'),
            'weekly' => __('Weekly', 'wupz')
        );
        
        echo '<select name="wupz_settings[backup_schedule]">';
        foreach ($intervals as $key => $label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('How often to automatically create backups.', 'wupz') . '</p>';
    }
    
    /**
     * Email notifications field callback
     */
    public function email_notifications_callback() {
        $settings = wp_parse_args(get_option('wupz_settings', array()), self::$defaults);
        $value = $settings['email_notifications'];
        
        echo '<label>';
        echo '<input type="checkbox" name="wupz_settings[email_notifications]" value="1" ' . checked(1, $value, false) . ' />';
        echo ' ' . esc_html__('Send email notifications when scheduled backups fail', 'wupz');
        echo '</label>';
        echo '<p class="description">' . esc_html__('Notifications will be sent to the admin email address.', 'wupz') . '</p>';
    }
    
    /**
     * S3 enabled field callback
     */
    public function s3_enabled_callback() {
        $settings = wp_parse_args(get_option('wupz_storage_settings', array()), self::$storage_defaults);
        $value = $settings['s3_enabled'];
        
        echo '<label>';
        echo '<input type="checkbox" name="wupz_storage_settings[s3_enabled]" value="1" ' . checked(1, $value, false) . ' />';
        echo ' ' . esc_html__('Enable uploading backups to S3', 'wupz');
        echo '</label>';
    }

    /**
     * S3 access key field callback
     */
    public function s3_access_key_callback() {
        $settings = wp_parse_args(get_option('wupz_storage_settings', array()), self::$storage_defaults);
        $value = $settings['s3_access_key'];
        
        echo '<input type="text" name="wupz_storage_settings[s3_access_key]" value="' . esc_attr($value) . '" class="regular-text" />';
    }

    /**
     * S3 secret key field callback
     */
    public function s3_secret_key_callback() {
        $settings = wp_parse_args(get_option('wupz_storage_settings', array()), self::$storage_defaults);
        $value = $settings['s3_secret_key'];
        
        echo '<input type="password" name="wupz_storage_settings[s3_secret_key]" value="' . esc_attr($value) . '" class="regular-text" />';
    }

    /**
     * S3 bucket field callback
     */
    public function s3_bucket_callback() {
        $settings = wp_parse_args(get_option('wupz_storage_settings', array()), self::$storage_defaults);
        $value = $settings['s3_bucket'];
        
        echo '<input type="text" name="wupz_storage_settings[s3_bucket]" value="' . esc_attr($value) . '" class="regular-text" />';
    }

    /**
     * S3 region field callback
     */
    public function s3_region_callback() {
        $settings = wp_parse_args(get_option('wupz_storage_settings', array()), self::$storage_defaults);
        $value = $settings['s3_region'];
        
        echo '<input type="text" name="wupz_storage_settings[s3_region]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('e.g. us-east-1', 'wupz') . '</p>';
    }

    /**
     * S3 endpoint field callback
     */
    public function s3_endpoint_callback() {
        $settings = wp_parse_args(get_option('wupz_storage_settings', array()), self::$storage_defaults);
        $value = $settings['s3_endpoint'];
        
        echo '<input type="url" name="wupz_storage_settings[s3_endpoint]" value="' . esc_attr($value) . '" class="regular-text" placeholder="https://s3.example.com" />';
        echo '<p class="description">' . esc_html__('Optional. Provide the endpoint for S3-compatible services. Leave blank for AWS S3.', 'wupz') . '</p>';
    }

    /**
     * S3 delete local field callback
     */
    public function s3_delete_local_callback() {
        $settings = wp_parse_args(get_option('wupz_storage_settings', array()), self::$storage_defaults);
        $value = $settings['s3_delete_local'];
        
        echo '<label>';
        echo '<input type="checkbox" name="wupz_storage_settings[s3_delete_local]" value="1" ' . checked(1, $value, false) . ' />';
        echo ' ' . esc_html__('Delete local backup file after successful S3 upload', 'wupz');
        echo '</label>';
    }

    /**
     * Exclude files field callback
     */
    public function exclude_patterns_callback() {
        $settings = wp_parse_args(get_option('wupz_settings', array()), self::$defaults);
        $value = implode("\n", $settings['exclude_patterns']);
        
        echo '<textarea name="wupz_settings[exclude_patterns]" rows="5" cols="50" class="large-text code">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . esc_html__('One pattern per line. Use wildcards (*) to match characters.', 'wupz') . '</p>';
    }
    
    /**
     * Sanitize settings
     * 
     * @param array $input Input settings
     * @return array Sanitized settings
     */
    public function sanitize_settings($input) {
        $sanitized_input = array();
        
        // Sanitize max_backups
        if (isset($input['max_backups'])) {
            $sanitized_input['max_backups'] = absint($input['max_backups']);
        }
        
        // Sanitize checkboxes
        $checkboxes = array('backup_database', 'backup_files', 'email_notifications');
        foreach ($checkboxes as $checkbox) {
            $sanitized_input[$checkbox] = isset($input[$checkbox]) ? 1 : 0;
        }
        
        // Sanitize schedule interval
        if (isset($input['backup_schedule'])) {
            $allowed_intervals = array('disabled', 'daily', 'weekly');
            $sanitized_input['backup_schedule'] = in_array($input['backup_schedule'], $allowed_intervals) 
                ? $input['backup_schedule'] 
                : 'weekly';
                
            // Update schedule if changed
            $current_settings = get_option('wupz_settings', array());
            $current_interval = isset($current_settings['backup_schedule']) ? $current_settings['backup_schedule'] : 'weekly';
            
            if ($sanitized_input['backup_schedule'] !== $current_interval) {
                $schedule = new Wupz_Schedule();
                $schedule->update_schedule($sanitized_input['backup_schedule']);
            }
        }
        
        // Sanitize exclude patterns
        if (isset($input['exclude_patterns'])) {
            $patterns = explode("\n", $input['exclude_patterns']);
            $sanitized_input['exclude_patterns'] = array_map('sanitize_text_field', $patterns);
        }
        
        return $sanitized_input;
    }

    /**
     * Sanitize storage settings
     */
    public function sanitize_storage_settings($input) {
        $sanitized_input = array();

        // Sanitize S3 settings
        $sanitized_input['s3_enabled'] = isset($input['s3_enabled']) ? 1 : 0;
        $sanitized_input['s3_access_key'] = sanitize_text_field($input['s3_access_key']);
        $sanitized_input['s3_secret_key'] = sanitize_text_field($input['s3_secret_key']);
        $sanitized_input['s3_bucket'] = sanitize_text_field($input['s3_bucket']);
        $sanitized_input['s3_region'] = sanitize_text_field($input['s3_region']);
        $sanitized_input['s3_endpoint'] = isset($input['s3_endpoint']) ? esc_url_raw($input['s3_endpoint']) : '';
        $sanitized_input['s3_delete_local'] = isset($input['s3_delete_local']) ? 1 : 0;

        return $sanitized_input;
    }
    
    /**
     * Display settings page
     */
    public function display_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'wupz'));
        }
        
        // Handle settings form submission
        if (isset($_POST['submit']) && check_admin_referer('wupz_settings_nonce')) {
            $this->handle_settings_save();
        }
        
        $schedule = new Wupz_Schedule();
        $schedule_info = $schedule->get_schedule_info();
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php if (isset($_GET['settings-updated']) && sanitize_text_field(wp_unslash($_GET['settings-updated']))): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Settings saved successfully.', 'wupz'); ?></p>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['update-checked']) && sanitize_text_field(wp_unslash($_GET['update-checked']))): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Forced update check. If a new version is available, it will appear on the Plugins page.', 'wupz'); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="wupz-settings-wrap">
                <div class="wupz-settings-main">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('wupz_settings_group');
                        do_settings_sections('wupz_settings');
                        submit_button();
                        ?>
                    </form>
                </div>
                
                <div class="wupz-settings-sidebar">
                    <div class="wupz-info-box">
                        <h3><?php esc_html_e('Current Schedule', 'wupz'); ?></h3>
                        <p><strong><?php esc_html_e('Interval:', 'wupz'); ?></strong> <?php echo esc_html(ucfirst($schedule_info['interval'])); ?></p>
                        <p><strong><?php esc_html_e('Next Backup:', 'wupz'); ?></strong> <?php echo esc_html($schedule_info['next_backup_formatted']); ?></p>
                        <?php if ($schedule_info['is_scheduled']): ?>
                            <p class="wupz-status-active"><?php esc_html_e('Automatic backups are active', 'wupz'); ?></p>
                        <?php else: ?>
                            <p class="wupz-status-inactive"><?php esc_html_e('Automatic backups are disabled', 'wupz'); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="wupz-info-box">
                        <h3><?php esc_html_e('Backup Location', 'wupz'); ?></h3>
                        <p><?php echo esc_html(str_replace(ABSPATH, '', WUPZ_BACKUP_DIR)); ?></p>
                        <p class="description"><?php esc_html_e('Backup files are stored in this directory relative to your WordPress root.', 'wupz'); ?></p>
                    </div>
                    
                    <div class="wupz-info-box">
                        <h3><?php esc_html_e('Support', 'wupz'); ?></h3>
                        <p><?php esc_html_e('Need help with Wupz? Check out our documentation or contact support.', 'wupz'); ?></p>
                        <a target="_blank" href="https://docs.wupz.org" class="button button-secondary"><?php esc_html_e('Documentation', 'wupz'); ?></a>
                    </div>

                    <div class="wupz-info-box">
                        <h3><?php esc_html_e('Plugin Updates', 'wupz'); ?></h3>
                        <p><?php esc_html_e('Force WordPress to check for a new version of the plugin.', 'wupz'); ?></p>
                        <?php
                            $check_url = wp_nonce_url(add_query_arg('action', 'wupz_check_for_updates'), 'wupz_check_for_updates');
                        ?>
                        <a href="<?php echo esc_url($check_url); ?>" class="button button-secondary"><?php esc_html_e('Check for Updates', 'wupz'); ?></a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Display storage settings page
     */
    public function display_storage_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Wupz Storage Settings', 'wupz'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('wupz_storage_settings_group');
                do_settings_sections('wupz_storage_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Handle settings save
     */
    private function handle_settings_save() {
        // This is handled by WordPress settings API automatically
        // Additional custom handling can be added here if needed
    }
    
    /**
     * Get all settings
     */
    public static function get_settings() {
        $general_settings = wp_parse_args(get_option('wupz_settings', array()), self::$defaults);
        $storage_settings = wp_parse_args(get_option('wupz_storage_settings', array()), self::$storage_defaults);
        
        return array_merge($general_settings, $storage_settings);
    }
    
    /**
     * Get system status information
     *
     * @return array Status information with indicators
     */
    public function get_system_status() {
        $status = [
            'overall' => 'success', // success, warning, error
            'checks' => []
        ];
        
        // Check WordPress version
        global $wp_version;
        $min_wp_version = '5.0';
        $wp_check = version_compare($wp_version, $min_wp_version, '>=');
        $status['checks']['wordpress'] = [
            'label' => 'WordPress Version',
            'status' => $wp_check ? 'success' : 'error',
            'message' => $wp_check ? 
                sprintf('WordPress %s (✓ %s+)', $wp_version, $min_wp_version) :
                sprintf('WordPress %s (✗ Requires %s+)', $wp_version, $min_wp_version),
            'critical' => true
        ];
        
        // Check PHP version
        $min_php_version = '7.4';
        $php_check = version_compare(PHP_VERSION, $min_php_version, '>=');
        $status['checks']['php'] = [
            'label' => 'PHP Version',
            'status' => $php_check ? 'success' : 'error',
            'message' => $php_check ?
                sprintf('PHP %s (✓ %s+)', PHP_VERSION, $min_php_version) :
                sprintf('PHP %s (✗ Requires %s+)', PHP_VERSION, $min_php_version),
            'critical' => true
        ];
        
        // Check ZipArchive extension
        $zip_check = class_exists('ZipArchive');
        $status['checks']['ziparchive'] = [
            'label' => 'ZipArchive Extension',
            'status' => $zip_check ? 'success' : 'error',
            'message' => $zip_check ? 'Available ✓' : 'Not available ✗',
            'critical' => true
        ];
        
        // Check backup directory
        $backup_dir = WP_CONTENT_DIR . '/wupz-backups/';
        $dir_exists = is_dir($backup_dir);
        
        // Use WP_Filesystem to check writability
        global $wp_filesystem;
        if (!$wp_filesystem) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
        
        $dir_writable = $dir_exists && $wp_filesystem->is_writable($backup_dir);
        $status['checks']['backup_directory'] = [
            'label' => 'Backup Directory',
            'status' => $dir_writable ? 'success' : ($dir_exists ? 'warning' : 'error'),
            'message' => $dir_writable ? 
                'Writable ✓' : 
                ($dir_exists ? 'Exists but not writable ⚠' : 'Does not exist ✗'),
            'critical' => true
        ];
        
        // Check disk space
        $free_space = disk_free_space(WP_CONTENT_DIR);
        $free_space_gb = $free_space / (1024 * 1024 * 1024);
        $space_status = $free_space_gb > 1 ? 'success' : ($free_space_gb > 0.5 ? 'warning' : 'error');
        $status['checks']['disk_space'] = [
            'label' => 'Available Disk Space',
            'status' => $space_status,
            'message' => sprintf('%.2f GB available', $free_space_gb),
            'critical' => false
        ];
        
        // Check memory limit
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        $memory_limit_mb = $memory_limit / (1024 * 1024);
        $memory_status = $memory_limit_mb >= 256 ? 'success' : ($memory_limit_mb >= 128 ? 'warning' : 'error');
        $status['checks']['memory_limit'] = [
            'label' => 'PHP Memory Limit',
            'status' => $memory_status,
            'message' => sprintf('%d MB %s', $memory_limit_mb, 
                $memory_limit_mb >= 256 ? '✓' : ($memory_limit_mb >= 128 ? '⚠' : '✗')),
            'critical' => false
        ];
        
        // Check execution time limit
        $max_execution_time = ini_get('max_execution_time');
        $time_status = $max_execution_time == 0 || $max_execution_time >= 120 ? 'success' : 
                      ($max_execution_time >= 60 ? 'warning' : 'error');
        $status['checks']['execution_time'] = [
            'label' => 'Max Execution Time',
            'status' => $time_status,
            'message' => $max_execution_time == 0 ? 
                'Unlimited ✓' : 
                sprintf('%d seconds %s', $max_execution_time,
                    $max_execution_time >= 120 ? '✓' : ($max_execution_time >= 60 ? '⚠' : '✗')),
            'critical' => false
        ];
        
        // Check WordPress cron
        $cron_disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
        $status['checks']['wordpress_cron'] = [
            'label' => 'WordPress Cron',
            'status' => $cron_disabled ? 'warning' : 'success',
            'message' => $cron_disabled ? 'Disabled ⚠' : 'Enabled ✓',
            'critical' => false
        ];
        
        // Determine overall status
        $critical_errors = 0;
        $warnings = 0;
        
        foreach ($status['checks'] as $check) {
            if ($check['status'] === 'error' && $check['critical']) {
                $critical_errors++;
            } elseif ($check['status'] === 'warning' || ($check['status'] === 'error' && !$check['critical'])) {
                $warnings++;
            }
        }
        
        if ($critical_errors > 0) {
            $status['overall'] = 'error';
        } elseif ($warnings > 0) {
            $status['overall'] = 'warning';
        }
        
        return $status;
    }
    
    /**
     * Get status indicator emoji
     *
     * @param string $status Status level
     * @return string Emoji indicator
     */
    public function get_status_indicator($status) {
        switch ($status) {
            case 'success':
                return '🟢';
            case 'warning':
                return '🟡';
            case 'error':
                return '🔴';
            default:
                return '⚪';
        }
    }
    
    /**
     * Get status message
     *
     * @param string $status Status level
     * @return string Status message
     */
    public function get_status_message($status) {
        switch ($status) {
            case 'success':
                return __('All systems operational', 'wupz');
            case 'warning':
                return __('Warnings present', 'wupz');
            case 'error':
                return __('Critical issues detected', 'wupz');
            default:
                return __('Status unknown', 'wupz');
        }
    }
} 