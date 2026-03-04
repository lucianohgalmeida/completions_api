# API de Conclusões de Cursos — Documentação

## Visão Geral

API REST que retorna as conclusões de cursos do Moodle dentro de um período informado.
Cada registro inclui dados do curso, do colaborador, data de conclusão e dados do certificado (SimpleCertificate).

- **Somente leitura** — não altera nenhum dado no Moodle.
- Projetada para integração com sistemas corporativos (ERP, RH, BI).

---

## Endpoint

```
POST https://<seu-moodle>/webservice/rest/server.php
```

---

## Parâmetros

| Parâmetro            | Tipo   | Obrigatório | Descrição                                          |
|----------------------|--------|-------------|-----------------------------------------------------|
| `wstoken`            | string | Sim         | Token de autenticação fornecido pelo administrador  |
| `wsfunction`         | string | Sim         | `local_completions_api_get_completions`              |
| `moodlewsrestformat` | string | Sim         | `json`                                               |
| `data_inicial`       | string | Sim         | Data de início no formato `YYYY-MM-DD`               |
| `data_final`         | string | Sim         | Data de fim no formato `YYYY-MM-DD`                  |
| `pagina`             | int    | Nao          | Numero da pagina (padrao: 1)                         |
| `registros_por_pagina` | int  | Nao          | Registros por pagina (padrao: 100, maximo: 500)      |

---

## Exemplo de chamada: cURL

```bash
curl -X POST "https://<seu-moodle>/webservice/rest/server.php" \
  -d "wstoken=SEU_TOKEN" \
  -d "wsfunction=local_completions_api_get_completions" \
  -d "moodlewsrestformat=json" \
  -d "data_inicial=2026-01-01" \
  -d "data_final=2026-03-31"
```

## Exemplo de chamada: URL GET

```
https://<seu-moodle>/webservice/rest/server.php?wstoken=SEU_TOKEN&wsfunction=local_completions_api_get_completions&moodlewsrestformat=json&data_inicial=2026-01-01&data_final=2026-03-31
```

---

## Exemplo de resposta JSON

```json
{
  "periodo": {
    "data_inicial": "2026-01-01",
    "data_final": "2026-03-31"
  },
  "paginacao": {
    "pagina_atual": 1,
    "registros_por_pagina": 100,
    "total_registros": 2,
    "total_paginas": 1
  },
  "registros": [
    {
      "codigo_curso": "1234567",
      "nome_completo_curso": "A Segunda Guerra Mundial: Conflito, Consequencias e Licoes",
      "nome_breve_curso": "A Segunda Guerra Mundial",
      "categoria_curso": "Categoria 1",
      "matricula": "12121212121",
      "nome": "Aluno",
      "sobrenome": "Modelo",
      "email": "teste@gmail.com",
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
          "nome_curso": "A Segunda Guerra Mundial: Conflito, Consequencias e Licoes",
          "ultimo_acesso": "2026-03-01T14:30:00-03:00"
        }
      ],
      "certificado": {
        "codigo": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
        "nome": "Certificado de Conclusao",
        "data_emissao": "2026-03-01T14:30:05-03:00",
        "link": "https://<seu-moodle>/mod/simplecertificate/verify.php?code=a1b2c3d4-e5f6-7890-abcd-ef1234567890"
      }
    },
    {
      "codigo_curso": "1234567",
      "nome_completo_curso": "A Segunda Guerra Mundial: Conflito, Consequencias e Licoes",
      "nome_breve_curso": "A Segunda Guerra Mundial",
      "categoria_curso": "Categoria 1",
      "matricula": "99999999999",
      "nome": "Joao",
      "sobrenome": "Silva",
      "email": "joao.silva@empresa.com.br",
      "status_conclusao": "concluido",
      "data_conclusao": "2026-03-02T09:15:00-03:00",
      "id_usuario": 58,
      "primeiro_acesso": "2025-06-10T10:00:00-03:00",
      "ultimo_acesso": "2026-03-02T09:15:00-03:00",
      "campos_personalizados": [
        {"campo": "cidade", "valor": ""},
        {"campo": "estado", "valor": ""}
      ],
      "cursos_acessados": [
        {
          "codigo_curso": "1234567",
          "nome_curso": "A Segunda Guerra Mundial: Conflito, Consequencias e Licoes",
          "ultimo_acesso": "2026-03-02T09:10:00-03:00"
        }
      ],
      "certificado": {
        "codigo": "",
        "nome": "",
        "data_emissao": "",
        "link": ""
      }
    }
  ]
}
```

> **Nota:** No segundo registro, o certificado esta vazio porque o colaborador ainda nao recebeu o certificado emitido pelo SimpleCertificate, ou porque o plugin nao esta instalado. Os campos em `campos_personalizados` retornam `"valor": ""` quando o campo personalizado nao existe ou nao esta preenchido para o usuario. Os campos `primeiro_acesso` e `ultimo_acesso` retornam vazio quando o valor e 0 (usuario nunca acessou).

---

## Campos da resposta

### Bloco `periodo`

| Campo          | Tipo   | Descricao                          |
|----------------|--------|------------------------------------|
| `data_inicial` | string | Data de inicio do periodo consultado |
| `data_final`   | string | Data de fim do periodo consultado    |

### Bloco `paginacao`

| Campo                 | Tipo | Descricao                   |
|-----------------------|------|-----------------------------|
| `pagina_atual`        | int  | Pagina atual                |
| `registros_por_pagina` | int | Registros por pagina         |
| `total_registros`     | int  | Total de registros no periodo |
| `total_paginas`       | int  | Total de paginas              |

### Bloco `registros[]`

| Campo                 | Tipo   | Descricao                                                        |
|-----------------------|--------|------------------------------------------------------------------|
| `codigo_curso`        | string | Codigo do curso (campo "Numero de identificacao" do Moodle)       |
| `nome_completo_curso` | string | Nome completo do curso                                            |
| `nome_breve_curso`    | string | Nome breve do curso                                               |
| `categoria_curso`     | string | Nome da categoria do curso                                        |
| `matricula`           | string | Matricula do colaborador (campo "Numero de identificacao" do usuario) |
| `nome`                | string | Primeiro nome do colaborador                                      |
| `sobrenome`           | string | Sobrenome do colaborador                                          |
| `email`               | string | E-mail do colaborador                                             |
| `status_conclusao`    | string | Status da conclusao (sempre `"concluido"`)                        |
| `data_conclusao`      | string | Data e hora da conclusao em formato ISO 8601 com timezone          |
| `id_usuario`          | int    | ID do usuario no Moodle                                            |
| `primeiro_acesso`     | string | Primeiro acesso ao Moodle em ISO 8601 com timezone (vazio se nunca acessou) |
| `ultimo_acesso`       | string | Ultimo acesso ao Moodle em ISO 8601 com timezone (vazio se nunca acessou)   |

### Bloco `registros[].cursos_acessados[]`

| Campo          | Tipo   | Descricao                                                      |
|----------------|--------|----------------------------------------------------------------|
| `codigo_curso`  | string | Codigo do curso (campo "Numero de identificacao" do Moodle)     |
| `nome_curso`    | string | Nome completo do curso                                          |
| `ultimo_acesso` | string | Ultimo acesso ao curso em ISO 8601 com timezone                  |

> Lista todos os cursos que o usuario ja visitou (nao apenas os concluidos). Se o usuario nunca acessou nenhum curso, o array retorna vazio.

### Bloco `registros[].campos_personalizados[]`

| Campo   | Tipo   | Descricao                                                          |
|---------|--------|--------------------------------------------------------------------|
| `campo` | string | Alias do campo conforme configurado nas configuracoes do plugin     |
| `valor` | string | Valor do campo (string vazia se nao preenchido ou campo nao existe) |

> Os campos personalizados sao configurados na pagina de administracao do plugin (Admin > Plugins > API de Conclusoes > Configuracoes). Cada linha define um mapeamento `shortname|alias`. Por padrao, os campos `cidade` e `estado` sao retornados.

### Bloco `registros[].certificado`

| Campo          | Tipo   | Descricao                                                      |
|----------------|--------|----------------------------------------------------------------|
| `codigo`       | string | Codigo UUID do certificado emitido pelo SimpleCertificate       |
| `nome`         | string | Nome do certificado (configurado na atividade)                  |
| `data_emissao` | string | Data e hora de emissao do certificado em ISO 8601 com timezone   |
| `link`         | string | URL de verificacao e download do certificado                     |

> Se o plugin SimpleCertificate nao estiver instalado, ou se nao houver certificado emitido para aquele curso/colaborador, todos os campos do bloco `certificado` retornam como string vazia (`""`).

---

## Validacoes e erros

| Situacao                        | Comportamento                                               |
|---------------------------------|-------------------------------------------------------------|
| Formato de data invalido        | Retorna erro — as datas devem estar no formato `YYYY-MM-DD` |
| Data inicial posterior a final  | Retorna erro — `data_inicial` nao pode ser posterior a `data_final` |
| Intervalo excede 366 dias       | Retorna erro — o intervalo maximo permitido e de 366 dias    |
| Token invalido ou ausente       | Retorna erro de acesso negado                                |
| Sem conclusoes no periodo       | Retorna JSON valido com array `registros` vazio e `total_registros = 0` |

---

## Observacoes importantes

- Esta API e **somente leitura** — nao altera nenhum dado no Moodle.
- Cursos e usuarios sem **Numero de identificacao** (`idnumber`) sao automaticamente excluidos dos resultados.
- Usuarios excluidos (`deleted = 1`) sao automaticamente excluidos dos resultados.
- Se `total_paginas > 1`, itere incrementando o parametro `pagina`.

---

## Requisitos

- Moodle 4.1 ou superior
- PHP 7.4 ou superior
- Plugin `local_completions_api` instalado e ativado
- Web Service habilitado com token configurado para o servico **"API de Conclusoes de Cursos"**
- Usuario do token com a capability `local/completions_api:viewcompletions`

---

## Configuracao rapida

1. Instalar o plugin (upload do zip em *Administracao > Plugins > Instalar plugin*)
2. Ativar Web Services em *Administracao > Recursos avancados*
3. Ativar o protocolo REST em *Administracao > Plugins > Web services > Gerenciar protocolos*
4. Criar um usuario de servico (ou usar um existente)
5. Em *Administracao > Plugins > Web services > Servicos externos*, localizar **"API de Conclusoes de Cursos"** e adicionar o usuario autorizado
6. Gerar o token em *Administracao > Plugins > Web services > Gerenciar tokens*
7. Testar com o cURL de exemplo acima

---

## Documentacao interativa

A documentacao interativa com dados reais do ambiente esta disponivel em:

```
https://<seu-moodle>/local/completions_api/docs.php
```

Essa pagina mostra:
- Exemplos com dados reais do banco
- Listagem de todos os cursos e usuarios com status do campo "Numero de identificacao"
- Exportacao em CSV e Excel das listagens
