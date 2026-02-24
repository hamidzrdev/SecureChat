#!/usr/bin/env sh
set -eu

cd /var/www/html

if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
fi

mkdir -p storage/logs bootstrap/cache database
touch database/database.sqlite
if [ "${FIX_PERMISSIONS:-false}" = "true" ]; then
    chmod -R ug+rw storage bootstrap/cache database || true
fi

if [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist
fi

if [ -z "${APP_KEY:-}" ]; then
    php artisan key:generate --force --no-interaction
fi

if [ "${ENABLE_VITE_DEV_SERVER:-false}" != "true" ] && [ -f public/hot ]; then
    rm -f public/hot
fi

if [ "${WAIT_FOR_DB:-true}" = "true" ]; then
    case "${DB_CONNECTION:-sqlite}" in
        mysql)
            ATTEMPTS=30
            until php -r 'try { new PDO("mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit(0);} catch (Throwable $e) { exit(1);}'; do
                ATTEMPTS=$((ATTEMPTS - 1))
                if [ "${ATTEMPTS}" -le 0 ]; then
                    echo "Database connection timeout (mysql)."
                    exit 1
                fi
                sleep 2
            done
            ;;
        pgsql)
            ATTEMPTS=30
            until php -r 'try { new PDO("pgsql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit(0);} catch (Throwable $e) { exit(1);}'; do
                ATTEMPTS=$((ATTEMPTS - 1))
                if [ "${ATTEMPTS}" -le 0 ]; then
                    echo "Database connection timeout (pgsql)."
                    exit 1
                fi
                sleep 2
            done
            ;;
    esac
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

exec "$@"
