#!/bin/sh

# Navega al directorio raíz del proyecto (la tilde ~ funciona en SSH)
cd ~/laravel-project

# 1. Trae los últimos cambios (Hostinger a veces lo hace, pero es un seguro)
git pull origin main

# 2. Instala/actualiza las dependencias de Composer
composer install --no-dev --prefer-dist

# 3. Ejecuta migraciones (actualiza la estructura de la base de datos)
php artisan migrate --force

# 4. Limpia la caché (CRUCIAL para ver cambios)
php artisan cache:clear
php artisan config:clear
php artisan view:clear

echo "Despliegue automático de Laravel completado."
