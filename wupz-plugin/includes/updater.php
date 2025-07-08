<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Wupz_Updater {
    private $file;
    private $plugin_data;
    private $basename;
    private $github_repo_url;
    
    public function __construct($file) {
        $this->file = $file;
        $this->basename = plugin_basename($this->file);
        // Replace with your GitHub repository URL
        $this->github_repo_url = 'https://api.github.com/repos/danielgietmann/wupz/releases/latest';

        add_action('admin_init', array($this, 'set_plugin_properties'));
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_plugin_update'));
    }

    public function set_plugin_properties() {
        $this->plugin_data = get_plugin_data($this->file);
    }
    
    public function check_for_plugin_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $latest_release = $this->get_repository_info();

        if ($latest_release && version_compare($this->plugin_data['Version'], $latest_release->tag_name, '<')) {
            $obj = new stdClass();
            $obj->slug = $this->basename;
            $obj->plugin = $this->basename;
            $obj->new_version = $latest_release->tag_name;
            $obj->url = $this->plugin_data['PluginURI'];
            $obj->package = $latest_release->zipball_url;
            $transient->response[$this->basename] = $obj;
        }

        return $transient;
    }

    private function get_repository_info() {
        $cache_key = 'wupz_github_release_info';
        $release_info = get_transient($cache_key);

        if (false === $release_info) {
            $response = wp_remote_get($this->github_repo_url);
            
            if (is_wp_error($response)) {
                return false;
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body);

            if (!empty($data) && isset($data->tag_name)) {
                set_transient($cache_key, $data, HOUR_IN_SECONDS);
                return $data;
            }
        }
        
        return $release_info;
    }

    public static function clear_update_transient() {
        delete_transient('wupz_github_release_info');
    }
} 