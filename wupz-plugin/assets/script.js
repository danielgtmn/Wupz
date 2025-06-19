/**
 * Wupz Admin JavaScript
 * Handles AJAX interactions and UI functionality
 */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        WupzAdmin.init();
    });
    
    // Main admin object
    const WupzAdmin = {
        
        // Initialize all functionality
        init: function() {
            this.bindEvents();
            this.checkBackupStatus();
        },
        
        // Bind event handlers
        bindEvents: function() {
            // Manual backup button
            $(document).on('click', '#wupz-create-backup', this.handleManualBackup);
            
            // Delete backup buttons
            $(document).on('click', '.wupz-delete-backup', this.handleDeleteBackup);
            
            // Quick schedule change
            $(document).on('change', '#wupz-quick-schedule', this.handleScheduleChange);
            
            // Dismiss notices
            $(document).on('click', '.notice-dismiss', this.dismissNotice);
        },
        
        // Handle manual backup creation
        handleManualBackup: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const $progress = $('#wupz-backup-progress');
            const $messages = $('#wupz-messages');
            
            // Check if backup is already running
            if ($button.prop('disabled')) {
                return;
            }
            
            // Disable button and show progress
            $button.prop('disabled', true).addClass('wupz-loading');
            $progress.show();
            $messages.empty();
            
            // Update button text
            const originalText = $button.html();
            $button.html('<span class="dashicons dashicons-update"></span> ' + wupz_ajax.strings.backup_in_progress);
            
            // Start progress animation
            WupzAdmin.startProgressAnimation();
            
            // Make AJAX request
            $.ajax({
                url: wupz_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wupz_manual_backup',
                    nonce: wupz_ajax.nonce
                },
                timeout: 300000, // 5 minutes timeout
                success: function(response) {
                    WupzAdmin.stopProgressAnimation();
                    
                    setTimeout(function() {
                        $progress.hide();
                        $button.prop('disabled', false).removeClass('wupz-loading');
                        $button.html(originalText);
                        
                        if (response.success) {
                            WupzAdmin.showMessage(response.data.message, 'success');
                            // Reload page to update backup list
                            setTimeout(function() {
                                window.location.reload();
                            }, 2000);
                        } else {
                            const message = response.data && response.data.message 
                                ? response.data.message 
                                : wupz_ajax.strings.backup_failed;
                            WupzAdmin.showMessage(message, 'error');
                        }
                    }, 1000);
                },
                error: function(xhr, status, error) {
                    WupzAdmin.stopProgressAnimation();
                    $progress.hide();
                    $button.prop('disabled', false).removeClass('wupz-loading');
                    $button.html(originalText);
                    
                    let errorMessage = wupz_ajax.strings.backup_failed;
                    if (status === 'timeout') {
                        errorMessage = 'Backup request timed out. The backup may still be running in the background.';
                    }
                    
                    WupzAdmin.showMessage(errorMessage, 'error');
                }
            });
        },
        
        // Handle backup deletion
        handleDeleteBackup: function(e) {
            e.preventDefault();
            
            if (!confirm(wupz_ajax.strings.confirm_delete)) {
                return;
            }
            
            const $button = $(this);
            const filename = $button.data('filename');
            const $row = $button.closest('tr');
            
            $button.prop('disabled', true);
            
            $.ajax({
                url: wupz_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wupz_delete_backup',
                    file: filename,
                    nonce: wupz_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(400, function() {
                            $row.remove();
                            
                            // Check if table is empty
                            if ($('.wupz-backups-table-wrap tbody tr').length === 0) {
                                $('.wupz-backups-table-wrap').html(
                                    '<div class="wupz-empty-state">' +
                                    '<span class="dashicons dashicons-backup"></span>' +
                                    '<h3>No backups found</h3>' +
                                    '<p>Create your first backup to get started.</p>' +
                                    '</div>'
                                );
                            }
                            
                            // Update total count in status section
                            WupzAdmin.updateBackupCount();
                        });
                    } else {
                        WupzAdmin.showMessage('Failed to delete backup.', 'error');
                        $button.prop('disabled', false);
                    }
                },
                error: function() {
                    WupzAdmin.showMessage('Failed to delete backup.', 'error');
                    $button.prop('disabled', false);
                }
            });
        },
        
        // Handle schedule change
        handleScheduleChange: function() {
            const schedule = $(this).val();
            const $select = $(this);
            
            $select.prop('disabled', true);
            
            $.ajax({
                url: wupz_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wupz_update_schedule',
                    schedule: schedule,
                    nonce: wupz_ajax.nonce
                },
                success: function(response) {
                    $select.prop('disabled', false);
                    
                    if (response.success) {
                        WupzAdmin.showMessage('Schedule updated successfully.', 'success');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        WupzAdmin.showMessage('Failed to update schedule.', 'error');
                    }
                },
                error: function() {
                    $select.prop('disabled', false);
                    WupzAdmin.showMessage('Failed to update schedule.', 'error');
                }
            });
        },
        
        // Progress animation
        progressInterval: null,
        
        startProgressAnimation: function() {
            let progress = 0;
            this.progressInterval = setInterval(function() {
                progress += Math.random() * 10;
                if (progress > 90) progress = 90;
                $('.wupz-progress-fill').css('width', progress + '%');
            }, 800);
        },
        
        stopProgressAnimation: function() {
            if (this.progressInterval) {
                clearInterval(this.progressInterval);
                this.progressInterval = null;
            }
            $('.wupz-progress-fill').css('width', '100%');
        },
        
        // Show message
        showMessage: function(message, type) {
            const $messages = $('#wupz-messages');
            const alertClass = type === 'success' ? 'notice-success' : 'notice-error';
            
            const messageHtml = '<div class="notice ' + alertClass + ' is-dismissible">' +
                                '<p>' + message + '</p>' +
                                '<button type="button" class="notice-dismiss">' +
                                '<span class="screen-reader-text">Dismiss this notice.</span>' +
                                '</button>' +
                                '</div>';
            
            $messages.html(messageHtml);
            
            // Auto-dismiss success messages after 5 seconds
            if (type === 'success') {
                setTimeout(function() {
                    $messages.find('.notice').fadeOut();
                }, 5000);
            }
        },
        
        // Dismiss notice
        dismissNotice: function() {
            $(this).closest('.notice').fadeOut();
        },
        
        // Update backup count in status section
        updateBackupCount: function() {
            const count = $('.wupz-backups-table-wrap tbody tr').length;
            $('.wupz-status-item').eq(2).find('.wupz-status-value').text(count);
        },
        
        // Check backup status periodically
        checkBackupStatus: function() {
            // Check every 30 seconds if backup is running
            setInterval(function() {
                if ($('#wupz-create-backup').prop('disabled')) {
                    // Backup is running, check status
                    $.ajax({
                        url: wupz_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'wupz_check_backup_status',
                            nonce: wupz_ajax.nonce
                        },
                        success: function(response) {
                            if (response.data && response.data.completed) {
                                // Backup completed, reload page
                                window.location.reload();
                            }
                        }
                    });
                }
            }, 30000);
        },
        
        // Utility functions
        formatBytes: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        // Validate backup filename
        isValidBackupFile: function(filename) {
            return /^wupz-backup-[\d\-_]+\.zip$/.test(filename);
        }
    };
    
    // Make WupzAdmin globally available
    window.WupzAdmin = WupzAdmin;
    
})(jQuery); 