<?php
// Este arquivo faz parte do plugin local_completions_api para o Moodle.
//
// Função externa que retorna as conclusões de cursos por período.

namespace local_completions_api\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;

class get_completions extends external_api {

    /**
     * Retorna os campos personalizados configurados no plugin.
     *
     * @return array [shortname => alias] ex: ['cidade' => 'cidade', 'estado' => 'estado']
     */
    public static function get_configured_fields() {
        $config = get_config('local_completions_api', 'custom_fields');
        if (empty($config)) {
            return [];
        }

        $fields = [];
        $lines = explode("\n", $config);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = explode('|', $line, 2);
            if (count($parts) === 2) {
                $shortname = trim($parts[0]);
                $alias = trim($parts[1]);
                if ($shortname !== '' && $alias !== '') {
                    $fields[$shortname] = $alias;
                }
            }
        }
        return $fields;
    }

    /**
     * Define os parâmetros de entrada da função.
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'data_inicial' => new external_value(PARAM_TEXT, 'Data de início no formato YYYY-MM-DD'),
            'data_final'   => new external_value(PARAM_TEXT, 'Data de fim no formato YYYY-MM-DD'),
            'pagina'       => new external_value(PARAM_INT, 'Número da página (padrão: 1)', VALUE_DEFAULT, 1),
            'registros_por_pagina' => new external_value(PARAM_INT, 'Registros por página (padrão: 100, máximo: 500)', VALUE_DEFAULT, 100),
        ]);
    }

    /**
     * Executa a consulta de conclusões de cursos.
     *
     * @param string $data_inicial Data de início (YYYY-MM-DD)
     * @param string $data_final Data de fim (YYYY-MM-DD)
     * @param int $pagina Número da página
     * @param int $registros_por_pagina Quantidade de registros por página
     * @return array Conclusões paginadas
     */
    public static function execute($data_inicial, $data_final, $pagina = 1, $registros_por_pagina = 100) {
        global $DB, $CFG;

        // Validar parâmetros recebidos.
        $params = self::validate_parameters(self::execute_parameters(), [
            'data_inicial'         => $data_inicial,
            'data_final'           => $data_final,
            'pagina'               => $pagina,
            'registros_por_pagina' => $registros_por_pagina,
        ]);

        // Validar contexto do sistema.
        $context = \context_system::instance();
        self::validate_context($context);

        // Verificar se o usuário possui a capability necessária.
        require_capability('local/completions_api:viewcompletions', $context);

        // Validar formato das datas (YYYY-MM-DD).
        $formato_data = '/^\d{4}-\d{2}-\d{2}$/';
        if (!preg_match($formato_data, $params['data_inicial'])) {
            throw new \invalid_parameter_exception(
                'O parâmetro data_inicial deve estar no formato YYYY-MM-DD.'
            );
        }
        if (!preg_match($formato_data, $params['data_final'])) {
            throw new \invalid_parameter_exception(
                'O parâmetro data_final deve estar no formato YYYY-MM-DD.'
            );
        }

        // Converter datas para timestamp Unix.
        $ts_inicio = strtotime($params['data_inicial'] . ' 00:00:00');
        $ts_fim    = strtotime($params['data_final'] . ' 23:59:59');

        if ($ts_inicio === false || $ts_fim === false) {
            throw new \invalid_parameter_exception(
                'Data inválida. Verifique se os valores informados são datas válidas no formato YYYY-MM-DD.'
            );
        }

        // Validar que data_inicial não é posterior a data_final.
        if ($ts_inicio > $ts_fim) {
            throw new \invalid_parameter_exception(
                'O parâmetro data_inicial não pode ser posterior a data_final.'
            );
        }

        // Validar intervalo máximo de 366 dias.
        $diferenca_dias = ($ts_fim - $ts_inicio) / 86400;
        if ($diferenca_dias > 366) {
            throw new \invalid_parameter_exception(
                'O intervalo entre data_inicial e data_final não pode exceder 366 dias.'
            );
        }

        // Validar paginação.
        $pagina_atual = $params['pagina'];
        if ($pagina_atual < 1) {
            throw new \invalid_parameter_exception(
                'O parâmetro pagina deve ser um inteiro maior ou igual a 1.'
            );
        }

        $por_pagina = $params['registros_por_pagina'];
        if ($por_pagina < 1 || $por_pagina > 500) {
            throw new \invalid_parameter_exception(
                'O parâmetro registros_por_pagina deve estar entre 1 e 500.'
            );
        }

        // Usar limite exclusivo para o fim do dia.
        $ts_fim_exclusivo = $ts_fim + 1;

        // Condições comuns para contagem e consulta.
        $where = "cc.timecompleted IS NOT NULL
                   AND cc.timecompleted >= :data_inicio
                   AND cc.timecompleted < :data_fim
                   AND u.idnumber != ''
                   AND c.idnumber != ''";

        $sql_params = [
            'data_inicio' => $ts_inicio,
            'data_fim'    => $ts_fim_exclusivo,
        ];

        // Contar total de registros.
        $sql_count = "SELECT COUNT(cc.id)
                        FROM {course_completions} cc
                        JOIN {user} u ON u.id = cc.userid AND u.deleted = 0
                        JOIN {course} c ON c.id = cc.course
                       WHERE $where";

        $total_registros = $DB->count_records_sql($sql_count, $sql_params);

        // Calcular paginação.
        $total_paginas = ($total_registros > 0) ? (int) ceil($total_registros / $por_pagina) : 0;
        $offset = ($pagina_atual - 1) * $por_pagina;

        // Consulta principal com paginação.
        $sql = "SELECT cc.id,
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
                 WHERE $where
              ORDER BY cc.timecompleted ASC";

        $registros = $DB->get_records_sql($sql, $sql_params, $offset, $por_pagina);

        // Obter timezone do servidor Moodle.
        $timezone = new \DateTimeZone($CFG->timezone !== '99' ? $CFG->timezone : date_default_timezone_get());

        // Verificar se o SimpleCertificate está instalado e carregar certificados.
        $dbman = $DB->get_manager();
        $has_simplecert = $dbman->table_exists('simplecertificate')
                          && $dbman->table_exists('simplecertificate_issues');

        $certificados = [];
        if ($has_simplecert && !empty($registros)) {
            $userids = [];
            $courseids = [];
            foreach ($registros as $reg) {
                $userids[$reg->userid] = $reg->userid;
                $courseids[$reg->courseid] = $reg->courseid;
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
                // Guardar o mais recente por userid+course.
                if (!isset($certificados[$key]) || $cert->timecreated > $certificados[$key]->timecreated) {
                    $certificados[$key] = $cert;
                }
            }
        }

        $cert_vazio = ['codigo' => '', 'nome' => '', 'data_emissao' => '', 'link' => ''];

        // Carregar campos personalizados configurados e cursos acessados em batch.
        $configured_fields = self::get_configured_fields();
        $campos_personalizados = []; // userid => [shortname => data]
        $cursos_acessados = [];      // userid => [array de cursos]

        if (!empty($registros)) {
            $userids = [];
            foreach ($registros as $reg) {
                $userids[$reg->userid] = $reg->userid;
            }

            // Batch: campos personalizados configurados.
            if (!empty($configured_fields)) {
                $shortnames = array_keys($configured_fields);
                list($cp_sql, $cp_params) = $DB->get_in_or_equal(array_values($userids), SQL_PARAMS_NAMED, 'cpuid');
                $cp_query = "SELECT uid.userid, uif.shortname, uid.data
                               FROM {user_info_data} uid
                               JOIN {user_info_field} uif ON uif.id = uid.fieldid
                              WHERE uid.userid $cp_sql";
                $cp_rs = $DB->get_recordset_sql($cp_query, $cp_params);
                foreach ($cp_rs as $cp) {
                    $sn = strtolower(trim($cp->shortname));
                    if (isset($configured_fields[$sn])) {
                        if (!isset($campos_personalizados[$cp->userid])) {
                            $campos_personalizados[$cp->userid] = [];
                        }
                        $campos_personalizados[$cp->userid][$sn] = $cp->data;
                    }
                }
                $cp_rs->close();
            }

            // Batch: cursos acessados (user_lastaccess).
            list($ca_sql, $ca_params) = $DB->get_in_or_equal(array_values($userids), SQL_PARAMS_NAMED, 'cauid');
            $ca_query = "SELECT ula.id, ula.userid, ula.courseid, ula.timeaccess,
                                c.fullname, c.idnumber
                           FROM {user_lastaccess} ula
                           JOIN {course} c ON c.id = ula.courseid
                          WHERE ula.userid $ca_sql";
            $ca_records = $DB->get_records_sql($ca_query, $ca_params);

            foreach ($ca_records as $ca) {
                if (!isset($cursos_acessados[$ca->userid])) {
                    $cursos_acessados[$ca->userid] = [];
                }
                $dt_ca = new \DateTime('@' . $ca->timeaccess);
                $dt_ca->setTimezone($timezone);
                $cursos_acessados[$ca->userid][] = [
                    'codigo_curso'  => $ca->idnumber !== null ? $ca->idnumber : '',
                    'nome_curso'    => $ca->fullname,
                    'ultimo_acesso' => $dt_ca->format('Y-m-d\TH:i:sP'),
                ];
            }
        }

        // Montar array de retorno no formato flat.
        $conclusoes = [];
        foreach ($registros as $registro) {
            $dt = new \DateTime('@' . $registro->timecompleted);
            $dt->setTimezone($timezone);

            // Montar bloco certificado.
            $cert_key = $registro->userid . '_' . $registro->courseid;
            if (isset($certificados[$cert_key])) {
                $cert = $certificados[$cert_key];
                $dt_cert = new \DateTime('@' . $cert->timecreated);
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
            $cp_usuario_data = isset($campos_personalizados[$registro->userid])
                ? $campos_personalizados[$registro->userid]
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
            if (!empty($registro->firstaccess)) {
                $dt_fa = new \DateTime('@' . $registro->firstaccess);
                $dt_fa->setTimezone($timezone);
                $primeiro_acesso = $dt_fa->format('Y-m-d\TH:i:sP');
            }
            $ultimo_acesso = '';
            if (!empty($registro->lastaccess)) {
                $dt_la = new \DateTime('@' . $registro->lastaccess);
                $dt_la->setTimezone($timezone);
                $ultimo_acesso = $dt_la->format('Y-m-d\TH:i:sP');
            }

            $conclusoes[] = [
                'codigo_curso'        => $registro->codigo_curso,
                'nome_completo_curso' => $registro->nome_completo_curso,
                'nome_breve_curso'    => $registro->nome_breve_curso,
                'categoria_curso'     => isset($registro->categoria_curso) ? $registro->categoria_curso : '',
                'matricula'           => $registro->matricula,
                'nome'                => $registro->nome,
                'sobrenome'           => $registro->sobrenome,
                'email'               => $registro->email,
                'status_conclusao'    => 'concluido',
                'data_conclusao'      => $dt->format('Y-m-d\TH:i:sP'),
                'id_usuario'          => (int) $registro->id_usuario,
                'primeiro_acesso'     => $primeiro_acesso,
                'ultimo_acesso'       => $ultimo_acesso,
                'campos_personalizados' => $campos_custom,
                'cursos_acessados'    => isset($cursos_acessados[$registro->userid])
                    ? $cursos_acessados[$registro->userid]
                    : [],
                'certificado'         => $bloco_cert,
            ];
        }

        return [
            'periodo' => [
                'data_inicial' => $params['data_inicial'],
                'data_final'   => $params['data_final'],
            ],
            'paginacao' => [
                'pagina_atual'         => $pagina_atual,
                'registros_por_pagina' => $por_pagina,
                'total_registros'      => $total_registros,
                'total_paginas'        => $total_paginas,
            ],
            'registros' => $conclusoes,
        ];
    }

    /**
     * Define a estrutura de retorno da função.
     */
    public static function execute_returns() {
        return new external_single_structure([
            'periodo' => new external_single_structure([
                'data_inicial' => new external_value(PARAM_TEXT, 'Data de início do período consultado'),
                'data_final'   => new external_value(PARAM_TEXT, 'Data de fim do período consultado'),
            ]),
            'paginacao' => new external_single_structure([
                'pagina_atual'         => new external_value(PARAM_INT, 'Página atual'),
                'registros_por_pagina' => new external_value(PARAM_INT, 'Registros por página'),
                'total_registros'      => new external_value(PARAM_INT, 'Total de registros no período'),
                'total_paginas'        => new external_value(PARAM_INT, 'Total de páginas'),
            ]),
            'registros' => new external_multiple_structure(
                new external_single_structure([
                    'codigo_curso'        => new external_value(PARAM_TEXT, 'Código do curso (idnumber)'),
                    'nome_completo_curso' => new external_value(PARAM_TEXT, 'Nome completo do curso'),
                    'nome_breve_curso'    => new external_value(PARAM_TEXT, 'Nome breve do curso'),
                    'categoria_curso'     => new external_value(PARAM_TEXT, 'Categoria do curso'),
                    'matricula'           => new external_value(PARAM_TEXT, 'Matrícula do colaborador (idnumber)'),
                    'nome'                => new external_value(PARAM_TEXT, 'Primeiro nome do colaborador'),
                    'sobrenome'           => new external_value(PARAM_TEXT, 'Sobrenome do colaborador'),
                    'email'               => new external_value(PARAM_TEXT, 'E-mail do colaborador'),
                    'status_conclusao'    => new external_value(PARAM_TEXT, 'Status da conclusão'),
                    'data_conclusao'      => new external_value(PARAM_TEXT, 'Data/hora da conclusão (ISO 8601)'),
                    'id_usuario'          => new external_value(PARAM_INT, 'ID do usuário no Moodle'),
                    'primeiro_acesso'     => new external_value(PARAM_TEXT, 'Primeiro acesso ao Moodle (ISO 8601)'),
                    'ultimo_acesso'       => new external_value(PARAM_TEXT, 'Último acesso ao Moodle (ISO 8601)'),
                    'campos_personalizados' => new external_multiple_structure(
                        new external_single_structure([
                            'campo' => new external_value(PARAM_TEXT, 'Nome do campo'),
                            'valor' => new external_value(PARAM_TEXT, 'Valor do campo'),
                        ])
                    ),
                    'cursos_acessados'    => new external_multiple_structure(
                        new external_single_structure([
                            'codigo_curso'  => new external_value(PARAM_TEXT, 'Código do curso (idnumber)'),
                            'nome_curso'    => new external_value(PARAM_TEXT, 'Nome completo do curso'),
                            'ultimo_acesso' => new external_value(PARAM_TEXT, 'Último acesso ao curso (ISO 8601)'),
                        ])
                    ),
                    'certificado' => new external_single_structure([
                        'codigo'       => new external_value(PARAM_TEXT, 'Código do certificado'),
                        'nome'         => new external_value(PARAM_TEXT, 'Nome do certificado'),
                        'data_emissao' => new external_value(PARAM_TEXT, 'Data de emissão ISO 8601'),
                        'link'         => new external_value(PARAM_TEXT, 'Link de verificação'),
                    ]),
                ])
            ),
        ]);
    }
}
