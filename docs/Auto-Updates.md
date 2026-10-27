# Auto-Updates

WUPZ includes a convenient auto-update feature that simplifies keeping the plugin current with the latest features, security patches, and bug fixes.

## How It Works

The update mechanism is designed to be seamless and operate just like plugins from the official WordPress.org repository.

1.  **Regular Checks**: WUPZ periodically checks for new versions by connecting to the official WUPZ GitHub repository.
2.  **GitHub Releases**: The updater looks at the "releases" section of the repository to find the latest official version.
3.  **Version Comparison**: It compares the installed version of the plugin with the latest version available on GitHub.
4.  **Update Notification**: If a newer version is found, a standard update notification will appear on your **Plugins** page in the WordPress admin area.
5.  **One-Click Update**: You can then update the plugin with a single click, directly from your dashboard.

This process ensures that you can easily benefit from the latest improvements without needing to manually download and install new versions.

## Technical Details

For those interested in the technical implementation:

-   The plugin uses the `https://api.github.com/repos/danielgietmann/wupz/releases/latest` API endpoint to fetch information about the newest release.
-   To ensure good performance and avoid hitting API rate limits, the result of the update check is cached in a WordPress transient for one hour.
-   The updater hooks into the `pre_set_site_transient_update_plugins` filter to inject the update information into WordPress's update process.
