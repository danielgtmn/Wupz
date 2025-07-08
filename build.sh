#!/bin/bash

# Wupz WordPress Plugin - Build Script
# This script creates a distributable ZIP file for the plugin, suitable for production.

set -e

echo "🚀 Wupz WordPress Plugin - Build Script"
echo "======================================"
echo ""

# --- Configuration ---
PLUGIN_SLUG="wupz"
PLUGIN_SOURCE_DIR="wupz-plugin"
BUILD_DIR="build"
FINAL_BUILD_DIR="$BUILD_DIR/$PLUGIN_SLUG"

# --- Pre-flight Checks ---
echo "🔎 Performing pre-flight checks..."

# Check for Composer
COMPOSER_CMD=""
if command -v composer &> /dev/null; then
    COMPOSER_CMD="composer"
elif [ -f "$PLUGIN_SOURCE_DIR/composer.phar" ]; then
    COMPOSER_CMD="php composer.phar" # This command needs to be run from within the plugin dir
else
    echo "❌ Composer is not found."
    echo "   Please install it globally or run 'curl -sS https://getcomposer.org/installer | php' in the '$PLUGIN_SOURCE_DIR' directory."
    exit 1
fi
echo "✅ Composer is available."
echo ""

# Check for zip command
if ! command -v zip &> /dev/null; then
    echo "❌ 'zip' command is not installed. Please install it to continue."
    exit 1
fi
echo "✅ Zip command is available."
echo ""

# --- Install Dependencies ---
echo "📦 Installing Composer dependencies..."
(cd "$PLUGIN_SOURCE_DIR" && $COMPOSER_CMD install --no-dev --optimize-autoloader)
echo "✅ Dependencies installed."
echo ""

# --- Clean & Prepare ---
echo "🧹 Cleaning up old builds..."
rm -rf "$BUILD_DIR"
rm -f ${PLUGIN_SLUG}-*.zip
mkdir -p "$FINAL_BUILD_DIR"
echo "✅ Cleanup complete."
echo ""

# --- Get Version ---
PLUGIN_VERSION=$(grep "Version:" "$PLUGIN_SOURCE_DIR/wupz.php" | head -1 | awk -F: '{print $2}' | xargs)
if [ -z "$PLUGIN_VERSION" ]; then
    echo "❌ Could not determine plugin version."
    exit 1
fi
echo "ℹ️  Plugin Version: $PLUGIN_VERSION"
echo ""

# --- Copy Files ---
echo "📂 Copying plugin files to build directory..."
cp -r ${PLUGIN_SOURCE_DIR}/* "$FINAL_BUILD_DIR/"
echo "✅ Plugin files copied."
echo ""

# --- Remove Development Files ---
echo "🗑️  Removing development files from package..."
rm -f "$FINAL_BUILD_DIR/composer.json"
rm -f "$FINAL_BUILD_DIR/composer.lock"
rm -f "$FINAL_BUILD_DIR/composer.phar"
rm -f "$FINAL_BUILD_DIR/.gitignore"
find "$FINAL_BUILD_DIR" -name ".git*" -exec rm -rf {} + 2>/dev/null || true
find "$FINAL_BUILD_DIR" -name ".DS_Store" -delete 2>/dev/null || true
echo "✅ Development files removed."
echo ""

# --- Create ZIP ---
ZIP_FILE="${PLUGIN_SLUG}-v${PLUGIN_VERSION}.zip"
echo "📦 Creating ZIP file: $ZIP_FILE..."
cd "$BUILD_DIR"
zip -r "../$ZIP_FILE" "$PLUGIN_SLUG/"
cd ..
echo "✅ ZIP file created."
echo ""

# --- Final Cleanup ---
echo "🧹 Cleaning up build directory..."
rm -rf "$BUILD_DIR"
echo "✅ Cleanup complete."
echo ""

# --- Finish ---
echo "🎉 BUILD COMPLETE!"
echo "=================="
echo ""
echo "✅ Your distributable plugin file is ready: $ZIP_FILE"
echo "📦 Size: $(du -h "$ZIP_FILE" | cut -f1)"
echo "" 