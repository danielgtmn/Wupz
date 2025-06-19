# Wupz WordPress Plugin - Docker Development Environment

This Docker setup provides a complete WordPress development environment for testing and developing the Wupz backup plugin.

## 🚀 Quick Start

### Prerequisites

- Docker and Docker Compose installed
- Make (optional, for easier commands)

### Option 1: One-Command Setup (Recommended)

```bash
# Make the setup script executable and run it
chmod +x setup.sh
./setup.sh
```

### Option 2: Using Make Commands

```bash
# Complete setup with one command
make setup

# Or step by step:
make build          # Build and start containers
make wp-setup       # Install WordPress
make wp-activate-plugin  # Activate Wupz plugin
make wp-create-content   # Create test content
```

### Option 3: Manual Docker Compose

```bash
# Start containers
docker-compose up -d --build

# Wait for WordPress to start (about 30 seconds)
sleep 30

# Setup WordPress
docker-compose run --rm wp-cli wp core install \
    --url=http://localhost:8080 \
    --title="Wupz Test Site" \
    --admin_user=admin \
    --admin_password=admin123 \
    --admin_email=admin@test.local \
    --skip-email

# Activate plugin
docker-compose run --rm wp-cli wp plugin activate wupz
```

## 🌐 Access Points

| Service | URL | Credentials |
|---------|-----|-------------|
| **WordPress Admin** | http://localhost:8080/wp-admin | admin / admin123 |
| **WordPress Site** | http://localhost:8080 | - |
| **phpMyAdmin** | http://localhost:8081 | root / root_password |

## 🔧 Available Commands

### Make Commands (if Make is installed)

```bash
make help                 # Show all available commands
make build               # Build and start all containers
make up                  # Start existing containers
make down                # Stop all containers
make clean               # Remove containers and data (⚠️ destroys everything)
make restart             # Restart containers
make logs                # Show logs from all containers
make logs-wp             # Show WordPress logs only
make logs-mysql          # Show MySQL logs only
make status              # Show container status

# WordPress specific
make wp-setup            # Install WordPress
make wp-activate-plugin  # Activate Wupz plugin
make wp-deactivate-plugin # Deactivate Wupz plugin
make wp-plugin-list      # List all plugins
make wp-create-content   # Create test content
make wp-info             # Show WordPress info

# Shell access
make shell-wp            # Access WordPress container shell
make shell-wp-cli        # Access WP-CLI container
```

### Docker Compose Commands

```bash
# Container management
docker-compose up -d                    # Start containers in background
docker-compose down                     # Stop containers
docker-compose restart                  # Restart containers
docker-compose logs -f                  # Follow logs
docker-compose ps                       # Show container status

# WordPress management via WP-CLI
docker-compose run --rm wp-cli wp plugin list
docker-compose run --rm wp-cli wp post list
docker-compose run --rm wp-cli wp user list
docker-compose run --rm wp-cli wp option get siteurl

# Shell access
docker-compose exec wordpress bash      # Access WordPress container
docker-compose run --rm wp-cli bash     # Access WP-CLI container
```

## 📁 File Structure

```
/
├── docker-compose.yml           # Docker configuration
├── Makefile                     # Make commands
├── setup.sh                     # Quick setup script
├── README-DOCKER.md            # This file
└── wupz-plugin/                 # Plugin files (auto-mounted)
    ├── wupz.php                 # Main plugin file
    ├── includes/                # Core functionality
    │   ├── backup.php
    │   ├── schedule.php
    │   └── settings.php
    ├── templates/               # Admin templates
    │   └── admin-page.php
    └── assets/                  # CSS and JS files
        ├── style.css
        └── script.js
```

## 🧪 Testing the Plugin

1. **Access WordPress Admin**: http://localhost:8080/wp-admin
2. **Login** with `admin` / `admin123`
3. **Navigate to Wupz** in the admin menu
4. **Create a manual backup** to test functionality
5. **Check the Settings** page to configure schedules
6. **Verify backup files** are created (they should appear in the backup list)

### Plugin Features to Test

- ✅ **Manual Backup Creation**: Click "Create Backup Now"
- ✅ **Backup Download**: Download created backup files
- ✅ **Backup Deletion**: Delete old backup files
- ✅ **Schedule Configuration**: Set daily/weekly backup schedules
- ✅ **Settings Management**: Configure backup retention and options
- ✅ **WordPress Cron**: Test automatic scheduled backups

## 🗃️ Database Access

Use phpMyAdmin at http://localhost:8081 to inspect the database:

- **Host**: mysql
- **Username**: root  
- **Password**: root_password
- **Database**: wordpress

Useful tables to check:
- `wp_options` - Look for `wupz_settings` entries
- `wp_posts` - Your test content
- `wp_usermeta` - User settings

## 📊 Monitoring

### View Container Logs

```bash
# All containers
docker-compose logs -f

# Specific container
docker-compose logs -f wordpress
docker-compose logs -f mysql
```

### Check WordPress Debug Log

```bash
# Access WordPress container
docker-compose exec wordpress bash

# View debug log
tail -f /var/www/html/wp-content/debug.log
```

### Monitor Backup Directory

The backup directory is mapped to the WordPress container. To see backup files:

```bash
# List backup files
docker-compose exec wordpress ls -la /var/www/html/wp-content/wupz-backups/

# Check backup directory permissions
docker-compose exec wordpress ls -la /var/www/html/wp-content/
```

## 🔧 Development Workflow

1. **Edit Plugin Files**: Edit files in the `wupz-plugin/` directory
2. **Changes Auto-Reflect**: Files are mounted, so changes appear immediately
3. **Test in Browser**: Refresh WordPress admin to see changes
4. **Check Logs**: Use `make logs` to monitor for errors
5. **Restart if Needed**: Use `make restart` if container restart is needed

## ⚠️ Important Notes

### File Permissions

The WordPress container runs as `www-data`. If you encounter permission issues:

```bash
# Fix ownership (run from host)
sudo chown -R $USER:$USER wupz-plugin/

# Or fix inside container
docker-compose exec wordpress chown -R www-data:www-data /var/www/html/wp-content/plugins/wupz
```

### Backup Storage

- Backups are stored in the WordPress container's volume
- To persist backups between container rebuilds, they're stored in a Docker volume
- Use `make clean` to remove all data including backups

### Memory and Performance

The setup includes optimized PHP settings:
- Memory limit: 512M
- Max execution time: 300s
- Upload size limits: 100M

## 🧹 Cleanup

```bash
# Stop containers (keeps data)
make down

# Remove everything including data
make clean

# Or manually:
docker-compose down -v
docker volume prune -f
```

## 🐛 Troubleshooting

### WordPress not accessible?

```bash
# Check container status
docker-compose ps

# Check WordPress logs
docker-compose logs wordpress

# Restart containers
docker-compose restart
```

### Plugin not working?

```bash
# Check if plugin is activated
docker-compose run --rm wp-cli wp plugin list

# Check WordPress debug log
docker-compose exec wordpress tail -f /var/www/html/wp-content/debug.log

# Check plugin files are mounted
docker-compose exec wordpress ls -la /var/www/html/wp-content/plugins/wupz/
```

### Database connection issues?

```bash
# Check MySQL logs
docker-compose logs mysql

# Test database connection
docker-compose run --rm wp-cli wp db check
```

### Permission errors?

```bash
# Fix WordPress file permissions
docker-compose exec wordpress chown -R www-data:www-data /var/www/html
docker-compose exec wordpress find /var/www/html -type d -exec chmod 755 {} \;
docker-compose exec wordpress find /var/www/html -type f -exec chmod 644 {} \;
```

## 📞 Support

If you encounter issues:

1. Check the logs: `make logs`
2. Verify container status: `make status`
3. Try restarting: `make restart`
4. For complete reset: `make clean` then `make setup`

Happy testing! 🚀 