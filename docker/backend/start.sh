#!/bin/bash
set -e

echo "🚀 Starting Backend..."

# Create necessary directories
mkdir -p /var/www/html/data/cache
mkdir -p /var/log/php
mkdir -p /var/log/supervisor

# Set permissions
chown -R www-data:www-data /var/www/html/data/cache
chmod -R 775 /var/www/html/data/cache

# Regenerate autoloader to ensure paths are correct for Docker environment
# The autoloader may have been generated from the project root with different paths.
# Production images already contain an optimized autoloader without dev classes.
if [ "${APP_ENV:-development}" != "production" ] && [ -f "/var/www/html/composer.json" ]; then
    echo "🔄 Regenerating autoloader for Docker environment..."
    cd /var/www/html
    if command -v composer >/dev/null 2>&1; then
        composer dump-autoload --optimize --no-interaction || echo "⚠️  Autoloader regeneration failed"
    else
        echo "⚠️  Composer not available, skipping autoloader regeneration"
    fi
fi

# Clear config cache if in development mode
if [ -f "/var/www/html/data/cache/config-cache.php" ]; then
    echo "🗑️  Clearing config cache..."
    rm -f /var/www/html/data/cache/config-cache.php
fi

# Wait for database to be ready
echo "⏳ Waiting for database..."
timeout=60
counter=0
while ! mysql -h"${DB_HOST:-database}" -P"${DB_PORT:-3306}" -u"${DB_USER:-app_user}" -p"${DB_PASSWORD:-app_password}" --skip-ssl -e "SELECT 1" >/dev/null 2>&1; do
    sleep 2
    counter=$((counter + 2))
    if [ $counter -ge $timeout ]; then
        echo "❌ Database connection timeout"
        exit 1
    fi
done
echo "✅ Database is ready"

# Run database migrations
echo "🗄️  Running database migrations..."
if [ -f "/var/www/html/bin/doctrine-migrations" ]; then
    chmod +x /var/www/html/bin/doctrine-migrations
    cd /var/www/html
    php bin/doctrine-migrations migrate --no-interaction || {
        echo "⚠️  Migration failed, but continuing..."
    }
    echo "✅ Migrations completed"
else
    echo "⚠️  Migrations binary not found, skipping..."
fi

echo "✅ Backend ready!"

# Start supervisord (manages PHP-FPM and Nginx)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

