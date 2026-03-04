<?php
// Este arquivo faz parte do plugin local_clemar para o Moodle.
//
// Definição de capabilities customizadas.

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/clemar:viewcompletions' => [
        'captype'      => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [],
    ],
];
