<?php
// Create a local test student without storing a password in this script.
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');

global $DB, $CFG;

$username = 'prueba.innova';
if ($DB->record_exists('user', ['username' => $username, 'deleted' => 0])) {
    fwrite(STDERR, "La cuenta de prueba ya existe.\n");
    exit(1);
}

$password = getenv('TEST_USER_PASSWORD');
if (!$password) {
    fwrite(STDERR, "Falta TEST_USER_PASSWORD.\n");
    exit(1);
}

$user = (object) [
    'auth' => 'manual',
    'confirmed' => 1,
    'mnethostid' => $CFG->mnet_localhost_id,
    'username' => $username,
    'password' => $password,
    'firstname' => 'Estudiante',
    'lastname' => 'de Prueba',
    'email' => 'prueba.innova@example.invalid',
    'lang' => 'es',
];

$id = \core\user::create_user($user);
echo "Cuenta de prueba creada (ID $id).\n";
