<?php
// Este arquivo faz parte do plugin local_clemar para o Moodle.
//
// Declaração do Web Service e serviço pré-configurado.

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_clemar_get_completions' => [
        'classname'   => 'local_clemar\external\get_completions',
        'description' => 'Retorna as conclusões de cursos dentro de um período informado.',
        'type'        => 'read',
        'ajax'        => false,
    ],
];

$services = [
    'CLEMAR - Conclusões de Cursos' => [
        'functions'       => ['local_clemar_get_completions'],
        'restrictedusers' => 1,
        'enabled'         => 1,
        'shortname'       => 'local_clemar_service',
    ],
];
