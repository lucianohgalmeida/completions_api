<?php
// Este arquivo faz parte do plugin local_completions_api para o Moodle.
//
// Página de configurações e documentação do plugin.

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Página de configurações do plugin.
    $settings = new admin_settingpage(
        'local_completions_api_settings',
        get_string('pluginname', 'local_completions_api')
    );

    // Textarea para mapeamento de campos personalizados.
    $settings->add(new admin_setting_configtextarea(
        'local_completions_api/custom_fields',
        get_string('settings_custom_fields', 'local_completions_api'),
        get_string('settings_custom_fields_desc', 'local_completions_api'),
        "cidade|cidade\nestado|estado"
    ));

    $ADMIN->add('localplugins', $settings);

    // Link para a documentação interativa.
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_completions_api_docs',
        get_string('docs_title', 'local_completions_api'),
        new moodle_url('/local/completions_api/docs.php')
    ));
}
