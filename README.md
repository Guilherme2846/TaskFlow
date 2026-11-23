# TaskFlow · Agenda acadêmica

**Guilherme de Andrade Evangelista e Fabia Aurelie Losija**  
6º semestre de Análise e Desenvolvimento de Sistemas · Fatec Ourinhos  
Professor David Silva · Apresentação em 23/11/2026

Aplicação web para estudantes acompanharem trabalhos, provas e leituras por disciplina. Cada conta tem sua própria agenda. Ao entrar, o aluno vê as entregas atrasadas, as que vencem hoje e as próximas. Pode cadastrar, editar, pesquisar, filtrar, iniciar, concluir e excluir tarefas.

## Rodar no Windows com XAMPP

1. Extraia o ZIP ou clone https://github.com/Guilherme2846/TaskFlow.git e abra a pasta que contém este README.
2. Abra o PowerShell nessa pasta e execute:

   ```powershell
   C:\xampp\php\php.exe -S 127.0.0.1:8000 -t public
   ```

3. Acesse **http://127.0.0.1:8000**. Clique em **Criar uma conta**, informe nome, e-mail e uma senha própria. Em seguida, clique em **Nova tarefa**.
4. Mantenha o terminal aberto na apresentação. Ctrl+C encerra o servidor. Ao reiniciar, as tarefas continuam salvas.

Em outros ambientes com PHP 8.1 ou superior no PATH, execute `php -S 127.0.0.1:8000 -t public`. O driver `pdo_sqlite` deve aparecer em `php -m`. Caso esteja desabilitado no XAMPP, habilite `extension=pdo_sqlite` no php.ini usado pelo terminal. A pasta do projeto precisa permitir escrita. O banco é criado automaticamente, sem importar SQL. Não é necessário iniciar Apache ou MySQL.

## Arquitetura e técnicas

- **Interface** · HTML e CSS responsivos. O navegador envia formulários para `public/index.php`. GET consulta telas e filtros. POST registra mudanças.
- **Aplicação** · PHP identifica o usuário pela sessão, valida campos e datas, aplica as transições de status e recalcula os indicadores em cada consulta. Após gravar, redireciona o navegador à agenda.
- **Dados** · `src/app.php` inicializa o SQLite via PDO, valida dados e autentica a conta. A tabela users tem relação um-para-muitos com tasks. Cada operação de tarefa usa a identidade do usuário. Datas seguem o fuso de São Paulo.
- **Proteção** · Senhas são armazenadas como hashes. Formulários de escrita incluem um token de sessão. Valores de entrada são enviados por parâmetros nas consultas. O banco fica fora da pasta pública em `data/taskflow.sqlite` e é ignorado pelo Git.

## Demonstração em dupla

| Quem | Ação e fala sugerida |
| --- | --- |
| Fabia | Apresentar o problema. “Quando uma entrega está no mural da faculdade e outra nas minhas anotações, eu preciso consultar dois lugares para decidir o que fazer hoje.” Mostrar a agenda ao entrar. |
| Guilherme | Cadastrar **Apresentar o SaaS**, disciplina **Desenvolvimento web**, prazo **hoje**, prioridade **Alta**, status **Pendente**. Mostrar o contador **Vencem hoje**. |
| Fabia | Clicar em **Iniciar**. Explicar que o prazo continua valendo durante o trabalho. Clicar em **Concluir**. Mostrar o contador de concluídas subir e o de hoje diminuir. Filtrar por **Concluída** para recuperar a entrega. |
| Guilherme | Explicar o caminho do formulário até o SQLite e como uma conta consulta apenas suas tarefas. Mostrar o filtro por usuário no código e o caso de teste da tarefa concluída. |
| Dupla | Explicar o apoio da IA, a dificuldade de distinguir prazo de status, a hipótese de assinatura e o próximo passo. |

Antes da aula, cadastrem uma leitura com prazo de ontem e uma prova com prazo futuro para que a mudança nos indicadores fique visível. O relatório acadêmico acompanha a entrega. A apresentação em PDF pode ser exibida em tela cheia.

## Uso de IA e modelo de serviço

O ChatGPT com Codex apoiou o planejamento, a programação, os testes e a documentação. Um exemplo concreto foi testar uma tarefa que vence hoje antes e depois de concluída. Ela permanece na agenda e deixa de contar como urgente. Essa regra pode ser conferida na função `urgency` e demonstrada ao vivo.

A proposta comercial é hospedar uma única aplicação com contas individuais. Uma hipótese é oferecer gratuitamente a agenda básica e cobrar R$ 9,90 por mês por sessões de estudo e relatório semanal por disciplina. O preço é uma proposta acadêmica. O Plus pode ser experimentado por sete dias por conta. A cobrança recorrente é um próximo passo.


## Assistente de estudos e API

Na agenda, pergunte “Quais tarefas estão atrasadas?” ou “Por onde começar?”. O navegador chama a API do TaskFlow por POST em public/assistente.php. Ela exige sessão e token do formulário, consulta as tarefas da conta e devolve JSON. Cada pergunta é independente.

O modo padrão usa um classificador Naive Bayes multinomial treinado com exemplos curtos de sete intenções em português. Ele identifica a intenção e o PHP monta a resposta com os registros reais. Funciona offline. O vocabulário é limitado, e perguntas ambíguas podem ser classificadas incorretamente.

O modo OpenAI é opcional e gera texto pela Responses API. A escolha desse modo envia a pergunta e até 50 tarefas da disciplina selecionada (título, disciplina, prazo, prioridade e status). Para habilitar, configure OPENAI_API_KEY no ambiente do processo PHP, com uma chave de API com acesso ao modelo gpt-4.1-mini, e reinicie o servidor. OPENAI_MODEL permite escolher outro modelo disponível na conta. Mantenha a chave fora do código. O modo local continua disponível sem configuração.

## Planejamento semanal e quadro

A aba **Semana** organiza as entregas de segunda a domingo, permite consultar semanas anteriores ou futuras e destaca o dia atual. A aba **Quadro** reúne as mesmas tarefas em três colunas. Clique em Iniciar ou Concluir para avançar uma entrega. O título abre a edição. A aba Lista mantém a pesquisa e os filtros por prazo.

No assistente, selecione a disciplina ou consulte todas. As sugestões de pergunta executam a consulta. A semana no assistente cobre hoje e os seis dias seguintes. A aba Semana mostra a semana de calendário.

Referências de produto: [visualização Em breve do Todoist](https://www.todoist.com/pt-BR/help/todoist/get-started/plan-your-week-with-the-upcoming-view-OKOg1mR8) e [quadros Kanban do Asana](https://help.asana.com/s/article/asana-kanban-boards).

## Demonstração dos planos

Em Planos, ative o teste do Plus. Abra Estudos, selecione uma tarefa e registre 25 minutos. Confira a soma semanal por disciplina. O teste começa uma única vez por conta, vale por sete datas consecutivas e preserva o histórico para consulta após expirar. A versão acadêmica não processa pagamentos.
