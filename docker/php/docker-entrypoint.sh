#!/bin/bash
set -e

# Ejecutar composer install si no existe vendor
if [ ! -d "vendor" ]; then
    echo "Carpeta vendor no encontrada. Ejecutando composer install..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Ejecutar el comando original
exec "$@"

