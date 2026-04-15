#!/bin/bash
set -e

echo "🚀 Starting Webze Deployment..."

# Check if application is in maintenance mode
echo "🚧 Putting application into maintenance mode..."
php artisan down || true

echo "📥 Pulling latest changes from Git..."
git pull origin main

echo "📦 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "🎨 Building frontend assets..."
npm install
npm run build

echo "📦 Running database migrations..."
php artisan migrate --force

echo "⚡ Optimizing performance (Caching)..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "🔗 Ensuring storage link exists..."
php artisan storage:link

echo "🔄 Restarting background workers..."
php artisan queue:restart

# Bring application back up
echo "✅ Bringing application out of maintenance mode..."
php artisan up

echo "⭐⭐⭐ Deployment Complete! ⭐⭐⭐"
