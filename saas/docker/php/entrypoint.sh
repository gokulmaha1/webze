#!/bin/bash
set -e

echo "🚀 Starting Webze application..."

# Wait for MySQL to be ready
until php artisan db:monitor --databases=mysql 2>/dev/null; do
    echo "⏳ Waiting for database..."
    sleep 3
done

echo "✅ Database connected!"

# Run migrations
echo "📦 Running migrations..."
php artisan migrate --force

# Cache for production performance
echo "⚡ Caching config, routes, and views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Create storage symlink if missing
if [ ! -L public/storage ]; then
    echo "🔗 Creating storage symlink..."
    php artisan storage:link
fi

echo "✅ Webze is ready!"

exec "$@"
