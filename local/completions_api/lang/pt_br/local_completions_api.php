<?php
// Este arquivo faz parte do plugin local_completions_api para o Moodle.
//
// Strings em português brasileiro.

$string['pluginname'] = 'API de Conclusões de Cursos';
$string['completions_api:viewcompletions'] = 'Visualizar conclusões de cursos via web service';

// Documentação.
$string['docs_title'] = 'API de Conclusões de Cursos - Documentação';
$string['docs_overview_title'] = 'Visão Geral';
$string['docs_overview_text'] = 'Este plugin disponibiliza um Web Service REST que retorna as conclusões de cursos dentro de um período informado. Cada registro inclui dados do curso, do usuário e da conclusão. Projetado para integração com sistemas corporativos.';
$string['docs_endpoint_title'] = 'Endpoint';
$string['docs_params_title'] = 'Parâmetros';
$string['docs_param_name'] = 'Parâmetro';
$string['docs_param_type'] = 'Tipo';
$string['docs_param_required'] = 'Obrigatório';
$string['docs_param_desc'] = 'Descrição';
$string['docs_param_wstoken'] = 'Token de autenticação fornecido pelo administrador';
$string['docs_param_data_inicial'] = 'Data de início no formato YYYY-MM-DD';
$string['docs_param_data_final'] = 'Data de fim no formato YYYY-MM-DD';
$string['docs_param_pagina'] = 'Número da página (padrão: 1)';
$string['docs_param_registros'] = 'Registros por página (padrão: 100, máximo: 500)';
$string['docs_example_curl_title'] = 'Exemplo de chamada: cURL';
$string['docs_example_get_title'] = 'Exemplo de chamada: URL GET';
$string['docs_example_response_title'] = 'Exemplo de resposta JSON';
$string['docs_fields_title'] = 'Campos da resposta';
$string['docs_field_name'] = 'Campo';
$string['docs_field_codigo_curso'] = 'Código do curso (Número de identificação do curso)';
$string['docs_field_nome_completo'] = 'Nome completo do curso';
$string['docs_field_nome_breve'] = 'Nome breve do curso';
$string['docs_field_categoria'] = 'Nome da categoria do curso';
$string['docs_field_matricula'] = 'Matrícula do colaborador (Número de identificação do usuário)';
$string['docs_field_nome'] = 'Primeiro nome do colaborador';
$string['docs_field_sobrenome'] = 'Sobrenome do colaborador';
$string['docs_field_email'] = 'E-mail do colaborador';
$string['docs_field_status'] = 'Status da conclusão (atualmente sempre "concluido")';
$string['docs_field_data_conclusao'] = 'Data e hora da conclusão em formato ISO 8601 com timezone';
$string['docs_field_cert_codigo'] = 'Código do certificado (UUID)';
$string['docs_field_cert_nome'] = 'Nome do certificado';
$string['docs_field_cert_data_emissao'] = 'Data e hora de emissão do certificado em ISO 8601';
$string['docs_field_cert_link'] = 'URL de verificação e download do certificado';
$string['docs_field_id_usuario'] = 'ID do usuário no Moodle';
$string['docs_field_primeiro_acesso'] = 'Primeiro acesso ao Moodle em formato ISO 8601 com timezone';
$string['docs_field_ultimo_acesso'] = 'Último acesso ao Moodle em formato ISO 8601 com timezone';
$string['docs_field_cursos_acessados'] = 'Array de cursos que o usuário já acessou';
$string['docs_field_cursos_acessados_codigo'] = 'Código do curso (Número de identificação)';
$string['docs_field_cursos_acessados_nome'] = 'Nome completo do curso';
$string['docs_field_cursos_acessados_acesso'] = 'Último acesso ao curso em formato ISO 8601 com timezone';
$string['docs_errors_title'] = 'Validações e erros';
$string['docs_error_situation'] = 'Situação';
$string['docs_error_behavior'] = 'Comportamento';
$string['docs_error_invalid_date'] = 'Formato de data inválido';
$string['docs_error_invalid_date_desc'] = 'Retorna erro — as datas devem estar no formato YYYY-MM-DD';
$string['docs_error_date_order'] = 'Data inicial posterior à data final';
$string['docs_error_date_order_desc'] = 'Retorna erro — data_inicial não pode ser posterior a data_final';
$string['docs_error_max_interval'] = 'Intervalo excede 366 dias';
$string['docs_error_max_interval_desc'] = 'Retorna erro — o intervalo máximo permitido é de 366 dias';
$string['docs_error_invalid_token'] = 'Token inválido ou ausente';
$string['docs_error_invalid_token_desc'] = 'Retorna erro de acesso negado';
$string['docs_error_no_results'] = 'Sem conclusões no período';
$string['docs_error_no_results_desc'] = 'Retorna JSON válido com array registros vazio e total_registros = 0';
$string['docs_live_preview'] = 'Exibindo {$a->count} dos {$a->total} registros reais encontrados no banco (5 mais recentes):';
$string['docs_no_data'] = 'Nenhum registro de conclusão encontrado com idnumber preenchido em cursos e usuários. O exemplo abaixo mostra a estrutura de uma resposta vazia.';
$string['docs_notes_title'] = 'Observações importantes';
$string['docs_note_readonly'] = 'Esta API é somente leitura — não altera nenhum dado no Moodle.';
$string['docs_note_idnumber'] = 'Cursos e usuários sem Número de identificação (idnumber) são automaticamente excluídos dos resultados.';
$string['docs_note_deleted'] = 'Usuários excluídos (deleted = 1) são automaticamente excluídos dos resultados.';
$string['docs_note_pagination'] = 'Se total_paginas > 1, itere incrementando o parâmetro pagina.';

// Seções de listagem: Cursos e Usuários.
$string['docs_listing_courses_title'] = 'Todos os Cursos';
$string['docs_listing_users_title'] = 'Todos os Usuários';
$string['docs_listing_course_name'] = 'Nome do curso';
$string['docs_listing_shortname'] = 'Nome breve';
$string['docs_listing_category'] = 'Categoria';
$string['docs_listing_idnumber'] = 'Número de identificação';
$string['docs_listing_completions'] = 'Total de conclusões';
$string['docs_listing_firstname'] = 'Nome';
$string['docs_listing_lastname'] = 'Sobrenome';
$string['docs_listing_email'] = 'E-mail';
$string['docs_listing_not_filled'] = 'Não preenchido';
$string['docs_listing_courses_summary'] = '{$a->missing} de {$a->total} cursos sem Número de Identificação';
$string['docs_listing_users_summary'] = '{$a->missing} de {$a->total} usuários sem Número de Identificação';
$string['docs_listing_all_ok'] = 'Todos os registros possuem o Número de Identificação preenchido.';
$string['docs_listing_has_pending'] = 'Existem registros sem Número de Identificação. Eles serão excluídos dos resultados da API.';
$string['docs_export_csv'] = 'Download CSV';
$string['docs_export_xlsx'] = 'Download Excel';

// Configurações.
$string['settings_custom_fields'] = 'Campos personalizados do perfil';
$string['settings_custom_fields_desc'] = 'Defina quais campos personalizados do perfil aparecem na resposta da API. Um mapeamento por linha, no formato: <code>shortname|alias</code><br>Onde <strong>shortname</strong> é o nome curto do campo no Moodle e <strong>alias</strong> é o nome que aparecerá na resposta da API.<br><br>Exemplo:<br><code>cidade|cidade<br>estado|estado<br>cpf|cpfuser</code><br><br>Deixe vazio para não retornar campos personalizados.';

// Docs: campos personalizados.
$string['docs_field_campos_personalizados'] = 'Array de campos personalizados do perfil (configurados nas configurações do plugin)';
$string['docs_field_campos_personalizados_campo'] = 'Alias do campo conforme configurado nas configurações do plugin';
$string['docs_field_campos_personalizados_valor'] = 'Valor do campo (string vazia se não preenchido ou campo não existe)';
