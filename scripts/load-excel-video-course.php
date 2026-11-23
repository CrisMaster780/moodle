<?php
// Create a small video course and place the supplied sample in its first lesson.
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->libdir . '/completionlib.php');

global $DB, $CFG;

$source = $argv[1] ?? '';
if (!$source || !is_file($source) || !is_readable($source)) {
    throw new RuntimeException('Indica la ruta de un video MP4 legible como primer argumento.');
}
if (mime_content_type($source) !== 'video/mp4') {
    throw new RuntimeException('El archivo indicado debe ser un video MP4.');
}

\core\session\manager::set_user(get_admin());
$course = $DB->get_record('course', ['shortname' => 'EXCEL-INNOVA-DEMO']);
if (!$course) {
    $course = create_course((object) [
        'category' => 1,
        'fullname' => 'Excel: práctica con datos',
        'shortname' => 'EXCEL-INNOVA-DEMO',
        'idnumber' => 'INNOVA-EXCEL-DEMO',
        'summary' => '<p>Aprende con videoclases breves y ejercicios prácticos en Excel.</p>',
        'summaryformat' => FORMAT_HTML,
        'format' => 'topics',
        'numsections' => 1,
        'enablecompletion' => 1,
        'showcompletionconditions' => 1,
        'visible' => 1,
    ]);
}
course_create_sections_if_missing($course, [1]);
$section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 1], '*', MUST_EXIST);
$DB->update_record('course_sections', (object) [
    'id' => $section->id,
    'name' => 'Primeros pasos con Excel',
    'summary' => '<p>Mira la clase y realiza la práctica para comprobar lo aprendido.</p>',
    'summaryformat' => FORMAT_HTML,
]);

$lessonname = 'Lección 1: trabajo con datos en Excel';
$page = $DB->get_record('page', ['course' => $course->id, 'name' => $lessonname]);
if (!$page) {
    [, , , , $data] = prepare_new_moduleinfo_data($course, 'page', 1);
    $data->name = $lessonname;
    $data->intro = '';
    $data->introformat = FORMAT_HTML;
    $data->content = '<p>Observa cómo se trabaja con una tabla de datos en Excel. Después, completa la práctica del módulo.</p>';
    $data->contentformat = FORMAT_HTML;
    $data->display = 0;
    $data->printintro = 0;
    $data->printlastmodified = 0;
    $data->revision = 1;
    add_moduleinfo($data, $course);
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $lessonname], '*', MUST_EXIST);
}

$cm = get_coursemodule_from_instance('page', $page->id, $course->id, false, MUST_EXIST);
$context = context_module::instance($cm->id);
$fs = get_file_storage();
$filename = 'innova-excel-leccion-1-' . substr(sha1_file($source), 0, 12) . '.mp4';
if (!$fs->get_file($context->id, 'mod_page', 'content', 0, '/', $filename)) {
    $fs->create_file_from_pathname([
        'contextid' => $context->id,
        'component' => 'mod_page',
        'filearea' => 'content',
        'itemid' => 0,
        'filepath' => '/',
        'filename' => $filename,
        'mimetype' => 'video/mp4',
        'userid' => get_admin()->id,
    ], $source);
}

$start = '<!-- INNOVA_VIDEO_START -->';
$end = '<!-- INNOVA_VIDEO_END -->';
$block = $start . "\n" . '<div class="innova-lesson-video">' .
    '<video controls playsinline preload="metadata" width="960" height="540" aria-label="Videoclase de Excel">' .
    '<source src="@@PLUGINFILE@@/' . $filename . '" type="video/mp4">' .
    'Tu navegador no puede reproducir este video.' .
    '</video></div>' . "\n" . $end;
$oldcontent = $page->content;
$startpos = strpos($oldcontent, $start);
$endpos = strpos($oldcontent, $end);
if ($startpos !== false && $endpos !== false && $endpos > $startpos) {
    $newcontent = substr($oldcontent, 0, $startpos) . $block .
        substr($oldcontent, $endpos + strlen($end));
} else {
    $newcontent = $block . "\n" . $oldcontent;
}
if ($newcontent !== $oldcontent) {
    $DB->update_record('page', (object) [
        'id' => $page->id,
        'content' => $newcontent,
        'revision' => $page->revision + 1,
        'timemodified' => time(),
    ]);
}

$assignmentname = 'Práctica 1: conoce la tabla de datos';
if (!$DB->record_exists('assign', ['course' => $course->id, 'name' => $assignmentname])) {
    [, , , , $data] = prepare_new_moduleinfo_data($course, 'assign', 1);
    $data->name = $assignmentname;
    $data->intro = '<p>Después de ver el video, describe qué datos aparecen en las columnas de la tabla y escribe dos acciones que podrías realizar con esa información en Excel.</p>';
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
}

$DB->set_field('course', 'enablecompletion', 1, ['id' => $course->id]);
$DB->set_field('course', 'showcompletionconditions', 1, ['id' => $course->id]);
$assignmoduleid = $DB->get_field('modules', 'id', ['name' => 'assign'], MUST_EXIST);
$DB->set_field('course_modules', 'completion', COMPLETION_TRACKING_MANUAL, ['id' => $cm->id]);
$DB->set_field('course_modules', 'completionview', COMPLETION_VIEW_NOT_REQUIRED, ['id' => $cm->id]);
$DB->execute('UPDATE {course_modules} SET completion = :completion, completionview = :completionview
    WHERE course = :course AND module = :module', [
    'completion' => COMPLETION_TRACKING_AUTOMATIC,
    'completionview' => COMPLETION_VIEW_NOT_REQUIRED,
    'course' => $course->id,
    'module' => $assignmoduleid,
]);
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
}

rebuild_course_cache($course->id, true);
echo "Curso de Excel: {$CFG->wwwroot}/course/view.php?id={$course->id}\n";
echo "Videoclase: {$CFG->wwwroot}/mod/page/view.php?id={$cm->id}\n";
