#!/bin/sh

# Estos comandos SÍ deben estar en tu script.
php artisan migrate --force
php artisan db:seed --class=PermissionSeeder --force
php artisan permission:cache-reset
php artisan cache:clear
php artisan config:clear
php artisan storage:link
