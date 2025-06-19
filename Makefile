# Wupz WordPress Plugin - Docker Setup
# Make commands for easy development and testing

.PHONY: help build up down clean restart logs wp-setup wp-install-plugin wp-activate-plugin

# Default target
help: ## Show this help message
	@echo "Wupz WordPress Plugin - Docker Commands"
	@echo "======================================"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

build: ## Build and start all containers
	@echo "🏗️  Building and starting Wupz development environment..."
	docker-compose up -d --build
	@echo "✅ Done! WordPress is starting up..."
	@echo "🌐 WordPress: http://localhost:8080"
	@echo "🗄️  phpMyAdmin: http://localhost:8081"
	@echo "⏳ Please wait a few minutes for WordPress to initialize..."

up: ## Start all containers
	@echo "🚀 Starting Wupz development environment..."
	docker-compose up -d
	@echo "✅ Done!"
	@echo "🌐 WordPress: http://localhost:8080"
	@echo "🗄️  phpMyAdmin: http://localhost:8081"

down: ## Stop all containers
	@echo "🛑 Stopping Wupz development environment..."
	docker-compose down
	@echo "✅ Containers stopped"

clean: ## Stop containers and remove volumes (⚠️  This will delete all data!)
	@echo "🧹 Cleaning up everything (including data)..."
	@read -p "Are you sure? This will delete all WordPress data [y/N]: " confirm && [ "$$confirm" = "y" ]
	docker-compose down -v
	docker volume prune -f
	@echo "✅ Everything cleaned"

restart: ## Restart all containers
	@echo "🔄 Restarting containers..."
	docker-compose restart
	@echo "✅ Containers restarted"

logs: ## Show logs from all containers
	docker-compose logs -f

logs-wp: ## Show WordPress container logs
	docker-compose logs -f wordpress

logs-mysql: ## Show MySQL container logs
	docker-compose logs -f mysql

shell-wp: ## Access WordPress container shell
	docker-compose exec wordpress bash

shell-wp-cli: ## Access WP-CLI container
	docker-compose run --rm wp-cli bash

wp-setup: ## Complete WordPress setup (run after first startup)
	@echo "🔧 Setting up WordPress..."
	@echo "⏳ Waiting for WordPress to be ready..."
	@sleep 10
	docker-compose run --rm wp-cli wp core install \
		--url=http://localhost:8080 \
		--title="Wupz Test Site" \
		--admin_user=admin \
		--admin_password=admin123 \
		--admin_email=admin@test.local \
		--skip-email
	@echo "✅ WordPress setup complete!"
	@echo "👤 Admin User: admin"
	@echo "🔑 Admin Password: admin123"
	@echo "🌐 Login: http://localhost:8080/wp-admin"

wp-activate-plugin: ## Activate the Wupz plugin
	@echo "🔌 Activating Wupz plugin..."
	docker-compose run --rm wp-cli wp plugin activate wupz
	@echo "✅ Wupz plugin activated!"

wp-deactivate-plugin: ## Deactivate the Wupz plugin
	@echo "🔌 Deactivating Wupz plugin..."
	docker-compose run --rm wp-cli wp plugin deactivate wupz
	@echo "✅ Wupz plugin deactivated!"

wp-plugin-list: ## List all installed plugins
	docker-compose run --rm wp-cli wp plugin list

wp-create-content: ## Create some test content
	@echo "📝 Creating test content..."
	docker-compose run --rm wp-cli wp post create \
		--post_title="Test Post for Backup" \
		--post_content="This is a test post to verify backup functionality." \
		--post_status=publish
	docker-compose run --rm wp-cli wp post create \
		--post_title="Another Test Post" \
		--post_content="Another post with some content for testing backups." \
		--post_status=publish
	@echo "✅ Test content created!"

wp-info: ## Show WordPress information
	docker-compose run --rm wp-cli wp core version
	docker-compose run --rm wp-cli wp db size

status: ## Show container status
	@echo "📊 Container Status:"
	docker-compose ps

# Complete setup workflow
setup: build wp-setup wp-activate-plugin wp-create-content ## Complete setup: build, install WP, activate plugin, create content
	@echo ""
	@echo "🎉 WUPZ DEVELOPMENT ENVIRONMENT READY!"
	@echo "========================================="
	@echo "🌐 WordPress: http://localhost:8080"
	@echo "🗄️  phpMyAdmin: http://localhost:8081"
	@echo "👤 Admin User: admin"
	@echo "🔑 Admin Password: admin123"
	@echo "🔌 Wupz Plugin: Activated and ready to test!"
	@echo ""
	@echo "🧪 To test the plugin:"
	@echo "   1. Go to http://localhost:8080/wp-admin"
	@echo "   2. Navigate to 'Wupz' in the admin menu"
	@echo "   3. Create your first backup!"

# Landing Page Commands
.PHONY: landing-dev landing-build landing-install landing-preview
landing-install: ## Install landing page dependencies
	@echo "Installing landing page dependencies..."
	cd landingpage && pnpm install

landing-dev: ## Start landing page development server
	@echo "Starting landing page development server..."
	cd landingpage && pnpm dev

landing-build: ## Build landing page for production
	@echo "Building landing page for production..."
	cd landingpage && pnpm build

landing-preview: ## Preview landing page production build
	@echo "Previewing landing page production build..."
	cd landingpage && pnpm preview 