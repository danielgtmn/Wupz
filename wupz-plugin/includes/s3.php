<?php
/**
 * Wupz S3 Class
 * Handles all S3 interactions
 */

if (!defined('ABSPATH')) {
    exit;
}

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class Wupz_S3 {

    const BACKUP_KEY_PREFIX = 'wupz-backup-';

    /**
     * Stop paging a shared bucket if a prefix listing is unexpectedly huge.
     * Cleanup is skipped when the listing stops early.
     */
    const LIST_PAGE_LIMIT = 100;

    private $s3_client;
    private $settings;
    private $list_complete = true;

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = Wupz_Settings::get_settings();
        
        if ($this->is_configured()) {
            $config = [
                'version' => 'latest',
                'region'  => $this->settings['s3_region'],
                'credentials' => [
                    'key'    => $this->settings['s3_access_key'],
                    'secret' => $this->settings['s3_secret_key'],
                ]
            ];

            if (!empty($this->settings['s3_endpoint'])) {
                $config['endpoint'] = $this->settings['s3_endpoint'];
                $config['use_path_style_endpoint'] = true;
            }

            $this->s3_client = new S3Client($config);
        }
    }

    /**
     * Check if S3 is configured and enabled
     */
    public function is_configured() {
        return !empty($this->settings['s3_enabled']) &&
               !empty($this->settings['s3_access_key']) &&
               !empty($this->settings['s3_secret_key']) &&
               !empty($this->settings['s3_bucket']) &&
               !empty($this->settings['s3_region']);
    }

    /**
     * Upload a file to S3
     */
    public function upload_file($filepath, $filename) {
        if (!$this->is_configured()) {
            return false;
        }

        try {
            $this->s3_client->putObject([
                'Bucket' => $this->settings['s3_bucket'],
                'Key'    => $filename,
                'SourceFile' => $filepath,
            ]);
            return true;
        } catch (S3Exception $e) {
            error_log('Wupz S3 Upload Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * List Wupz backup objects in the configured bucket.
     *
     * The bucket may hold other data. Listing is limited to the key prefix
     * used by upload_file(), and each key must match a Wupz backup filename
     * before it is returned or eligible for retention deletes.
     *
     * @return array
     */
    public function list_files() {
        $this->list_complete = true;

        if (!$this->is_configured() || !$this->s3_client) {
            return array();
        }

        $files = array();
        $params = array(
            'Bucket' => $this->settings['s3_bucket'],
            'Prefix' => self::BACKUP_KEY_PREFIX,
        );

        try {
            $pages = 0;
            do {
                $pages++;
                $result = $this->s3_client->listObjectsV2($params);
                $contents = isset($result['Contents']) ? $result['Contents'] : array();

                foreach ($contents as $object) {
                    if (!isset($object['Key']) || !self::is_wupz_backup_key($object['Key'])) {
                        continue;
                    }
                    $files[] = $object;
                }

                $truncated = !empty($result['IsTruncated']);
                $token = isset($result['NextContinuationToken']) ? $result['NextContinuationToken'] : '';
                if ($truncated && $token !== '' && $pages < self::LIST_PAGE_LIMIT) {
                    $params['ContinuationToken'] = $token;
                    continue;
                }

                if ($truncated) {
                    $this->list_complete = false;
                }
                break;
            } while (true);
        } catch (S3Exception $e) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- S3 failures have no admin logger
            error_log('Wupz S3 List Error: ' . $e->getMessage());
            $this->list_complete = false;
            return array();
        }

        return $files;
    }

    /**
     * False when the last list_files() call stopped early or failed.
     * Retention must not delete objects based on a partial list.
     *
     * @return bool
     */
    public function list_is_complete() {
        return $this->list_complete;
    }

    /**
     * Backup object keys are the zip names created in Wupz_Backup::create_backup().
     *
     * @param string $key
     * @return bool
     */
    public static function is_wupz_backup_key($key) {
        return is_string($key) && (bool) preg_match('/^wupz-backup-\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.zip$/', $key);
    }

    /**
     * Delete a file from S3
     */
    public function delete_file($filename) {
        if (!$this->is_configured() || !self::is_wupz_backup_key($filename)) {
            return false;
        }

        try {
            $this->s3_client->deleteObject([
                'Bucket' => $this->settings['s3_bucket'],
                'Key'    => $filename,
            ]);
            return true;
        } catch (S3Exception $e) {
            error_log('Wupz S3 Delete Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get a pre-signed URL for downloading a file
     */
    public function get_download_url($filename) {
        if (!$this->is_configured() || !self::is_wupz_backup_key($filename)) {
            return null;
        }

        try {
            $cmd = $this->s3_client->getCommand('GetObject', [
                'Bucket' => $this->settings['s3_bucket'],
                'Key'    => $filename
            ]);

            $request = $this->s3_client->createPresignedRequest($cmd, '+20 minutes');
            return (string) $request->getUri();
        } catch (S3Exception $e) {
            error_log('Wupz S3 URL Generation Error: ' . $e->getMessage());
            return null;
        }
    }
} 