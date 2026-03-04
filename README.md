# Course Completions API — Moodle Plugin

Plugin local para Moodle que disponibiliza uma **API REST** para consulta de conclusoes de cursos por periodo, com paginacao, campos personalizados configuraveis, integracao com SimpleCertificate e documentacao interativa.

Projetado para integracao com sistemas corporativos (ERP, RH, BI).

## Recursos

- Consulta de conclusoes por periodo com **paginacao configuravel** (ate 500 registros por pagina)
- **Campos personalizados dinamicos** — o administrador configura quais campos do perfil aparecem na resposta
- **Integracao com SimpleCertificate** — retorna codigo, nome, data de emissao e link de verificacao
- **Cursos acessados** — lista todos os cursos que o usuario visitou com data do ultimo acesso
- **Documentacao interativa** (`docs.php`) com dados reais do ambiente
- **Exportacao CSV/Excel** de cursos e usuarios na pagina de documentacao
- Validacoes completas (formato de data, intervalo maximo, paginacao, permissoes)
- Strings em **portugues brasileiro** e **ingles**

## Requisitos

- Moodle 4.1+
- PHP 7.4+

## Instalacao

1. Baixe o arquivo `local_completions_api.zip` da pagina de [Releases](../../releases)
2. Acesse *Administracao > Plugins > Instalar plugin* e faca upload do zip
3. Ative Web Services em *Administracao > Recursos avancados*
4. Ative o protocolo REST em *Administracao > Plugins > Web services > Gerenciar protocolos*
5. Crie um usuario de servico (ou use um existente)
6. Em *Administracao > Plugins > Web services > Servicos externos*, localize **"API de Conclusoes de Cursos"** e adicione o usuario autorizado
7. Gere o token em *Administracao > Plugins > Web services > Gerenciar tokens*
8. Acesse a documentacao interativa em `/local/completions_api/docs.php`

## Configuracao de campos personalizados

Em *Administracao > Plugins > API de Conclusoes de Cursos > Configuracoes*, configure quais campos do perfil do usuario aparecem na resposta da API.

Formato: um mapeamento por linha, `shortname_moodle|nome_na_api`

```
cidade|cidade
estado|estado
cpf|cpfuser
rg|registro_identidade
```

Valor padrao: `cidade|cidade` e `estado|estado`. Deixe vazio para nao retornar campos personalizados.

## Uso da API

### Endpoint

```
POST https://<seu-moodle>/webservice/rest/server.php
```

### Parametros

| Parametro            | Tipo   | Obrigatorio | Descricao                                 |
|----------------------|--------|-------------|-------------------------------------------|
| `wstoken`            | string | Sim         | Token de autenticacao                     |
| `wsfunction`         | string | Sim         | `local_completions_api_get_completions`   |
| `moodlewsrestformat` | string | Sim         | `json`                                    |
| `data_inicial`       | string | Sim         | Data de inicio no formato `YYYY-MM-DD`    |
| `data_final`         | string | Sim         | Data de fim no formato `YYYY-MM-DD`       |
| `pagina`             | int    | Nao         | Numero da pagina (padrao: 1)              |
| `registros_por_pagina` | int  | Nao         | Registros por pagina (padrao: 100, max: 500) |

### Exemplo cURL

```bash
curl -X POST "https://<seu-moodle>/webservice/rest/server.php" \
  -d "wstoken=SEU_TOKEN" \
  -d "wsfunction=local_completions_api_get_completions" \
  -d "moodlewsrestformat=json" \
  -d "data_inicial=2026-01-01" \
  -d "data_final=2026-03-31"
```

### Exemplo de resposta

```json
{
  "periodo": {
    "data_inicial": "2026-01-01",
    "data_final": "2026-03-31"
  },
  "paginacao": {
    "pagina_atual": 1,
    "registros_por_pagina": 100,
    "total_registros": 1,
    "total_paginas": 1
  },
  "registros": [
    {
      "codigo_curso": "1234567",
      "nome_completo_curso": "Seguranca do Trabalho",
      "nome_breve_curso": "SEG-TRAB",
      "categoria_curso": "Treinamentos",
      "matricula": "12345",
      "nome": "Joao",
      "sobrenome": "Silva",
      "email": "joao.silva@empresa.com.br",
      "status_conclusao": "concluido",
      "data_conclusao": "2026-03-01T14:30:00-03:00",
      "id_usuario": 42,
      "primeiro_acesso": "2025-01-15T08:30:00-03:00",
      "ultimo_acesso": "2026-03-03T14:20:00-03:00",
      "campos_personalizados": [
        {"campo": "cidade", "valor": "Joinville"},
        {"campo": "estado", "valor": "SC"}
      ],
      "cursos_acessados": [
        {
          "codigo_curso": "1234567",
          "nome_curso": "Seguranca do Trabalho",
          "ultimo_acesso": "2026-03-01T14:30:00-03:00"
        }
      ],
      "certificado": {
        "codigo": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
        "nome": "Certificado de Conclusao",
        "data_emissao": "2026-03-01T14:30:05-03:00",
        "link": "https://<seu-moodle>/mod/simplecertificate/verify.php?code=a1b2c3d4-e5f6-7890-abcd-ef1234567890"
      }
    }
  ]
}
```

## Campos da resposta

### `registros[]`

| Campo                 | Tipo   | Descricao                                                |
|-----------------------|--------|----------------------------------------------------------|
| `codigo_curso`        | string | Numero de identificacao do curso                         |
| `nome_completo_curso` | string | Nome completo do curso                                   |
| `nome_breve_curso`    | string | Nome breve do curso                                      |
| `categoria_curso`     | string | Categoria do curso                                       |
| `matricula`           | string | Numero de identificacao do usuario                       |
| `nome`                | string | Primeiro nome do usuario                                 |
| `sobrenome`           | string | Sobrenome do usuario                                     |
| `email`               | string | E-mail do usuario                                        |
| `status_conclusao`    | string | Sempre `"concluido"`                                     |
| `data_conclusao`      | string | Data/hora ISO 8601 com timezone                          |
| `id_usuario`          | int    | ID do usuario no Moodle                                  |
| `primeiro_acesso`     | string | Primeiro acesso ao Moodle (ISO 8601, vazio se nunca)     |
| `ultimo_acesso`       | string | Ultimo acesso ao Moodle (ISO 8601, vazio se nunca)       |

### `registros[].campos_personalizados[]`

| Campo   | Tipo   | Descricao                                         |
|---------|--------|---------------------------------------------------|
| `campo` | string | Alias do campo conforme configuracao do plugin     |
| `valor` | string | Valor do campo (vazio se nao preenchido)           |

### `registros[].cursos_acessados[]`

| Campo          | Tipo   | Descricao                              |
|----------------|--------|----------------------------------------|
| `codigo_curso`  | string | Numero de identificacao do curso       |
| `nome_curso`    | string | Nome completo do curso                 |
| `ultimo_acesso` | string | Ultimo acesso ao curso (ISO 8601)      |

### `registros[].certificado`

| Campo          | Tipo   | Descricao                                           |
|----------------|--------|-----------------------------------------------------|
| `codigo`       | string | Codigo UUID do certificado (SimpleCertificate)       |
| `nome`         | string | Nome do certificado                                  |
| `data_emissao` | string | Data de emissao (ISO 8601)                           |
| `link`         | string | URL de verificacao e download                        |

> Se o SimpleCertificate nao estiver instalado ou nao houver certificado emitido, todos os campos retornam como string vazia.

## Validacoes

| Situacao                        | Comportamento                                     |
|---------------------------------|---------------------------------------------------|
| Formato de data invalido        | Retorna erro                                      |
| Data inicial posterior a final  | Retorna erro                                      |
| Intervalo excede 366 dias       | Retorna erro                                      |
| Token invalido ou ausente       | Retorna erro de acesso negado                     |
| Sem conclusoes no periodo       | Retorna JSON com `registros: []`                  |

## Observacoes

- API **somente leitura** — nao altera dados no Moodle
- Cursos e usuarios sem Numero de Identificacao (`idnumber`) sao excluidos dos resultados
- Usuarios excluidos (`deleted = 1`) sao excluidos dos resultados
- Datas de conclusao em ISO 8601 com timezone do servidor Moodle
- Se `total_paginas > 1`, itere incrementando o parametro `pagina`

## Estrutura do plugin

```
local/completions_api/
  classes/external/get_completions.php   # Logica principal da API
  db/access.php                          # Capability
  db/services.php                        # Registro do Web Service
  lang/en/local_completions_api.php      # Strings em ingles
  lang/pt_br/local_completions_api.php   # Strings em portugues
  docs.php                               # Documentacao interativa
  docs_export.php                        # Exportacao CSV/Excel
  settings.php                           # Configuracoes do plugin
  version.php                            # Metadados e versao
```

## Licenca

GPL v3+ — [GNU General Public License](https://www.gnu.org/licenses/gpl-3.0.html)
