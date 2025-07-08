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

    private $s3_client;
    private $settings;

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
     * List files from S3 bucket
     */
    public function list_files() {
        if (!$this->is_configured()) {
            return [];
        }

        try {
            $result = $this->s3_client->listObjectsV2([
                'Bucket' => $this->settings['s3_bucket'],
            ]);
            return $result['Contents'] ?? [];
        } catch (S3Exception $e) {
            error_log('Wupz S3 List Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete a file from S3
     */
    public function delete_file($filename) {
        if (!$this->is_configured()) {
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
        if (!$this->is_configured()) {
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