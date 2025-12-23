#!/bin/sh

# Estos comandos SÍ deben estar en tu script.
php artisan migrate --force
php artisan cache:clear
php artisan config:clear
