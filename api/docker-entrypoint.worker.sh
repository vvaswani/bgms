#!/bin/sh
set -e

# Install dependencies
composer install --prefer-dist --no-progress --no-suggest --no-interaction

# Wait for DB to be ready
until bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do
  echo "Waiting for DB to be ready..."
  sleep 2
done

exec "$@"
