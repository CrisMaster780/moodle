# Publicar esta copia de Moodle

La configuración de `compose.prod.yaml` está preparada para un servidor Linux
con Docker Compose. Usa PostgreSQL 17, PHP 8.3, tareas programadas cada minuto
y Caddy para obtener y renovar HTTPS. La base de datos solo es accesible dentro
de la red de Docker. Moodle conserva sus archivos y datos en volúmenes.

## Antes de instalar

1. Contrata un servidor Linux con al menos 4 GB de RAM para empezar. Si hay
   muchos exámenes simultáneos o archivos grandes, probablemente necesitarás
   más recursos. Deja espacio suficiente para cursos y copias de seguridad.
2. Registra un dominio y crea un registro DNS `A` del subdominio elegido que
   apunte a la IP pública del servidor. Abre los puertos TCP 80 y 443 y UDP 443
   en el cortafuegos; conserva SSH restringido a tus direcciones de confianza.
3. Instala Docker Engine y el complemento Docker Compose en el servidor.
4. Copia esta carpeta al servidor, excluyendo `.git`, los archivos con
   contraseñas y `config.php`. Por ejemplo, desde esta computadora:

   ```sh
   rsync -a --exclude .git --exclude .env --exclude .env.production \
     --exclude admin-credentials.txt \
     --exclude admin-credentials.production.txt \
     --exclude test-credentials.txt --exclude config.php \
     ./ usuario@SERVIDOR:~/moodle/
   ```

## Instalación en el servidor

Desde la carpeta `~/moodle` del servidor:

```sh
cp .env.production.example .env.production
openssl rand -hex 24
```

Edita `.env.production`: usa tu dominio real en `MOODLE_DOMAIN` y el resultado
de `openssl` en `MOODLE_DB_PASSWORD`. Después ejecuta:

```sh
chmod 600 .env.production
chmod +x setup-production.sh
./setup-production.sh
```

La cuenta inicial aparece en `admin-credentials.production.txt`. Cambia su
contraseña y correo después del primer acceso. Comprueba que la página HTTPS
abre desde otra red y que `docker compose --env-file .env.production -f
compose.prod.yaml exec -T --user www-data web php admin/cli/checks.php` termina
sin errores.

Configura correo saliente para avisos y recuperación de contraseñas. Crea cursos
y cuentas de estudiantes antes de enviarles el enlace. Programa copias de
seguridad externas de la base de datos y del volumen `moodle_data`; ambas
partes son necesarias para restaurar el sitio.
