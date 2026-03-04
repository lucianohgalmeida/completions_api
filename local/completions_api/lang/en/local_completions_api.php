<?php
// Este arquivo faz parte do plugin local_completions_api para o Moodle.
//
// Strings em inglês (obrigatório pelo Moodle).

$string['pluginname'] = 'Course Completions API';
$string['completions_api:viewcompletions'] = 'View course completions via web service';

// Documentação.
$string['docs_title'] = 'Course Completions API - Documentation';
$string['docs_overview_title'] = 'Overview';
$string['docs_overview_text'] = 'This plugin provides a REST Web Service that returns course completions within a given date range. Each record includes course data, user data and completion date. Designed for integration with corporate systems.';
$string['docs_endpoint_title'] = 'Endpoint';
$string['docs_params_title'] = 'Parameters';
$string['docs_param_name'] = 'Parameter';
$string['docs_param_type'] = 'Type';
$string['docs_param_required'] = 'Required';
$string['docs_param_desc'] = 'Description';
$string['docs_param_wstoken'] = 'Authentication token provided by the administrator';
$string['docs_param_data_inicial'] = 'Start date in YYYY-MM-DD format';
$string['docs_param_data_final'] = 'End date in YYYY-MM-DD format';
$string['docs_param_pagina'] = 'Page number (default: 1)';
$string['docs_param_registros'] = 'Records per page (default: 100, max: 500)';
$string['docs_example_curl_title'] = 'Example: cURL call';
$string['docs_example_get_title'] = 'Example: GET URL';
$string['docs_example_response_title'] = 'Example: JSON response';
$string['docs_fields_title'] = 'Response fields';
$string['docs_field_name'] = 'Field';
$string['docs_field_codigo_curso'] = 'Course ID number (identification number)';
$string['docs_field_nome_completo'] = 'Full course name';
$string['docs_field_nome_breve'] = 'Short course name';
$string['docs_field_categoria'] = 'Course category name';
$string['docs_field_matricula'] = 'User ID number (enrollment/registration number)';
$string['docs_field_nome'] = 'User first name';
$string['docs_field_sobrenome'] = 'User last name';
$string['docs_field_email'] = 'User email';
$string['docs_field_status'] = 'Completion status (currently always "concluido")';
$string['docs_field_data_conclusao'] = 'Completion date and time in ISO 8601 format with timezone';
$string['docs_field_cert_codigo'] = 'Certificate code (UUID)';
$string['docs_field_cert_nome'] = 'Certificate name';
$string['docs_field_cert_data_emissao'] = 'Certificate issue date and time in ISO 8601';
$string['docs_field_cert_link'] = 'Certificate verification and download URL';
$string['docs_field_id_usuario'] = 'User ID in Moodle';
$string['docs_field_primeiro_acesso'] = 'First access to Moodle in ISO 8601 format with timezone';
$string['docs_field_ultimo_acesso'] = 'Last access to Moodle in ISO 8601 format with timezone';
$string['docs_field_cursos_acessados'] = 'Array of courses the user has accessed';
$string['docs_field_cursos_acessados_codigo'] = 'Course ID number (idnumber)';
$string['docs_field_cursos_acessados_nome'] = 'Full course name';
$string['docs_field_cursos_acessados_acesso'] = 'Last access to the course in ISO 8601 format with timezone';
$string['docs_errors_title'] = 'Validations and errors';
$string['docs_error_situation'] = 'Situation';
$string['docs_error_behavior'] = 'Behavior';
$string['docs_error_invalid_date'] = 'Invalid date format';
$string['docs_error_invalid_date_desc'] = 'Returns error — dates must be in YYYY-MM-DD format';
$string['docs_error_date_order'] = 'Start date after end date';
$string['docs_error_date_order_desc'] = 'Returns error — data_inicial cannot be after data_final';
$string['docs_error_max_interval'] = 'Interval exceeds 366 days';
$string['docs_error_max_interval_desc'] = 'Returns error — maximum allowed range is 366 days';
$string['docs_error_invalid_token'] = 'Invalid or missing token';
$string['docs_error_invalid_token_desc'] = 'Returns access denied error';
$string['docs_error_no_results'] = 'No completions in the period';
$string['docs_error_no_results_desc'] = 'Returns valid JSON with empty records array and total_registros = 0';
$string['docs_live_preview'] = 'Showing {$a->count} of {$a->total} real records found in the database (5 most recent):';
$string['docs_no_data'] = 'No completion records found with ID number filled in for both courses and users. The example below shows the structure of an empty response.';
$string['docs_notes_title'] = 'Important notes';
$string['docs_note_readonly'] = 'This API is read-only — it does not modify any data in Moodle.';
$string['docs_note_idnumber'] = 'Courses and users without an ID number (idnumber) are automatically excluded from results.';
$string['docs_note_deleted'] = 'Deleted users (deleted = 1) are automatically excluded from results.';
$string['docs_note_pagination'] = 'If total_paginas > 1, iterate by incrementing the pagina parameter.';

// Listing sections: Courses and Users.
$string['docs_listing_courses_title'] = 'All Courses';
$string['docs_listing_users_title'] = 'All Users';
$string['docs_listing_course_name'] = 'Course name';
$string['docs_listing_shortname'] = 'Short name';
$string['docs_listing_category'] = 'Category';
$string['docs_listing_idnumber'] = 'ID number';
$string['docs_listing_completions'] = 'Total completions';
$string['docs_listing_firstname'] = 'First name';
$string['docs_listing_lastname'] = 'Last name';
$string['docs_listing_email'] = 'Email';
$string['docs_listing_not_filled'] = 'Not filled';
$string['docs_listing_courses_summary'] = '{$a->missing} of {$a->total} courses without ID number';
$string['docs_listing_users_summary'] = '{$a->missing} of {$a->total} users without ID number';
$string['docs_listing_all_ok'] = 'All records have the ID number filled in.';
$string['docs_listing_has_pending'] = 'There are records without ID number. They will be excluded from the API results.';
$string['docs_export_csv'] = 'Download CSV';
$string['docs_export_xlsx'] = 'Download Excel';

// Settings.
$string['settings_custom_fields'] = 'Custom profile fields';
$string['settings_custom_fields_desc'] = 'Define which custom profile fields appear in the API response. One mapping per line, in the format: <code>shortname|alias</code><br>Where <strong>shortname</strong> is the field shortname in Moodle and <strong>alias</strong> is the name that will appear in the API response.<br><br>Example:<br><code>cidade|cidade<br>estado|estado<br>cpf|cpfuser</code><br><br>Leave empty to not return any custom fields.';

// Docs: custom fields.
$string['docs_field_campos_personalizados'] = 'Array of custom profile fields (configured in plugin settings)';
$string['docs_field_campos_personalizados_campo'] = 'Field alias as configured in plugin settings';
$string['docs_field_campos_personalizados_valor'] = 'Field value (empty string if not filled or field does not exist)';
