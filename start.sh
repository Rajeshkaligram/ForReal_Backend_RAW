#!/bin/bash

# Wait for MySQL to be ready
echo "Waiting for MySQL..."
sleep 10

# Import SQL dump if it exists
if [ -f "db_rentasuit_php.sql" ]; then
    echo "Importing SQL dump..."
    mysql -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE < db_rentasuit_php.sql
fi

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
