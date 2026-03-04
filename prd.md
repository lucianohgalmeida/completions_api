Objetivo (por que existe):
Permitir que o RH da CLEMAR consulte, em um período (ex.: um mês), quais colaboradores concluíram quais cursos, para fins de relatórios internos e integração com o sistema corporativo.

Contexto do pedido (reunião RH + TI)

O RH precisa identificar os colaboradores/clientes que concluíram cursos em um determinado mês.

A TI informou que consegue integrar no sistema deles se a TechEduConnect disponibilizar uma API consumível.

Foi combinado que o “código do curso” usado na integração será o campo “Número de identificação do curso” (idnumber) no Moodle — e que nós vamos padronizar/atualizar esse campo para ficar igual ao código do sistema da CLEMAR.

Para identificar o colaborador no sistema deles, a chave será a “matrícula do usuário”, que será armazenada/atualizada no Moodle no campo “Número de identificação” do usuário (idnumber do usuário).

Regras de negócio (o que deve acontecer)

1) Padronização dos identificadores (pré-requisito)

Cursos: garantir que todos os cursos relevantes tenham o Número de identificação do curso preenchido e igual ao código do curso no sistema da CLEMAR.

Usuários: garantir que os usuários tenham o Número de identificação preenchido e igual à matrícula do colaborador no sistema da CLEMAR.

Resultado esperado: TI da CLEMAR consegue cruzar dados usando:

codigo_curso = Número de identificação do curso (Moodle)

matricula = Número de identificação do usuário (Moodle)

2) Consulta por período (mês)

A solução deve permitir que a TI solicite os dados filtrando por:

Período de conclusão (ex.: 01/11/2025 a 30/11/2025)

Retornar apenas registros em que existe data de conclusão.

3) O que deve retornar em cada registro

Para cada conclusão dentro do período, a API deve retornar:

Curso

Código do curso (Número de identificação do curso)

Nome completo do curso

Nome breve do curso

Categoria do curso

Usuário

Matrícula do usuário (Número de identificação do usuário)

Nome

Sobrenome

E-mail

Conclusão

Status de Conclusão

Data de conclusão

Código do curso

Matrícula do usuário

4) Forma de acesso

A integração será feita via API do Moodle Web Service, com usuário de integração e token de acesso.

O endpoint precisa ser estável e seguro para consumo externo pela TI.

Critérios de aceite (como saber que está pronto)

Conseguimos consultar um mês e obter uma lista de conclusões com data de conclusão preenchida.

Cada item retorna código do curso (Número de identificação do curso) e matrícula do usuário (Número de identificação do usuário).

A TI consegue pegar o JSON e importar/conciliar no sistema deles sem tratamento manual.

API acessível com token (sem login interativo).

Validado com um mês “real” (ex.: mês anterior) e conferido com amostra do RH.

Entregáveis

Endpoint de consulta (via Web Service Moodle) retornando JSON conforme regras acima.

Registro/guia curto para a TI da CLEMAR contendo:

quais parâmetros informar (período)

exemplo de resposta

quais chaves usar (codigo_curso e matricula)

Dependências / Ações paralelas

Mapear quais cursos entram no relatório do RH (se é “todos” ou categorias específicas).

Alinhar com a CLEMAR a lista oficial de “códigos de curso” do sistema deles para atualizar no Moodle.

Alinhar origem da matrícula (qual campo/lista oficial) para atualizar o Número de identificação dos usuários.