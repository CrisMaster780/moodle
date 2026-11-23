#!/bin/sh
set -eu

cd "$(dirname "$0")"
umask 077

if [ ! -f config.php ]; then
    cp docker/config.php.dist config.php
fi

if [ ! -f .env ]; then
    db_password=$(openssl rand -hex 24)
    printf 'MOODLE_DB_PASSWORD=%s\nMOODLE_WWWROOT=http://localhost:8080\n' "$db_password" > .env
fi

docker compose up -d --build --wait db web

installed=$(docker compose exec -T db psql -U moodle -d moodle -tAc \
    "SELECT to_regclass('public.mdl_config') IS NOT NULL" | tr -d '[:space:]')

if [ "$installed" != "t" ]; then
    admin_password="Aa1!$(openssl rand -hex 16)"
    docker compose exec -T --user www-data web php admin/cli/install_database.php \
        --agree-license \
        --lang=es \
        --adminuser=admin \
        --adminpass="$admin_password" \
        --adminemail=admin@example.org \
        --fullname='Aula Virtual' \
        --shortname='Aula Virtual'
    printf 'URL: http://localhost:8080\nUsuario: admin\nContraseña: %s\n' \
        "$admin_password" > admin-credentials.txt
fi

docker compose exec -T --user www-data web php scripts/apply-innova-identity.php
docker compose exec -T --user www-data web php admin/cli/purge_caches.php
docker compose up -d cron
printf '\nMoodle disponible en http://localhost:8080\n'
if [ -f admin-credentials.txt ]; then
    printf 'Credenciales: %s/admin-credentials.txt\n' "$PWD"
fi
