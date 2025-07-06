<?php
/**
 * Plugin Name: Wupz Backup
 * Plugin URI: https://wupz.org
 * Description: A comprehensive WordPress backup solution that allows manual and scheduled backups of your database and files.
 * Version: 0.0.2
 * Author: Daniel Gietmann
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wupz
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WUPZ_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WUPZ_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WUPZ_PLUGIN_VERSION', '1.0.0');
define('WUPZ_BACKUP_DIR', WP_CONTENT_DIR . '/wupz-backups/');

/**
 * Main Wupz Plugin Class
 */
class Wupz {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('wupz', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        
        // Include required files
        $this->include_files();
        
        // Initialize components
        if (is_admin()) {
            new Wupz_Settings();
            new Wupz_Updater(__FILE__);
        }
        
        new Wupz_Schedule();
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Enqueue admin styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Handle AJAX requests
        add_action('wp_ajax_wupz_manual_backup', array($this, 'handle_manual_backup'));
        add_action('wp_ajax_wupz_download_backup', array($this, 'handle_download_backup'));
        add_action('wp_ajax_wupz_delete_backup', array($this, 'handle_delete_backup'));
        add_action('wp_ajax_wupz_update_schedule', array($this, 'handle_update_schedule'));
        add_action('wp_ajax_wupz_check_backup_status', array($this, 'handle_check_backup_status'));
    }
    
    /**
     * Include required files
     */
    private function include_files() {
        require_once WUPZ_PLUGIN_PATH . 'includes/backup.php';
        require_once WUPZ_PLUGIN_PATH . 'includes/schedule.php';
        require_once WUPZ_PLUGIN_PATH . 'includes/settings.php';
        require_once WUPZ_PLUGIN_PATH . 'includes/updater.php';
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Wupz Backup', 'wupz'),
            __('Wupz', 'wupz'),
            'manage_options',
            'wupz',
            array($this, 'admin_page'),
            'dashicons-backup',
            30
        );
        
        add_submenu_page(
            'wupz',
            __('Settings', 'wupz'),
            __('Settings', 'wupz'),
            'manage_options',
            'wupz-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'wupz') !== false) {
            wp_enqueue_style('wupz-admin', WUPZ_PLUGIN_URL . 'assets/style.css', array(), WUPZ_PLUGIN_VERSION);
            wp_enqueue_script('wupz-admin', WUPZ_PLUGIN_URL . 'assets/script.js', array('jquery'), WUPZ_PLUGIN_VERSION, true);
            wp_localize_script('wupz-admin', 'wupz_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wupz_nonce'),
                'strings' => array(
                    'backup_in_progress' => __('Backup in progress...', 'wupz'),
                    'backup_completed' => __('Backup completed successfully!', 'wupz'),
                    'backup_failed' => __('Backup failed. Please check the error log.', 'wupz'),
                    'confirm_delete' => __('Are you sure you want to delete this backup?', 'wupz')
                )
            ));
        }
    }
    
    /**
     * Main admin page
     */
    public function admin_page() {
        $backup = new Wupz_Backup();
        $backups = $backup->get_backup_list();
        $last_backup = $backup->get_last_backup_info();
        
        include WUPZ_PLUGIN_PATH . 'templates/admin-page.php';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        $settings = new Wupz_Settings();
        $settings->display_settings_page();
    }
    
    /**
     * Handle manual backup AJAX request
     */
    public function handle_manual_backup() {
        check_ajax_referer('wupz_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient permissions', 'wupz'));
        }
        
        $backup = new Wupz_Backup();
        $result = $backup->create_backup();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Handle backup download
     */
    public function handle_download_backup() {
        check_ajax_referer('wupz_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient permissions', 'wupz'));
        }
        
        if (!isset($_GET['file'])) {
            wp_die(esc_html__('No file specified', 'wupz'));
        }
        
        $filename = sanitize_file_name(wp_unslash($_GET['file']));
        $filepath = WUPZ_BACKUP_DIR . $filename;
        
        if (file_exists($filepath)) {
            // Use WP_Filesystem for file operations
            global $wp_filesystem;
            if (!$wp_filesystem) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                WP_Filesystem();
            }
            
            $file_content = $wp_filesystem->get_contents($filepath);
            if ($file_content !== false) {
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . esc_attr($filename) . '"');
                header('Content-Length: ' . strlen($file_content));
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary file content for download
                echo $file_content;
                exit;
            }
        }
        
        wp_die(esc_html__('File not found', 'wupz'));
    }
    
    /**
     * Handle backup deletion
     */
    public function handle_delete_backup() {
        check_ajax_referer('wupz_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient permissions', 'wupz'));
        }
        
        if (!isset($_POST['file'])) {
            wp_send_json_error(array('message' => __('No file specified', 'wupz')));
        }
        
        $filename = sanitize_file_name(wp_unslash($_POST['file']));
        $backup = new Wupz_Backup();
        $result = $backup->delete_backup($filename);
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create backup directory
        if (!file_exists(WUPZ_BACKUP_DIR)) {
            wp_mkdir_p(WUPZ_BACKUP_DIR);
            
            // Add .htaccess for security
            $htaccess_content = "Order deny,allow\nDeny from all\n";
            file_put_contents(WUPZ_BACKUP_DIR . '.htaccess', $htaccess_content);
        }
        
        // Set default options
        if (!get_option('wupz_settings')) {
            update_option('wupz_settings', array(
                'schedule_interval' => 'weekly',
                'max_backups' => 5,
                'backup_database' => 1,
                'backup_files' => 1
            ));
        }
        
        // Schedule first backup if auto-backup is enabled
        wp_schedule_event(time(), 'weekly', 'wupz_scheduled_backup');
    }
    
    /**
     * Handle schedule update
     */
    public function handle_update_schedule() {
        check_ajax_referer('wupz_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient permissions', 'wupz'));
        }
        
        if (!isset($_POST['schedule'])) {
            wp_send_json_error(array('message' => __('No schedule specified', 'wupz')));
        }
        
        $schedule = sanitize_text_field(wp_unslash($_POST['schedule']));
        $allowed = array('disabled', 'daily', 'weekly');
        
        if (in_array($schedule, $allowed)) {
            $schedule_manager = new Wupz_Schedule();
            $schedule_manager->update_schedule($schedule);
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }
    
    /**
     * Handle backup status check
     */
    public function handle_check_backup_status() {
        check_ajax_referer('wupz_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient permissions', 'wupz'));
        }
        
        $schedule = new Wupz_Schedule();
        $is_running = $schedule->is_backup_running();
        
        wp_send_json_success(array(
            'running' => $is_running,
            'completed' => !$is_running
        ));
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('wupz_scheduled_backup');
    }
}

// Initialize the plugin
new Wupz(); 