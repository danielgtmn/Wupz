#!/bin/bash

# Wupz WordPress Plugin - Quick Setup Script
# This script sets up a complete Docker development environment for testing the Wupz plugin

set -e

echo "🚀 Wupz WordPress Plugin - Quick Setup"
echo "======================================"
echo ""

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker first."
    exit 1
fi

echo "✅ Docker is running"

# Check if docker-compose is available
if ! command -v docker-compose &> /dev/null; then
    echo "❌ docker-compose is not installed. Please install Docker Compose first."
    exit 1
fi

echo "✅ Docker Compose is available"

# Check if make is available
if ! command -v make &> /dev/null; then
    echo "⚠️  Make is not available. You can still use docker-compose commands directly."
    USE_MAKE=false
else
    echo "✅ Make is available"
    USE_MAKE=true
fi

echo ""
echo "🏗️  Setting up development environment..."
echo ""

# Build and start containers
echo "📦 Building and starting containers..."
docker-compose up -d --build

echo "⏳ Waiting for WordPress to initialize (30 seconds)..."
sleep 30

# Setup WordPress
echo "🔧 Installing WordPress..."
docker-compose run --rm wp-cli wp core install \
    --url=http://localhost:8080 \
    --title="Wupz Test Site" \
    --admin_user=admin \
    --admin_password=admin123 \
    --admin_email=admin@test.local \
    --skip-email

# Activate the plugin
echo "🔌 Activating Wupz plugin..."
docker-compose run --rm wp-cli wp plugin activate wupz

# Create some test content
echo "📝 Creating test content..."
docker-compose run --rm wp-cli wp post create \
    --post_title="Test Post for Backup" \
    --post_content="This is a test post to verify backup functionality." \
    --post_status=publish

docker-compose run --rm wp-cli wp post create \
    --post_title="Another Test Post" \
    --post_content="Another post with some content for testing backups." \
    --post_status=publish

echo ""
echo "🎉 SETUP COMPLETE!"
echo "=================="
echo ""
echo "🌐 WordPress Admin: http://localhost:8080/wp-admin"
echo "👤 Username: admin"
echo "🔑 Password: admin123"
echo ""
echo "🗄️  phpMyAdmin: http://localhost:8081"
echo "🔒 Database User: root"
echo "🔑 Database Password: root_password"
echo ""
echo "🔌 Wupz Plugin: Activated and ready to test!"
echo ""
echo "🧪 Next Steps:"
echo "   1. Open http://localhost:8080/wp-admin in your browser"
echo "   2. Login with admin/admin123"
echo "   3. Go to 'Wupz' in the admin menu"
echo "   4. Create your first backup!"
echo ""

if [ "$USE_MAKE" = true ]; then
    echo "💡 Available make commands:"
    echo "   make help          - Show all available commands"
    echo "   make logs          - View container logs"
    echo "   make restart       - Restart containers"
    echo "   make down          - Stop containers"
    echo "   make clean         - Remove everything (including data)"
    echo ""
fi

echo "📚 For more information, check the README-DOCKER.md file"
echo "" 