#!/bin/sh
set -eu

cd "$(dirname "$0")"
umask 077

if [ ! -f .env.production ]; then
    echo 'Crea .env.production desde .env.production.example y configura el dominio y una contraseña aleatoria.' >&2
    exit 1
fi

if grep -Eq 'aula\.example\.com|replace-with-long-random-password' .env.production; then
    echo 'Sustituye los valores de ejemplo en .env.production.' >&2
    exit 1
fi

if [ ! -f config.php ]; then
    cp docker/config.php.dist config.php
fi

compose() {
    docker compose --env-file .env.production -f compose.prod.yaml "$@"
}

compose up -d --build --wait db web

installed=$(compose exec -T db psql -U moodle -d moodle -tAc \
    "SELECT to_regclass('public.mdl_config') IS NOT NULL" | tr -d '[:space:]')

if [ "$installed" != "t" ]; then
    admin_password="Aa1!$(openssl rand -hex 16)"
    compose exec -T --user www-data web php admin/cli/install_database.php \
        --agree-license \
        --lang=es \
        --adminuser=admin \
        --adminpass="$admin_password" \
        --adminemail=admin@example.org \
        --fullname='Aula Virtual' \
        --shortname='Aula Virtual'
    printf 'Usuario: admin\nContraseña: %s\n' "$admin_password" \
        > admin-credentials.production.txt
fi

compose exec -T --user www-data web php scripts/apply-innova-identity.php
compose exec -T --user www-data web php admin/cli/purge_caches.php
compose up -d proxy cron
printf 'Instalación finalizada. Revisa el sitio HTTPS y cambia el correo y la contraseña del administrador.\n'
