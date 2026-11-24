# Publicar esta copia de Moodle

## Instalación actual de Innova en Proxmox

La copia de producción está preparada en la VM 105 (`innova-moodle`, Debian 12),
con IP privada `192.168.18.66`. Dispone de 4 vCPU, 4 GB de RAM y un disco virtual
de 120 GB. `compose.proxmox.yaml` ejecuta PostgreSQL, Moodle y el cron de Moodle.
El servidor web escucha únicamente en `192.168.18.66:8080`; la base de datos no
publica ningún puerto. El proxy Nginx existente en `192.168.18.23` recibe las
peticiones públicas. El dominio previsto es `plataforma.innova.net.py`.

La instalación está publicada en <https://plataforma.innova.net.py/> con HTTPS.
El registro A de Cloudflare `plataforma` apunta a `38.79.43.92` en modo Solo DNS.
El certificado de Let's Encrypt se renueva con Certbot en el proxy Nginx. Para
comprobar el despliegue o repetirlo en otro entorno:

1. Comprobar que `plataforma.innova.net.py` resuelve a `38.79.43.92` y que
   HTTP redirige a HTTPS.
2. Comprobar el certificado en el proxy y que Nginx pasa el tráfico a
   `http://192.168.18.66:8080`. La configuración está en
   `deploy/nginx-plataforma.conf`. Validar con `nginx -t` antes de recargar.
3. Si se activa mantenimiento para una intervención, desactivarlo en la VM,
   desde `/srv/innova-moodle`, después de probar los cambios:

   ```sh
   sudo docker compose --env-file .env.production -f compose.proxmox.yaml \
     exec -T --user www-data web php admin/cli/maintenance.php --disable
   ```

4. Verificar desde fuera de la red el inicio de sesión, los cursos, un video,
   una actividad, el botón de ayuda y `admin/cli/checks.php`.

La VM tiene una copia en `/var/lib/vz/dump` del Proxmox y un trabajo de copia
diaria a las 05:00 que conserva las dos últimas. Estas copias comparten el
almacenamiento local del mismo servidor; añade copias en un disco o destino
externo antes de depender del servicio para las clases. Reserva en el DHCP la
IP `192.168.18.66` para la MAC `BC:24:11:28:4B:7F`, pues el proxy depende de
esa dirección. Configura el correo saliente de Moodle para recuperación de
contraseñas y avisos reales.

## Instalación genérica en otro servidor

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
