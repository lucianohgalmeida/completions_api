<?php
// Este arquivo faz parte do plugin local_completions_api para o Moodle.
//
// Definição de capabilities customizadas.

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/completions_api:viewcompletions' => [
        'captype'      => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [],
    ],
];
