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
            
            $timestamp = date('Y-m-d_H-i-s');
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
                unlink($db_export['file']);
            }
            
            // Clean up old backups
            $this->cleanup_old_backups();
            
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
            // Log error
            error_log('Wupz Backup Error: ' . $e->getMessage());
            
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
            $temp_file = WUPZ_BACKUP_DIR . 'temp_database_' . time() . '.sql';
            $handle = fopen($temp_file, 'w');
            
            if (!$handle) {
                return array(
                    'success' => false,
                    'message' => __('Failed to create database export file', 'wupz')
                );
            }
            
            // Write header
            fwrite($handle, "-- Wupz Database Backup\n");
            fwrite($handle, "-- Generated on: " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- WordPress Version: " . get_bloginfo('version') . "\n\n");
            
            // Get all tables
            $tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
            
            foreach ($tables as $table) {
                $table_name = $table[0];
                
                // Skip non-WordPress tables if they exist
                if (strpos($table_name, $wpdb->prefix) !== 0) {
                    continue;
                }
                
                // Get table structure
                $create_table = $wpdb->get_row("SHOW CREATE TABLE `{$table_name}`", ARRAY_N);
                fwrite($handle, "\n-- Table structure for table `{$table_name}`\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$table_name}`;\n");
                fwrite($handle, $create_table[1] . ";\n\n");
                
                // Get table data
                $rows = $wpdb->get_results("SELECT * FROM `{$table_name}`", ARRAY_A);
                
                if (!empty($rows)) {
                    fwrite($handle, "-- Dumping data for table `{$table_name}`\n");
                    
                    foreach ($rows as $row) {
                        $values = array();
                        foreach ($row as $value) {
                            if (is_null($value)) {
                                $values[] = 'NULL';
                            } else {
                                $values[] = "'" . $wpdb->_real_escape($value) . "'";
                            }
                        }
                        fwrite($handle, "INSERT INTO `{$table_name}` VALUES (" . implode(',', $values) . ");\n");
                    }
                    fwrite($handle, "\n");
                }
            }
            
            fclose($handle);
            
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
     * Get list of backup files
     * 
     * @return array List of backup files with metadata
     */
    public function get_backup_list() {
        $backups = array();
        
        if (!is_dir(WUPZ_BACKUP_DIR)) {
            return $backups;
        }
        
        $files = scandir(WUPZ_BACKUP_DIR);
        
        foreach ($files as $file) {
            if (preg_match('/^wupz-backup-(.+)\.zip$/', $file, $matches)) {
                $file_path = WUPZ_BACKUP_DIR . $file;
                $backups[] = array(
                    'filename' => $file,
                    'size' => $this->format_bytes(filesize($file_path)),
                    'date' => filemtime($file_path),
                    'date_formatted' => date('Y-m-d H:i:s', filemtime($file_path))
                );
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
        $file_path = WUPZ_BACKUP_DIR . sanitize_file_name($filename);
        
        if (file_exists($file_path) && preg_match('/^wupz-backup-(.+)\.zip$/', $filename)) {
            return unlink($file_path);
        }
        
        return false;
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