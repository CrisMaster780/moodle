<?php
// Apply the confirmed Innova name using Moodle's normal configuration API.
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');

global $DB;

$site = (object) [
    'id' => SITEID,
    'fullname' => 'Innova | Aula Virtual',
    'shortname' => 'Innova',
    'summary' => 'Espacio de aprendizaje en línea de Innova.',
    'summaryformat' => FORMAT_HTML,
];
$DB->update_record('course', $site);

// The single-column login keeps the site's name in Moodle's accessible form heading.
set_config('auth_instructions', '');

$sitescss = file_get_contents(__DIR__ . '/../docker/innova-site.scss');
if ($sitescss === false) {
    throw new RuntimeException('No se pudo leer el diseño general de Innova.');
}
$loginscss = file_get_contents(__DIR__ . '/../docker/innova-login.scss');
if ($loginscss === false) {
    throw new RuntimeException('No se pudo leer el diseño de acceso de Innova.');
}
set_config('scss', $sitescss . "\n" . $loginscss, 'theme_boost');

$helpbutton = file_get_contents(__DIR__ . '/../docker/innova-help.html');
if ($helpbutton === false) {
    throw new RuntimeException('No se pudo leer el botón de ayuda de Innova.');
}
$courseview = file_get_contents(__DIR__ . '/../docker/innova-course.html');
if ($courseview === false) {
    throw new RuntimeException('No se pudo leer el diseño de cursos de Innova.');
}
set_config('additionalhtmltopofbody', $helpbutton . "\n" . $courseview);

purge_all_caches();
echo "Nombre, diseño de acceso, cursos y ayuda por WhatsApp de Innova actualizados.\n";
