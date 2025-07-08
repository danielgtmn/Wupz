<?php
/**
 * Wupz Admin Page Template
 * Main backup management interface
 */

if (!defined('ABSPATH')) {
    exit;
}

$schedule = new Wupz_Schedule();
$schedule_info = $schedule->get_schedule_info();

// Get system status
$settings = new Wupz_Settings();
$system_status = $settings->get_system_status();
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div id="wupz-messages"></div>
    
    <div class="wupz-admin-wrap">
        <!-- System Status Section -->
        <div class="wupz-system-status-section">
            <div class="wupz-card">
                <h2><?php esc_html_e('System Status', 'wupz'); ?></h2>
                
                <div class="wupz-status-indicator">
                    <span class="wupz-status-icon"><?php echo esc_html($settings->get_status_indicator($system_status['overall'])); ?></span>
                    <span class="wupz-status-text wupz-status-<?php echo esc_attr($system_status['overall']); ?>">
                        <?php echo esc_html($settings->get_status_message($system_status['overall'])); ?>
                    </span>
                </div>
                
                <?php if ($system_status['overall'] !== 'success'): ?>
                    <div class="wupz-status-details">
                        <h4><?php esc_html_e('System Check Details:', 'wupz'); ?></h4>
                        <ul class="wupz-status-list">
                            <?php foreach ($system_status['checks'] as $check): ?>
                                <?php if ($check['status'] !== 'success'): ?>
                                    <li class="wupz-status-item wupz-status-<?php echo esc_attr($check['status']); ?>">
                                        <strong><?php echo esc_html($check['label']); ?>:</strong>
                                        <?php echo esc_html($check['message']); ?>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Backup Status Section -->
        <div class="wupz-status-section">
            <div class="wupz-card">
                <h2><?php esc_html_e('Backup Status', 'wupz'); ?></h2>
                
                <div class="wupz-status-grid">
                    <div class="wupz-status-item">
                        <h3><?php esc_html_e('Last Backup', 'wupz'); ?></h3>
                        <?php if ($last_backup): ?>
                            <p class="wupz-status-value">
                                <?php echo esc_html(wp_date('Y-m-d H:i:s', $last_backup['timestamp'])); ?>
                            </p>
                            <p class="wupz-status-meta">
                                <span class="wupz-status-<?php echo esc_attr($last_backup['status']); ?>">
                                    <?php echo esc_html(ucfirst($last_backup['status'])); ?>
                                </span>
                                <?php if (isset($last_backup['filename'])): ?>
                                    - <?php echo esc_html($last_backup['filename']); ?>
                                <?php endif; ?>
                            </p>
                        <?php else: ?>
                            <p class="wupz-status-value"><?php esc_html_e('Never', 'wupz'); ?></p>
                            <p class="wupz-status-meta"><?php esc_html_e('No backups created yet', 'wupz'); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="wupz-status-item">
                        <h3><?php esc_html_e('Next Scheduled Backup', 'wupz'); ?></h3>
                        <p class="wupz-status-value">
                            <?php echo esc_html($schedule_info['next_backup_formatted']); ?>
                        </p>
                        <p class="wupz-status-meta">
                            <?php if ($schedule_info['is_scheduled']): ?>
                                <span class="wupz-status-active"><?php echo esc_html(ucfirst($schedule_info['interval'])); ?> schedule</span>
                            <?php else: ?>
                                <span class="wupz-status-inactive"><?php esc_html_e('Automatic backups disabled', 'wupz'); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <div class="wupz-status-item">
                        <h3><?php esc_html_e('Total Backups', 'wupz'); ?></h3>
                        <p class="wupz-status-value"><?php echo count($backups); ?></p>
                        <p class="wupz-status-meta">
                                                    <?php 
                        if (!empty($backups)) {
                            $total_size = 0;
                            foreach ($backups as $backup) {
                                $total_size += filesize(WUPZ_BACKUP_DIR . $backup['filename']);
                            }
                            $backup_helper = new Wupz_Backup();
                            echo esc_html($backup_helper->format_bytes($total_size));
                        } else {
                            echo esc_html__('0 bytes', 'wupz');
                        }
                        ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Manual Backup Section -->
        <div class="wupz-actions-section">
            <div class="wupz-card">
                <h2><?php esc_html_e('Manual Backup', 'wupz'); ?></h2>
                <p><?php esc_html_e('Create a backup of your WordPress site right now.', 'wupz'); ?></p>
                
                <div class="wupz-backup-controls">
                    <button id="wupz-create-backup" class="button button-primary button-large">
                        <span class="dashicons dashicons-backup"></span>
                        <?php esc_html_e('Create Backup Now', 'wupz'); ?>
                    </button>
                    
                    <div id="wupz-backup-progress" class="wupz-progress" style="display: none;">
                        <div class="wupz-progress-bar">
                            <div class="wupz-progress-fill"></div>
                        </div>
                        <p class="wupz-progress-text"><?php esc_html_e('Creating backup...', 'wupz'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Backup List Section -->
        <div class="wupz-backups-section">
            <div class="wupz-card">
                <h2><?php esc_html_e('Available Backups', 'wupz'); ?></h2>
                
                <?php if (!empty($backups)) : ?>
                    <ul class="wupz-backup-list">
                        <?php foreach ($backups as $item) : ?>
                            <li>
                                <span class="backup-name"><?php echo esc_html($item['filename']); ?></span>
                                <span class="backup-date"><?php echo esc_html(get_date_from_gmt(gmdate('Y-m-d H:i:s', $item['date']), 'Y-m-d H:i:s')); ?></span>
                                <span class="backup-size"><?php echo esc_html($item['size']); ?></span>
                                <span class="backup-location"><?php echo esc_html(strtoupper($item['location'])); ?></span>
                                <div class="backup-actions">
                                    <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=wupz_download_backup&file=' . urlencode($item['filename']) . '&location=' . urlencode($item['location']) . '&nonce=' . wp_create_nonce('wupz_nonce'))); ?>" class="button button-secondary"><?php esc_html_e('Download', 'wupz'); ?></a>
                                    <button class="button button-danger wupz-delete-backup" data-file="<?php echo esc_attr($item['filename']); ?>"><?php esc_html_e('Delete', 'wupz'); ?></button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <div class="wupz-empty-state">
                        <span class="dashicons dashicons-backup"></span>
                        <h3><?php esc_html_e('No backups found', 'wupz'); ?></h3>
                        <p><?php esc_html_e('Create your first backup to get started.', 'wupz'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Settings Section -->
        <div class="wupz-quick-settings-section">
            <div class="wupz-card">
                <h2><?php esc_html_e('Quick Settings', 'wupz'); ?></h2>
                
                <div class="wupz-quick-settings">
                    <div class="wupz-setting-item">
                        <label for="wupz-quick-schedule"><?php esc_html_e('Backup Schedule:', 'wupz'); ?></label>
                        <select id="wupz-quick-schedule" class="wupz-quick-setting">
                            <option value="disabled" <?php selected($schedule_info['interval'], 'disabled'); ?>><?php esc_html_e('Disabled', 'wupz'); ?></option>
                            <option value="daily" <?php selected($schedule_info['interval'], 'daily'); ?>><?php esc_html_e('Daily', 'wupz'); ?></option>
                            <option value="weekly" <?php selected($schedule_info['interval'], 'weekly'); ?>><?php esc_html_e('Weekly', 'wupz'); ?></option>
                        </select>
                    </div>
                    
                    <div class="wupz-setting-item">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=wupz-settings')); ?>" class="button">
                            <?php esc_html_e('More Settings', 'wupz'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Format bytes helper function
    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Handle manual backup
    $('#wupz-create-backup').on('click', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $progress = $('#wupz-backup-progress');
        const $messages = $('#wupz-messages');
        
        // Disable button and show progress
        $button.prop('disabled', true);
        $progress.show();
        $messages.empty();
        
        // Start progress animation
        let progress = 0;
        const progressInterval = setInterval(function() {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;
            $('.wupz-progress-fill').css('width', progress + '%');
        }, 500);
        
        // Make AJAX request
        $.post(wupz_ajax.ajax_url, {
            action: 'wupz_manual_backup',
            nonce: wupz_ajax.nonce
        }, function(response) {
            clearInterval(progressInterval);
            $('.wupz-progress-fill').css('width', '100%');
            
            setTimeout(function() {
                $progress.hide();
                $button.prop('disabled', false);
                
                if (response.success) {
                    $messages.html('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>');
                    // Reload page to update backup list
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    $messages.html('<div class="notice notice-error is-dismissible"><p>' + response.data.message + '</p></div>');
                }
            }, 500);
        }).fail(function() {
            clearInterval(progressInterval);
            $progress.hide();
            $button.prop('disabled', false);
            $messages.html('<div class="notice notice-error is-dismissible"><p>' + wupz_ajax.strings.backup_failed + '</p></div>');
        });
    });
    
    // Handle backup deletion
    $('.wupz-delete-backup').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm(wupz_ajax.strings.confirm_delete)) {
            return;
        }
        
        const $button = $(this);
        const filename = $button.data('filename');
        const $row = $button.closest('tr');
        
        $button.prop('disabled', true);
        
        $.post(wupz_ajax.ajax_url, {
            action: 'wupz_delete_backup',
            file: filename,
            nonce: wupz_ajax.nonce
        }, function(response) {
            if (response.success) {
                $row.fadeOut(function() {
                    $row.remove();
                    
                    // Check if table is empty
                    if ($('.wupz-backups-table-wrap tbody tr').length === 0) {
                        $('.wupz-backups-table-wrap').html('<div class="wupz-empty-state"><span class="dashicons dashicons-backup"></span><h3>No backups found</h3><p>Create your first backup to get started.</p></div>');
                    }
                });
            } else {
                $('#wupz-messages').html('<div class="notice notice-error is-dismissible"><p>Failed to delete backup.</p></div>');
                $button.prop('disabled', false);
            }
        }).fail(function() {
            $('#wupz-messages').html('<div class="notice notice-error is-dismissible"><p>Failed to delete backup.</p></div>');
            $button.prop('disabled', false);
        });
    });
    
    // Handle quick schedule change
    $('#wupz-quick-schedule').on('change', function() {
        const schedule = $(this).val();
        
        $.post(wupz_ajax.ajax_url, {
            action: 'wupz_update_schedule',
            schedule: schedule,
            nonce: wupz_ajax.nonce
        }, function(response) {
            if (response.success) {
                $('#wupz-messages').html('<div class="notice notice-success is-dismissible"><p>Schedule updated successfully.</p></div>');
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            }
        });
    });
});
</script> 