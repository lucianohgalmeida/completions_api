<?php
// Este arquivo faz parte do plugin local_completions_api para o Moodle.
//
// Página de documentação da API com exemplos de chamada.

require_once(__DIR__ . '/../../config.php');
require_login();
require_capability('local/completions_api:viewcompletions', context_system::instance());

// Parâmetros de paginação das tabelas de listagem.
$courses_page = optional_param('cpage', 0, PARAM_INT);
$users_page   = optional_param('upage', 0, PARAM_INT);
$perpage      = 20;

$PAGE->set_url(new moodle_url('/local/completions_api/docs.php', [
    'cpage' => $courses_page,
    'upage' => $users_page,
]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('docs_title', 'local_completions_api'));
$PAGE->set_heading(get_string('docs_title', 'local_completions_api'));
$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();

$baseurl = $CFG->wwwroot . '/webservice/rest/server.php';
$functionname = 'local_completions_api_get_completions';

// Montar conteúdo da documentação.
$html = '';

// --- Seção: Visão Geral ---
$html .= html_writer::tag('h3', get_string('docs_overview_title', 'local_completions_api'));
$html .= html_writer::tag('p', get_string('docs_overview_text', 'local_completions_api'));

// --- Seção: Endpoint ---
$html .= html_writer::tag('h3', get_string('docs_endpoint_title', 'local_completions_api'));
$html .= html_writer::tag('pre', $baseurl, ['style' => 'background:#f4f4f4;padding:12px;border-radius:4px;overflow-x:auto;']);

// --- Seção: Parâmetros ---
$html .= html_writer::tag('h3', get_string('docs_params_title', 'local_completions_api'));

$table = new html_table();
$table->head = [
    get_string('docs_param_name', 'local_completions_api'),
    get_string('docs_param_type', 'local_completions_api'),
    get_string('docs_param_required', 'local_completions_api'),
    get_string('docs_param_desc', 'local_completions_api'),
];
$table->data = [
    ['wstoken',             'string', get_string('yes'), get_string('docs_param_wstoken', 'local_completions_api')],
    ['wsfunction',          'string', get_string('yes'), $functionname],
    ['moodlewsrestformat',  'string', get_string('yes'), 'json'],
    ['data_inicial',        'string', get_string('yes'), get_string('docs_param_data_inicial', 'local_completions_api')],
    ['data_final',          'string', get_string('yes'), get_string('docs_param_data_final', 'local_completions_api')],
    ['pagina',              'int',    get_string('no'),  get_string('docs_param_pagina', 'local_completions_api')],
    ['registros_por_pagina','int',    get_string('no'),  get_string('docs_param_registros', 'local_completions_api')],
];
$table->attributes['class'] = 'generaltable';
$html .= html_writer::table($table);

// --- Seção: Exemplo cURL ---
$html .= html_writer::tag('h3', get_string('docs_example_curl_title', 'local_completions_api'));

$curl_example = 'curl -X POST "' . $baseurl . '" \\' . "\n"
    . '  -d "wstoken=SEU_TOKEN" \\' . "\n"
    . '  -d "wsfunction=' . $functionname . '" \\' . "\n"
    . '  -d "moodlewsrestformat=json" \\' . "\n"
    . '  -d "data_inicial=2025-11-01" \\' . "\n"
    . '  -d "data_final=2025-11-30"';

$html .= html_writer::tag('pre', htmlspecialchars($curl_example),
    ['style' => 'background:#1e1e1e;color:#d4d4d4;padding:16px;border-radius:4px;overflow-x:auto;font-size:13px;']);

// --- Seção: Exemplo URL GET ---
$html .= html_writer::tag('h3', get_string('docs_example_get_title', 'local_completions_api'));

$get_example = $baseurl
    . '?wstoken=SEU_TOKEN'
    . '&wsfunction=' . $functionname
    . '&moodlewsrestformat=json'
    . '&data_inicial=2025-11-01'
    . '&data_final=2025-11-30';

$html .= html_writer::tag('pre', htmlspecialchars($get_example),
    ['style' => 'background:#f4f4f4;padding:12px;border-radius:4px;overflow-x:auto;font-size:13px;']);

// --- Seção: Exemplo de Resposta JSON (dados reais) ---
$html .= html_writer::tag('h3', get_string('docs_example_response_title', 'local_completions_api'));

// Buscar os 5 registros de conclusão mais recentes com idnumber preenchido.
$preview_sql = "SELECT cc.id,
                       c.idnumber    AS codigo_curso,
                       c.fullname    AS nome_completo_curso,
                       c.shortname   AS nome_breve_curso,
                       cat.name      AS categoria_curso,
                       u.idnumber    AS matricula,
                       u.firstname   AS nome,
                       u.lastname    AS sobrenome,
                       u.email,
                       u.id          AS id_usuario,
                       u.firstaccess,
                       u.lastaccess,
                       cc.timecompleted,
                       cc.userid,
                       cc.course AS courseid
                  FROM {course_completions} cc
                  JOIN {user} u ON u.id = cc.userid AND u.deleted = 0
                  JOIN {course} c ON c.id = cc.course
             LEFT JOIN {course_categories} cat ON cat.id = c.category
                 WHERE cc.timecompleted IS NOT NULL
                   AND u.idnumber != ''
                   AND c.idnumber != ''
              ORDER BY cc.timecompleted DESC";

$preview_records = $DB->get_records_sql($preview_sql, [], 0, 5);
$preview_total = $DB->count_records_sql(
    "SELECT COUNT(cc.id)
       FROM {course_completions} cc
       JOIN {user} u ON u.id = cc.userid AND u.deleted = 0
       JOIN {course} c ON c.id = cc.course
      WHERE cc.timecompleted IS NOT NULL
        AND u.idnumber != ''
        AND c.idnumber != ''"
);

$timezone = new DateTimeZone($CFG->timezone !== '99' ? $CFG->timezone : date_default_timezone_get());

// Verificar se SimpleCertificate está instalado para o preview.
$dbman = $DB->get_manager();
$has_simplecert = $dbman->table_exists('simplecertificate')
                  && $dbman->table_exists('simplecertificate_issues');

$preview_certs = [];
if ($has_simplecert && !empty($preview_records)) {
    $userids = [];
    $courseids = [];
    foreach ($preview_records as $rec) {
        $userids[$rec->userid] = $rec->userid;
        $courseids[$rec->courseid] = $rec->courseid;
    }

    list($user_sql, $user_params) = $DB->get_in_or_equal(array_values($userids), SQL_PARAMS_NAMED, 'uid');
    list($course_sql, $course_params) = $DB->get_in_or_equal(array_values($courseids), SQL_PARAMS_NAMED, 'cid');

    $cert_sql = "SELECT si.id, si.code, si.timecreated, sc.name, sc.course, si.userid
                   FROM {simplecertificate_issues} si
                   JOIN {simplecertificate} sc ON sc.id = si.certificateid
                  WHERE si.userid $user_sql
                    AND sc.course $course_sql";

    $cert_records = $DB->get_records_sql($cert_sql, array_merge($user_params, $course_params));

    foreach ($cert_records as $cert) {
        $key = $cert->userid . '_' . $cert->course;
        if (!isset($preview_certs[$key]) || $cert->timecreated > $preview_certs[$key]->timecreated) {
            $preview_certs[$key] = $cert;
        }
    }
}

$cert_vazio = ['codigo' => '', 'nome' => '', 'data_emissao' => '', 'link' => ''];

// Carregar configuração de campos personalizados.
require_once($CFG->dirroot . '/local/completions_api/classes/external/get_completions.php');
$configured_fields = \local_completions_api\external\get_completions::get_configured_fields();

// Batch: campos personalizados configurados para preview.
$preview_campos_personalizados = [];
$preview_cursos_acessados = [];
if (!empty($preview_records)) {
    $preview_userids = [];
    foreach ($preview_records as $rec) {
        $preview_userids[$rec->userid] = $rec->userid;
    }

    if (!empty($configured_fields)) {
        list($cp_sql, $cp_params) = $DB->get_in_or_equal(array_values($preview_userids), SQL_PARAMS_NAMED, 'cpuid');
        $cp_query = "SELECT uid.userid, uif.shortname, uid.data
                       FROM {user_info_data} uid
                       JOIN {user_info_field} uif ON uif.id = uid.fieldid
                      WHERE uid.userid $cp_sql";
        $cp_rs = $DB->get_recordset_sql($cp_query, $cp_params);
        foreach ($cp_rs as $cp) {
            $sn = strtolower(trim($cp->shortname));
            if (isset($configured_fields[$sn])) {
                if (!isset($preview_campos_personalizados[$cp->userid])) {
                    $preview_campos_personalizados[$cp->userid] = [];
                }
                $preview_campos_personalizados[$cp->userid][$sn] = $cp->data;
            }
        }
        $cp_rs->close();
    }

    // Batch: cursos acessados (user_lastaccess) para preview.
    list($ca_sql, $ca_params) = $DB->get_in_or_equal(array_values($preview_userids), SQL_PARAMS_NAMED, 'cauid');
    $ca_query = "SELECT ula.id, ula.userid, ula.courseid, ula.timeaccess,
                        c.fullname, c.idnumber
                   FROM {user_lastaccess} ula
                   JOIN {course} c ON c.id = ula.courseid
                  WHERE ula.userid $ca_sql";
    $ca_records = $DB->get_records_sql($ca_query, $ca_params);

    foreach ($ca_records as $ca) {
        if (!isset($preview_cursos_acessados[$ca->userid])) {
            $preview_cursos_acessados[$ca->userid] = [];
        }
        $dt_ca = new DateTime('@' . $ca->timeaccess);
        $dt_ca->setTimezone($timezone);
        $preview_cursos_acessados[$ca->userid][] = [
            'codigo_curso'  => $ca->idnumber !== null ? $ca->idnumber : '',
            'nome_curso'    => $ca->fullname,
            'ultimo_acesso' => $dt_ca->format('Y-m-d\TH:i:sP'),
        ];
    }
}

$preview_registros = [];
$data_mais_antiga = null;
$data_mais_recente = null;
foreach ($preview_records as $rec) {
    $dt = new DateTime('@' . $rec->timecompleted);
    $dt->setTimezone($timezone);

    if ($data_mais_antiga === null || $rec->timecompleted < $data_mais_antiga) {
        $data_mais_antiga = $rec->timecompleted;
    }
    if ($data_mais_recente === null || $rec->timecompleted > $data_mais_recente) {
        $data_mais_recente = $rec->timecompleted;
    }

    // Montar bloco certificado para preview.
    $cert_key = $rec->userid . '_' . $rec->courseid;

    if (isset($preview_certs[$cert_key])) {
        $cert = $preview_certs[$cert_key];
        $dt_cert = new DateTime('@' . $cert->timecreated);
        $dt_cert->setTimezone($timezone);
        $bloco_cert = [
            'codigo'       => $cert->code,
            'nome'         => $cert->name,
            'data_emissao' => $dt_cert->format('Y-m-d\TH:i:sP'),
            'link'         => $CFG->wwwroot . '/mod/simplecertificate/verify.php?code=' . $cert->code,
        ];
    } else {
        $bloco_cert = $cert_vazio;
    }

    // Campos personalizados do usuario.
    $cp_usuario_data = isset($preview_campos_personalizados[$rec->userid])
        ? $preview_campos_personalizados[$rec->userid]
        : [];
    $campos_custom = [];
    foreach ($configured_fields as $shortname => $alias) {
        $campos_custom[] = [
            'campo' => $alias,
            'valor' => isset($cp_usuario_data[$shortname]) ? $cp_usuario_data[$shortname] : '',
        ];
    }

    // Primeiro acesso e ultimo acesso.
    $primeiro_acesso = '';
    if (!empty($rec->firstaccess)) {
        $dt_fa = new DateTime('@' . $rec->firstaccess);
        $dt_fa->setTimezone($timezone);
        $primeiro_acesso = $dt_fa->format('Y-m-d\TH:i:sP');
    }
    $ultimo_acesso_user = '';
    if (!empty($rec->lastaccess)) {
        $dt_la = new DateTime('@' . $rec->lastaccess);
        $dt_la->setTimezone($timezone);
        $ultimo_acesso_user = $dt_la->format('Y-m-d\TH:i:sP');
    }

    $preview_registros[] = [
        'codigo_curso'        => $rec->codigo_curso,
        'nome_completo_curso' => $rec->nome_completo_curso,
        'nome_breve_curso'    => $rec->nome_breve_curso,
        'categoria_curso'     => isset($rec->categoria_curso) ? $rec->categoria_curso : '',
        'matricula'           => $rec->matricula,
        'nome'                => $rec->nome,
        'sobrenome'           => $rec->sobrenome,
        'email'               => $rec->email,
        'status_conclusao'    => 'concluido',
        'data_conclusao'      => $dt->format('Y-m-d\TH:i:sP'),
        'id_usuario'          => (int) $rec->id_usuario,
        'primeiro_acesso'     => $primeiro_acesso,
        'ultimo_acesso'       => $ultimo_acesso_user,
        'campos_personalizados' => $campos_custom,
        'cursos_acessados'    => isset($preview_cursos_acessados[$rec->userid])
            ? $preview_cursos_acessados[$rec->userid]
            : [],
        'certificado'         => $bloco_cert,
    ];
}

if (!empty($preview_registros)) {
    $dt_inicio = new DateTime('@' . $data_mais_antiga);
    $dt_inicio->setTimezone($timezone);
    $dt_fim = new DateTime('@' . $data_mais_recente);
    $dt_fim->setTimezone($timezone);

    $preview_response = [
        'periodo' => [
            'data_inicial' => $dt_inicio->format('Y-m-d'),
            'data_final'   => $dt_fim->format('Y-m-d'),
        ],
        'paginacao' => [
            'pagina_atual'         => 1,
            'registros_por_pagina' => 100,
            'total_registros'      => (int) $preview_total,
            'total_paginas'        => (int) ceil($preview_total / 100),
        ],
        'registros' => $preview_registros,
    ];

    $html .= html_writer::tag('p', get_string('docs_live_preview', 'local_completions_api',
        (object) ['count' => count($preview_registros), 'total' => $preview_total]));

    $json_example = json_encode($preview_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} else {
    $html .= html_writer::div(
        get_string('docs_no_data', 'local_completions_api'),
        'alert alert-warning'
    );

    $json_example = json_encode([
        'periodo' => [
            'data_inicial' => date('Y-m-01'),
            'data_final'   => date('Y-m-t'),
        ],
        'paginacao' => [
            'pagina_atual'         => 1,
            'registros_por_pagina' => 100,
            'total_registros'      => 0,
            'total_paginas'        => 0,
        ],
        'registros' => [],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

$html .= html_writer::tag('pre', htmlspecialchars($json_example),
    ['style' => 'background:#1e1e1e;color:#d4d4d4;padding:16px;border-radius:4px;overflow-x:auto;font-size:13px;']);

// --- Seção: Campos da Resposta ---
$html .= html_writer::tag('h3', get_string('docs_fields_title', 'local_completions_api'));

$fields_table = new html_table();
$fields_table->head = [
    get_string('docs_field_name', 'local_completions_api'),
    get_string('docs_param_type', 'local_completions_api'),
    get_string('docs_param_desc', 'local_completions_api'),
];
$fields_table->data = [
    ['codigo_curso',        'string', get_string('docs_field_codigo_curso', 'local_completions_api')],
    ['nome_completo_curso', 'string', get_string('docs_field_nome_completo', 'local_completions_api')],
    ['nome_breve_curso',    'string', get_string('docs_field_nome_breve', 'local_completions_api')],
    ['categoria_curso',     'string', get_string('docs_field_categoria', 'local_completions_api')],
    ['matricula',           'string', get_string('docs_field_matricula', 'local_completions_api')],
    ['nome',                'string', get_string('docs_field_nome', 'local_completions_api')],
    ['sobrenome',           'string', get_string('docs_field_sobrenome', 'local_completions_api')],
    ['email',               'string', get_string('docs_field_email', 'local_completions_api')],
    ['status_conclusao',    'string', get_string('docs_field_status', 'local_completions_api')],
    ['data_conclusao',      'string', get_string('docs_field_data_conclusao', 'local_completions_api')],
    ['id_usuario',               'int',    get_string('docs_field_id_usuario', 'local_completions_api')],
    ['primeiro_acesso',          'string', get_string('docs_field_primeiro_acesso', 'local_completions_api')],
    ['ultimo_acesso',            'string', get_string('docs_field_ultimo_acesso', 'local_completions_api')],
    ['campos_personalizados',    'array',  get_string('docs_field_campos_personalizados', 'local_completions_api')],
    ['campos_personalizados[].campo', 'string', get_string('docs_field_campos_personalizados_campo', 'local_completions_api')],
    ['campos_personalizados[].valor', 'string', get_string('docs_field_campos_personalizados_valor', 'local_completions_api')],
    ['cursos_acessados',         'array',  get_string('docs_field_cursos_acessados', 'local_completions_api')],
    ['cursos_acessados[].codigo_curso',  'string', get_string('docs_field_cursos_acessados_codigo', 'local_completions_api')],
    ['cursos_acessados[].nome_curso',    'string', get_string('docs_field_cursos_acessados_nome', 'local_completions_api')],
    ['cursos_acessados[].ultimo_acesso', 'string', get_string('docs_field_cursos_acessados_acesso', 'local_completions_api')],
    ['certificado.codigo',       'string', get_string('docs_field_cert_codigo', 'local_completions_api')],
    ['certificado.nome',         'string', get_string('docs_field_cert_nome', 'local_completions_api')],
    ['certificado.data_emissao', 'string', get_string('docs_field_cert_data_emissao', 'local_completions_api')],
    ['certificado.link',         'string', get_string('docs_field_cert_link', 'local_completions_api')],
];
$fields_table->attributes['class'] = 'generaltable';
$html .= html_writer::table($fields_table);

// --- Seção: Validações e Erros ---
$html .= html_writer::tag('h3', get_string('docs_errors_title', 'local_completions_api'));

$errors_table = new html_table();
$errors_table->head = [
    get_string('docs_error_situation', 'local_completions_api'),
    get_string('docs_error_behavior', 'local_completions_api'),
];
$errors_table->data = [
    [get_string('docs_error_invalid_date', 'local_completions_api'),
     get_string('docs_error_invalid_date_desc', 'local_completions_api')],
    [get_string('docs_error_date_order', 'local_completions_api'),
     get_string('docs_error_date_order_desc', 'local_completions_api')],
    [get_string('docs_error_max_interval', 'local_completions_api'),
     get_string('docs_error_max_interval_desc', 'local_completions_api')],
    [get_string('docs_error_invalid_token', 'local_completions_api'),
     get_string('docs_error_invalid_token_desc', 'local_completions_api')],
    [get_string('docs_error_no_results', 'local_completions_api'),
     get_string('docs_error_no_results_desc', 'local_completions_api')],
];
$errors_table->attributes['class'] = 'generaltable';
$html .= html_writer::table($errors_table);

// --- Seção: Notas ---
$html .= html_writer::tag('h3', get_string('docs_notes_title', 'local_completions_api'));
$html .= html_writer::alist([
    get_string('docs_note_readonly', 'local_completions_api'),
    get_string('docs_note_idnumber', 'local_completions_api'),
    get_string('docs_note_deleted', 'local_completions_api'),
    get_string('docs_note_pagination', 'local_completions_api'),
], null, 'ul');

echo html_writer::div($html, '', ['style' => 'max-width:900px;']);

// =====================================================================
// Seção: Listagem de Todos os Cursos e Usuários (status do idnumber)
// =====================================================================
$html2 = '';

// --- Contar cursos ---
$courses_count_sql = "SELECT COUNT(c.id)
                        FROM {course} c
                       WHERE c.id != :siteid";
$total_courses = $DB->count_records_sql($courses_count_sql, ['siteid' => SITEID]);

$courses_sql = "SELECT c.id, c.fullname, c.shortname, c.idnumber,
                       COALESCE(cat.name, '') AS category_name,
                       COUNT(cc.id) AS total_completions
                  FROM {course} c
             LEFT JOIN {course_categories} cat ON cat.id = c.category
             LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.timecompleted IS NOT NULL
                 WHERE c.id != :siteid
              GROUP BY c.id, c.fullname, c.shortname, c.idnumber, cat.name
              ORDER BY CASE WHEN c.idnumber = '' OR c.idnumber IS NULL THEN 0 ELSE 1 END,
                       c.fullname ASC";
$courses = $DB->get_records_sql($courses_sql, ['siteid' => SITEID], $courses_page * $perpage, $perpage);

// Contar cursos sem idnumber (para o resumo, query leve).
$missing_courses = $DB->count_records_sql(
    "SELECT COUNT(c.id) FROM {course} c WHERE c.id != :siteid AND (c.idnumber = '' OR c.idnumber IS NULL)",
    ['siteid' => SITEID]
);

// --- Contar usuários ---
$guestid = $DB->get_field('user', 'id', ['username' => 'guest']);
$guestid = $guestid ? $guestid : 0;

$total_users = $DB->count_records_sql(
    "SELECT COUNT(u.id) FROM {user} u WHERE u.deleted = 0 AND u.id != :guestid",
    ['guestid' => $guestid]
);

$users_sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.idnumber,
                     COUNT(cc.id) AS total_completions
                FROM {user} u
           LEFT JOIN {course_completions} cc ON cc.userid = u.id AND cc.timecompleted IS NOT NULL
               WHERE u.deleted = 0
                 AND u.id != :guestid
            GROUP BY u.id, u.firstname, u.lastname, u.email, u.idnumber
            ORDER BY CASE WHEN u.idnumber = '' OR u.idnumber IS NULL THEN 0 ELSE 1 END,
                     u.firstname ASC, u.lastname ASC";
$users = $DB->get_records_sql($users_sql, ['guestid' => $guestid], $users_page * $perpage, $perpage);

$missing_users = $DB->count_records_sql(
    "SELECT COUNT(u.id) FROM {user} u WHERE u.deleted = 0 AND u.id != :guestid AND (u.idnumber = '' OR u.idnumber IS NULL)",
    ['guestid' => $guestid]
);

// --- Resumo visual ---
$html2 .= html_writer::tag('hr', '');
$html2 .= html_writer::tag('h2', get_string('docs_listing_courses_title', 'local_completions_api')
    . ' &amp; ' . get_string('docs_listing_users_title', 'local_completions_api'));

$has_pending = ($missing_courses > 0 || $missing_users > 0);
$alert_class = $has_pending ? 'alert alert-warning' : 'alert alert-success';

$summary_lines = [];
$summary_lines[] = get_string('docs_listing_courses_summary', 'local_completions_api',
    (object) ['missing' => $missing_courses, 'total' => $total_courses]);
$summary_lines[] = get_string('docs_listing_users_summary', 'local_completions_api',
    (object) ['missing' => $missing_users, 'total' => $total_users]);

$summary_text = implode('<br>', $summary_lines);
$summary_text .= '<br><br><strong>' . ($has_pending
    ? get_string('docs_listing_has_pending', 'local_completions_api')
    : get_string('docs_listing_all_ok', 'local_completions_api')) . '</strong>';

$html2 .= html_writer::div($summary_text, $alert_class);

// URL base para links de exportação.
$export_baseurl = new moodle_url('/local/completions_api/docs_export.php');

// --- Tabela de Cursos ---
$html2 .= html_writer::tag('h3', get_string('docs_listing_courses_title', 'local_completions_api'));

// Botões de download para cursos.
$csv_courses_url  = new moodle_url($export_baseurl, ['type' => 'courses', 'format' => 'csv']);
$xlsx_courses_url = new moodle_url($export_baseurl, ['type' => 'courses', 'format' => 'excel']);
$html2 .= html_writer::div(
    html_writer::link($csv_courses_url,
        get_string('docs_export_csv', 'local_completions_api'),
        ['class' => 'btn btn-sm btn-outline-secondary mr-2', 'style' => 'margin-right:8px;'])
    . html_writer::link($xlsx_courses_url,
        get_string('docs_export_xlsx', 'local_completions_api'),
        ['class' => 'btn btn-sm btn-outline-secondary']),
    '', ['style' => 'margin-bottom:10px;']
);

$courses_table = new html_table();
$courses_table->head = [
    get_string('docs_listing_course_name', 'local_completions_api'),
    get_string('docs_listing_shortname', 'local_completions_api'),
    get_string('docs_listing_category', 'local_completions_api'),
    get_string('docs_listing_idnumber', 'local_completions_api'),
    get_string('docs_listing_completions', 'local_completions_api'),
];
$courses_table->data = [];
foreach ($courses as $c) {
    $idnumber_display = trim($c->idnumber) !== ''
        ? s($c->idnumber)
        : html_writer::tag('span',
            get_string('docs_listing_not_filled', 'local_completions_api'),
            ['style' => 'color:#dc3545;font-weight:bold;']);

    $courses_table->data[] = [
        s($c->fullname),
        s($c->shortname),
        s($c->category_name),
        $idnumber_display,
        (int) $c->total_completions,
    ];
}
$courses_table->attributes['class'] = 'generaltable';
$html2 .= html_writer::table($courses_table);

// Paginação de cursos.
$courses_pagingbar = new paging_bar($total_courses, $courses_page, $perpage,
    new moodle_url('/local/completions_api/docs.php', ['upage' => $users_page]),
    'cpage');
$html2 .= $OUTPUT->render($courses_pagingbar);

// --- Tabela de Usuários ---
$html2 .= html_writer::tag('h3', get_string('docs_listing_users_title', 'local_completions_api'));

// Botões de download para usuários.
$csv_users_url  = new moodle_url($export_baseurl, ['type' => 'users', 'format' => 'csv']);
$xlsx_users_url = new moodle_url($export_baseurl, ['type' => 'users', 'format' => 'excel']);
$html2 .= html_writer::div(
    html_writer::link($csv_users_url,
        get_string('docs_export_csv', 'local_completions_api'),
        ['class' => 'btn btn-sm btn-outline-secondary mr-2', 'style' => 'margin-right:8px;'])
    . html_writer::link($xlsx_users_url,
        get_string('docs_export_xlsx', 'local_completions_api'),
        ['class' => 'btn btn-sm btn-outline-secondary']),
    '', ['style' => 'margin-bottom:10px;']
);

$users_table = new html_table();
$users_table->head = [
    get_string('docs_listing_firstname', 'local_completions_api'),
    get_string('docs_listing_lastname', 'local_completions_api'),
    get_string('docs_listing_email', 'local_completions_api'),
    get_string('docs_listing_idnumber', 'local_completions_api'),
    get_string('docs_listing_completions', 'local_completions_api'),
];
$users_table->data = [];
foreach ($users as $u) {
    $idnumber_display = trim($u->idnumber) !== ''
        ? s($u->idnumber)
        : html_writer::tag('span',
            get_string('docs_listing_not_filled', 'local_completions_api'),
            ['style' => 'color:#dc3545;font-weight:bold;']);

    $users_table->data[] = [
        s($u->firstname),
        s($u->lastname),
        s($u->email),
        $idnumber_display,
        (int) $u->total_completions,
    ];
}
$users_table->attributes['class'] = 'generaltable';
$html2 .= html_writer::table($users_table);

// Paginação de usuários.
$users_pagingbar = new paging_bar($total_users, $users_page, $perpage,
    new moodle_url('/local/completions_api/docs.php', ['cpage' => $courses_page]),
    'upage');
$html2 .= $OUTPUT->render($users_pagingbar);

echo html_writer::div($html2, '', ['style' => 'max-width:900px;']);

echo $OUTPUT->footer();
