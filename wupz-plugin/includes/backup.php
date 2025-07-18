<?php
/**
 * Wupz Backup Class
 * Handles backup creation, database export, and file compression
 */

if (!defined('ABSPATH')) {
    exit;
}

class Wupz_Backup {
    
    /**
     * Create a complete backup
     * 
     * @return array Result array with success status and message
     */
    public function create_backup() {
        $settings = get_option('wupz_settings', array());
        $backup_database = isset($settings['backup_database']) ? $settings['backup_database'] : 1;
        $backup_files = isset($settings['backup_files']) ? $settings['backup_files'] : 1;
        
        try {
            // Create backup directory if it doesn't exist
            if (!file_exists(WUPZ_BACKUP_DIR)) {
                wp_mkdir_p(WUPZ_BACKUP_DIR);
            }
            
            $timestamp = current_time('Y-m-d_H-i-s');
            $backup_filename = "wupz-backup-{$timestamp}.zip";
            $backup_path = WUPZ_BACKUP_DIR . $backup_filename;
            
            // Initialize ZIP archive
            $zip = new ZipArchive();
            if ($zip->open($backup_path, ZipArchive::CREATE) !== TRUE) {
                return array(
                    'success' => false,
                    'message' => __('Failed to create ZIP archive', 'wupz')
                );
            }
            
            // Backup database
            if ($backup_database) {
                $db_export = $this->export_database();
                if ($db_export['success']) {
                    $zip->addFile($db_export['file'], 'database.sql');
                } else {
                    $zip->close();
                    return $db_export;
                }
            }
            
            // Backup files
            if ($backup_files) {
                $this->add_directory_to_zip($zip, WP_CONTENT_DIR, 'wp-content');
            }
            
            $zip->close();
            
            // Clean up temporary database file
            if (isset($db_export['file']) && file_exists($db_export['file'])) {
                wp_delete_file($db_export['file']);
            }
            
            // Clean up old backups
            $this->cleanup_old_backups();
            
            // Upload to S3 if configured
            $s3 = new Wupz_S3();
            if ($s3->is_configured()) {
                $s3_upload_success = $s3->upload_file($backup_path, $backup_filename);

                if ($s3_upload_success && !empty($settings['s3_delete_local'])) {
                    wp_delete_file($backup_path);
                }
            }

            // Update last backup info
            update_option('wupz_last_backup', array(
                'timestamp' => time(),
                'filename' => $backup_filename,
                'size' => filesize($backup_path),
                'status' => 'completed'
            ));
            
            return array(
                'success' => true,
                'message' => __('Backup created successfully', 'wupz'),
                'filename' => $backup_filename,
                'size' => $this->format_bytes(filesize($backup_path))
            );
            
        } catch (Exception $e) {
            // Log error using WordPress debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Only logs in debug mode
                error_log('Wupz Backup Error: ' . $e->getMessage());
            }
            
            return array(
                'success' => false,
                'message' => __('Backup failed: ', 'wupz') . $e->getMessage()
            );
        }
    }
    
    /**
     * Export database to SQL file
     * 
     * @return array Result with success status and file path
     */
    private function export_database() {
        global $wpdb;
        
        try {
            // Initialize WordPress filesystem
            if (!function_exists('WP_Filesystem')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
            
            $creds = request_filesystem_credentials('', '', false, false, array());
            if (!WP_Filesystem($creds)) {
                return array(
                    'success' => false,
                    'message' => __('Failed to initialize filesystem', 'wupz')
                );
            }
            
            global $wp_filesystem;
            
            $temp_file = WUPZ_BACKUP_DIR . 'temp_database_' . time() . '.sql';
            
            // Create content string instead of direct file operations
            $content = '';
            
            // Write header
            $content .= "-- Wupz Database Backup\n";
            $content .= "-- Generated on: " . current_time('Y-m-d H:i:s') . "\n";
            $content .= "-- WordPress Version: " . get_bloginfo('version') . "\n\n";
            
            // Get all tables
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for database backup functionality
            $tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
            
            foreach ($tables as $table) {
                $table_name = $table[0];
                
                // Skip non-WordPress tables if they exist
                if (strpos($table_name, $wpdb->prefix) !== 0) {
                    continue;
                }
                
                // Get table structure (escape table name for security)
                $escaped_table_name = esc_sql($table_name);
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table names cannot be parameterized, required for backup
                $create_table = $wpdb->get_row("SHOW CREATE TABLE `{$escaped_table_name}`", ARRAY_N);
                $content .= "\n-- Table structure for table `{$table_name}`\n";
                $content .= "DROP TABLE IF EXISTS `{$table_name}`;\n";
                $content .= $create_table[1] . ";\n\n";
                
                // Get table data
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table names cannot be parameterized, required for backup
                $rows = $wpdb->get_results("SELECT * FROM `{$escaped_table_name}`", ARRAY_A);
                
                if (!empty($rows)) {
                    $content .= "-- Dumping data for table `{$table_name}`\n";
                    
                    foreach ($rows as $row) {
                        $values = array();
                        foreach ($row as $value) {
                            if (is_null($value)) {
                                $values[] = 'NULL';
                            } else {
                                $values[] = "'" . $wpdb->_real_escape($value) . "'";
                            }
                        }
                        $content .= "INSERT INTO `{$table_name}` VALUES (" . implode(',', $values) . ");\n";
                    }
                    $content .= "\n";
                }
            }
            
            // Write content to file using WP_Filesystem
            if (!$wp_filesystem->put_contents($temp_file, $content, FS_CHMOD_FILE)) {
                return array(
                    'success' => false,
                    'message' => __('Failed to write database export file', 'wupz')
                );
            }
            
            return array(
                'success' => true,
                'file' => $temp_file
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => __('Database export failed: ', 'wupz') . $e->getMessage()
            );
        }
    }
    
    /**
     * Add directory to ZIP archive recursively
     * 
     * @param ZipArchive $zip ZIP archive object
     * @param string $dir Directory path to add
     * @param string $zip_dir Path in ZIP archive
     */
    private function add_directory_to_zip($zip, $dir, $zip_dir = '') {
        if (is_dir($dir)) {
            $files = scandir($dir);
            
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    $file_path = $dir . '/' . $file;
                    $zip_path = $zip_dir ? $zip_dir . '/' . $file : $file;
                    
                    // Skip wupz-backups directory to avoid recursive backups
                    if (basename($file_path) === 'wupz-backups') {
                        continue;
                    }
                    
                    // Skip cache directories
                    if (in_array(basename($file_path), array('cache', 'tmp', 'temp'))) {
                        continue;
                    }
                    
                    if (is_dir($file_path)) {
                        $zip->addEmptyDir($zip_path);
                        $this->add_directory_to_zip($zip, $file_path, $zip_path);
                    } else {
                        // Skip large files over 100MB to prevent memory issues
                        if (filesize($file_path) > 100 * 1024 * 1024) {
                            continue;
                        }
                        
                        $zip->addFile($file_path, $zip_path);
                    }
                }
            }
        }
    }
    
    /**
     * Clean up old backups based on max_backups setting
     */
    private function cleanup_old_backups() {
        $settings = get_option('wupz_settings', array());
        $max_backups = isset($settings['max_backups']) ? intval($settings['max_backups']) : 5;
        
        if ($max_backups <= 0) {
            return; // Keep all backups if set to 0
        }
        
        $backups = $this->get_backup_list();
        
        // Handle S3 backups
        $s3 = new Wupz_S3();
        if ($s3->is_configured()) {
            $s3_files = $s3->list_files();
            if (count($s3_files) > $max_backups) {
                // Sort by date (oldest first)
                usort($s3_files, function($a, $b) {
                    return $a['LastModified'] <=> $b['LastModified'];
                });

                $files_to_delete = array_slice($s3_files, 0, count($s3_files) - $max_backups);

                foreach ($files_to_delete as $file) {
                    $s3->delete_file($file['Key']);
                }
            }
        }

        // Handle local backups
        if (count($backups) > $max_backups) {
            // Sort by date (oldest first)
            usort($backups, function($a, $b) {
                return $a['date'] <=> $b['date'];
            });
            
            // Remove oldest backups
            $to_remove = array_slice($backups, 0, count($backups) - $max_backups);
            
            foreach ($to_remove as $backup) {
                $this->delete_backup($backup['filename']);
            }
        }
    }
    
    /**
     * Get list of available backups
     */
    public function get_backup_list() {
        $backups = array();

        // Get local backups
        if (is_dir(WUPZ_BACKUP_DIR)) {
            $files = scandir(WUPZ_BACKUP_DIR);
            
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) == 'zip') {
                    $filepath = WUPZ_BACKUP_DIR . $file;
                    $backups[] = array(
                        'filename' => $file,
                        'size' => $this->format_bytes(filesize($filepath)),
                        'date' => filemtime($filepath),
                        'location' => 'local'
                    );
                }
            }
        }

        // Get S3 backups
        $s3 = new Wupz_S3();
        if ($s3->is_configured()) {
            $s3_files = $s3->list_files();
            foreach ($s3_files as $file) {
                // Avoid duplicates if local file also exists
                if (!in_array($file['Key'], array_column($backups, 'filename'))) {
                    $backups[] = array(
                        'filename' => $file['Key'],
                        'size' => $this->format_bytes($file['Size']),
                        'date' => $file['LastModified']->getTimestamp(),
                        'location' => 's3'
                    );
                }
            }
        }
        
        // Sort by date (newest first)
        usort($backups, function($a, $b) {
            return $b['date'] <=> $a['date'];
        });
        
        return $backups;
    }
    
    /**
     * Delete a backup file
     * 
     * @param string $filename Backup filename
     * @return bool Success status
     */
    public function delete_backup($filename) {
        $filepath = WUPZ_BACKUP_DIR . $filename;
        $deleted = false;

        // Delete local file
        if (file_exists($filepath)) {
            if (wp_delete_file($filepath)) {
                $deleted = true;
            }
        }
        
        // Delete from S3
        $s3 = new Wupz_S3();
        if ($s3->is_configured()) {
            if ($s3->delete_file($filename)) {
                $deleted = true;
            }
        }

        return $deleted;
    }
    
    /**
     * Get information about the last backup
     * 
     * @return array|false Last backup info or false if none
     */
    public function get_last_backup_info() {
        return get_option('wupz_last_backup', false);
    }
    
    /**
     * Format bytes to human readable format
     * 
     * @param int $size Size in bytes
     * @return string Formatted size
     */
    public function format_bytes($size) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, 2) . ' ' . $units[$i];
    }
} 