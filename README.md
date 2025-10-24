# Documentação da Aplicação

Este arquivo descreve funcionalidades, regras e uso da aplicação, sem incluir informações de desenvolvedor ou de identidade do projeto. Abaixo há um passo a passo detalhado para iniciar, criar e executar o sistema com segurança.

## Pré-requisitos
- PHP 8+ instalado
- Extensões habilitadas: pdo_sqlite e sqlite3 (persistência em SQLite)
- Permissão de escrita nas pastas `data/` e `assets/uploads/`

Como verificar:
- `php -v` (verifica versão)
- `php -m` (lista módulos; confirme que `pdo_sqlite` e `sqlite3` aparecem)

Caso as extensões não estejam habilitadas, ajuste seu `php.ini` adicionando/garantindo:
- `extension=pdo_sqlite`
- `extension=sqlite3`

Alternativa de execução sem editar php.ini (exemplo):
- `php -S localhost:8000 -d extension=sqlite3 -d extension=pdo_sqlite router.php`

## Passo a passo de inicialização e start
1) Obtenha o código
- Faça o download dos arquivos do projeto (ou clone o repositório) para uma pasta local.

2) Verifique extensões e permissões
- Confirme que `pdo_sqlite` e `sqlite3` estão ativos (`php -m`).
- Garanta que a pasta `data/` existe e tem permissão de escrita. Se não existir, a aplicação criará automaticamente. O mesmo vale para `assets/uploads/`.

3) Inicie o servidor embutido do PHP
- Na raiz do projeto, execute:
  - Simples: `php -S localhost:8000 router.php`
  - Com flags de extensão (se necessário): `php -S localhost:8000 -d extension=sqlite3 -d extension=pdo_sqlite router.php`
  - Opcional para diagnóstico: adicionar `-d display_errors=1` para ver erros diretamente no navegador.

4) Acesse as páginas
- Login: `http://localhost:8000/login.php`
- Página principal (Home): `http://localhost:8000/index.php`
- Admin: `http://localhost:8000/admin/manage.php` (requer autenticação; o link "Admin" no cabeçalho só é exibido para administradores)

5) Primeira execução (criação automática do banco)
- Ao iniciar, o banco SQLite é criado automaticamente em `data/revvo.sqlite`.
- Se a tabela de usuários estiver vazia, um usuário de teste é criado como administrador:
  - Email: `teste@teste.com`
  - Senha: `123456`
- Faça login com esse usuário para acessar a aplicação e a área administrativa.

6) Fluxo inicial sugerido
- Acesse o Admin e crie alguns slides e cursos para substituir o conteúdo de fallback.
- Crie um segundo usuário administrador (via Admin) e depois teste a proteção do último admin:
  - Remover o status de admin do primeiro é permitido enquanto existe outro admin.
  - Tentar remover o status de admin do último administrador é bloqueado com status `admin_guard` e um popup explicativo.

7) Reset do ambiente (opcional)
- Para recriar o banco e o usuário de teste, pare o servidor e apague `data/revvo.sqlite`.
- Inicie novamente o servidor; o banco será criado e o seed executado conforme acima.
- Se quiser limpar imagens, remova arquivos dentro de `assets/uploads/` (cuidado para não apagar imagens necessárias ao layout, como `avatar.avif`, `cards.jpg` ou `modal_desk.jpg`).

## Estrutura principal
- Roteamento: `router.php` controla redirecionamentos, protege rotas com JWT e expõe APIs.
- Banco/Helpers: `inc/db.php` inicializa tabelas, faz upload de imagens, lida com JWT e utilitários.
- Views: `inc/views/*` contém cabeçalho, rodapé, modais e componentes.
- Páginas públicas: `public/index.php`, `public/login.php`, `public/logout.php`.
- Administração: `admin/manage.php` contém CRUD de cursos, slides e usuários.
- Assets: CSS/JS e uploads em `assets/*`.

## Autenticação e sessão (JWT)
- Ao autenticar, são emitidos dois cookies HttpOnly: `jwt` (access, ~15min) e `refresh` (~1h).
- Se o token de acesso expira, há tentativa automática de refresh para manter a sessão.
- Cookies usam SameSite=Lax e `secure` conforme HTTPS.
- O segredo do JWT é gerado e salvo em `data/jwt_secret.txt` na primeira execução.

## Página principal (Home)
- Carrega slides e cursos do banco. Se o banco estiver vazio ou pdo_sqlite desabilitado, exibe conteúdo de fallback (3 slides estáticos e 3 cards de cursos com imagem `assets/uploads/cards.jpg`).
- Disponibiliza variáveis globais JS com dados para o frontend:
  - `window.__SLIDES__`, `window.__CURSOS_DB__`, `window.__CURSOS_FALLBACK__`, `window.__CURSOS_IS_FALLBACK__`.
- Header exibe:
  - Avatar do usuário (default `assets/uploads/avatar.avif` ou o avatar configurado).
  - Nome do usuário seguido de `(admin)` se ele for administrador.
  - Menu do usuário com “Profile”, “Sair” e link “Admin” exibido somente para administradores.

## Modais
- Modal principal de destaque (conteúdo em `inc/views/modal.php`), com imagem e botão.
- Estado “mostrar modal principal” por usuário é controlado em banco (`usuarios.show_main_modal`).
- APIs para consultar e marcar como fechado (respostas padronizadas):
  - GET `/api/user/modal-state` → `200 {"success":true,"data":{"show_main_modal":boolean}}` (401 quando não autenticado)
  - POST `/api/user/main-modal/close` → `200 {"success":true,"data":{"modal_closed":true}}` (401 quando não autenticado)
- Alerta por popup no Admin para `admin_guard` (impede remover o último administrador).

## Administração (CRUD)
### Cursos
- Criar: título (obrigatório), descrição (obrigatória), imagem (obrigatória). Upload aceita `jpg`, `jpeg`, `png`, `gif`, `webp`.
- Editar: título, descrição e imagem (opcional). Se nenhuma imagem nova for enviada, a anterior é mantida.
- Excluir: remove o curso.

### Slides
- Criar: imagem (obrigatória), título (obrigatório), descrição (obrigatória), link (opcional; padrão `#`).
- Editar: título, descrição, link e imagem (opcional). Mantém a anterior se não houver nova.
- Excluir: remove o slide.

### Usuários
- Criar: nome (obrigatório), email (obrigatório e único), senha (obrigatória).
  - Avatar (opcional); Admin (checkbox opcional).
  - Emails duplicados geram status `dup` (alerta: “Email já cadastrado. Escolha outro.”).
- Editar: nome (obrigatório), email (obrigatório), Admin (checkbox), avatar (opcional).
  - Mantém avatar atual se nenhum novo for enviado.
  - Proteção: não é permitido remover o último administrador. Ao tentar, a operação é bloqueada com status `admin_guard` e um popup orienta: “Para remover o último administrador, defina outro usuário como administrador primeiro.”
- Observações:
  - Não há edição de senha no Admin (pode ser adicionada futuramente).
  - Não há exclusão de usuários no Admin, por enquanto.

## Alertas de status no Admin
- `ok`: operação realizada com sucesso.
- `err`: campos obrigatórios faltando (“Preencha os campos obrigatórios.”).
- `dup`: email já cadastrado.
- `admin_guard`: tentativa de remover o único admin; exibe alerta e modal.

## Regras e obrigatoriedades
- Somente usuários autenticados acessam `/index.php` e a área `/admin`.
- Somente administradores veem o botão/link “Admin” no cabeçalho.
- Upload de imagens é validado pela extensão. Arquivos são salvos em `assets/uploads/` com nome único.
- Emails são únicos (índice único no banco).
- Fallback da Home é exibido quando não há dados ou o SQLite está indisponível.

## APIs de customização da Home
- GET `/api/homepage-courses`: `200 {"success":true,"data":{"course_ids":[...],"recent_course_ids":[...]}}` (401 quando não autenticado)
- POST `/api/homepage-courses` body: `{ "course_id": number }` →
  - `200 {"success":true,"data":{"added":true,"course_id":<id>}}`
  - `400 {"success":false,"message":"ID do curso é obrigatório e deve ser numérico","error_code":"BAD_REQUEST"}`
  - `422 {"success":false,"message":"Falha ao adicionar curso","error_code":"ADD_COURSE_FAILED"}`
  - `401` quando não autenticado

## Dicas de teste
1. Faça login com `teste@teste.com` / `123456` (admin).
2. Verifique o fallback na Home se o banco estiver vazio ou sem pdo_sqlite.
3. Crie slides e cursos no Admin para substituir o fallback.
4. Crie um segundo usuário admin. Em seguida, tente remover o status de admin do primeiro; deve permitir. Depois, tente remover do último admin restante; deve bloquear com o alerta popup.
5. Verifique que o link “Admin” no menu do usuário só aparece para administradores, e que “(admin)” aparece ao lado do nome do admin.

## Solução de problemas (FAQ)
- “SQLite não carregou” → Verifique `php -m`. Se faltar, habilite `pdo_sqlite` e `sqlite3` no `php.ini` ou use flags `-d extension=sqlite3 -d extension=pdo_sqlite` ao iniciar.
- “Permissão negada ao criar `data/revvo.sqlite`” → Ajuste permissões da pasta `data/` (Windows: desbloquear pasta; Linux/macOS: `chmod` apropriado). Reinicie o servidor.
- “Página em branco ou erro 500” → Inicie com `-d display_errors=1` e verifique o log/console. Confirme rotas pelo `router.php`.
- “Porta 8000 ocupada” → Use outra porta: `php -S localhost:8080 router.php` e acesse `http://localhost:8080`.
- “Sessão expirada (JWT)” → Faça login novamente. O refresh pode não ocorrer se o cookie de refresh tiver expirado.

## Formato das respostas da API
- Sucesso (`200`): `{"success":true,"data":{...},"timestamp":"<ISO8601>"}`
- Erro genérico (`400`): `{"success":false,"error":{"message":"<mensagem>","code":"BAD_REQUEST"},"timestamp":"<ISO8601>"}`
- Não autorizado (`401`): `{"success":false,"error":{"message":"Não autorizado","code":"UNAUTHORIZED"},"timestamp":"<ISO8601>"}`
- Não encontrado (`404`): `{"success":false,"error":{"message":"Não encontrado","code":"NOT_FOUND"},"timestamp":"<ISO8601>"}`
- Erro de negócio (`422`): `{"success":false,"error":{"message":"<mensagem>","code":"<CÓDIGO_ESPECÍFICO>"},"timestamp":"<ISO8601>"}`
- Erro interno (`500`): `{"success":false,"error":{"message":"Erro interno","code":"INTERNAL_ERROR"},"timestamp":"<ISO8601>"}`

## Segurança (Resumo)
- CSRF: campos ocultos com token nas formas do Admin; validação no servidor (`CsrfService` + `AdminController`).
- Uploads: validação de MIME, extensão e tamanho; nomes únicos e caminhos seguros (via `UploadService`).
- Views x Dados: remoção de queries PDO diretas, uso de Repositories (ex.: `UserRepository::listAll()` em `admin/manage.php`).
- Autenticação: cookies HttpOnly com SameSite; tentativa de refresh para sessão contínua.

## Testes Automatizados (Smoke)
- Scripts de smoke test para APIs foram adicionados em `tests/`.
- Como rodar:
  1) Inicie `php -S localhost:8080 router.php`
  2) Execute `php tests/api_smoke.php` para verificar respostas não autenticadas (401) e formato JSON.
  3) Para testar autenticado, copie o cookie `jwt` do navegador e ajuste a constante `COOKIE_HEADER` no script.
- Os testes cobrem:
  - GET `/api/homepage-courses` (401 sem autenticação)
  - POST `/api/homepage-courses` (401 sem autenticação; validações se autenticado)
  - GET `/api/user/modal-state` (401 sem autenticação)
  - POST `/api/user/main-modal/close` (401 sem autenticação)

## Observações sobre versionamento
- O arquivo do banco (`data/*.sqlite`) geralmente não deve ser versionado; o banco é recriado ao iniciar.
- O segredo JWT é salvo em `data/jwt_secret.txt`. Para maior segurança, considere adicioná-lo ao `.gitignore` se ainda não estiver.
- A pasta `assets/uploads/` pode conter arquivos gerados pelo uso da aplicação. Decide se devem ser versionados conforme a política do projeto.

## Qualidade e Operação

### Health Check
- Endpoint: `GET /api/health`
- Retorno: `200 OK` com JSON padronizado:
```
{
  "success": true,
  "data": {
    "status": "ok",
    "php_version": "8.x.x",
    "sqlite_enabled": true,
    "time": "2025-01-01T12:00:00Z"
  },
  "timestamp": "2025-01-01T12:00:00Z"
}
```
- Útil para monitoramento e validação rápida do ambiente.

### Logger
- Serviço: `App\Services\Logger`
- Saída: `data/logs/app.log` (JSON Lines: `timestamp`, `level`, `message`, `context`)
- Níveis suportados: `info`, `warning`, `error`
- Uso interno em erros de API e handlers globais (router).

### Handlers Globais
- Configurados no `router.php`: `set_error_handler`, `set_exception_handler`, `register_shutdown_function`
- Comportamento:
  - Em rotas `\/api\/...`: responde com `ApiResponse::internalError` (JSON padronizado)
  - Em páginas públicas/admin: define `500` e exibe mensagem simples
- Todos os eventos críticos são logados no `app.log`.

### Scripts de Qualidade e Saúde
- `scripts/quality.ps1`:
  - Lint: `php -l` em todos `.php`
  - Executa `tests/api_smoke.php`
  - Mostra últimos logs (`data/logs/app.log`)
  - Uso: `powershell -ExecutionPolicy Bypass -File .\scripts\quality.ps1 -BaseUrl http://localhost:8080`
- `scripts/health.ps1`:
  - Consulta `GET /api/health` e imprime campos principais
  - Exit `0` em OK, `1` em falha
  - Uso: `powershell -ExecutionPolicy Bypass -File .\scripts\health.ps1 -BaseUrl http://localhost:8080`

Dica: se usar outra porta, ajuste `-BaseUrl` nos scripts. Se rodar o servidor com `php -S localhost:8000 router.php`, use `http://localhost:8000`.

## Testes (Unitários)
- Pré-requisitos:
  - `PHP >= 8.1`
  - `Composer` instalado (ou `composer.phar` na raiz)
  - Extensão `mbstring` habilitada no CLI
- Instalação de dependências:
  - `php composer.phar update phpunit/phpunit -n` (se necessário, usar `--ignore-platform-req=ext-mbstring`)
- Habilitar `mbstring` no CLI:
  - Descobrir qual `php.ini` está em uso: `php --ini`
  - Editar o `php.ini` do CLI e garantir `extension=mbstring` habilitada
  - No Windows, verifique se `ext\\php_mbstring.dll` existe e se `extension_dir` aponta para a pasta `ext`
- Como executar os testes:
  - Com `mbstring` habilitado: `vendor\\bin\\phpunit -c phpunit.xml`
  - Sem habilitar globalmente: `php -d extension=mbstring vendor\\bin\\phpunit -c phpunit.xml`
  - Executar arquivo específico: `php -d extension=mbstring vendor\\bin\\phpunit tests\\unit\\Repositories\\SlideRepositoryTest.php`
- Estrutura dos testes:
  - O bootstrap (`tests/bootstrap.php`) usa SQLite em memória e prepara o esquema de testes
  - Suites em `tests/unit`: Services (`CsrfService`) e Repositories (`User`, `Course`, `Homepage`, `Slide`)
- Cobertura (opcional):
  - `vendor\\bin\\phpunit --coverage-text` (requer `xdebug` ou `pcov` habilitado)
  - `401` quando não autenticado