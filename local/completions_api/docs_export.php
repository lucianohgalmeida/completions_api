<?php
// Este arquivo faz parte do plugin local_completions_api para o Moodle.
//
// Exporta dados de cursos ou usuários em CSV ou XLSX.

require_once(__DIR__ . '/../../config.php');
require_login();
require_capability('local/completions_api:viewcompletions', context_system::instance());

$type   = required_param('type', PARAM_ALPHA);   // 'courses' ou 'users'.
$format = required_param('format', PARAM_ALPHA);  // 'csv' ou 'excel'.

if (!in_array($type, ['courses', 'users'])) {
    throw new moodle_exception('invalidparam', '', '', 'type');
}

if ($type === 'courses') {
    $filename = 'cursos_' . date('Ymd_His');
    $columns = [
        'fullname'           => get_string('docs_listing_course_name', 'local_completions_api'),
        'shortname'          => get_string('docs_listing_shortname', 'local_completions_api'),
        'category_name'      => get_string('docs_listing_category', 'local_completions_api'),
        'idnumber'           => get_string('docs_listing_idnumber', 'local_completions_api'),
        'total_completions'  => get_string('docs_listing_completions', 'local_completions_api'),
    ];

    $sql = "SELECT c.id, c.fullname, c.shortname, c.idnumber,
                   COALESCE(cat.name, '') AS category_name,
                   COUNT(cc.id) AS total_completions
              FROM {course} c
         LEFT JOIN {course_categories} cat ON cat.id = c.category
         LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.timecompleted IS NOT NULL
             WHERE c.id != :siteid
          GROUP BY c.id, c.fullname, c.shortname, c.idnumber, cat.name
          ORDER BY CASE WHEN c.idnumber = '' OR c.idnumber IS NULL THEN 0 ELSE 1 END,
                   c.fullname ASC";

    $rs = $DB->get_recordset_sql($sql, ['siteid' => SITEID]);

} else {
    $filename = 'usuarios_' . date('Ymd_His');
    $columns = [
        'firstname'          => get_string('docs_listing_firstname', 'local_completions_api'),
        'lastname'           => get_string('docs_listing_lastname', 'local_completions_api'),
        'email'              => get_string('docs_listing_email', 'local_completions_api'),
        'idnumber'           => get_string('docs_listing_idnumber', 'local_completions_api'),
        'total_completions'  => get_string('docs_listing_completions', 'local_completions_api'),
    ];

    $guestid = $DB->get_field('user', 'id', ['username' => 'guest']);
    $guestid = $guestid ? $guestid : 0;

    $sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.idnumber,
                   COUNT(cc.id) AS total_completions
              FROM {user} u
         LEFT JOIN {course_completions} cc ON cc.userid = u.id AND cc.timecompleted IS NOT NULL
             WHERE u.deleted = 0
               AND u.id != :guestid
          GROUP BY u.id, u.firstname, u.lastname, u.email, u.idnumber
          ORDER BY CASE WHEN u.idnumber = '' OR u.idnumber IS NULL THEN 0 ELSE 1 END,
                   u.firstname ASC, u.lastname ASC";

    $rs = $DB->get_recordset_sql($sql, ['guestid' => $guestid]);
}

\core\dataformat::download_data($filename, $format, $columns, $rs);
$rs->close();
