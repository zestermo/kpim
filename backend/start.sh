#!/bin/bash

# Run package discovery (skipped during build)
php artisan package:discover --ansi

# Clear old caches
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# Run migrations
php artisan migrate --force

# Seed the database (only if managers table is empty)
php artisan db:seed --force

# Cache config for production
php artisan config:cache
php artisan route:cache

# Start the server
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
