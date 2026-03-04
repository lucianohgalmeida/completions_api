# API CLEMAR — Conclusões de Cursos (Moodle Web Service)

## Visão Geral

Esta API permite consultar as conclusões de cursos do Moodle dentro de um período específico. Foi desenvolvida como um plugin local do Moodle (`local_clemar`) para integração com o sistema corporativo da CLEMAR.

**Ambiente:** Moodle 4.1.2 | PHP 7.4

---

## Dados de Acesso

| Item | Valor |
|------|-------|
| URL base | `https://<seu-moodle>/webservice/rest/server.php` |
| Token | Fornecido pelo administrador do Moodle |
| Função | `local_clemar_get_completions` |
| Método HTTP | `POST` |
| Formato de resposta | JSON |

---

## Parâmetros de Entrada

| Parâmetro | Tipo | Obrigatório | Formato | Descrição |
|-----------|------|:-----------:|---------|-----------|
| `wstoken` | string | Sim | — | Token de autenticação do Web Service |
| `wsfunction` | string | Sim | — | Sempre `local_clemar_get_completions` |
| `moodlewsrestformat` | string | Sim | — | Sempre `json` |
| `data_inicio` | string | Sim | `YYYY-MM-DD` | Data de início do período de consulta |
| `data_fim` | string | Sim | `YYYY-MM-DD` | Data de fim do período de consulta |

> **Nota:** O período considera a `data_inicio` a partir das 00:00:00 e a `data_fim` até as 23:59:59 (inclusive).

---

## Exemplo de Chamada

### cURL

```bash
curl -X POST "https://<seu-moodle>/webservice/rest/server.php" \
  -d "wstoken=SEU_TOKEN_AQUI" \
  -d "wsfunction=local_clemar_get_completions" \
  -d "moodlewsrestformat=json" \
  -d "data_inicio=2025-11-01" \
  -d "data_fim=2025-11-30"
```

### PowerShell

```powershell
$body = @{
    wstoken           = "SEU_TOKEN_AQUI"
    wsfunction        = "local_clemar_get_completions"
    moodlewsrestformat = "json"
    data_inicio       = "2025-11-01"
    data_fim          = "2025-11-30"
}

$response = Invoke-RestMethod -Uri "https://<seu-moodle>/webservice/rest/server.php" -Method POST -Body $body
$response | ConvertTo-Json -Depth 5
```

### Python

```python
import requests

url = "https://<seu-moodle>/webservice/rest/server.php"

payload = {
    "wstoken": "SEU_TOKEN_AQUI",
    "wsfunction": "local_clemar_get_completions",
    "moodlewsrestformat": "json",
    "data_inicio": "2025-11-01",
    "data_fim": "2025-11-30",
}

response = requests.post(url, data=payload)
dados = response.json()

for item in dados["conclusoes"]:
    cert = item["certificado"]
    print(f"Matrícula: {item['conclusao']['matricula']} | "
          f"Curso: {item['conclusao']['codigo_curso']} | "
          f"Data: {item['conclusao']['data_conclusao']} | "
          f"Certificado: {cert['link'] if cert['link'] else 'Não emitido'}")
```

### C# (.NET)

```csharp
using System.Net.Http;

var client = new HttpClient();
var content = new FormUrlEncodedContent(new Dictionary<string, string>
{
    { "wstoken", "SEU_TOKEN_AQUI" },
    { "wsfunction", "local_clemar_get_completions" },
    { "moodlewsrestformat", "json" },
    { "data_inicio", "2025-11-01" },
    { "data_fim", "2025-11-30" }
});

var response = await client.PostAsync(
    "https://<seu-moodle>/webservice/rest/server.php", content);
var json = await response.Content.ReadAsStringAsync();
```

---

## Estrutura da Resposta (JSON)

```json
{
  "conclusoes": [
    {
      "curso": {
        "codigo_curso": "CLEMAR-001",
        "nome_completo": "Segurança do Trabalho",
        "nome_breve": "SEG-TRAB",
        "categoria": "Treinamentos Obrigatórios"
      },
      "usuario": {
        "matricula": "12345",
        "nome": "João",
        "sobrenome": "Silva",
        "email": "joao.silva@clemar.com.br"
      },
      "conclusao": {
        "status": "Concluído",
        "data_conclusao": "2025-11-15 14:30:00",
        "codigo_curso": "CLEMAR-001",
        "matricula": "12345"
      },
      "certificado": {
        "codigo": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
        "nome": "Certificado de Segurança do Trabalho",
        "data_emissao": "2025-11-15 14:35:00",
        "link": "https://<seu-moodle>/mod/simplecertificate/verify.php?code=a1b2c3d4-e5f6-7890-abcd-ef1234567890"
      }
    },
    {
      "curso": {
        "codigo_curso": "CLEMAR-002",
        "nome_completo": "NR-10 Básico",
        "nome_breve": "NR10-BAS",
        "categoria": "Normas Regulamentadoras"
      },
      "usuario": {
        "matricula": "67890",
        "nome": "Maria",
        "sobrenome": "Santos",
        "email": "maria.santos@clemar.com.br"
      },
      "conclusao": {
        "status": "Concluído",
        "data_conclusao": "2025-11-20 09:15:00",
        "codigo_curso": "CLEMAR-002",
        "matricula": "67890"
      },
      "certificado": {
        "codigo": "f9e8d7c6-b5a4-3210-fedc-ba9876543210",
        "nome": "Certificado NR-10 Básico",
        "data_emissao": "2025-11-20 09:20:00",
        "link": "https://<seu-moodle>/mod/simplecertificate/verify.php?code=f9e8d7c6-b5a4-3210-fedc-ba9876543210"
      }
    }
  ]
}
```

---

## Descrição dos Campos de Retorno

### Bloco `curso`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `codigo_curso` | string | Código do curso no sistema CLEMAR (campo "Número de identificação" do curso no Moodle) |
| `nome_completo` | string | Nome completo do curso no Moodle |
| `nome_breve` | string | Nome breve (código curto) do curso no Moodle |
| `categoria` | string | Categoria à qual o curso pertence no Moodle |

### Bloco `usuario`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `matricula` | string | Matrícula do colaborador (campo "Número de identificação" do usuário no Moodle) |
| `nome` | string | Primeiro nome do usuário |
| `sobrenome` | string | Sobrenome do usuário |
| `email` | string | E-mail do usuário |

### Bloco `conclusao`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `status` | string | Sempre `"Concluído"` (só retorna registros concluídos) |
| `data_conclusao` | string | Data e hora da conclusão no formato `YYYY-MM-DD HH:MM:SS` |
| `codigo_curso` | string | Código do curso (chave para cruzamento com o sistema CLEMAR) |
| `matricula` | string | Matrícula do colaborador (chave para cruzamento com o sistema CLEMAR) |

### Bloco `certificado`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `codigo` | string | Código único do certificado (UUID). Vazio se não houver certificado emitido |
| `nome` | string | Nome do certificado conforme configurado no Moodle |
| `data_emissao` | string | Data e hora da emissão do certificado no formato `YYYY-MM-DD HH:MM:SS` |
| `link` | string | URL para verificação e download do certificado em PDF. Vazio se não houver certificado |

> **Nota:** O bloco `certificado` sempre estará presente na resposta. Se o curso não possuir um certificado configurado (simplecertificate) ou o certificado ainda não tiver sido emitido para o usuário, todos os campos virão com valor vazio (`""`).

---

## Chaves de Cruzamento

Para integrar os dados com o sistema corporativo da CLEMAR:

| Campo na API | Correspondência no sistema CLEMAR |
|--------------|-----------------------------------|
| `codigo_curso` | Código do curso no sistema da CLEMAR |
| `matricula` | Matrícula do colaborador no sistema da CLEMAR |

> Esses campos são os mesmos nos blocos `curso`/`usuario` e `conclusao`, facilitando o cruzamento.

---

## Respostas de Erro

### Token inválido ou ausente

```json
{
  "exception": "moodle_exception",
  "errorcode": "invalidtoken",
  "message": "Token inválido"
}
```

### Parâmetro ausente ou formato inválido

```json
{
  "exception": "invalid_parameter_exception",
  "errorcode": "invalidparameter",
  "message": "Formato de data inválido. Utilize o formato YYYY-MM-DD."
}
```

### Sem permissão (capability não atribuída)

```json
{
  "exception": "required_capability_exception",
  "errorcode": "nopermissions",
  "message": "Desculpe, você não tem permissão para fazer isso..."
}
```

### Nenhuma conclusão no período

Retorna a estrutura normal com array vazio:

```json
{
  "conclusoes": []
}
```

---

## Observações Importantes

1. **Formato de data:** Sempre utilizar `YYYY-MM-DD` (ex: `2025-11-01`). Outros formatos serão rejeitados.

2. **Fuso horário:** As datas de conclusão seguem o fuso horário configurado no servidor Moodle.

3. **Limite de registros:** Não há limite de registros por consulta. Para períodos com muitas conclusões, considere consultar meses individualmente.

4. **Segurança:** O token é pessoal e intransferível. Não compartilhe em código-fonte público ou URLs expostas.

5. **Disponibilidade:** A API está disponível 24/7, sujeita à disponibilidade do servidor Moodle.

---

## Contato

Em caso de dúvidas ou problemas com a API, entre em contato com a equipe TechEduConnect.
