# Overview

Anubis Serve is a browser-based RPG set in the Naruto universe. Players create ninja characters, train abilities, engage in battles, and progress through the game world. The application is built with PHP 8+ and **MySQL only** (SQLite support has been fully removed). Features a traditional server-side rendered architecture with AJAX enhancements for dynamic interactions, a guided 5-step web installer (`/install/`), and automatic schema migrations on every request via `_inc/mysql_compat.php`.

## Backup system (admin)
- Painel completo em `adm/?modulo=backup` (apenas ADM ou GM com permissão `backup`).
- Engine em `_inc/backup_engine.php` usa `mariadb-dump`/`mysqldump` para fazer dump dos bancos `naruto` e `forum` (este último opcional).
- Configuração persistida na tabela `backup_config` (modo: minutos 1-60 / horas 1-24 / semanal dia+hora; pasta destino; rotação por quantidade).
- Tick automático em `_inc/conexao.php` (`backup_tick_check`) dispara o backup quando `proximo_backup <= NOW() AND ativo=1`. Sem cron — funciona em qualquer hospedagem.
- Histórico em `backup_historico` com download (`?download=ID&tipo=naruto|forum`) e remoção. Pasta `backups/` protegida por `.htaccess` (Deny from all) e ignorada no git.

## Character system (per-user unlock)
- 4 starters always available (`naruto`, `sasuke`, `sakura`, `kakashi`).
- 29 unlockable characters defined in `_inc/personagens_catalogo.php` with required level (5–60); the last 7 are flagged VIP.
- One row per user in the `personagens` table with a TINYINT(1) column per unlockable character.
- Unlocks happen automatically on level-up via `personagens_unlock_por_nivel()`, called from `_inc/verifica_nivel.php` and `_inc/verifica_nivelatk.php`. VIP characters require an active VIP subscription to be selected (checked in `_inc/config_char.php`).
- The shop only displays characters as a vitrine (`_inc/shop_characters.php`); buying is disabled — unlocks are by level only.
- The installer (Step 4) lets the admin pick any character from the full catalog (including VIP) for the ADM account.

# User Preferences

Preferred communication style: Simple, everyday language.

# System Architecture

## Frontend Architecture

**Traditional Server-Side Rendering**: The application uses PHP to generate HTML pages server-side, with minimal client-side JavaScript for enhanced interactivity. Pages are structured using a template-based approach with includes for common elements (header, footer, navigation).

**CSS Styling System**: Multiple CSS files handle different aspects:
- `naruto.css` - Main game styling with dark theme (#BBBBBB text on dark backgrounds)
- `modal.css` - Modal dialog system
- `banner_invasao.css` - Invasion event banner system with animations
- Custom box/border styling using background images

**JavaScript Enhancements**: 
- jQuery 1.2.6 for DOM manipulation and AJAX
- Custom AJAX loading system (`scripts.js`) for dynamic content updates
- Modal dialog system (`jquery-modal-1.0.pack.js`)
- Banner notification system for game events
- TinyMCE rich text editor for content management

**Rationale**: Server-side rendering was chosen for simplicity and broad browser compatibility. The dark theme aligns with the Naruto aesthetic. AJAX is used selectively to improve UX without full SPA complexity.

## Backend Architecture

**PHP 8.2+ Procedural Style**: Core application logic uses procedural PHP with includes for modularity. Files in `_inc/` directory contain feature-specific code (battles, messages, map system, etc.).

**File-Based Routing**: The main `index.php` file handles routing through query parameters and includes, loading different modules based on user actions.

**Session Management**: PHP sessions manage user authentication and state. Login credentials are verified against the database, with session data persisting user information.

**Modular Structure**:
- `_inc/` - Core game logic modules (attack.php, messages_form.php, mapa.php, report.php, etc.)
- `adm/` - Administrative interface with news management system
- `news/` - Legacy CuteNews system (being modernized with MVC approach)
- `noticia/` - Modern news system with MVC architecture:
  - `model/NoticiaRepository.php` - Database operations for news
  - `controllers/AdminController.php` - Admin news management
  - `controllers/PublicController.php` - Public news display
  - `views/admin/` - Admin interface templates
  - `views/public/` - Public news display templates
  - `helpers/ColorHelper.php` - BBCode color tag rendering
  - `helpers/SecurityHelper.php` - CSRF protection and input sanitization
- `config/` - Configuration files including reCAPTCHA settings

**Rationale**: Procedural PHP provides straightforward development and debugging. File-based routing is simple to understand and modify. The modular include system allows feature isolation while sharing common functionality.

## Data Storage

**SQLite Database**: Primary data storage using `database.sqlite` file. Tables store:
- Player accounts and characters
- Game statistics (levels, abilities, items)
- Messages between players
- Battle reports
- News articles with color formatting and expiration tracking (`noticias` table with `data_expiracao` and `usar_cores` fields)
- News read tracking (`noticia_lida` table with cascading deletes)
- Administrative data

**File-Based Storage**: Legacy systems (CuteNews) use text files in `news/data/` directory for content storage. This is being migrated to database storage.

**Rationale**: SQLite chosen for simplicity - no separate database server required, easy deployment, sufficient for game scale. File permissions handle security. Migration to database-backed news system improves performance and provides better data structure.

## Authentication & Security

**Google reCAPTCHA v2**: Login form protected against bots using "I'm not a robot" checkbox verification. Configuration stored in `config/recaptcha.php` (excluded from version control via `.gitignore`).

**Session-Based Authentication**: User login creates PHP session with player data. Subsequent requests validate session existence and user privileges.

**Input Sanitization**: Database queries use prepared statements or sanitization functions to prevent SQL injection. User input is filtered before processing.

**Rationale**: reCAPTCHA v2 provides strong bot protection with good UX. Session-based auth is straightforward for server-rendered architecture. Configuration file separation allows per-environment settings.

## Game Systems

**Battle System**: Turn-based combat calculations performed server-side. Battle reports stored in database with detailed logs of damage, abilities used, and outcomes. Players can view historical battle results.

**Messaging System**: Player-to-player communication stored in database. Recent fix addressed form submission issues where disabled buttons prevented POST data transmission.

**News System with Color Customization**: Admin interface allows creation and management of game news with custom color formatting using BBCode-style tags. Features include:
- Color tags: `[cor=#HEXCODE]text[/cor]` for custom text colors
- Additional formatting: `[b]bold[/b]`, `[i]italic[/i]`, `[u]underline[/u]`
- Auto-expiration: Admins can set news posts to automatically hide after a specified number of days
- Color helper with preset color buttons (Red, Green, Blue, Gold, Pink, White)
- Status indicators showing whether news is permanent, active, or expired
- Separate views for admin (sees all news including expired) and public (sees only active news)

**Map/Location System**: Complete tilemap-based world navigation system with multiplayer support:
- **World Map (MapaBase)**: Larger navigable map using `_img/mapas_vilas/MapaBase.jpg` with 20px tiles - camera follows player position for exploration of vast distances
- **Village Maps**: 7 unique villages (Akatsuki, Areia, Chuva, Folha, Nevoa, Nuvem, Pedra) with 40px tiles in `_img/mapas_vilas/` - static camera fits map to canvas
- **Real-time Multiplayer**: Players see each other on the same map via PHP polling (3 second intervals)
- **Movement System**: WASD/Arrow key controls; normal players move 1 tile, VIP players move 2 tiles per keypress
- **Portal System**: Automatic transitions between world map and villages via configured entry/exit points using icone_vila.png
- **Admin Editor**: In-game map editing for admins to create/remove entry points and exits between maps - icone_vila.png used automatically
- **Files**: `_inc/mapa.php` (frontend), `_inc/map_api.php` (backend API), `map_config.json` (portal configuration), `map_players.json` (player positions)
- **Camera System**: 
  - World Map (MapaBase): Dynamic camera follows player, allowing exploration of large map
  - Village Maps: Static camera with scaled canvas rendering - map fits entirely in view
- **Grid Overlay**: Always visible grid system showing tile-based movement structure
- **Player Icons Based on Relationship**:
  - `Ninja_personagem.png`: Current logged-in player (you)
  - `Ninja_vila.jpg`: Players from the same village (green in player list)
  - `Ninja_aliado.jpg`: Players from allied villages (blue in player list)
  - `Ninja_Inimigo.jpg`: Players from enemy villages (red in player list)
  - `Ninja_bot.jpg`: Bot ninjas (purple in player list) - prepared for future bot system
- **Visual Legend**: On-screen legend explaining icon meanings
- **Alliance System**: Villages 1 (Folha) and 2 (Areia) are allies; other villages have no alliances
- **Player Persistence**: Positions saved to `map_players.json` with TTL cleanup, positions restored on reconnect with vila-based initial positions
- **Travel Restrictions**: Players outside their home village (or allied villages) are restricted from accessing other pages until they return; handled in `_inc/verificar.php` with robust error handling for missing/stale data

**Invasion Events**: Dynamic banner system notifies players of server-wide invasion events. Uses JavaScript animations and AJAX polling to check for active events.

**Forum System**: Community discussion platform with category-based organization and advanced features:
- **Standalone Design**: Independent page layout with custom header (logo.jpg) and footer (Rodape.jpg)
- **Reaction System**: Multiple emoji reactions (heart ❤️, laughing 😂, sad 😢, angry 😠, surprised 😮) using AJAX for real-time updates
- **Player Integration**: Displays player avatars and village affiliations from main PostgreSQL database
- **Vila Neutra**: Universal category accessible to all players regardless of village affiliation
- **Village-Based Access**: Categories restricted by player village (Konoha, Areia, Som, Chuva, Nuvem, Névoa, Rocha, Akatsuki)
- **Database Architecture**: SQLite for forum data (`forum.sqlite`), PostgreSQL for player data integration
- **Security**: CSRF-protected forms, sanitized inputs, prepared statements for SQL queries

**VIP System**: Premium membership system with enhanced gameplay benefits:
- **Privileges Documentation**: Full comparison table in `_inc/vip1.php` showing 15 VIP benefits
- **Shop Discounts**: VIP users receive 10% off consumables, 15% off weapons, 20% off equipment
- **Hunt System Enhancements**: 
  - Faster energy regeneration during hunts
  - Hunt cancel feature (3x per day with proportional rewards via `cancelhunt.php`)
  - Enhanced loot and experience from hunts
- **Visual Indicators**:
  - Golden border (#FFD700) with glow effect on VIP player avatars
  - Appears in profile pages (`view.php`), ranking lists (`rank.php`), and user menu (`menu_on.php`)
  - "VIP" badge displayed on profile avatars with gradient styling
- **VIP Status Check**: Uses date comparison `date('Y-m-d H:i:s') < $db['vip']` for active status
- **Database Field**: `vip` datetime field in `usuarios` table stores VIP expiration date

**Rationale**: Server-side battle calculations prevent cheating. Database-backed messaging ensures reliability. Event systems add dynamic content to maintain player engagement. Forum provides community engagement while maintaining village-based privacy and security. VIP system provides monetization while enhancing player experience with visual distinction and gameplay conveniences.

# External Dependencies

## Third-Party Services

**Google reCAPTCHA API**: Bot protection for login form
- Site key and secret key required
- Configuration: `config/recaptcha.php`
- Documentation: `CONFIGURACAO_RECAPTCHA.md`

## JavaScript Libraries

**jQuery 1.2.6**: DOM manipulation and AJAX requests (legacy version, potential upgrade candidate)

**TinyMCE 3.2.x**: WYSIWYG editor for news and content management (legacy version in `_js/tinymce/`)

**Custom Libraries**:
- Walter Zorn tooltip library (`_js/wz/`)
- Custom modal dialog system
- Rich text editor (RTE) for news system (`news/rte/`)

## PHP Extensions Required

- SQLite3 (PDO_SQLITE)
- Session support
- cURL or file_get_contents with allow_url_fopen (for reCAPTCHA verification)
- GD library (likely for image manipulation, though not explicitly confirmed)

## Recent Bug Fixes (2026-04-25)

A user-reported bug pass corrected the following issues across the codebase:

- **`adm/adm.php` (linha 2291)** — Subquery em `FROM` sem alias estava causando erro de sintaxe SQL na página `?p=adm&modulo=denuncias`. Adicionado `AS sub` no `SELECT COUNT(*) FROM (...) AS sub`.
- **`_inc/top.php` (linhas 7 e 25)** — Link "Manual" no menu superior apontava para `http://pt.wikipedia.org/wiki/AnubisServe` (link externo de wikipedia). Corrigido para `?p=manual` (página interna do manual).
- **`_inc/config_avat.php`** — A página de troca de avatar mostrava sempre os avatares do Naruto, mesmo para jogadores com outros personagens. A variável `$char_atual` agora é inicializada a partir de `$db['personagem']` quando não está definida, garantindo que os avatares correspondam ao personagem atual.
- **`_inc/config_char.php` (linha 105)** — Imagens dos personagens bloqueados (`unlock_*.jpg`) tem 220x100px e estavam sendo renderizadas em tamanho real, deformando o layout. Adicionado `style="width:110px;height:60px;object-fit:cover;"` para padronizar tamanho.
- **`_inc/login.php` (topo)** — Adicionado guard de sessão: se `$_SESSION['logado']` já existir, redireciona para `?p=home` com headers `Cache-Control: no-store` para impedir que o navegador re-exiba a página de login após o usuário ter logado (proteção contra back-button cache).
- **`_inc/conexao.php`** — Adicionado salvaguarda `CREATE TABLE IF NOT EXISTS` para `invasoes` e `inventario` (executado uma vez por sessão via flag `$_SESSION['__schema_check_done']`). Resolve erro `Table 'anubis.invasoes' doesn't exist` em bancos legados (XAMPP) que não foram reimportados após a atualização do schema.
- **`adm/adm.php` (vilas + Akatsuki)** — O array `$vilas` (linhas ~510 e ~1039) estava com IDs trocados (Némoa=3, Pedra=4, Som=6, Chuva=7) e tratava Akatsuki como uma vila (vila=8). Isto causava: (a) salvar a vila errada quando o ADM editava um jogador (ex.: escolher "Pedra" no painel salvava vila 4 = Chuva); (b) o checkbox de Akatsuki vinha de `vila == 8`, então selecionar "Pedra" marcava o jogador como renegado. Corrigido para os IDs reais (1=Folha, 2=Areia, 3=Som, 4=Chuva, 5=Nuvem, 6=Névoa, 8=Pedra) e adicionado checkbox separado **Akatsuki (Renegado)** que controla apenas a coluna `renegado`, mantendo a vila de origem do jogador.
- **Doujutsu — bônus mínimo de 1 ponto** (`_inc/home.php`, `_inc/attack.php`, `_inc/attackinIn.php`) — Em nível 1 com atributo base baixo, a fórmula `round(stat * (nivel/50))` arredondava para 0, então o jogador ficava sem status do Doujutsu. Aplicado `max(1, round(...))` para garantir que, com Doujutsu desbloqueado, o jogador receba pelo menos +1 no atributo correspondente (Sharingan→Gen, Byakugan→Tai, Rinnegan→Nin). Mudança aplicada na home (display + tooltip) e nas duas funções de combate (consistência durante batalhas).
- **Instalador — i18n com Espanhol e bandeiras SVG** (`install/install.php`) — O título "Instalador Naruto By Anubis" estava em uma `const INSTALL_TITLE` hard-coded e nunca traduzia. Convertido para a chave `t('install_title')` em todos os pontos de exibição (constante mantida só para uso em logs internos). Adicionado idioma **Espanhol (`es`)** com tradução completa de ~120 chaves. O seletor de idioma deixou de mostrar texto "🇧🇷 PT / 🇺🇸 EN" e passou a renderizar **bandeiras SVG inline** (BR, US, ES) sem nenhum arquivo de imagem extra. O atributo `<html lang>` agora também reconhece `es`.
- **reCAPTCHA v3 (suporte opcional)** (`config/recaptcha.php.example`, `_inc/funcoes_manutencao.php`, `_inc/login.php`) — Sistema de captcha agora aceita as duas versões do Google reCAPTCHA via campo `version` no `config/recaptcha.php`:
  - `'version' => 'v2'` (padrão, retroativo) — caixa "Não sou um robô" tradicional.
  - `'version' => 'v3'` — verificação invisível baseada em pontuação (sem clique). Requer chaves novas do v3 (não compatíveis com v2). Pontuação mínima ajustável via `'min_score' => 0.5` (entre 0.0 e 1.0).
  
  A função `verificar_recaptcha($response, $action_esperada = null)` agora valida pontuação ≥ `min_score` quando v3 e (opcionalmente) confere a `action` enviada pelo `grecaptcha.execute()`. O formulário de login renderiza o widget v2 OU injeta o script invisível do v3 (carrega o token no campo hidden no submit) conforme a versão configurada. Configs antigas sem `version` continuam funcionando como v2 (default retroativo). Foi criado `config/recaptcha.php.example` documentando os dois modos.

## Legacy Systems

**CuteNews**: Third-party news management system located in `news/` directory. Currently being replaced with custom MVC implementation using database storage. License file indicates free use with attribution requirement.

**LiveZilla**: Live support chat system in `_support/` directory (appears inactive/unused)

**Rationale**: External services minimize development effort for complex features like bot protection. jQuery provides cross-browser compatibility. Legacy systems being modernized to reduce external dependencies and improve maintainability.
## Refactor de Segurança e Preparação para MySQL (24/04/2026)

Primeiro passe de hardening do projeto (sem quebrar nada existente):

### Novos arquivos
- **`config/database.php`** — configuração centralizada do banco (driver, host, dbname, etc.). Lê variáveis de ambiente `DB_DRIVER`, `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`. Para migrar para MySQL: setar `DB_DRIVER=mysql` + credenciais. Default = SQLite.
- **`_inc/Database.php`** — fábrica singleton de PDO. Métodos: `Database::conn()`, `Database::driver()`, `Database::isMysql()`, `Database::nowExpr()`. Substitui o `new PDO(...)` direto em `conexao.php`. Backward-compatible: `$conexao` continua disponível em todos os scripts.
- **`_inc/security.php`** — utilitários de segurança carregados ANTES de `session_start`:
  - Cookies de sessão hardened (`HttpOnly`, `SameSite=Lax`, `Secure` quando HTTPS)
  - Headers HTTP: `X-Content-Type-Options`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`, `Permissions-Policy`, `Strict-Transport-Security` (em HTTPS), `Content-Security-Policy` (default-src 'self' + inline ainda permitido pra não quebrar scripts antigos; `object-src 'none'`)
  - Helpers: `e($v)` (escape XSS), `input_int()`, `input_str()`
  - CSRF: `csrf_token()`, `csrf_field()`, `csrf_validar()`
  - Senhas: `senha_hash()`, `senha_verificar()` (aceita md5 legado E password_hash moderno), `senha_precisa_rehash()`
  - Rate limit: `rate_limit_check($acao, $max, $janela)`, `rate_limit_reset($acao)`

### Mudanças em arquivos existentes
- **`_inc/conexao.php`**: agora carrega `security.php` antes de `session_start` e usa `Database::conn()` no lugar do PDO direto.
- **`index.php`** (login):
  - Trocou `md5($_POST['login_senha']) != $db['senha']` por `senha_verificar()`.
  - Auto-upgrade silencioso: ao logar com senha legada (md5), o hash é regravado como `password_hash` (bcrypt) em background.
  - Adicionado `rate_limit_check('login', 8, 300)` — máx 8 tentativas em 5 min por sessão.
  - `rate_limit_reset('login')` no login bem-sucedido.
- **`_inc/reg.php`**: novos cadastros gravam senha com `senha_hash()` (bcrypt) em vez de md5.
- **`_inc/config_pass.php`**: troca de senha usa `senha_verificar()` para validar a atual e `senha_hash()` para gravar a nova.

### Convenção de senhas (importante)
- Schema da coluna `senha` continua `VARCHAR(...)` mas agora pode conter:
  - md5 hex de 32 caracteres (legado, ainda aceito no login)
  - hash `password_hash` de 60+ caracteres iniciando com `$2y$` ou `$argon2...`
- A migração é gradual e automática: cada usuário vira pra bcrypt no próximo login bem-sucedido. Nunca tocar manualmente.

### O que ainda está pendente do prompt original
- Aplicar `csrf_field()` / `csrf_validar()` nos formulários POST sensíveis (transferências, vendas, deletes administrativos). Helpers prontos, falta retrofit por arquivo.
- Trocar concatenação de variáveis em SQL legado pelos prepared statements equivalentes (ainda há queries antigas em alguns includes — buscar com `grep "\\$.*\\.\\.\\$"` em `_inc/`).
- Escapar saídas com `e()` em todos os `echo $db[...]`, especialmente em mensagens, fórum, perfis. Helper pronto.
- Endurecer CSP: hoje ainda permite `'unsafe-inline'` e `'unsafe-eval'` porque o jogo usa muito JS inline antigo (jQuery 1.2.6, wz_tooltip). Migração gradual.
- Validação de uploads de arquivos (apenas se houver upload aberto a usuários — verificar em `adm/` e config de avatar).
- Migrar `news/` (CuteNews) — sistema legado com risco próprio.

### Status MySQL
- Camada PDO 100%, sem código SQLite-específico no código novo.
- `_inc/conexao.php` ainda contém migrações com `PRAGMA` e `datetime('now')` específicos do SQLite — funcionam normalmente em SQLite e os blocos estão envoltos em try/catch. Para MySQL, criar um script de migração equivalente (recomendo `migrations/mysql.sql`) e desligar essas migrações via `Database::isMysql()`.
- `_inc/mysql_compat.php` (já existente) provê adaptações para o código legado que usava funções tipo `mysql_*`.

## Migração para MySQL-only (25/04/2026)

O projeto agora usa **exclusivamente MySQL**. Todo o suporte a SQLite foi removido,
incluindo bancos `.sqlite`, conversor on-the-fly e ramos condicionais por driver.

### O que mudou
- **Bancos `.sqlite` removidos**: `database.sqlite` e `forum.sqlite` deletados.
  Em seu lugar, dois dumps MySQL prontos: `database.sql` (~263 KB, 50+ tabelas
  com conteúdo estático) e `forum.sql` (~6,5 KB, com categorias do fórum).
- **`config/database.php`**: removida a chave `'sqlite'`. Apenas `'mysql'` e
  `'mysql_forum'` (este último opcional — se vazio, fórum compartilha o banco
  principal). `driver` fixo em `'mysql'`.
- **`_inc/Database.php`**: reescrita MySQL-only. Métodos `isMysql()` (sempre
  `true`) e `nowExpr()` (sempre `'NOW()'`) preservados para compatibilidade
  com ~20 arquivos legados que ainda usam ternários por driver.
- **`_inc/conexao.php`**: removidas todas as migrações `PRAGMA` /
  `datetime('now')` / `INTEGER PRIMARY KEY AUTOINCREMENT`. O esquema é criado
  pelo instalador (vindo do dump). Continua carregando `mysql_compat.php`
  e `cutenews_sso.php`.
- **`forum/helpers/ForumDB.php`**: reescrito — usa `Database::forumConn()`
  diretamente, sem fallback para SQLite e sem migrações em runtime.
- **Instalador (`install/install.php`)**: substituída a importação on-the-fly
  do SQLite pela aplicação dos dumps `.sql`. Removidos checks de extensões
  `pdo_sqlite` / `sqlite3` e a ajuda contextual sobre como habilitá-las.
- **`install/lib_convert.php` deletado**. Substituído por
  **`install/lib_sql_import.php`** com `importar_dump_mysql(PDO $mysql,
  string $arquivo, array &$log, array $skipDataFor)`:
  - Faz parsing simples de statements separados por `;` fora de
    strings/comentários (suporta `'`, `"`, `` ` ``, `--`, `#`, `/* */`).
  - Executa cada statement com `FOREIGN_KEY_CHECKS=0` durante a importação.
  - Se uma tabela está em `$skipDataFor`, INSERTs dela são pulados (apenas
    o CREATE TABLE é aplicado — usado para criar as contas/personagens
    vazios mas manter conteúdo estático como itens, jutsus, missões etc.).
- **`adm/limpar_ip.php`** e **`adm/gerenciar_clas.php`**: trocados
  `DELETE FROM sqlite_sequence` por `ALTER TABLE ... AUTO_INCREMENT = 1`.
- **`_tools/generate_mysql_dump.php`** (utilitário usado para gerar os
  dumps a partir dos antigos `.sqlite`): também removido após uso.

### Geração dos dumps (histórico)
Os arquivos `database.sql` e `forum.sql` foram gerados uma única vez a partir
dos antigos bancos SQLite via script auxiliar (já deletado). Eles devem ser
versionados junto com o código — o instalador depende deles para criar
a estrutura inicial. Para regenerar dumps a partir de uma instalação MySQL
existente, basta usar `mysqldump` padrão.

### Fluxo do instalador (atualizado)
1. **Requisitos** — checa PHP ≥ 8.0, `pdo_mysql`, presença dos dumps
   `database.sql` e `forum.sql`, e permissão de escrita em `config/`
   e `install/`.
2. **Banco MySQL** — formulário de conexão (host, porta, usuário, senha,
   nome do banco do jogo e nome opcional do banco do fórum).
3. **Servidores** — quantos mundos (1 a 10) e capacidade de cada.
4. **Conta ADM** — usuário, email e senha do administrador.
5. **Importar & Concluir** — aplica `database.sql` e `forum.sql` no(s)
   banco(s), ajusta a coluna `usuarios.senha` para `VARCHAR(255)` (compatível
   com bcrypt), cria os servidores configurados, cria a conta ADM e grava
   `config/database.php`.

### Por que esta abordagem
- **Hostgator-friendly**: o usuário hospeda em servidor compartilhado (Hostgator)
  onde só MySQL está disponível. Suporte a SQLite era código morto em produção.
- **Mais simples**: 1 driver, 1 esquema, sem ramos condicionais por driver
  em conexao.php, ForumDB e adm/.
- **Mais rápido na instalação**: aplicar um dump pronto é muito mais rápido
  do que ler tabela-por-tabela do SQLite e regenerar CREATE TABLE em runtime.
- **Determinístico**: o esquema fica versionado em `database.sql` /
  `forum.sql`, fácil de revisar em diff.

## Sistema de Instalação MySQL (24/04/2026)

Adicionado wizard de instalação completo em `install/install.php` para migrar
o jogo de SQLite para MySQL em servidor próprio.

### Arquivos
- **`install/install.php`** — wizard de 4 passos com interface dark/laranja:
  1. **Requisitos** — checa PHP ≥ 8.0, `pdo_mysql`, `pdo_sqlite`, `sqlite3`,
     existência de `database.sqlite` / `forum.sqlite` e permissão de escrita em
     `config/` e `install/`.
  2. **Banco MySQL** — formulário com host, porta, usuário, senha e nome do banco.
     Cria o banco automaticamente se não existir (`CREATE DATABASE IF NOT EXISTS`).
  3. **Conta ADM** — formulário com usuário, email e senha (com confirmação)
     do administrador do jogo. Validação: usuário 3-20 caracteres alfanuméricos,
     email válido, senha ≥ 6 chars.
  4. **Importar & Concluir** — converte os dois SQLite (`database.sqlite` +
     `forum.sqlite`) para MySQL, cria/promove a conta ADM (`adm=1`, `nivel=99`,
     `status=ativo`), grava `config/database.php` apontando para o MySQL e
     mostra log completo da operação.
- **`install/lib_convert.php`** — `sqlite_para_mysql(PDO $sqlite, PDO $mysql, string $prefix, array &$log)`:
  - Lê esquema via `PRAGMA table_info` e mapeia tipos
    (`INTEGER`→`INT`, `INTEGER PK AUTOINCREMENT`→`INT AUTO_INCREMENT PK`,
     `datetime('now')`→`CURRENT_TIMESTAMP`, etc.).
  - Recria cada tabela com `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`.
  - Copia dados via prepared statements em transação, batch por tabela.
  - Porta UNIQUE inline e índices secundários (`CREATE INDEX`).
  - Limitações: não recria FOREIGN KEYs, CHECK, triggers ou views (não usadas).
- **`install/concluir.php`** — apaga todos os arquivos de `install/` e tenta
  `rmdir()`. Se a pasta não puder ser removida (permissão), avisa para apagar
  manualmente. Redireciona para `?p=login`.

### Fluxo de uso (deploy em servidor próprio)
1. Subir o projeto inteiro (incluindo `install/`, `database.sqlite` e `forum.sqlite`).
2. Acessar `https://seudominio/install/install.php`.
3. Preencher os passos do wizard.
4. Clicar em "Apagar pasta install/ e ir para o jogo" no final.
5. Pronto — o jogo agora roda em MySQL.

### Mudanças de código de suporte
- **`forum/helpers/ForumDB.php`**: detecta automaticamente se o jogo está em
  MySQL (via `Database::isMysql()` ou leitura direta de `config/database.php`)
  e nesse caso reutiliza a `Database::conn()` em vez de abrir `forum.sqlite`.
  As tabelas do fórum (`categorias`, `topicos`, `postagens`, `curtidas`,
  `reacoes`, `notificacoes`, `seguir_topicos`, `topicos_lidos`) ficam no mesmo
  banco MySQL — verificado que não há conflito de nome com tabelas do main DB.
- **`_inc/conexao.php`**: ao detectar `Database::isMysql() == true`, pula todas
  as migrações com sintaxe SQLite-específica (`PRAGMA`, `datetime('now')`,
  `INTEGER PRIMARY KEY AUTOINCREMENT`) — o instalador já criou o esquema
  correto. Só carrega `mysql_compat.php` e `cutenews_sso.php` e retorna.

### Trava de re-instalação
Se já houver `config/database.php` com `driver=mysql`, o instalador exibe um
aviso amarelo no topo. O usuário pode prosseguir mesmo assim (recriará as
tabelas, perdendo os dados do MySQL atual), ou abortar.

### Senha do ADM criado
A conta administrativa é criada com `senha_hash()` (bcrypt) — já no esquema
moderno, não usa md5.

## Atualizações no Instalador (Renomeação de Marca + Auto-Redirect)

### 1. Campo "Nome do jogo" no Passo 2
Adicionado no formulário do passo 2 (junto com config MySQL). Default `AnubisServe`
(mantém o nome original). Validação: 1–30 caracteres.

### 2. Renomeação automática (`install/lib_rename.php`)
Função `renomear_projeto($rootDir, $nomeNovo, &$log)` chamada no passo 4 após
a criação do `config/database.php`. Substitui case-sensitive em todo o repo:

| Token original | Substituído por |
|---|---|
| `AnubisServe`, `AnubisServe`, `AnubisServe` | $semEspaco (ex: `MeuJogo`) |
| `anubisserve` | strtolower (ex: `meujogo`) |
| `ANUBISSERVE` | strtoupper (ex: `MEUJOGO`) |
| `Naruto O Game`, `Naruto O Game`, `Naruto O Game` | $comEspaco (ex: `Meu Jogo`) |
| `naruto o game` | lowercase com espaço |
| `NARUTO O GAME` | uppercase com espaço |

Processa apenas extensões: `php css js html htm json md txt xml htaccess`.
Pula: pasta `install/`, `.git/`, `_img/`, `_js/tinymce/`, `_js/wz/`, `_cache/`,
`news/` (CuteNews), `attached_assets/`, `vendor/`, `node_modules/`, e arquivos
> 1 MB. **Nunca** toca em `database.sqlite` / `forum.sqlite`.

Log informa quantos arquivos foram modificados. Atualmente ~57 arquivos contêm
a marca.

### 3. Auto-redirect para o instalador (`index.php`)
Adicionado no topo de `index.php` (antes de `session_start()`):
```php
if (
    is_dir(__DIR__ . '/install') &&
    is_file(__DIR__ . '/install/install.php') &&
    !is_file(__DIR__ . '/config/database.php')
) {
    header('Location: install/install.php');
    exit;
}
```
Só redireciona quando `config/database.php` ainda não foi gerado. Após o
instalador rodar e gravar esse arquivo, o site abre normalmente — a pasta
`install/` pode ficar no projeto para reinstalações manuais (acessadas
diretamente via `install/install.php`).

### 4. Esquema em `database.sql` — só estrutura, sem dados
`database.sql` contém **apenas** `CREATE TABLE` (52 tabelas) +
`CREATE INDEX`. **Nenhum** `INSERT INTO` — o dump é estritamente
estrutural. Cada nova instalação parte de um banco zerado, e a
população acontece em duas etapas:

1. **Instalador (`install/install.php`)**: cria as tabelas a partir
   do dump, depois insere os `servidores` configurados no passo 3 e
   a conta ADM do passo 4 diretamente via PDO.
2. **Painel ADM no jogo**: cadastrar mapas, jutsus, itens, missões,
   floresta_icones, configurações etc. através das telas administrativas.

**Não** existem mais `CREATE TABLE IF NOT EXISTS` ou `ALTER TABLE` em
runtime dentro de `_inc/conexao.php` — qualquer ajuste de esquema
deve ser feito em `database.sql` e o instalador re-executado (ou
aplicado manualmente via phpMyAdmin / console MySQL).

Pontos importantes do dump:
- `usuarios.personagem` é `VARCHAR(50) DEFAULT 'naruto'` (guarda o slug
  literal, ex: `naruto`, `sasuke`, `sakura`, `kakashi`).
- `usuarios.renegado` é `VARCHAR(3) DEFAULT 'nao'` (guarda `'sim'` /
  `'nao'`, lido pelo gerador de bandanas e pelo ranking Akatsuki).
- O dump começa com `SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'` para
  preservar `servidores.id = 0` (Konoha como servidor zero) quando o
  instalador inserir os servidores configurados.
- Tabelas `configuracoes`, `fragmentos`, `spam`, `invasoes`,
  `inventario`, `gm_permissions` (PK composta `usuario_id,modulo`) e
  `maps_pages` (PK auto-increment) estão definidas com tipos coerentes
  com o que o código grava.

## Atualizações 26/04/2026 — Correções de bugs reportados

### Bug #1 — Não consegue selecionar personagem (`config_char.php`)
A coluna `usuarios.config_personagem` não existia em produção, mas era
gravada por `_inc/config_char.php`. O `UPDATE` lançava `PDOException`
silenciosa e redirecionava para `?msg=4` ("Personagem indisponível").
Solução:
- Adicionada `config_personagem VARCHAR(3) DEFAULT 'nao'` em
  `database.sql` (linha 946) na tabela `usuarios`, depois de `config_radio`.
- Aplicado `ALTER TABLE usuarios ADD COLUMN config_personagem VARCHAR(3)
  DEFAULT 'nao' AFTER config_radio` no banco de dev.
- **Em produção (XAMPP)**: rodar o mesmo `ALTER TABLE` ou re-importar o
  `database.sql` atualizado.

### Bug #4 — "AnubisServe" hardcoded em todo o site
Substituído por chamadas a `nome_servidor()` (helper definido em
`config/brand.php`) — carregado automaticamente por
`_inc/security.php` → `_inc/conexao.php`. Para personalizar o nome
basta editar `BRAND_NAME` em `config/brand.php`. O script
`scripts/replace_brand.php` foi usado para a substituição automática,
preservando contextos PHP (strings simples/duplas, comentários) e HTML.
Arquivos atualizados (35 ao todo): `_inc/ads.php`, `_inc/busyhunt.php`,
`_inc/busymission.php`, `_inc/busytrain.php`, `_inc/chat.php`,
`_inc/config_conn.php`, `_inc/config_inicial.php`, `_inc/donations.php`,
`_inc/events.php`, `_inc/faq.php`, `_inc/first.php`, `_inc/home.php`,
`_inc/login.php`, `_inc/mail.php`, `_inc/manual.php`, `_inc/orkut.php`,
`_inc/penalty.php`, `_inc/polls.php`, `_inc/recover.php`, `_inc/reg.php`,
`_inc/reg2.php`, `_inc/terms.php`, `_inc/vip1.php`, `_inc/vip2.php`,
`_inc/vipform.php`, `adm/adm.php`, `forum/views/categorias/index.php`,
`forum/views/layouts/header.php`, `index.php`, `manual.php`,
`newpass.php`, `novonivel.php`, `pixel.php`, `search_msg.php`.
`_inc/cutenews_sso.php` foi mantido — é um identificador interno de
secret, não brand visível.

### Bug #5 — `attack.php` `$arma2` undefined
Linha 641 tentava ler `$arma2` quando o defensor não tinha arma equipada.
Adicionada inicialização `$arma1=0; $arma2=0;` antes dos loops `items1`/
`items2` em `_inc/attack.php`, e atribuição `=1` quando o item pertence à
categoria `arma`.

### Bug #7 — Cristais de Buff e de Craft no painel admin
- `adm/cristal.php` agora possui três blocos: refinamento (categoria
  `cristal`), buff (`cristal_buff`) e craft (`cristal_craft`).
- A aba `C. de Craft` foi adicionada ao inventário do jogador
  (`_inc/inventario_abas.php`) e ao endpoint
  `_inc/ajax_inventory.php` (com diretório de imagens `_img/Craft/`).
- `_inc/rewardorgmission.php` agora inclui drop separado de cristal
  de craft em missões de clã (4% de chance — 60% cristal completo,
  40% fragmento), análogo ao de buff. A query geral de cristal foi
  ajustada para excluir `cristal_buff` **e** `cristal_craft`. A tela
  de recompensa exibe o drop com cor roxa.
- A nova tabela `craft_fragmentos` é criada sob demanda
  (`CREATE TABLE IF NOT EXISTS`) na primeira queda de fragmento.
- O painel admin "Criadores de Conteúdo" também inclui agora cristais
  de craft entre os itens disponíveis para presente.
- **Receita configurável (table_usaveis.fragmentos_necessarios):** quantos
  fragmentos formam 1 cristal completo (entre 2 e 20; padrão 5). Editável
  por linha em `adm/cristal.php`. Lido por `_inc/parchments.php` (player) e
  `_inc/ajax_blacksmith.php` (`get_fragments` + `combine_fragments`).
- **Imagem própria do fragmento (table_usaveis.imagem_fragmento):** upload
  separado em `adm/cristal.php` (cadastro e edição), salvo em
  `_img/Craft/fragmentos/`. Quando NULL, o jogo usa a mesma imagem do
  cristal aplicando filtro CSS roxo (compatibilidade retroativa).
- **Fragmentos como ENTIDADES PRÓPRIAS (cat='fragmento_craft'):** desacopla
  fragmento de cristal. Cada fragmento tem nome, imagem (em
  `_img/Fragmento de Cristal/`) e `cristal_alvo_id` (FK para cat='cristal_craft').
  Vários fragmentos podem virar o mesmo cristal alvo. Cadastro/edição/remoção
  em `adm/cristal.php` (seção "🧩 Fragmentos de Craft") com galeria de
  imagens existentes + upload novo + dropdown de cristal alvo.
  - **Drop em missões** (`_inc/rewardorgmission.php`): 60% cristal completo
    (cat=cristal_craft), 40% fragmento (cat=fragmento_craft, sorteado entre
    fragmentos com alvo válido).
  - **Player** (`_inc/parchments.php`, `_inc/ajax_blacksmith.php`): lê
    `craft_fragmentos` JOIN `table_usaveis` WHERE cat='fragmento_craft', faz
    LEFT JOIN no cristal alvo para mostrar "N fragmentos → 1× nome do cristal".
  - **Combine no Ferreiro** (`_inc/ajax_blacksmith.php`): consome N do
    fragmento, entrega 1 unidade do `cristal_alvo_id` em `usaveis`.
  - **Botão "🧪 TESTE +5" no admin** (`adm/cristal.php`, action
    `dar_fragmento_teste`): adiciona 5 daquele fragmento ao próprio admin
    para testar o Ferreiro sem precisar fazer missão dropar.

### Aba "Fragmento" no Inventário (home)
- Em `_inc/inventario_abas.php` a aba antiga "C. de Craft" foi RENOMEADA
  para "Fragmento" (`data-category="fragmentos"`). A contagem agora SOMA
  fragmentos de equipamento + craft + buff (3 tabelas).
- `_inc/ajax_inventory.php` responde a `categoria='fragmentos'` agregando
  os 3 tipos com `tipo_label` (EQUIP/CRAFT/BUFF) e cor própria. NÃO mostra
  cristais completos — só fragmentos.

### Renomeação de marca no Install (config/brand.php)
- `install/lib_rename.php` agora REGRAVA `config/brand.php` ao final do
  `renomear_projeto()`. O `strtr()` por arquivo não atualizava o BRAND_NAME
  porque o valor padrão (`NarutoTheGame`) não casa com nenhum token do
  mapa, então o nome do servidor exibido em rodapés/emails/login continuava
  o antigo. Agora o brand.php é regravado com o nome digitado no install.

### Bug `%3fp=login` no Install (concluir.php)
- Causa raiz: a tela final do install usava `<meta http-equiv="refresh">`
  + `setTimeout` com URL absoluta. Mesmo com URL absoluta, alguns
  Chromium/Brave normalizam meta-refresh percent-encodando o `?` (vira
  `%3F`), gerando `/install/%3Fp=login` que cai em 404/403/branco.
- Solução: `install/concluir.php` agora apaga os arquivos do `install/`
  e faz `header("Location: …/index.php?p=login", 302)` direto, sem tela
  intermediária e sem JS/meta-refresh. O navegador segue o 302 limpinho.

### Pendências reportadas pelo usuário (não corrigidas nesta rodada)

**Bug #2 (ADM criado sem personagem) e Bug #3 (servername sobrescrevendo
dbname como `narutothegame`)** vivem dentro de `install/install.php`, que
**não está mais presente** no projeto nem em nenhum commit do git
local. Para corrigir esses dois bugs é preciso restaurar o
`install/install.php` original (do XAMPP do usuário ou de backup),
ou recriá-lo do zero. O fluxo correto é:
- `INSERT INTO usuarios` do ADM precisa preencher `personagem='naruto'`
  e demais campos default (vila, energia, etc.) para que a conta admin
  funcione no painel.
- Os campos de "nome do servidor" e "nome do banco" devem ser lidos
  como variáveis distintas — não usar a mesma `$_POST` para ambos.

**Bug #6 (CSP bloqueando "Adicionar Equipamento")** depende de
verificar o JS inline em `adm/gerenciar_equipamentos.php` no XAMPP do
usuário. No Replit `_inc/security.php` (linha 55) já permite
`script-src 'unsafe-inline'`, então o problema só ocorre se o XAMPP
estiver com CSP mais restrita ou com cache antigo. Se reproduzir, abra
DevTools → Console para ver a mensagem exata da CSP.

**Bug #8 (padronizar URLs adm.php?modulo vs adm/file.php)** envolve
refatorar todos os módulos internos de `adm/adm.php` (gm_perms,
admin_logs, contas, ban_penalty, config_site, denuncias, criadores,
limpar_banco, limpar_ip, servidores) para arquivos separados em
`adm/`, ou converter os arquivos separados em handlers `?modulo=`.
São ~10 módulos internos × ~100-300 linhas cada — refator grande
fora do escopo desta rodada. Recomendação: fazer em uma task
dedicada com escopo definido (qual padrão adotar — separar ou unificar).

## Atualizações 26/04/2026 (rodada 2)

### `adm/cristal.php` — Cadastro de novos cristais
A página agora possui 3 formulários de cadastro (um por categoria —
refinamento, buff e craft) que inserem novos tipos em
`table_usaveis`. Anteriormente só havia o formulário para
**distribuir** cristais; se a `table_usaveis` estivesse vazia, nada
aparecia para selecionar. Agora o admin pode criar cristais
diretamente:

- Campos: nome (obrigatório, 1-100 chars), descrição (opcional, até
  255 chars), imagem (opcional, PNG/JPG/GIF/WEBP).
- A imagem é validada via `getimagesize()` e salva em:
  - `_img/Cristais/` para categoria `cristal`
  - `_img/Buff/` para categoria `cristal_buff`
  - `_img/Craft/` para categoria `cristal_craft`
- Nome do arquivo é sanitizado (`[^a-z0-9_-]` vira `_`) e
  desambiguado se já existir (`_1`, `_2`, …).
- O upload usa `move_uploaded_file()` com criação automática do
  diretório se faltar.

Após cadastrar, o cristal aparece imediatamente nos cards da seção
correspondente para ser distribuído a jogadores.

### `usuarios.personagem` — default vazio
- `database.sql` (linha 931): mudado de
  `VARCHAR(50) NOT NULL DEFAULT 'naruto'` para
  `VARCHAR(50) NOT NULL DEFAULT ''`. Agora a coluna fica vazia até o
  jogador escolher o personagem em `_inc/reg.php` (no cadastro) ou
  `_inc/config_char.php` (uma vez por conta).
- `_inc/mysql_compat.php`: o `ALTER` de runtime foi atualizado para o
  mesmo default `''` e o `UPDATE` que sobrescrevia
  NULL/'' para `'naruto'` foi removido — agora só normaliza valores
  legados `'0'`/`'1'`.
- ALTER aplicado no banco do Replit. **No XAMPP**, rodar:
  `ALTER TABLE usuarios MODIFY COLUMN personagem VARCHAR(50) NOT NULL DEFAULT '';`

A coluna `renegado` foi mantida com `DEFAULT 'nao'` — todo jogador
novo NÃO é renegado por padrão; virar renegado é um evento de
gameplay disparado pelo próprio sistema, não algo que o jogador
escolhe ao criar a conta.

## Atualizações 26/04/2026 (rodada 3) — Seed de cristais

Importados 6 cristais do `database.sqlite` enviado pelo usuário para
o `database.sql`, logo após o `CREATE TABLE table_usaveis`:

| ID | Nome                          | Categoria      | Imagem                          |
|----|-------------------------------|----------------|---------------------------------|
| 1  | Cristal de Chakra Refinado    | cristal        | Cristal de Chakra Refinado.png  |
| 2  | Cristal de Chakra Bruto       | cristal        | Cristal de Chakra Bruto.png     |
| 3  | Chakra Forjado                | cristal        | Chakra Forjado.png              |
| 4  | Cristal de Taijutsu           | cristal_buff   | Taijutsu.png                    |
| 5  | Cristal de Ninjutsu           | cristal_buff   | Ninjutsu.png                    |
| 6  | Cristal de Genjutsu           | cristal_buff   | Genjutsu.png                    |

Todos já inseridos no banco do Replit. **Imagens** dos cristais de
refinamento ficam em `_img/ferreiro/` (já presentes no repositório),
e as de buff em `_img/Buff/`. Atualizei `adm/cristal.php`:
- O cadastro de cristal de refinamento agora salva uploads em
  `_img/ferreiro/` (antes era `_img/Cristais/`) — convenção
  preexistente do código (`_inc/blacksmith.php`, `_inc/parchments.php`,
  `_inc/addmy.php`, `_inc/myshop.php`, `_inc/shops.php`,
  `_inc/viewshop.php`).
- Os cards de cristal de refinamento agora exibem a imagem
  (`<img src="../_img/ferreiro/...">`) igual fazem os de buff/craft.

## Atualizações 26/04/2026 (rodada 4) — Correções de bugs reportados

Corrigidos 6 dos 8 bugs reportados pelo usuário. URL admin standardization
(`adm.php?modulo=X` para todos os arquivos) ficou pendente — é uma
refatoração maior em ~8 arquivos standalone.

**Bugs corrigidos:**

1. **`?p=config&type=char` bloqueando seleção de personagem inicial.**
   `_inc/config_char.php`: ADM agora ignora o limite de troca; e o
   limite "uma vez por dia" ganhou reset automático quando muda o dia
   (compara `DATE(ultimo_acesso)` com `CURDATE()`).

2. **ADM criado via install com personagem Itachi aparecia "sem
   personagem".** Causa: a pasta `_img/personagens/itachi/` só tinha
   `0.jpg`, e o jogo procura `1.jpg..9.jpg` para o avatar 1 (default).
   Copiados `0.jpg` para `1.jpg`–`9.jpg` em `itachi/` para resolver
   sem mudar lógica.

3. **Botão "Adicionar Equipamento" não funcionava no painel admin.**
   `adm/gerenciar_equipamentos.php`: convertidos todos os `onclick=""`
   inline (botão add, abas de filtro, botões editar/deletar, fechar
   modal, change de categoria) para `addEventListener` com atributos
   `data-*`, resistente a CSP estrita.

4. **Padronizar URLs admin para `adm.php?modulo=X`** — *PENDENTE.*
   Refatoração maior: cada arquivo standalone (`gerenciar_equipamentos.php`,
   `gerenciar_clas.php`, `cristal.php`, `editor_database.php`,
   `admin_manutencao.php`, `tickets.php`, `gerenciar_invasao.php`,
   `limpar_itens.php`) tem auth/header próprios e precisa ser
   convertido em "módulo" (com guard) ou roteado por inclusão.

5. **Bônus de invasão não somava nos atributos / tooltip não mostrava.**
   `index.php` linha 291 (SELECT do usuário logado): adicionadas as
   colunas `bonus_invasao_tai`, `bonus_invasao_nin`, `bonus_invasao_gen`,
   `bonus_invasao_pct`, `adm` e `timestamp`. Sem isso, `$db['bonus_invasao_*']`
   ficava `null`, então `home.php` (que usa `isset`) zerava o bônus.

6. **"Players Derrotados: 0" mesmo após invasor matar player.**
   `_inc/attackinIn.php` após o loop de combate: se `$is_invasao &&
   $player_hp <= 0 && $invasor_hp > 0`, executa
   `UPDATE invasoes SET players_derrotados = players_derrotados + 1`.
   Antes só `_inc/invasao.php` (action attack_monster) incrementava,
   e somente quando o monster derrotava o player automaticamente.

7. **"Usuários online: 0" sempre.** `_inc/menu_comum.php` linhas 88 e
   109: as queries comparavam `timestamp` (INT epoch) com
   `NOW() - INTERVAL 300 SECOND` (DATETIME) — comparação de tipos
   incompatíveis. Trocado para `UNIX_TIMESTAMP() - 300` (INT vs INT).
   `_inc/online.php` já grava `time()` em `usuarios.timestamp` em
   cada page load, então agora a contagem volta a funcionar.


## Atualizações 26/04/2026 (rodada 4b) — Itachi sem avatar

A correção anterior (cp `0.jpg` → `1-9.jpg` em `itachi/`) era enganosa:
o `0.jpg` da pasta itachi é um **placeholder** de 110x60px / 3KB (avatares
reais como `naruto/1.jpg` são 162x150px / 9KB), então copiá-lo deixava
o avatar visível mas em branco. Solução definitiva:

1. **Deletados os 9 arquivos copiados** em `_img/personagens/itachi/`
   (sobrou apenas o `0.jpg` original).

2. **Novo helper `personagem_tem_avatares($chave)`** em
   `_inc/personagens_catalogo.php`: retorna `true` se a pasta tem `1.jpg`
   real. Centraliza a checagem para uso futuro quando outros personagens
   forem adicionados sem todos os assets.

3. **`install/install.php`**: filtra desbloqueáveis sem avatares no
   seletor ADM (passo 4) e na validação POST do passo. Novos installs
   não conseguem mais escolher itachi. ADMs já criados com itachi
   continuam no banco mas o jogo agora se vira.

4. **`_inc/config_char.php`**: o foreach do catálogo pula personagens
   sem avatares — não aparecem nem como bloqueados/BO no seletor.

5. **`_inc/config_avat.php` e `_inc/first.php`**: depois do guard
   normal de `$char_atual`, se o personagem não tem `1.jpg`, cai para
   `naruto`. O ADM legado com `personagem='itachi'` consegue abrir o
   editor de avatar (vê os avatares do Naruto) e, depois de clicar
   "Alterar Avatar"+ ir em `?p=config&type=char` (já liberado para
   ADM sem cooldown), troca para um personagem válido.

6. **`_inc/attack.php`**: adicionado `onerror` nos `<img>` do jogador e
   do oponente para cair em `_img/personagens/no_avatar.jpg` em vez
   de mostrar ícone de imagem quebrada (proteção genérica).

## Atualizações 26/04/2026 (rodada 4c) — Padronização das URLs do painel admin (bug 4)

Todas as URLs do painel admin agora seguem o formato `adm.php?modulo=<nome>`.

**Como funciona:** no topo de `adm/adm.php` (antes da auth) há um
roteador `$adm_modulos_standalone` que mapeia chaves de módulo para
arquivos standalone e faz `include + exit` quando o módulo é
solicitado. Cada arquivo standalone continua cuidando da própria
auth/conexão/header (sem duplicação porque adm.php sai antes de
inicializar o resto).

**Mapeamento:**

| URL | Arquivo destino |
|-----|-----------------|
| `adm.php?modulo=equipamentos`     | `adm/gerenciar_equipamentos.php` |
| `adm.php?modulo=clas`             | `adm/gerenciar_clas.php`         |
| `adm.php?modulo=cristais`         | `adm/cristal.php`                |
| `adm.php?modulo=editor_database`  | `adm/editor_database.php`        |
| `adm.php?modulo=manutencao`       | `adm/admin_manutencao.php`       |
| `adm.php?modulo=tickets`          | `adm/tickets.php`                |
| `adm.php?modulo=limpar_itens`     | `adm/limpar_itens.php`           |
| `adm.php?modulo=invasao_completa` | `adm/gerenciar_invasao.php`      |
| `adm.php?modulo=limpar_banco_full`| `adm/limpar_banco.php`           |
| `adm.php?modulo=desbloquear_ips`  | `adm/limpar_ip.php`              |

Os módulos que já eram inline (`home`, `contas`, `database`,
`servidores`, `gm_perms`, `limpar_banco`, `limpar_ip`,
`gerenciar_invasao` legado etc.) seguem inalterados.

**Updates aplicados:**
- `adm/adm.php`: roteador adicionado no topo + todos os `href="X.php"`
  trocados por `href="?modulo=Y"`.
- `adm/adm_header.php`: todos os links da nav superior trocados por
  `href="adm.php?modulo=Y"`.
- `adm/limpar_ip.php` e `adm/limpar_itens.php`: botão "🔄 Limpar
  Novamente" agora aponta para a URL canônica.

Os arquivos `.php` standalone continuam acessíveis diretamente (não
foram quebrados), mas todos os menus e botões internos passam pela
URL canônica `adm.php?modulo=X`.

## Atualizações 26/04/2026 (rodada 5) — Mercado dos Jogadores + remoção de Orkut/Twitter/Rádio

### Removido completamente do sistema

**Orkut** (a rede social não existe mais):
- Apagados: `_inc/orkut.php`, `_img/orkut.png`
- Tirados links em `_inc/menu_off.php`, `_inc/top.php` (deslogado)
- Tirados textos em `_inc/faq.php`, `_inc/mail.php`, `_inc/recover.php`,
  `_inc/reg.php`, `newpass.php` (que mencionavam "comunidade no orkut")
- O roteador `index.php` mantém `case 'orkut'` apenas como fallthrough
  para `case 'mercado'` (compatibilidade de URLs antigas).

**Twitter** (não combina com a estética do jogo):
- Apagado: `_inc/view_twitter.php`
- Removida a seção Twitter inteira de `_inc/config_conn.php` (form de
  configuração do perfil) — campos `config_twitter`, `config_viewtwitter`,
  `config_oktwitter` não são mais escritos
- Removido o include condicional em `_inc/view.php`
- Removido o botão "Compartilhar no X" em `novonivel.php` (subiu de nível)
- Removido o `<script>` legado do twitterjs em `index.php`

**Rádio** (rádios externas há muito mortas):
- Apagados: `_inc/radio.php`, `_inc/radio_animix.php`,
  `_inc/radio_radiorox.php`, `_inc/radio_vibetrance.php`
- Removida a seção Rádio inteira de `_inc/config_conn.php`
- Removido `case 'radio'` do roteador em `index.php`
- Removido `u.config_radio` do SELECT em `index.php`
- Removido o include comentado em `_inc/menu_comum.php`

> ⚠️ **Importante:** os `<input type="radio">` em formulários HTML
> (registro, config_char, config_avat, addmy, first, etc.) são apenas
> radio buttons de formulário — NÃO foram removidos, são padrão HTML.

### Nova feature: Mercado dos Jogadores (`?p=mercado`)

A nova aba **Mercado** substitui a antiga aba Orkut na navegação tanto
deslogada quanto logada. Servida por `_inc/mercado.php`.

**Logado** (mercado ao vivo):
- Lista todos os itens com `inventario.venda='sim'` (todos os jogadores)
- Filtros: por moeda (Todas | Yens | Cristal Refinado | Cristal Bruto |
  Chakra Forjado) e por ordem de preço
- Mostra: nome, atributos, vendedor (link p/ perfil + visitar loja),
  botão **Comprar** que reaproveita o fluxo de `?p=viewshop&buy=`
- Paginação de 30 itens por página

**Deslogado** (vitrine pública / estatísticas históricas):
- Total movimentado por moeda (Yens, Cristal Refinado, Cristal Bruto,
  Chakra Forjado) — soma dos valores e contagem de transações
- Top 5 vendedores
- Top 10 itens mais vendidos
- Últimas 15 vendas (item, vendedor, comprador, preço, data)

### Nova tabela `mercado_historico`

A tabela existente `vendas` é **transitória** — ela apaga as linhas
quando o vendedor "saca" os yens. Por isso uma nova tabela permanente
foi criada:

```sql
CREATE TABLE mercado_historico (
  id, vendedor_id, comprador_id, item_id,
  item_nome, item_imagem, valor, moeda_tipo, data,
  KEY (data), KEY (moeda_tipo), KEY (vendedor_id)
)
```

Adicionada ao `database.sql` para novos installs E auto-criada via
`CREATE TABLE IF NOT EXISTS` no topo de `_inc/mercado.php` para
servidores já instalados (sem quebrar nada).

**Pontos onde uma compra é registrada:**
- `_inc/shops.php` (compra direta no comércio interno)
- `_inc/viewshop.php` (compra na loja específica de um jogador)

Em ambos foi adicionado um INSERT na nova tabela logo após a mensagem
ao vendedor, dentro de try/catch para nunca bloquear a venda.

## Rodada 7 — Cristais com novos efeitos + canais de contato (abr/2026)

### 1) Fix do erro fatal em `_inc/contact.php`

Arquivo legado tinha `break;` solto em `if/else` (não em loop nem switch),
quebrando em PHP 8.2 com:
> Fatal error: 'break' not in the 'loop' or 'switch' context

Reescrito do zero usando PDO. Agora:
- Cria/usa a tabela `contato` (com `ip`, `lido`, índices).
- Anti-flood (1 mensagem/min por IP).
- Mostra os canais oficiais cadastrados pelo ADM como ícones clicáveis.
- Form com validação real e `<select>` de assunto.

### 2) Novo módulo ADM — Canais de Contato

`adm/gerenciar_contatos.php` (registrado em `$adm_modulos_standalone`
como `'contatos'` e linkado em todos os menus admin).

- Edita um arquivo `config/contato.php` (PHP `return [...]`) com chaves:
  `email, discord, whatsapp, twitter, instagram, facebook, telegram,
  youtube, website`.
- Cada canal tem ícone próprio na página `?p=contact` e gera URL
  apropriada (`mailto:`, `wa.me`, `t.me`, etc.).
- Lista as últimas 30 mensagens recebidas pelo formulário.
- Arquivo de exemplo: `config/contato.php.example`.

### 3) Cristais com FUNÇÕES distintas (não duplicar refinamento/buff/craft)

#### Schema novo em `table_usaveis`
Auto-migrado em `adm/cristal.php`:

```sql
ALTER TABLE table_usaveis ADD COLUMN tipo_efeito  VARCHAR(32) NULL;
ALTER TABLE table_usaveis ADD COLUMN valor_efeito VARCHAR(64) NULL;  -- JSON
```

#### Catálogo de efeitos (`$cristal_efeitos_buff` em `adm/cristal.php`)
- `taijutsu`   → +X% Taijutsu por Y horas (admin define pct/horas)
- `ninjutsu`   → +X% Ninjutsu por Y horas
- `genjutsu`   → +X% Genjutsu por Y horas
- `cura_total` → restaura HP/Chakra ao máximo (instantâneo, sem buff)

Para registrar um novo tipo de efeito, basta acrescentar a chave no array
do admin **e** tratar a chave em `_inc/usar_cristal_buff.php`.

#### Form de cadastro (admin)
Adicionado `<select tipo_efeito>` + inputs dinâmicos de parâmetros
(JS `atualizarCamposEfeito()`); a tabela de cristais existentes agora
mostra a coluna **Efeito** com label e parâmetros entre colchetes
(ex.: `+% Taijutsu (temporário) [pct=10, horas=6]`).

#### Player-side (`_inc/usar_cristal_buff.php`)
Reescrito para ler `tipo_efeito`/`valor_efeito` da tabela:
- stats temporários → `INSERT INTO buff_ativos` com pct/horas customizados
- `cura_total` → `UPDATE usuarios SET vida=vidamax, chakra=chakramax`
- Sem `tipo_efeito` (cristais legados) → fallback +5% por 3h pelo nome
- Tudo dentro de transação; rollback em caso de erro.

#### Listagem do jogador (`_inc/cristais_buff.php`)
- Função `descrever_efeito_cristal_buff()` gera a frase certa por efeito.
- Botão muda para "💚 Curar" quando o efeito é cura instantânea (não
  emite o aviso "buff anterior será substituído").

### 4) Cristais de Craft combináveis no Ferreiro (29/04/2026)

Antes, fragmentos de cristal de craft (categoria `cristal_craft`, dropados em
Missões de Clã) iam para `craft_fragmentos` mas o Ferreiro só conhecia
fragmentos de equipamento — ficavam parados sem uso. Agora:

#### Backend (`_inc/ajax_blacksmith.php`)
- `get_fragments`: une fragmentos de equipamento (`fragmentos` + `table_itens`)
  com fragmentos de cristal (`craft_fragmentos` + `table_usaveis` cat
  `cristal_craft`). Cria a tabela `craft_fragmentos` se faltar. Cada item
  carrega `tipo` (`equipment`/`crystal`), `img_base`, `precisa` (5) e `chance`
  (20% equipamento / 100% cristal).
- `combine_fragments`: aceita POST `tipo`. Para `crystal`, consome 5 fragmentos
  e insere 1 cristal completo em `usaveis` — combinação garantida, sem
  Provably Fair. Para `equipment`, mantém o fluxo PF de 20%.

#### Frontend (`_inc/blacksmith.php`)
- Lista de fragmentos diferencia visualmente: cristais com borda/badge roxa
  (#cf6ecf), tag "CRISTAL", imagem de `_img/Craft/`.
- Ao selecionar um cristal, esconde a seção de Provably Fair e mostra o botão
  "💎 Combinar 5 Fragmentos → Cristal Completo" indicando o nome do cristal
  final que será formado.
- `performFragmentCombine` envia `tipo` no POST e usa `f.img_base` na imagem
  do overlay de animação; trata o resultado de cristal sem dados PF.

#### Página `?p=parchments` (`_inc/parchments.php`)
- Nova seção "Cristais de Craft" abaixo dos refinamentos:
  - Cristais completos no inventário (lendo `usaveis` cat `cristal_craft`).
  - Fragmentos pendentes em `craft_fragmentos` mostrando `X/5`, o nome do
    cristal final ("Vai formar Cristal X") e botão "Combinar no Ferreiro"
    (link `?p=blacksmith`). Botão habilitado quando `quantidade ≥ 5`.

### 5) Fix: redirect do instalador para `localhost/%3fp=login`

Em `install/concluir.php`, o meta-refresh, o link e o `setTimeout` JS usavam
URLs **relativas** (`../index.php?p=login`). Em alguns navegadores/proxies,
o `?` da query string era percent-encoded para `%3F`, gerando
`/install/%3Fp=login`. Substituídos por `$loginUrl` absoluto (calculado em
PHP a partir de `$scheme/$host/$rootPath`) — meta tag e link com
`htmlspecialchars`, JS com `json_encode` para preservar o `?` literal.
