#!/bin/bash

# Wait for MySQL to be ready
echo "Waiting for MySQL..."
sleep 10

# Run migrations
php artisan migrate --force

# Clear and cache configs
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fix permissions
chmod -R 775 storage bootstrap/cache

# Start the server
php artisan serve --host=0.0.0.0 --port=$PORT
