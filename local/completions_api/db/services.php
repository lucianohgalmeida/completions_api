<?php
// Este arquivo faz parte do plugin local_completions_api para o Moodle.
//
// Declaração do Web Service e serviço pré-configurado.

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_completions_api_get_completions' => [
        'classname'   => 'local_completions_api\external\get_completions',
        'description' => 'Retorna as conclusões de cursos dentro de um período informado.',
        'type'        => 'read',
        'ajax'        => false,
    ],
];

$services = [
    'API de Conclusões de Cursos' => [
        'functions'       => ['local_completions_api_get_completions'],
        'restrictedusers' => 1,
        'enabled'         => 1,
        'shortname'       => 'completions_api_service',
    ],
];
