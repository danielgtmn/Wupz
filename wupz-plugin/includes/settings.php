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
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'init_settings'));
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
            'schedule_interval',
            __('Backup Schedule', 'wupz'),
            array($this, 'schedule_interval_callback'),
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
            'exclude_files',
            __('Exclude File Patterns', 'wupz'),
            array($this, 'exclude_files_callback'),
            'wupz_settings',
            'wupz_advanced_section'
        );
    }
    
    /**
     * General section callback
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure general backup settings.', 'wupz') . '</p>';
    }
    
    /**
     * Schedule section callback
     */
    public function schedule_section_callback() {
        echo '<p>' . __('Configure automatic backup scheduling.', 'wupz') . '</p>';
    }
    
    /**
     * Advanced section callback
     */
    public function advanced_section_callback() {
        echo '<p>' . __('Advanced configuration options.', 'wupz') . '</p>';
    }
    
    /**
     * Max backups field callback
     */
    public function max_backups_callback() {
        $settings = get_option('wupz_settings', array());
        $value = isset($settings['max_backups']) ? $settings['max_backups'] : 5;
        
        echo '<input type="number" name="wupz_settings[max_backups]" value="' . esc_attr($value) . '" min="0" max="50" />';
        echo '<p class="description">' . __('Number of backup files to keep. Set to 0 to keep all backups.', 'wupz') . '</p>';
    }
    
    /**
     * Backup database field callback
     */
    public function backup_database_callback() {
        $settings = get_option('wupz_settings', array());
        $value = isset($settings['backup_database']) ? $settings['backup_database'] : 1;
        
        echo '<label>';
        echo '<input type="checkbox" name="wupz_settings[backup_database]" value="1" ' . checked(1, $value, false) . ' />';
        echo ' ' . __('Include database in backups', 'wupz');
        echo '</label>';
    }
    
    /**
     * Backup files field callback
     */
    public function backup_files_callback() {
        $settings = get_option('wupz_settings', array());
        $value = isset($settings['backup_files']) ? $settings['backup_files'] : 1;
        
        echo '<label>';
        echo '<input type="checkbox" name="wupz_settings[backup_files]" value="1" ' . checked(1, $value, false) . ' />';
        echo ' ' . __('Include files in backups', 'wupz');
        echo '</label>';
        echo '<p class="description">' . __('Includes the wp-content directory and its subdirectories.', 'wupz') . '</p>';
    }
    
    /**
     * Schedule interval field callback
     */
    public function schedule_interval_callback() {
        $settings = get_option('wupz_settings', array());
        $value = isset($settings['schedule_interval']) ? $settings['schedule_interval'] : 'weekly';
        
        $intervals = array(
            'disabled' => __('Disabled', 'wupz'),
            'daily' => __('Daily', 'wupz'),
            'weekly' => __('Weekly', 'wupz')
        );
        
        echo '<select name="wupz_settings[schedule_interval]">';
        foreach ($intervals as $key => $label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('How often to automatically create backups.', 'wupz') . '</p>';
    }
    
    /**
     * Email notifications field callback
     */
    public function email_notifications_callback() {
        $settings = get_option('wupz_settings', array());
        $value = isset($settings['email_notifications']) ? $settings['email_notifications'] : 0;
        
        echo '<label>';
        echo '<input type="checkbox" name="wupz_settings[email_notifications]" value="1" ' . checked(1, $value, false) . ' />';
        echo ' ' . __('Send email notifications when scheduled backups fail', 'wupz');
        echo '</label>';
        echo '<p class="description">' . __('Notifications will be sent to the admin email address.', 'wupz') . '</p>';
    }
    
    /**
     * Exclude files field callback
     */
    public function exclude_files_callback() {
        $settings = get_option('wupz_settings', array());
        $value = isset($settings['exclude_files']) ? $settings['exclude_files'] : "*.log\n*.tmp\ncache/*\ntmp/*";
        
        echo '<textarea name="wupz_settings[exclude_files]" rows="5" cols="50" class="regular-text">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . __('File patterns to exclude from backups, one per line. Use * as wildcard.', 'wupz') . '</p>';
    }
    
    /**
     * Sanitize settings
     * 
     * @param array $input Input settings
     * @return array Sanitized settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Sanitize max_backups
        if (isset($input['max_backups'])) {
            $sanitized['max_backups'] = absint($input['max_backups']);
        }
        
        // Sanitize checkboxes
        $checkboxes = array('backup_database', 'backup_files', 'email_notifications');
        foreach ($checkboxes as $checkbox) {
            $sanitized[$checkbox] = isset($input[$checkbox]) ? 1 : 0;
        }
        
        // Sanitize schedule interval
        if (isset($input['schedule_interval'])) {
            $allowed_intervals = array('disabled', 'daily', 'weekly');
            $sanitized['schedule_interval'] = in_array($input['schedule_interval'], $allowed_intervals) 
                ? $input['schedule_interval'] 
                : 'weekly';
                
            // Update schedule if changed
            $current_settings = get_option('wupz_settings', array());
            $current_interval = isset($current_settings['schedule_interval']) ? $current_settings['schedule_interval'] : 'weekly';
            
            if ($sanitized['schedule_interval'] !== $current_interval) {
                $schedule = new Wupz_Schedule();
                $schedule->update_schedule($sanitized['schedule_interval']);
            }
        }
        
        // Sanitize exclude files
        if (isset($input['exclude_files'])) {
            $sanitized['exclude_files'] = sanitize_textarea_field($input['exclude_files']);
        }
        
        return $sanitized;
    }
    
    /**
     * Display settings page
     */
    public function display_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wupz'));
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
            
            <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated']): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Settings saved successfully.', 'wupz'); ?></p>
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
                        <h3><?php _e('Current Schedule', 'wupz'); ?></h3>
                        <p><strong><?php _e('Interval:', 'wupz'); ?></strong> <?php echo esc_html(ucfirst($schedule_info['interval'])); ?></p>
                        <p><strong><?php _e('Next Backup:', 'wupz'); ?></strong> <?php echo esc_html($schedule_info['next_backup_formatted']); ?></p>
                        <?php if ($schedule_info['is_scheduled']): ?>
                            <p class="wupz-status-active"><?php _e('Automatic backups are active', 'wupz'); ?></p>
                        <?php else: ?>
                            <p class="wupz-status-inactive"><?php _e('Automatic backups are disabled', 'wupz'); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="wupz-info-box">
                        <h3><?php _e('Backup Location', 'wupz'); ?></h3>
                        <p><?php echo esc_html(str_replace(ABSPATH, '', WUPZ_BACKUP_DIR)); ?></p>
                        <p class="description"><?php _e('Backup files are stored in this directory relative to your WordPress root.', 'wupz'); ?></p>
                    </div>
                    
                    <div class="wupz-info-box">
                        <h3><?php _e('Support', 'wupz'); ?></h3>
                        <p><?php _e('Need help with Wupz? Check out our documentation or contact support.', 'wupz'); ?></p>
                        <a href="#" class="button button-secondary"><?php _e('Documentation', 'wupz'); ?></a>
                    </div>
                </div>
            </div>
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
     * Get plugin settings
     * 
     * @return array Plugin settings
     */
    public static function get_settings() {
        return get_option('wupz_settings', array(
            'schedule_interval' => 'weekly',
            'max_backups' => 5,
            'backup_database' => 1,
            'backup_files' => 1,
            'email_notifications' => 0,
            'exclude_files' => "*.log\n*.tmp\ncache/*\ntmp/*"
        ));
    }
} 