# Moodle local

Esta carpeta contiene Moodle 5.3 y una configuración para ejecutarlo con Docker.

1. Inicia el motor de Docker.
2. Ejecuta `./setup-moodle.sh` en esta carpeta.
3. Abre <http://localhost:8080> y usa las credenciales guardadas en
   `admin-credentials.txt`. Cambia la contraseña y el correo del administrador
   después del primer acceso.

Para detenerlo: `docker compose down`. Para volver a iniciarlo:
`docker compose up -d`. La base de datos y los archivos de los cursos se
conservan en volúmenes de Docker. Haz copias de ambos volúmenes antes de
actualizar Moodle.

La dirección local solo funciona en esta computadora. Para que los estudiantes
puedan acceder por internet se requiere un servidor siempre encendido, un
dominio y HTTPS. En ese servidor cambia `MOODLE_WWWROOT` en `.env` a la URL
final, configura un proxy HTTPS y no publiques directamente el puerto de la
base de datos. El puerto web de esta instalación está limitado a `127.0.0.1`.
Una instalación pública también requiere configurar correo saliente, copias de
seguridad y cuentas de estudiantes.
