#!/bin/bash

# Run migrations
php artisan migrate --force

# Seed the database
php artisan db:seed --force

# Cache config for production
php artisan config:cache
php artisan route:cache

# Start the server
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}

