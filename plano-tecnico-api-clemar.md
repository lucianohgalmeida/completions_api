# Plano Técnico — API de Conclusões de Cursos Moodle para CLEMAR

**Projeto:** Integração Moodle → Sistema Corporativo CLEMAR  
**Fornecedora:** TechEduConnect  
**Plataforma:** Moodle 4.1.2 · PHP 7.4  
**Data:** Março 2026  
**Classificação:** Documento técnico-consultivo  

---

## 1. Recomendação Executiva

A CLEMAR precisa consultar, de forma automatizada e por período, quais colaboradores concluíram quais cursos no Moodle. A TI da empresa confirmou capacidade de consumir dados via API REST com retorno JSON. A solução recomendada é a criação de um **plugin local com Web Service customizado**, registrado na arquitetura oficial do Moodle, expondo uma única função que retorna conclusões de cursos filtradas por período.

Essa abordagem é a mais segura, sustentável e compatível com o Moodle 4.1.2, pois utiliza exclusivamente a camada de Web Services nativa, respeita o modelo de permissões do Moodle, permite controle granular de acesso via token e usuário técnico, e pode ser mantida como produto/serviço pela TechEduConnect sem comprometer a estabilidade da plataforma.

A implementação deve ser precedida por um trabalho de saneamento de identificadores (campo `idnumber` de cursos e usuários), que é pré-requisito absoluto para que a integração produza dados cruzáveis com o sistema corporativo da CLEMAR.

---

## 2. Arquitetura Recomendada

### 2.1 Análise comparativa de abordagens

#### Opção A — Funções nativas do Moodle (Web Services built-in)

O Moodle oferece funções nativas como `core_completion_get_activities_completion_status` e `core_course_get_contents`, mas nenhuma delas atende ao requisito específico de listar conclusões de cursos por período com os campos exigidos (categoria, idnumber do curso e do usuário, dados consolidados). Seria necessário orquestrar múltiplas chamadas no lado do consumidor (TI CLEMAR), o que gera acoplamento, fragilidade e complexidade desnecessária para a TI.

**Veredicto:** Inadequada. Não existe função nativa que resolva o caso de uso completo em uma única chamada.

#### Opção B — Plugin local com Web Service customizado (RECOMENDADA)

Criar um plugin do tipo `local` (ex.: `local_clemar_integration`) que registre um Web Service próprio com uma função específica. Essa função consulta internamente as tabelas de conclusão do Moodle, cruza com dados de curso, usuário e categoria, aplica filtro por período e retorna um JSON estruturado e padronizado.

**Veredicto:** Melhor abordagem. Usa a infraestrutura oficial de Web Services do Moodle, permite controle total sobre o formato de resposta, segue o ciclo de vida de plugins Moodle (instalação, atualização, desinstalação), é auditável e mantém a TI CLEMAR desacoplada da estrutura interna do Moodle.

#### Opção C — Relatório interno sem API

Criar um relatório acessível pelo painel do Moodle (plugin do tipo `report` ou bloco), com exportação CSV/Excel. Resolveria o RH, mas não atende a TI, que precisa de consumo automatizado sem login interativo.

**Veredicto:** Complementar, mas insuficiente como solução principal.

#### Opção D — Acesso direto ao banco de dados

Expor uma view ou permitir queries diretas ao banco do Moodle. Extremamente arriscado: acopla o consumidor à estrutura interna do banco, quebra em qualquer atualização de versão, viola boas práticas de segurança e impossibilita controle de acesso granular.

**Veredicto:** Inaceitável em ambiente de produção corporativo.

#### Opção E — Endpoint externo fora do padrão Web Service

Criar um script PHP avulso (ex.: `api.php` na raiz do Moodle) que responda diretamente. Não utiliza a camada de autenticação e autorização do Moodle, não é rastreável pelo sistema de Web Services, não aparece na administração, e cria um ponto de entrada não governado.

**Veredicto:** Antipadrão. Risco de segurança e manutenção.

### 2.2 Decisão de arquitetura

**Abordagem escolhida: Plugin local com Web Service customizado (Opção B).**

Justificativas consolidadas:

- **Segurança:** Herda toda a camada de autenticação/autorização do Moodle (token, capabilities, restrição por serviço).
- **Manutenção:** Segue o ciclo de vida padrão de plugins Moodle — versionado, atualizável, desinstalável.
- **Compatibilidade:** Totalmente compatível com Moodle 4.1.2 e PHP 7.4 — utiliza apenas APIs estáveis e documentadas.
- **Clareza para a TI:** Um único endpoint, um único token, resposta JSON padronizada.
- **Estabilidade:** Não depende de estruturas internas do banco; utiliza as APIs de conclusão de curso do Moodle.

---

## 3. Escopo do Plugin

### 3.1 Identificação

- **Tipo:** Plugin local (`local`)
- **Nome sugerido:** `local_clemar_integration`
- **Diretório:** `local/clemar_integration/`

### 3.2 Finalidade

Expor um Web Service customizado que permite à TI da CLEMAR consultar, por período de conclusão, todos os registros de cursos concluídos por colaboradores, retornando dados estruturados em JSON com identificadores cruzáveis (matrícula e código do curso).

### 3.3 Responsabilidade funcional

O plugin é responsável por:

- Registrar uma função externa (`external function`) no sistema de Web Services do Moodle.
- Receber parâmetros de período (data inicial e data final).
- Consultar os registros de conclusão de curso (`course_completions`) dentro do período informado.
- Cruzar os dados com informações de curso (nome, nome breve, idnumber, categoria) e de usuário (nome, sobrenome, email, idnumber).
- Filtrar apenas registros com data de conclusão efetivamente preenchida.
- Retornar os dados no formato JSON especificado no contrato da API.
- Respeitar as capabilities de acesso configuradas.

### 3.4 O que entra no escopo

- Função de Web Service para consulta de conclusões por período.
- Validação de parâmetros de entrada (datas).
- Formatação padronizada da resposta JSON.
- Registro de capability específica para controle de acesso.
- Arquivo de definição de serviço para vinculação ao Web Service do Moodle.
- Strings de idioma (lang).
- Arquivo de versão e db/access.php.

### 3.5 O que fica fora do escopo

- Interface gráfica (telas, formulários, dashboards).
- Saneamento automático de idnumber de cursos e usuários (processo manual/pré-requisito).
- Emissão ou gestão de certificados (responsabilidade do `mod_simplecertificate`).
- Integração reversa (receber dados do sistema CLEMAR para o Moodle).
- Relatórios visuais no Moodle.
- Gestão de matrículas ou inscrições.

### 3.6 Encaixe na arquitetura Moodle

O plugin se registra como plugin local e utiliza a External API do Moodle (framework `external_api` / `external_function_parameters`). Ele declara suas funções em `db/services.php`, define capabilities em `db/access.php`, e implementa a lógica de consulta em uma classe que estende `external_api`. O Moodle gerencia autenticação, autorização e serialização da resposta.

### 3.7 Nota sobre o SimpleCertificate

O plugin `mod_simplecertificate` é responsável pela emissão de certificados e não deve ser confundido com a lógica de conclusão de curso. A conclusão de curso no Moodle é gerenciada pelo subsistema de `course completion` (`mdl_course_completions`), que é independente da emissão de certificados. O plugin da API consultará os dados de conclusão de curso, não os dados de certificados. Se futuramente for necessário incluir dados de certificação (como data de emissão do certificado ou código do certificado), o escopo do plugin poderá ser estendido, mas isso não é parte do requisito atual.

---

## 4. Contrato Funcional da API

### 4.1 Identificação do serviço

- **Nome do serviço (shortname):** `clemar_integration_service`
- **Nome da função:** `local_clemar_integration_get_completions`
- **Protocolo:** REST (padrão do Web Service Moodle)
- **Formato de resposta:** JSON

### 4.2 Parâmetros de entrada

| Parâmetro | Tipo | Obrigatório | Descrição |
|-----------|------|-------------|-----------|
| `data_inicial` | string | Sim | Data de início do período no formato `YYYY-MM-DD` |
| `data_final` | string | Sim | Data de fim do período no formato `YYYY-MM-DD` |
| `pagina` | int | Não | Número da página (padrão: 1) |
| `registros_por_pagina` | int | Não | Quantidade de registros por página (padrão: 100, máximo: 500) |

### 4.3 Validações obrigatórias na entrada

- `data_inicial` e `data_final` devem estar no formato `YYYY-MM-DD` (ISO 8601 date).
- `data_inicial` não pode ser posterior a `data_final`.
- O intervalo máximo entre as datas não deve exceder 366 dias (proteção contra consultas excessivamente amplas).
- `pagina` deve ser um inteiro positivo maior ou igual a 1.
- `registros_por_pagina` deve estar entre 1 e 500.
- Parâmetros ausentes ou com formato inválido devem retornar erro estruturado com código e mensagem.

### 4.4 Formato da resposta JSON (caso com dados)

```json
{
  "periodo": {
    "data_inicial": "2025-11-01",
    "data_final": "2025-11-30"
  },
  "paginacao": {
    "pagina_atual": 1,
    "registros_por_pagina": 100,
    "total_registros": 2,
    "total_paginas": 1
  },
  "registros": [
    {
      "codigo_curso": "SEG-001",
      "nome_completo_curso": "Treinamento de Segurança Operacional",
      "nome_breve_curso": "SEGURANCA_OPERACIONAL",
      "categoria_curso": "Treinamentos Corporativos",
      "matricula": "45872",
      "nome": "João",
      "sobrenome": "Silva",
      "email": "joao.silva@clemar.com.br",
      "status_conclusao": "concluido",
      "data_conclusao": "2025-11-14T10:32:15-03:00"
    }
  ]
}
```

### 4.5 Recomendações sobre o formato

**Paginação:** Recomendada. Mesmo que o volume atual seja baixo, uma empresa pode ter centenas de conclusões por mês. A paginação evita timeouts e payloads excessivos. Padrão de 100 registros por página é adequado para consumo por sistemas corporativos.

**Metadados:** O bloco `periodo` e `paginacao` são essenciais. Eles permitem que o sistema consumidor valide que recebeu a resposta correta e itere nas páginas. O campo `total_registros` permite ao consumidor saber, na primeira página, quantos registros existem no total.

**Campo `status_conclusao`:** Recomenda-se utilizar um **enum textual padronizado** (não booleano). Valores possíveis: `"concluido"`. Embora hoje exista apenas um status relevante (concluído, já que a API só retorna conclusões efetivas), usar enum permite extensão futura sem quebrar o contrato (ex.: `"concluido_com_ressalva"`, `"concluido_por_equivalencia"`). Booleano seria semanticamente pobre e dificultaria extensões.

**Padrão de data:** Recomenda-se **ISO 8601 com timezone** (`YYYY-MM-DDTHH:mm:ss-03:00`) para as datas de conclusão na resposta. Nos parâmetros de entrada, manter `YYYY-MM-DD` sem horário é suficiente, pois o filtro por período considera o dia inteiro. Internamente, a data inicial será tratada como `00:00:00` e a data final como `23:59:59` do dia informado.

**Ordenação:** Resultados ordenados por `data_conclusao` ascendente (mais antigos primeiro). Isso facilita o processamento sequencial pelo sistema consumidor.

### 4.6 Resposta vazia (sem registros no período)

```json
{
  "periodo": {
    "data_inicial": "2025-12-01",
    "data_final": "2025-12-31"
  },
  "paginacao": {
    "pagina_atual": 1,
    "registros_por_pagina": 100,
    "total_registros": 0,
    "total_paginas": 0
  },
  "registros": []
}
```

A estrutura se mantém idêntica — o array `registros` vem vazio e `total_registros` é zero. Isso permite que o consumidor trate a resposta com a mesma lógica, sem precisar de tratamento especial para ausência de dados.

### 4.7 Padrão de mensagens de erro

```json
{
  "erro": true,
  "codigo_erro": "PARAMETRO_INVALIDO",
  "mensagem": "O parâmetro data_inicial deve estar no formato YYYY-MM-DD."
}
```

Códigos de erro previstos:

| Código | Situação |
|--------|----------|
| `PARAMETRO_INVALIDO` | Formato ou valor de parâmetro incorreto |
| `PERIODO_EXCEDIDO` | Intervalo entre datas maior que o permitido |
| `DATA_INICIAL_POSTERIOR` | Data inicial posterior à data final |
| `ACESSO_NEGADO` | Token inválido ou sem permissão |
| `ERRO_INTERNO` | Falha inesperada no servidor |

Nota: O framework de Web Services do Moodle tem seu próprio mecanismo de exceções (`invalid_parameter_exception`, `moodle_exception`, `required_capability_exception`). As exceções do plugin devem utilizar esses mecanismos nativos, e o Moodle serializará os erros em formato JSON automaticamente. Os códigos acima servem como referência conceitual para documentação da TI.

### 4.8 Regras técnicas da consulta

**Conceito de "curso concluído":** A API deve adotar o conceito nativo de conclusão de curso do Moodle, registrado na tabela `mdl_course_completions`. Um curso é considerado concluído quando o campo `timecompleted` possui um valor preenchido (timestamp não nulo). Esse é o mecanismo padrão do Moodle para rastreamento de conclusão, acionado quando todas as condições de conclusão configuradas no curso são atendidas pelo aluno.

**Filtro por período:** A consulta deve considerar apenas registros cujo `timecompleted` (convertido para data) esteja dentro do intervalo informado (inclusive em ambos os limites). A data inicial corresponde ao início do dia (00:00:00) e a data final ao final do dia (23:59:59) no timezone do servidor Moodle.

**Cursos sem rastreamento de conclusão:** Se um curso não tem o rastreamento de conclusão habilitado (completion tracking desabilitado), o Moodle não gera registros em `mdl_course_completions` para esse curso. Consequentemente, esses cursos nunca aparecerão nos resultados da API. Isso é comportamento esperado e deve ser documentado. Antes da homologação, auditar todos os cursos relevantes para confirmar que o completion tracking está ativo.

**Cursos sem data de conclusão:** Em cenários raros, pode existir um registro de conclusão sem `timecompleted` preenchido (ex.: conclusão parcial, bug, ou conclusão manual sem data). A API deve ignorar esses registros — o filtro por período naturalmente os exclui, pois não há data para comparar.

**Usuários excluídos, suspensos ou inconsistentes:**

- **Usuários excluídos (deleted = 1):** Devem ser excluídos da consulta. Suas conclusões podem existir no banco, mas retornar dados de usuários excluídos é inconsistente e potencialmente problemático para LGPD.
- **Usuários suspensos (suspended = 1):** Recomenda-se incluí-los nos resultados. Um colaborador pode ser desativado no Moodle após ter concluído um curso no período consultado. A conclusão é um fato histórico e deve ser reportada. Se a CLEMAR preferir excluí-los, isso pode ser um parâmetro opcional futuro.
- **Usuários sem idnumber:** Não são retornados pela API (conforme regra de saneamento).

**Filtro por categoria ou área:** Recomenda-se não filtrar por categoria na versão inicial. A API deve retornar todas as conclusões de cursos que possuam idnumber preenchido, independentemente da categoria. Isso simplifica o desenvolvimento e dá flexibilidade à CLEMAR para usar todos os dados. Se futuramente for necessário filtrar por categorias específicas, o parâmetro pode ser adicionado sem quebrar o contrato existente.

---

## 5. Segurança e Governança

### 5.1 Usuário técnico de integração

Deve ser criado um usuário dedicado exclusivamente para a integração, com as seguintes características:

- **Username sugerido:** `integracao_clemar` ou `api_clemar`
- **Método de autenticação:** Web Services authentication (manual accounts)
- **Papel (role):** Nenhum papel global com privilégios amplos. Criar um papel customizado mínimo (ex.: `api_integration_reader`) com apenas a capability necessária.
- **Não deve ter acesso ao painel do Moodle** — conta exclusiva para consumo via API.
- **Senha forte e documentada** de forma segura, embora o acesso se dê via token.

### 5.2 Token de acesso

- Gerar token vinculado ao usuário técnico e ao serviço específico (`clemar_integration_service`).
- O token deve ser associado **exclusivamente** ao serviço do plugin, sem acesso a outras funções do Moodle.
- Definir restrição de IP se possível (campo "IP Restriction" na criação do token), limitando aos IPs conhecidos da infraestrutura da CLEMAR.
- Documentar o token de forma segura e compartilhar por canal criptografado com a TI da CLEMAR.
- Planejar rotação periódica do token (recomendação: a cada 6 meses, ou quando houver mudança de equipe na TI).

### 5.3 Capabilities e menor privilégio

- Criar uma capability específica: `local/clemar_integration:viewcompletions`
- Atribuir essa capability **apenas** ao papel customizado do usuário de integração.
- O papel não deve ter `moodle/site:accessallgroups`, `moodle/user:viewalldetails` ou qualquer capability administrativa genérica.
- A função do Web Service deve verificar essa capability antes de processar a requisição.

### 5.4 Restrição por serviço

Na configuração de Web Services do Moodle (Administração > Plugins > Web Services), o serviço `clemar_integration_service` deve:

- Ser marcado como **habilitado**.
- Ter apenas a função `local_clemar_integration_get_completions` associada.
- Ter o usuário técnico como único autorizado.
- **Não** ser marcado como "Autorised users only = No" (acesso aberto). Manter restrito a usuários autorizados.

### 5.5 Exposição externa e HTTPS

- O Moodle deve estar configurado com **HTTPS obrigatório** (já esperado em produção).
- Se o Moodle estiver atrás de proxy reverso ou WAF, garantir que as regras permitam chamadas ao endpoint de Web Service (`/webservice/rest/server.php`).
- Não expor o endpoint em domínio ou subdomínio separado — utilizar o mesmo domínio do Moodle com o caminho padrão.

### 5.6 Trilha de auditoria e logs

- O Moodle registra automaticamente chamadas de Web Service no log de eventos (`logstore_standard`).
- Garantir que o logstore esteja ativo e com retenção adequada (mínimo 90 dias recomendado).
- Periodicamente, revisar os logs de acesso ao serviço para detectar anomalias (volume inesperado, horários incomuns, tentativas com token inválido).
- O plugin pode, opcionalmente, registrar eventos customizados usando a Event API do Moodle para rastrear cada consulta realizada.

### 5.7 Rate limiting e controle de volume

- O Moodle não oferece rate limiting nativo para Web Services.
- Como medida de proteção, a limitação de intervalo máximo de consulta (366 dias) e paginação (máximo 500 registros por página) já controlam o volume por requisição.
- Se necessário, implementar controle adicional via proxy reverso (ex.: Nginx rate limiting) ou via lógica no plugin (registro de timestamp da última chamada por token).
- Para o cenário atual, com consumo previsível pela TI CLEMAR (provavelmente uma consulta mensal), o risco de abuso é baixo.

### 5.8 LGPD e proteção de dados pessoais

A API retorna dados pessoais: nome, sobrenome, e-mail e matrícula. Considerações obrigatórias:

- **Base legal:** O tratamento é justificado pelo legítimo interesse do empregador (CLEMAR) para gestão de capacitação de seus próprios colaboradores. Deve haver base legal documentada.
- **Minimização:** A API retorna apenas os campos estritamente necessários. Não incluir CPF, data de nascimento, telefone ou outros dados não requisitados.
- **Controle de acesso:** Token restrito, IP restrito quando possível, acesso apenas pela TI autorizada.
- **Retenção:** Os dados no Moodle seguem a política de retenção da plataforma. Os dados retornados pela API são transientes (não são armazenados adicionalmente pelo plugin).
- **Transparência:** Os colaboradores devem estar cientes (via política interna da CLEMAR) de que seus dados de capacitação são compartilhados entre sistemas.
- **Incidentes:** Ter procedimento para revogar o token imediatamente em caso de comprometimento.

---

## 6. Estratégia de Saneamento de Identificadores

### 6.1 Contexto

A integração depende de dois campos-chave:

- `idnumber` do curso = código do curso no sistema CLEMAR.
- `idnumber` do usuário = matrícula do colaborador no sistema CLEMAR.

Se esses campos não estiverem preenchidos e alinhados com os dados do sistema corporativo, a API retornará dados que a TI não conseguirá cruzar. O saneamento é **pré-requisito absoluto** para a entrada em produção da API.

### 6.2 Levantamento de cursos sem idnumber

- Identificar todos os cursos no Moodle que não possuem o campo `idnumber` preenchido.
- Classificar esses cursos entre "relevantes para a integração" (entram no relatório do RH) e "não relevantes" (cursos internos, testes, rascunhos).
- Apenas cursos relevantes precisam de idnumber preenchido. Cursos sem idnumber simplesmente não aparecerão nos resultados da API — o que é aceitável para cursos fora do escopo.

### 6.3 Levantamento de usuários sem idnumber

- Identificar todos os usuários ativos no Moodle que não possuem o campo `idnumber` preenchido.
- Solicitar à CLEMAR a lista oficial de matrículas dos colaboradores.
- Cruzar manualmente (ou via planilha) os usuários do Moodle com a lista da CLEMAR para identificar correspondências por nome, e-mail ou outro identificador.
- Preencher o campo `idnumber` de cada usuário com a matrícula correspondente.

### 6.4 Validação de correspondência

- Após preenchimento, gerar um relatório de conferência listando: nome do usuário no Moodle, idnumber preenchido, e-mail.
- Enviar esse relatório para o RH da CLEMAR validar se as matrículas estão corretas.
- Procedimento análogo para os cursos: listar curso, idnumber preenchido, e validar com a CLEMAR.

### 6.5 Ordem de saneamento recomendada

1. **Receber da CLEMAR** a lista oficial de códigos de cursos e a lista oficial de matrículas.
2. **Sanear cursos:** Preencher idnumber dos cursos relevantes.
3. **Sanear usuários:** Preencher idnumber dos colaboradores.
4. **Validar com a CLEMAR:** Enviar relatório de conferência.
5. **Corrigir divergências** apontadas pela CLEMAR.
6. **Liberar a API** somente após confirmação formal de que os identificadores estão corretos.

### 6.6 Prevenção de divergência futura

- Definir processo operacional: todo novo curso relevante deve ser criado com `idnumber` preenchido.
- Todo novo colaborador cadastrado no Moodle deve ter o `idnumber` preenchido com a matrícula no ato do cadastro.
- Se a CLEMAR utiliza integração de usuários (upload CSV, LDAP, etc.), o campo `idnumber` deve fazer parte do mapeamento.
- Considerar uma verificação periódica (mensal ou trimestral) de cursos e usuários sem idnumber, como rotina de qualidade.

### 6.7 Tratamento de exceções

- **Curso sem idnumber:** Não será retornado pela API. A conclusão existe, mas não é reportada. Documentar esse comportamento.
- **Usuário sem idnumber:** Não será retornado pela API. Mesma lógica.
- **Idnumber duplicado:** O Moodle permite (dependendo da configuração) idnumbers duplicados em usuários. A API deve assumir unicidade — se houver duplicatas, o saneamento deve resolvê-las antes da liberação.
- **Bloqueio de operação:** Recomenda-se que a API **não bloqueie** sua operação por conta de registros sem idnumber. Ela simplesmente os omite. Mas deve existir um processo de monitoramento para detectar conclusões "perdidas" (registros que existem mas não seriam retornados por falta de identificador).

---

## 7. Fases do Projeto

### Fase 1 — Levantamento e Saneamento

**Objetivo:** Garantir que os dados-base estejam corretos antes de iniciar o desenvolvimento.

**Atividades:**

- Solicitar à CLEMAR a lista oficial de códigos de cursos e matrículas de colaboradores.
- Levantar cursos sem idnumber e classificar relevância.
- Levantar usuários sem idnumber.
- Preencher idnumbers conforme listas oficiais.
- Gerar relatório de conferência e enviar para validação da CLEMAR.
- Corrigir divergências apontadas.

**Dependências:** Listas oficiais da CLEMAR.

**Riscos:** Atraso no envio das listas pela CLEMAR; inconsistências que exijam análise caso a caso.

**Entregáveis:** Relatório de saneamento validado; cursos e usuários com idnumber preenchidos.

---

### Fase 2 — Desenho da API e Contrato

**Objetivo:** Formalizar o contrato da API antes do desenvolvimento.

**Atividades:**

- Documentar o contrato funcional (parâmetros, resposta, erros, paginação).
- Validar com a TI da CLEMAR se o formato JSON atende às necessidades.
- Alinhar padrão de datas, nomes de campos e encoding.
- Definir o comportamento esperado para cenários de borda.

**Dependências:** Disponibilidade da TI CLEMAR para revisão.

**Riscos:** Mudanças de requisito após o desenvolvimento iniciar.

**Entregáveis:** Documento de contrato da API aprovado por ambas as partes.

---

### Fase 3 — Desenvolvimento do Plugin

**Objetivo:** Implementar o plugin local com a função de Web Service.

**Atividades:**

- Criar a estrutura do plugin (`local/clemar_integration/`).
- Implementar a função externa com validação de parâmetros.
- Implementar a consulta às tabelas de conclusão com filtro por período.
- Implementar cruzamento com dados de curso, usuário e categoria.
- Implementar paginação e ordenação.
- Implementar tratamento de erros e exceções.
- Definir capability e arquivo de acesso.
- Registrar o serviço e a função em `db/services.php`.
- Criar strings de idioma.
- Testes unitários (se viável no ambiente).

**Dependências:** Contrato da API definido (Fase 2).

**Riscos:** Complexidade de performance em consultas com muitos registros; divergências entre dados esperados e estrutura real do banco.

**Entregáveis:** Plugin instalável e funcional em ambiente de desenvolvimento/staging.

---

### Fase 4 — Publicação do Web Service e Criação do Acesso

**Objetivo:** Disponibilizar o serviço no Moodle e criar os artefatos de acesso.

**Atividades:**

- Instalar o plugin no ambiente de homologação.
- Habilitar os Web Services REST no Moodle (se ainda não estiverem).
- Criar o usuário técnico de integração.
- Criar o papel customizado com a capability mínima.
- Gerar o token de acesso.
- Configurar restrição de IP (se aplicável).
- Associar o token ao serviço e ao usuário.

**Dependências:** Plugin desenvolvido (Fase 3); acesso administrativo ao Moodle de homologação.

**Riscos:** Configurações de servidor bloqueando chamadas Web Service; conflitos com plugins existentes.

**Entregáveis:** Serviço publicado e acessível em homologação com token funcional.

---

### Fase 5 — Homologação com TI da CLEMAR

**Objetivo:** Validar que a TI consegue consumir a API corretamente.

**Atividades:**

- Compartilhar com a TI: URL do endpoint, token, documentação de uso.
- TI realiza chamadas de teste com período conhecido.
- Validar resposta JSON, encoding, paginação, erros.
- Ajustar eventuais divergências de formato ou comportamento.

**Dependências:** Serviço publicado (Fase 4); disponibilidade da TI CLEMAR.

**Riscos:** Firewall/proxy da CLEMAR bloqueando chamadas; divergências de expectativa sobre formato.

**Entregáveis:** Ata de validação técnica com TI; ajustes aplicados se necessário.

---

### Fase 6 — Validação com RH

**Objetivo:** Confirmar que os dados retornados correspondem à realidade.

**Atividades:**

- Selecionar um mês de referência (ex.: mês anterior) com conclusões conhecidas.
- Executar consulta pela API para esse mês.
- Comparar resultado com relatório manual ou planilha do RH.
- Verificar completude (todos os registros esperados estão presentes).
- Verificar acurácia (dados corretos, nomes, cursos, datas).

**Dependências:** Homologação técnica concluída (Fase 5); relatório de referência do RH.

**Riscos:** Divergências por saneamento incompleto; conclusões sem data preenchida no Moodle.

**Entregáveis:** Relatório de validação cruzada com RH.

---

### Fase 7 — Entrada em Produção

**Objetivo:** Disponibilizar a solução em ambiente definitivo.

**Atividades:**

- Instalar o plugin em produção.
- Replicar configurações de serviço, usuário, papel e token.
- Configurar restrição de IP para produção.
- TI realiza chamada final de validação.
- Entregar guia de uso para TI e documentação de suporte.
- Definir ponto de contato para suporte técnico.

**Dependências:** Todas as fases anteriores concluídas com sucesso.

**Riscos:** Diferenças de configuração entre homologação e produção; volume de dados real diferente do testado.

**Entregáveis:** Serviço em produção; guia de uso entregue; suporte ativo.

---

## 8. Critérios de Aceite

### 8.1 Critérios técnicos

| # | Critério | Verificação |
|---|----------|-------------|
| 1 | Consulta por período retorna apenas conclusões com data preenchida dentro do intervalo | Executar consulta e verificar ausência de registros sem data ou fora do período |
| 2 | Cada registro retorna `codigo_curso` (idnumber do curso) e `matricula` (idnumber do usuário) preenchidos | Inspecionar JSON e confirmar campos não nulos e não vazios |
| 3 | JSON é válido e parseable sem tratamento manual | Validar com ferramenta de JSON schema ou parser padrão |
| 4 | Autenticação funciona exclusivamente via token, sem login interativo | Testar chamada via cURL ou Postman com token no parâmetro |
| 5 | Chamada sem token ou com token inválido retorna erro estruturado | Testar e verificar resposta de erro |
| 6 | Parâmetros inválidos (datas mal formatadas, período invertido) retornam erro claro | Testar com datas inválidas |
| 7 | Resposta vazia retorna estrutura consistente com array vazio | Consultar período sem conclusões |
| 8 | Paginação funciona corretamente (total de registros, páginas, navegação) | Testar com volume que exija múltiplas páginas |
| 9 | Performance aceitável para um mês com alto volume (ex.: 500+ conclusões) | Medir tempo de resposta |
| 10 | Dados de categoria do curso estão corretos | Verificar correspondência com o Moodle |

### 8.2 Critérios funcionais (negócio)

| # | Critério | Verificação |
|---|----------|-------------|
| 11 | Retorno validado com mês real e conferido com amostra do RH | Comparar resultado da API com relatório manual do RH |
| 12 | TI da CLEMAR consegue importar o JSON no sistema corporativo sem transformação manual | Confirmação formal da TI |
| 13 | Identificadores (`codigo_curso`, `matricula`) correspondem aos usados no sistema CLEMAR | Cruzamento de dados confirmado pela TI |

---

## 9. Plano de Homologação

### 9.1 Massa de teste

- Utilizar dados reais do mês anterior (ou do mês com maior volume de conclusões recente).
- Se possível, garantir que a massa contenha: pelo menos 10 conclusões, de pelo menos 3 cursos diferentes, envolvendo pelo menos 5 usuários distintos.
- Incluir cenários de borda: curso sem idnumber (deve ser omitido), usuário sem idnumber (deve ser omitido), usuário suspenso com conclusão (avaliar comportamento).

### 9.2 Validação com mês anterior real

- Executar a consulta para o mês anterior completo.
- Exportar o resultado.
- Comparar com relatório manual gerado pelo RH ou extraído diretamente do Moodle.
- Confirmar que todos os registros esperados estão presentes e que não há registros extras.

### 9.3 Comparação com relatório manual

- O RH deve fornecer uma lista de "quem concluiu o quê no mês X".
- A TechEduConnect compara essa lista com o retorno da API.
- Divergências devem ser analisadas caso a caso (podem indicar saneamento incompleto, conclusões sem data, ou cursos sem rastreamento).

### 9.4 Validação de dados cruzados com RH

- Para uma amostra de 5 a 10 registros, o RH confirma: nome correto, matrícula correta, curso correto, data de conclusão coerente.

### 9.5 Validação de consumo pela TI

- TI da CLEMAR realiza chamadas reais ao endpoint de homologação.
- Testa diferentes períodos (mês com dados, mês sem dados, mês com alto volume).
- Testa paginação.
- Testa tratamento de erros (token errado, datas inválidas).
- Confirma que o JSON é integrável ao sistema corporativo.

### 9.6 Testes de segurança e acesso

- Testar chamada com token válido: deve funcionar.
- Testar chamada sem token: deve retornar erro.
- Testar chamada com token de outro serviço: deve retornar erro.
- Testar chamada de IP não autorizado (se restrição de IP estiver ativa): deve retornar erro.
- Verificar nos logs do Moodle que as chamadas são registradas.

### 9.7 Testes de performance

- Executar consulta para um mês com volume significativo (200+ registros).
- Medir tempo de resposta (esperado: abaixo de 5 segundos para 500 registros).
- Verificar consumo de memória e CPU no servidor durante a consulta.
- Se necessário, otimizar a query ou ajustar limites de paginação.

---

## 10. Guia Resumido para a TI da CLEMAR

### Objetivo do serviço

Este serviço permite consultar, por período de datas, todos os cursos concluídos por colaboradores da CLEMAR no Moodle. O retorno é um JSON estruturado com dados do curso, do colaborador e da conclusão, pronto para importação no sistema corporativo.

### Como consumir

**Endpoint:**
```
https://[dominio-moodle]/webservice/rest/server.php
```

**Método:** GET ou POST

**Parâmetros obrigatórios:**

| Parâmetro | Descrição | Exemplo |
|-----------|-----------|---------|
| `wstoken` | Token de autenticação fornecido pela TechEduConnect | `abc123def456...` |
| `wsfunction` | Nome da função a ser chamada | `local_clemar_integration_get_completions` |
| `moodlewsrestformat` | Formato de resposta desejado | `json` |
| `data_inicial` | Início do período de consulta (YYYY-MM-DD) | `2025-11-01` |
| `data_final` | Fim do período de consulta (YYYY-MM-DD) | `2025-11-30` |

**Parâmetros opcionais:**

| Parâmetro | Descrição | Padrão |
|-----------|-----------|--------|
| `pagina` | Número da página | `1` |
| `registros_por_pagina` | Registros por página | `100` |

### Exemplo descritivo de chamada

Uma requisição GET para consultar as conclusões de novembro de 2025 teria a seguinte estrutura de URL:

```
https://[dominio]/webservice/rest/server.php?wstoken=SEU_TOKEN&wsfunction=local_clemar_integration_get_completions&moodlewsrestformat=json&data_inicial=2025-11-01&data_final=2025-11-30
```

### Significado dos campos retornados

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `periodo.data_inicial` | string | Data de início do período consultado |
| `periodo.data_final` | string | Data de fim do período consultado |
| `paginacao.pagina_atual` | int | Página atual da resposta |
| `paginacao.registros_por_pagina` | int | Quantidade de registros por página |
| `paginacao.total_registros` | int | Total de registros encontrados no período |
| `paginacao.total_paginas` | int | Número total de páginas |
| `registros[].codigo_curso` | string | Código do curso (Número de identificação do curso no Moodle = código no sistema CLEMAR) |
| `registros[].nome_completo_curso` | string | Nome completo do curso no Moodle |
| `registros[].nome_breve_curso` | string | Nome breve (shortname) do curso no Moodle |
| `registros[].categoria_curso` | string | Nome da categoria à qual o curso pertence |
| `registros[].matricula` | string | Matrícula do colaborador (Número de identificação do usuário no Moodle = matrícula no sistema CLEMAR) |
| `registros[].nome` | string | Primeiro nome do colaborador |
| `registros[].sobrenome` | string | Sobrenome do colaborador |
| `registros[].email` | string | E-mail do colaborador |
| `registros[].status_conclusao` | string | Status da conclusão (atualmente sempre `"concluido"`) |
| `registros[].data_conclusao` | string | Data e hora da conclusão em formato ISO 8601 |

### Chaves de integração

Para cruzar os dados com o sistema corporativo:

- **Curso:** Usar o campo `codigo_curso` como chave de correspondência com o código do curso no sistema CLEMAR.
- **Colaborador:** Usar o campo `matricula` como chave de correspondência com a matrícula do colaborador no sistema CLEMAR.

### Comportamentos esperados

- **Período sem conclusões:** Retorna JSON com `registros: []` e `total_registros: 0`.
- **Token inválido:** Retorna erro de acesso negado.
- **Datas inválidas:** Retorna erro com descrição do problema.
- **Curso sem código de identificação:** Conclusão não aparece no resultado (por design).
- **Colaborador sem matrícula cadastrada:** Conclusão não aparece no resultado (por design).
- **Múltiplas páginas:** Se `total_paginas > 1`, iterar incrementando o parâmetro `pagina`.

### Observações importantes

- O token deve ser armazenado de forma segura e não compartilhado em canais não criptografados.
- As consultas são somente leitura — a API não altera nenhum dado no Moodle.
- Em caso de indisponibilidade, aguardar e tentar novamente; se persistir, contatar a TechEduConnect.
- A API retorna dados pessoais de colaboradores; observar as políticas internas de proteção de dados.

---

## 11. Riscos e Pontos de Atenção

| Risco | Impacto | Mitigação |
|-------|---------|-----------|
| Saneamento de idnumber incompleto ou atrasado | API retorna dados não cruzáveis; integração não funciona | Tratar como pré-requisito bloqueante; cobrar listas oficiais da CLEMAR com prazo definido |
| Cursos sem rastreamento de conclusão habilitado | Conclusões não são registradas pelo Moodle; API não os encontra | Auditar configuração de conclusão de cada curso relevante antes da homologação |
| Conclusões sem data preenchida no Moodle | Registro existe mas não aparece na consulta por período | Investigar causa (bug, configuração) e tratar antes da produção |
| Token comprometido | Acesso não autorizado a dados pessoais | Restrição de IP, rotação periódica, monitoramento de logs |
| Volume de dados maior que o esperado | Timeouts ou performance degradada | Paginação obrigatória, limite de intervalo de datas, otimização de consulta |
| Atualização do Moodle quebrando a API | Plugin deixa de funcionar após upgrade | Testar o plugin em nova versão antes de atualizar produção; manter plugin com versionamento |
| Divergência entre SimpleCertificate e conclusão de curso | Certificado emitido mas curso não marcado como concluído (ou vice-versa) | Esclarecer que a API trata de conclusão de curso, não de certificados; alinhar com RH |
| CLEMAR alterar códigos de curso ou matrículas sem avisar | Integração quebra silenciosamente | Estabelecer processo de comunicação para mudanças cadastrais |

---

## 12. Recomendação Final

A solução proposta é viável, segura e sustentável. O caminho mais eficiente é:

1. **Iniciar imediatamente pelo saneamento de identificadores** — é a etapa que depende mais da CLEMAR e costuma ser a mais demorada.
2. **Validar o contrato da API com a TI da CLEMAR antes de desenvolver** — evita retrabalho e garante que o formato atende.
3. **Desenvolver o plugin** seguindo estritamente a arquitetura de Web Services do Moodle, sem atalhos.
4. **Homologar com dados reais**, cruzando com o RH, antes de liberar para produção.

A TechEduConnect deve posicionar essa solução como um ativo técnico reutilizável: com ajustes mínimos de configuração (nomes de campos, identificadores), o mesmo plugin pode atender outros clientes corporativos que tenham necessidades semelhantes de integração com Moodle.

O fato de os certificados serem emitidos pelo `mod_simplecertificate` não interfere na arquitetura proposta, pois a API consulta o subsistema nativo de conclusão de curso do Moodle, que é independente do módulo de certificados. Se futuramente houver necessidade de incluir dados do certificado (número, data de emissão), o plugin pode ser estendido sem quebrar o contrato existente.

---

*Documento elaborado como plano técnico-consultivo para a TechEduConnect. Não contém código, pseudocódigo ou SQL. Todas as recomendações são compatíveis com Moodle 4.1.2 e PHP 7.4.*
