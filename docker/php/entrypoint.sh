#!/usr/bin/env bash
set -e
cd /var/www/html
if [ ! -f vendor/autoload.php ]; then
  echo "Instalando dependencias Composer..."
  composer install --no-interaction --prefer-dist --ignore-platform-reqs
fi
exec docker-php-entrypoint "$@"
