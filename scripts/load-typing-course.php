<?php
// Introductory typing course for Innova; safe to run again.
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->libdir . '/completionlib.php');

global $DB, $CFG;
\core\session\manager::set_user(get_admin());

$course = $DB->get_record('course', ['shortname' => 'DACTI-INNOVA']);
if (!$course) {
    $course = create_course((object) [
        'category' => 1,
        'fullname' => 'Dactilografía: escribe con precisión',
        'shortname' => 'DACTI-INNOVA',
        'idnumber' => 'INNOVA-DACTI-01',
        'summary' => '<p>Aprende la posición de los dedos, practica todas las filas del teclado y registra tu progreso.</p>',
        'summaryformat' => FORMAT_HTML,
        'format' => 'topics',
        'numsections' => 4,
        'enablecompletion' => 1,
        'showcompletionconditions' => 1,
        'visible' => 1,
    ]);
    echo "Curso creado (ID {$course->id}).\n";
}

course_create_sections_if_missing($course, [1, 2, 3, 4]);
$sections = [
    1 => ['Primeros pasos y fila guía', 'Postura y posición de los dedos.'],
    2 => ['Fila superior e inferior', 'Más teclas sin perder la posición inicial.'],
    3 => ['Mayúsculas, tildes y números', 'Escritura cotidiana en español.'],
    4 => ['Reto final', 'Mide tu precisión y velocidad inicial.'],
];
foreach ($sections as $number => [$name, $summary]) {
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $number], '*', MUST_EXIST);
    $DB->update_record('course_sections', (object) [
        'id' => $section->id, 'name' => $name,
        'summary' => '<p>' . $summary . '</p>', 'summaryformat' => FORMAT_HTML,
    ]);
}

$pages = [
    [0, 'Cómo usar este curso',
        '<h2>Tu punto de partida</h2><p>Dedica entre 10 y 15 minutos diarios. Escribe sin mirar el teclado y prioriza la precisión. Descansa si notas tensión en manos, hombros o cuello.</p><ol><li>Lee la lección de cada tema.</li><li>Practica despacio.</li><li>Entrega las actividades y anota tiempo y errores.</li><li>Repite el reto final después de una semana.</li></ol><p>Meta inicial: escribir con comodidad y al menos un 90 % de precisión.</p>'],
    [1, 'Lección 1: postura y fila guía',
        '<h2>Preparación</h2><p>Siéntate con la espalda apoyada, hombros relajados y pies en el suelo. Coloca los índices sobre <strong>F</strong> y <strong>J</strong>; sus marcas táctiles ayudan a ubicarlas.</p><h3>Fila guía</h3><p>Mano izquierda: <strong>A S D F</strong>. Mano derecha: <strong>J K L Ñ</strong>. Después de cada letra, vuelve a la posición inicial.</p><p>Escribe cada línea cinco veces, sin mirar:</p><pre>asdf jklñ<br>fjdkslañ<br>sal sala las alas</pre><p>Cuenta los errores. Aumenta la velocidad solo cuando mantengas la precisión.</p>'],
    [2, 'Lección 2: filas superior e inferior',
        '<h2>Más teclas, mismo punto de retorno</h2><p>Desde la fila guía, alcanza la superior <strong>Q W E R T Y U I O P</strong> y la inferior <strong>Z X C V B N M</strong>. Mueve el dedo necesario y regresa a su tecla inicial.</p><p>Escribe cuatro veces:</p><pre>qwer tyui op<br>zxcv bnm<br>una ventana nueva<br>cada tema trae una meta</pre><p>Si necesitas mirar el teclado, reduce el ritmo y vuelve a ubicar F y J.</p>'],
    [3, 'Lección 3: mayúsculas, tildes y números',
        '<h2>Escritura cotidiana</h2><p>Usa <strong>Mayús</strong> para una letra mayúscula y vuelve a la fila guía. Practica las tildes y la ñ con la distribución de teclado que uses.</p><p>Escribe tres veces:</p><pre>Innova abre nuevas oportunidades.<br>Hoy practico dactilografía durante 15 minutos.<br>Mi meta es escribir 20 palabras con precisión.</pre><p>Comprueba mayúsculas, tildes, números, espacios y puntos.</p>'],
    [4, 'Reto final: mide tu progreso',
        '<h2>Prueba de cinco minutos</h2><p>Prepara un cronómetro. Copia el texto de abajo durante cinco minutos sin pegarlo. Al terminar, cuenta palabras correctas y errores. Divide las palabras correctas entre cinco para estimar palabras correctas por minuto.</p><blockquote>Aprender a escribir con soltura requiere calma y práctica constante. Cada día puedo mejorar mi precisión si mantengo una postura cómoda, miro la pantalla y dejo que mis dedos encuentren las teclas. Lo importante es avanzar de forma gradual y reconocer el progreso conseguido.</blockquote><p>Registra el tiempo, las palabras y los errores en la actividad final. Repite la prueba en una semana.</p>'],
];

foreach ($pages as [$sectionnumber, $name, $content]) {
    if ($DB->record_exists('page', ['course' => $course->id, 'name' => $name])) {
        continue;
    }
    [, , , , $data] = prepare_new_moduleinfo_data($course, 'page', $sectionnumber);
    $data->name = $name;
    $data->intro = '';
    $data->introformat = FORMAT_HTML;
    $data->content = $content;
    $data->contentformat = FORMAT_HTML;
    $data->display = 0;
    $data->printintro = 0;
    $data->printlastmodified = 0;
    $data->revision = 1;
    add_moduleinfo($data, $course);
    echo "Lección añadida: $name\n";
}

$assignments = [
    [1, 'Práctica 1: fila guía',
        '<p>Durante cinco minutos, escribe «sal sala las alas» tantas veces como puedas sin pegar texto. Entrega una línea de tu práctica e indica tiempo, palabras escritas y errores. Observa si tus dedos vuelven a F y J.</p>'],
    [2, 'Práctica 2: filas del teclado',
        '<p>Escribe durante cinco minutos «una ventana nueva / cada tema trae una meta». Entrega el texto escrito e indica tiempo, palabras y errores. Prioriza precisión sobre velocidad.</p>'],
    [3, 'Práctica 3: escritura cotidiana',
        '<p>Escribe durante cinco minutos un texto propio de al menos cinco frases. Incluye mayúsculas, tildes, la ñ y números. Entrega el texto e indica cuántos errores corregiste.</p>'],
    [4, 'Entrega del reto final',
        '<p>Realiza la prueba de la lección «Reto final» sin pegar texto. Entrega el texto escrito, palabras correctas, errores y tu estimación de palabras correctas por minuto.</p>'],
];

foreach ($assignments as [$sectionnumber, $name, $intro]) {
    if ($DB->record_exists('assign', ['course' => $course->id, 'name' => $name])) {
        continue;
    }
    [, , , , $data] = prepare_new_moduleinfo_data($course, 'assign', $sectionnumber);
    $data->name = $name;
    $data->intro = $intro;
    $data->introformat = FORMAT_HTML;
    $data->submissiondrafts = 0;
    $data->requiresubmissionstatement = 0;
    $data->sendnotifications = 0;
    $data->sendlatenotifications = 0;
    $data->duedate = 0;
    $data->cutoffdate = 0;
    $data->gradingduedate = 0;
    $data->allowsubmissionsfromdate = 0;
    $data->grade = 100;
    $data->teamsubmission = 0;
    $data->requireallteammemberssubmit = 0;
    $data->blindmarking = 0;
    $data->markingworkflow = 0;
    $data->markingallocation = 0;
    $data->assignsubmission_onlinetext_enabled = 1;
    $data->assignsubmission_file_enabled = 0;
    $data->assignsubmission_file_maxfiles = 1;
    $data->assignsubmission_file_maxsizebytes = 0;
    $data->assignsubmission_file_filetypes = '';
    $data->assignfeedback_comments_enabled = 1;
    add_moduleinfo($data, $course);
    echo "Actividad añadida: $name\n";
}

// Show real progress as learners read each class and submit each exercise.
$DB->set_field('course', 'enablecompletion', 1, ['id' => $course->id]);
$DB->set_field('course', 'showcompletionconditions', 1, ['id' => $course->id]);
$pagemoduleid = $DB->get_field('modules', 'id', ['name' => 'page'], MUST_EXIST);
$assignmoduleid = $DB->get_field('modules', 'id', ['name' => 'assign'], MUST_EXIST);
$DB->execute(
    'UPDATE {course_modules} SET completion = :completion, completionview = :completionview
      WHERE course = :course AND module = :module',
    ['completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionview' => COMPLETION_VIEW_REQUIRED,
        'course' => $course->id, 'module' => $pagemoduleid]
);
$DB->execute(
    'UPDATE {course_modules} SET completion = :completion, completionview = :completionview
      WHERE course = :course AND module = :module',
    ['completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionview' => COMPLETION_VIEW_NOT_REQUIRED,
        'course' => $course->id, 'module' => $assignmoduleid]
);
$DB->set_field('assign', 'completionsubmit', 1, ['course' => $course->id]);

$student = $DB->get_record('user', ['username' => 'prueba.innova', 'deleted' => 0], '*', MUST_EXIST);
$manual = enrol_get_plugin('manual');
$instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual']);
if (!$instance) {
    $instanceid = $manual->add_instance($course);
    $instance = $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);
}
$studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
if (!$DB->record_exists('user_enrolments', ['enrolid' => $instance->id, 'userid' => $student->id])) {
    $manual->enrol_user($instance, $student->id, $studentroleid);
    echo "Estudiante de prueba matriculado.\n";
}
rebuild_course_cache($course->id, true);
echo "Curso listo: {$CFG->wwwroot}/course/view.php?id={$course->id}\n";
