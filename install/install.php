<?php
/**
 * install/install.php — Instalador do narutoHIT
 *
 * IMPORTANTE: a string "narutoHIT" é a marca interna usada por
 * lib_rename.php para reescrever o projeto. NÃO altere o valor
 * literal "narutoHIT" abaixo — apenas o título exibido foi mudado
 * para "Instalador Naruto By Anubis" (constante INSTALL_TITLE).
 */

// Marca interna usada para reescrever o projeto (NÃO TRADUZIR).
const BRAND_INTERNAL = 'narutoHIT';
// Título exibido (fallback). O título real exibido vem de t('install_title')
// para que cada idioma possa traduzi-lo. Mantido como constante apenas
// para uso em logs/auditoria que não devem ser traduzidos.
const INSTALL_TITLE = 'Naruto By Anubis';

session_start();
header('X-Frame-Options: SAMEORIGIN');
header('Content-Type: text/html; charset=UTF-8');

$ROOT = dirname(__DIR__);
$showWelcome = !isset($_GET['step']);
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$_SESSION['_install'] = $_SESSION['_install'] ?? [];

// ===== Internacionalização (pt-br padrão, en opcional) =====
$LANGS = [
    'pt' => [
        'lang_name' => 'Português',
        'install_title' => 'Instalador Naruto By Anubis',
        'page_title' => 'Instalador',
        'subtitle' => ':: Configuração inicial do servidor ::',
        'welcome_h' => 'Bem-vindo ao Instalador',
        'welcome_intro' => 'Este assistente vai te guiar pela configuração inicial do servidor em <b>5 passos rápidos</b>: verificar requisitos, conectar ao MySQL, criar os mundos do jogo, registrar o administrador e importar todo o conteúdo.',
        'step_req' => 'Requisitos',
        'step_req_d' => 'PHP, extensões e dumps .sql de origem',
        'step_db' => 'Banco MySQL',
        'step_db_d' => 'Conexão e nomes dos bancos do jogo e fórum',
        'step_srv' => 'Servidores',
        'step_srv_d' => 'Quantos mundos (1 a 10) e capacidade de cada um',
        'step_adm' => 'Conta ADM',
        'step_adm_d' => 'Sua conta de administrador no jogo',
        'step_imp' => 'Importar',
        'step_imp_d' => 'Revisão final + criação das tabelas e dados',
        'step_imp_full' => 'Importar & Concluir',
        'step_diag' => 'Diagnóstico',
        'meta_time' => '⏱ <b>Tempo estimado:</b> 3-5 minutos',
        'meta_sec' => '🔒 <b>Segurança:</b> token CSRF, lock por IP, log de auditoria',
        'meta_safe' => '💾 <b>Reversível:</b> seus arquivos atuais são preservados',
        'already_installed' => '⚠ <b>Atenção:</b> uma instalação MySQL já parece existir. Continuar irá <b>recriar</b> as tabelas.',
        'btn_start' => '▶ Iniciar instalação',
        'btn_continue' => '▶ Continuar instalação',
        'btn_diag' => '⚙ Diagnóstico de pastas',
        'installer_v' => 'Versão do instalador',
        'h_diag' => 'Diagnóstico do Ambiente',
        'p_diag' => 'Verificação de permissões de escrita nas pastas usadas pelo jogo. Itens marcados como <b style="color:#ff8c1a">crítico</b> precisam estar OK para a instalação prosseguir.',
        'critical' => '[CRÍTICO]',
        'optional' => '[opcional]',
        'all_ok' => '✓ Todas as pastas críticas estão OK. Pode prosseguir com segurança.',
        'has_issues' => '✗ Existem problemas críticos. Corrija as permissões antes de continuar — caso contrário a instalação vai falhar.',
        'btn_recheck' => '↻ Verificar novamente',
        'btn_to_req' => 'Continuar para Requisitos →',
        'h_env' => 'Verificação do Ambiente',
        'p_env' => 'Aguarde enquanto verificamos cada requisito do servidor...',
        'btn_next' => 'Próximo →',
        'lang_label' => 'Idioma',
        // Passo 2 — MySQL
        'h_mysql' => 'Configuração do Banco MySQL',
        'p_mysql' => 'Informe o servidor MySQL onde os dados serão importados. O banco é criado automaticamente se não existir.',
        'mysql_host' => 'Host',
        'mysql_port' => 'Porta',
        'mysql_user' => 'Usuário MySQL',
        'mysql_pass' => 'Senha MySQL',
        'mysql_pass_ph' => 'Deixe em branco se não tiver',
        'mysql_db_main' => 'Nome do banco principal (jogo)',
        'mysql_db_forum' => 'Nome do banco do fórum',
        'mysql_db_forum_opt' => '(opcional — deixe vazio para usar o mesmo banco do jogo)',
        'mysql_db_forum_hint' => 'Recomendado para servidores com muito tráfego no fórum: facilita backups independentes e permite escalar separadamente.',
        'game_name' => 'Nome do jogo (será aplicado no site inteiro)',
        'game_name_hint' => 'Substitui todas as ocorrências de %s / Naruto Hit nos arquivos do site. Deixe como %s para manter o nome original.',
        'btn_test_create' => 'Testar &amp; Criar →',
        // Passo 3 — Servidores
        'h_srv' => 'Configuração de Servidores',
        'p_srv' => 'Defina quantos servidores (mundos) o jogo terá. Os jogadores escolherão um servidor ao se registrar e ficarão isolados nele. Você pode editar/criar mais depois pelo painel ADM.',
        'srv_qtd' => 'Quantidade de servidores (1 a 10)',
        'btn_continue_arrow' => 'Continuar →',
        'srv_label' => 'Servidor',
        'srv_name' => 'Nome',
        'srv_capacity' => 'Capacidade (jogadores)',
        // Passo 4 — ADM
        'h_adm' => 'Conta de Administrador',
        'p_adm' => 'Esta conta é criada dentro do jogo já com privilégios de ADM (cargo nível 1) no primeiro servidor configurado: <b>%s</b>.',
        'adm_user' => 'Nome de usuário (login no jogo)',
        'adm_email' => 'Email',
        'adm_pass' => 'Senha',
        'adm_pass2' => 'Confirmar senha',
        // Passo 5 — Resumo / Importação
        'h_resumo' => 'Resumo da Instalação',
        'p_resumo' => 'Revise abaixo todas as configurações antes de iniciar a importação. Esta etapa <b>recriará as tabelas no MySQL</b> e não pode ser desfeita.',
        'csrf_fail' => '<b>Validação de segurança falhou.</b> O token anti-CSRF não corresponde. Recarregue a página e tente novamente.',
        'box_db' => '🗄️ Banco MySQL',
        'box_srv' => '🌐 Servidores',
        'box_adm' => '👤 Conta de Administrador',
        'lbl_host_port' => 'Host / Porta',
        'lbl_user' => 'Usuário',
        'lbl_pass' => 'Senha',
        'lbl_empty' => '(vazia)',
        'lbl_db_game' => 'Banco do jogo',
        'lbl_db_forum' => 'Banco do fórum',
        'lbl_same_as_game' => 'mesmo do jogo',
        'lbl_game_name' => 'Nome do jogo',
        'lbl_applied_site' => '(será aplicado em todo o site)',
        'col_name' => 'Nome',
        'col_cap' => 'Capacidade',
        'players_suffix' => 'jogadores',
        'lbl_init_srv' => 'Servidor inicial',
        'lbl_role' => 'Cargo',
        'lbl_role_val' => 'ADM (nível 1)',
        'what_happens' => 'O que vai acontecer:',
        'wh_1' => 'As tabelas serão criadas no MySQL a partir dos dumps database.sql e forum.sql.',
        'wh_2' => 'Os bancos serão criados <b>vazios</b> (sem contas, sem tópicos do fórum).',
        'wh_3' => 'O conteúdo estático do jogo (itens, jutsus, missões, mapas) será mantido.',
        'wh_4' => '%d servidor(es) serão registrados.',
        'wh_5' => 'A conta ADM será criada e linkada ao primeiro servidor.',
        'wh_6' => 'O nome do jogo será trocado para <b>%s</b> em todos os arquivos.',
        'tip_download' => '📄 <b>Dica:</b> baixe este resumo agora para guardar as senhas em local seguro.',
        'btn_download_txt' => '⬇ Baixar resumo (.txt)',
        'btn_edit_db' => '← Editar MySQL',
        'btn_edit_srv' => '← Editar Servidores',
        'btn_edit_adm' => '← Editar ADM',
        'btn_confirm_install' => '✓ Confirmar e instalar',
        'h_importing' => 'Importação em andamento...',
        // Tela final
        'install_ok' => '<b>Instalação concluída com sucesso!</b> O arquivo <code>config/database.php</code> agora aponta para o MySQL.',
        'install_remove_folder' => 'Por segurança, a pasta <code>install/</code> deve ser removida agora.',
        'install_log_warn' => '⚠️ <b>Atenção:</b> ao concluir, o arquivo <code>install.log</code> também será apagado. Se quiser manter o histórico de auditoria, baixe-o antes pelo botão abaixo.',
        'btn_finish' => 'Apagar pasta install/ e ir para o jogo',
        'install_fail' => 'A instalação falhou. Veja o log abaixo, ajuste e <a href="%s" style="color:#fdd">recomece</a>.',
        'log_title' => 'Log',
        'btn_download_log' => '⬇ Baixar log completo (auditoria)',
        'donation_h' => '♥ Gostou do projeto?',
        'donation_p' => 'Se quiser contribuir, qualquer valor é muito bem-vindo!',
        'donation_thanks' => 'Obrigado pelo apoio! 🙏',
        'btn_copy' => 'Copiar',
        'btn_copied' => '✓ Copiado',
        'already_installed_top' => '<b>Atenção:</b> uma instalação MySQL já parece existir em <code>config/database.php</code>. Continuar irá <b>recriar</b> as tabelas e perder os dados atuais do MySQL.',
        'footer_v' => '%s — instalador v1.2',
    ],
    'en' => [
        'lang_name' => 'English',
        'install_title' => 'Naruto Installer By Anubis',
        'page_title' => 'Installer',
        'subtitle' => ':: Initial server configuration ::',
        'welcome_h' => 'Welcome to the Installer',
        'welcome_intro' => 'This wizard will guide you through the initial server setup in <b>5 quick steps</b>: check requirements, connect to MySQL, create the game worlds, register the administrator and import all content.',
        'step_req' => 'Requirements',
        'step_req_d' => 'PHP, extensions and source .sql dumps',
        'step_db' => 'MySQL Database',
        'step_db_d' => 'Connection and names of the game and forum databases',
        'step_srv' => 'Servers',
        'step_srv_d' => 'How many worlds (1 to 10) and the capacity of each',
        'step_adm' => 'Admin Account',
        'step_adm_d' => 'Your administrator account in the game',
        'step_imp' => 'Import',
        'step_imp_d' => 'Final review + table and data creation',
        'step_imp_full' => 'Import & Finish',
        'step_diag' => 'Diagnostics',
        'meta_time' => '⏱ <b>Estimated time:</b> 3-5 minutes',
        'meta_sec' => '🔒 <b>Security:</b> CSRF token, IP lock, audit log',
        'meta_safe' => '💾 <b>Reversible:</b> your current files are preserved',
        'already_installed' => '⚠ <b>Warning:</b> a MySQL installation already seems to exist. Continuing will <b>recreate</b> the tables.',
        'btn_start' => '▶ Start installation',
        'btn_continue' => '▶ Continue installation',
        'btn_diag' => '⚙ Folder diagnostics',
        'installer_v' => 'Installer version',
        'h_diag' => 'Environment Diagnostics',
        'p_diag' => 'Write permission check for the folders used by the game. Items marked as <b style="color:#ff8c1a">critical</b> must be OK for the installation to proceed.',
        'critical' => '[CRITICAL]',
        'optional' => '[optional]',
        'all_ok' => '✓ All critical folders are OK. You can proceed safely.',
        'has_issues' => '✗ There are critical problems. Fix the permissions before continuing — otherwise the installation will fail.',
        'btn_recheck' => '↻ Check again',
        'btn_to_req' => 'Continue to Requirements →',
        'h_env' => 'Environment Check',
        'p_env' => 'Please wait while we verify each server requirement...',
        'btn_next' => 'Next →',
        'lang_label' => 'Language',
        // Step 2 — MySQL
        'h_mysql' => 'MySQL Database Configuration',
        'p_mysql' => 'Provide the MySQL server where data will be imported. The database is created automatically if it does not exist.',
        'mysql_host' => 'Host',
        'mysql_port' => 'Port',
        'mysql_user' => 'MySQL User',
        'mysql_pass' => 'MySQL Password',
        'mysql_pass_ph' => 'Leave blank if none',
        'mysql_db_main' => 'Main database name (game)',
        'mysql_db_forum' => 'Forum database name',
        'mysql_db_forum_opt' => '(optional — leave empty to use the same game database)',
        'mysql_db_forum_hint' => 'Recommended for servers with heavy forum traffic: makes independent backups easier and allows scaling separately.',
        'game_name' => 'Game name (will be applied across the whole site)',
        'game_name_hint' => 'Replaces every occurrence of %s / Naruto Hit in the site files. Keep as %s to preserve the original name.',
        'btn_test_create' => 'Test &amp; Create →',
        // Step 3 — Servers
        'h_srv' => 'Server Configuration',
        'p_srv' => 'Define how many servers (worlds) the game will have. Players will pick a server when registering and will be isolated within it. You can edit/create more later from the ADM panel.',
        'srv_qtd' => 'Number of servers (1 to 10)',
        'btn_continue_arrow' => 'Continue →',
        'srv_label' => 'Server',
        'srv_name' => 'Name',
        'srv_capacity' => 'Capacity (players)',
        // Step 4 — ADM
        'h_adm' => 'Administrator Account',
        'p_adm' => 'This account is created inside the game already with ADM privileges (level 1) on the first configured server: <b>%s</b>.',
        'adm_user' => 'Username (in-game login)',
        'adm_email' => 'Email',
        'adm_pass' => 'Password',
        'adm_pass2' => 'Confirm password',
        // Step 5 — Summary / Import
        'h_resumo' => 'Installation Summary',
        'p_resumo' => 'Review all settings below before starting the import. This step <b>will recreate the MySQL tables</b> and cannot be undone.',
        'csrf_fail' => '<b>Security validation failed.</b> The anti-CSRF token does not match. Reload the page and try again.',
        'box_db' => '🗄️ MySQL Database',
        'box_srv' => '🌐 Servers',
        'box_adm' => '👤 Administrator Account',
        'lbl_host_port' => 'Host / Port',
        'lbl_user' => 'User',
        'lbl_pass' => 'Password',
        'lbl_empty' => '(empty)',
        'lbl_db_game' => 'Game database',
        'lbl_db_forum' => 'Forum database',
        'lbl_same_as_game' => 'same as game',
        'lbl_game_name' => 'Game name',
        'lbl_applied_site' => '(will be applied across the whole site)',
        'col_name' => 'Name',
        'col_cap' => 'Capacity',
        'players_suffix' => 'players',
        'lbl_init_srv' => 'Initial server',
        'lbl_role' => 'Role',
        'lbl_role_val' => 'ADM (level 1)',
        'what_happens' => 'What will happen:',
        'wh_1' => 'Tables will be created in MySQL from the database.sql and forum.sql dumps.',
        'wh_2' => 'Databases will be created <b>empty</b> (no accounts, no forum topics).',
        'wh_3' => 'Static game content (items, jutsus, missions, maps) will be kept.',
        'wh_4' => '%d server(s) will be registered.',
        'wh_5' => 'The ADM account will be created and linked to the first server.',
        'wh_6' => 'The game name will be changed to <b>%s</b> in every file.',
        'tip_download' => '📄 <b>Tip:</b> download this summary now to keep the passwords in a safe place.',
        'btn_download_txt' => '⬇ Download summary (.txt)',
        'btn_edit_db' => '← Edit MySQL',
        'btn_edit_srv' => '← Edit Servers',
        'btn_edit_adm' => '← Edit ADM',
        'btn_confirm_install' => '✓ Confirm and install',
        'h_importing' => 'Import in progress...',
        // Final screen
        'install_ok' => '<b>Installation completed successfully!</b> The file <code>config/database.php</code> now points to MySQL.',
        'install_remove_folder' => 'For security, the <code>install/</code> folder must be removed now.',
        'install_log_warn' => '⚠️ <b>Warning:</b> when finishing, the <code>install.log</code> file will also be deleted. If you want to keep the audit history, download it first using the button below.',
        'btn_finish' => 'Delete install/ folder and go to the game',
        'install_fail' => 'Installation failed. Check the log below, fix it and <a href="%s" style="color:#fdd">restart</a>.',
        'log_title' => 'Log',
        'btn_download_log' => '⬇ Download full log (audit)',
        'donation_h' => '♥ Enjoyed the project?',
        'donation_p' => 'If you want to contribute, any amount is very welcome!',
        'donation_thanks' => 'Thanks for your support! 🙏',
        'btn_copy' => 'Copy',
        'btn_copied' => '✓ Copied',
        'already_installed_top' => '<b>Warning:</b> a MySQL installation already seems to exist in <code>config/database.php</code>. Continuing will <b>recreate</b> the tables and lose the current MySQL data.',
        'footer_v' => '%s — installer v1.2',
    ],
    'es' => [
        'lang_name' => 'Español',
        'install_title' => 'Instalador Naruto Por Anubis',
        'page_title' => 'Instalador',
        'subtitle' => ':: Configuración inicial del servidor ::',
        'welcome_h' => 'Bienvenido al Instalador',
        'welcome_intro' => 'Este asistente te guiará por la configuración inicial del servidor en <b>5 pasos rápidos</b>: verificar requisitos, conectar a MySQL, crear los mundos del juego, registrar el administrador e importar todo el contenido.',
        'step_req' => 'Requisitos',
        'step_req_d' => 'PHP, extensiones y dumps .sql de origen',
        'step_db' => 'Base de datos MySQL',
        'step_db_d' => 'Conexión y nombres de las bases del juego y foro',
        'step_srv' => 'Servidores',
        'step_srv_d' => 'Cuántos mundos (1 a 10) y la capacidad de cada uno',
        'step_adm' => 'Cuenta ADM',
        'step_adm_d' => 'Tu cuenta de administrador en el juego',
        'step_imp' => 'Importar',
        'step_imp_d' => 'Revisión final + creación de tablas y datos',
        'step_imp_full' => 'Importar y Finalizar',
        'step_diag' => 'Diagnóstico',
        'meta_time' => '⏱ <b>Tiempo estimado:</b> 3-5 minutos',
        'meta_sec' => '🔒 <b>Seguridad:</b> token CSRF, bloqueo por IP, registro de auditoría',
        'meta_safe' => '💾 <b>Reversible:</b> tus archivos actuales se conservan',
        'already_installed' => '⚠ <b>Atención:</b> ya parece existir una instalación MySQL. Continuar <b>recreará</b> las tablas.',
        'btn_start' => '▶ Iniciar instalación',
        'btn_continue' => '▶ Continuar instalación',
        'btn_diag' => '⚙ Diagnóstico de carpetas',
        'installer_v' => 'Versión del instalador',
        'h_diag' => 'Diagnóstico del Entorno',
        'p_diag' => 'Verificación de permisos de escritura en las carpetas usadas por el juego. Los items marcados como <b style="color:#ff8c1a">crítico</b> deben estar OK para que la instalación continúe.',
        'critical' => '[CRÍTICO]',
        'optional' => '[opcional]',
        'all_ok' => '✓ Todas las carpetas críticas están OK. Puedes continuar con seguridad.',
        'has_issues' => '✗ Existen problemas críticos. Corrige los permisos antes de continuar — de lo contrario la instalación fallará.',
        'btn_recheck' => '↻ Verificar de nuevo',
        'btn_to_req' => 'Continuar a Requisitos →',
        'h_env' => 'Verificación del Entorno',
        'p_env' => 'Espera mientras verificamos cada requisito del servidor...',
        'btn_next' => 'Siguiente →',
        'lang_label' => 'Idioma',
        // Paso 2 — MySQL
        'h_mysql' => 'Configuración de la Base MySQL',
        'p_mysql' => 'Indica el servidor MySQL donde se importarán los datos. La base de datos se crea automáticamente si no existe.',
        'mysql_host' => 'Host',
        'mysql_port' => 'Puerto',
        'mysql_user' => 'Usuario MySQL',
        'mysql_pass' => 'Contraseña MySQL',
        'mysql_pass_ph' => 'Deja en blanco si no tienes',
        'mysql_db_main' => 'Nombre de la base principal (juego)',
        'mysql_db_forum' => 'Nombre de la base del foro',
        'mysql_db_forum_opt' => '(opcional — deja vacío para usar la misma base del juego)',
        'mysql_db_forum_hint' => 'Recomendado para servidores con mucho tráfico en el foro: facilita backups independientes y permite escalar por separado.',
        'game_name' => 'Nombre del juego (se aplicará en todo el sitio)',
        'game_name_hint' => 'Reemplaza todas las apariciones de %s / Naruto Hit en los archivos del sitio. Deja como %s para mantener el nombre original.',
        'btn_test_create' => 'Probar y Crear →',
        // Paso 3 — Servidores
        'h_srv' => 'Configuración de Servidores',
        'p_srv' => 'Define cuántos servidores (mundos) tendrá el juego. Los jugadores elegirán un servidor al registrarse y quedarán aislados en él. Puedes editar/crear más después desde el panel ADM.',
        'srv_qtd' => 'Cantidad de servidores (1 a 10)',
        'btn_continue_arrow' => 'Continuar →',
        'srv_label' => 'Servidor',
        'srv_name' => 'Nombre',
        'srv_capacity' => 'Capacidad (jugadores)',
        // Paso 4 — ADM
        'h_adm' => 'Cuenta de Administrador',
        'p_adm' => 'Esta cuenta se crea dentro del juego ya con privilegios de ADM (nivel 1) en el primer servidor configurado: <b>%s</b>.',
        'adm_user' => 'Nombre de usuario (login en el juego)',
        'adm_email' => 'Email',
        'adm_pass' => 'Contraseña',
        'adm_pass2' => 'Confirmar contraseña',
        // Paso 5 — Resumen / Importación
        'h_resumo' => 'Resumen de la Instalación',
        'p_resumo' => 'Revisa abajo todas las configuraciones antes de iniciar la importación. Este paso <b>recreará las tablas en MySQL</b> y no se puede deshacer.',
        'csrf_fail' => '<b>Validación de seguridad fallida.</b> El token anti-CSRF no coincide. Recarga la página e intenta de nuevo.',
        'box_db' => '🗄️ Base MySQL',
        'box_srv' => '🌐 Servidores',
        'box_adm' => '👤 Cuenta de Administrador',
        'lbl_host_port' => 'Host / Puerto',
        'lbl_user' => 'Usuario',
        'lbl_pass' => 'Contraseña',
        'lbl_empty' => '(vacía)',
        'lbl_db_game' => 'Base del juego',
        'lbl_db_forum' => 'Base del foro',
        'lbl_same_as_game' => 'igual al juego',
        'lbl_game_name' => 'Nombre del juego',
        'lbl_applied_site' => '(se aplicará en todo el sitio)',
        'col_name' => 'Nombre',
        'col_cap' => 'Capacidad',
        'players_suffix' => 'jugadores',
        'lbl_init_srv' => 'Servidor inicial',
        'lbl_role' => 'Rol',
        'lbl_role_val' => 'ADM (nivel 1)',
        'what_happens' => 'Lo que va a ocurrir:',
        'wh_1' => 'Las tablas serán creadas en MySQL desde los dumps database.sql y forum.sql.',
        'wh_2' => 'Las bases serán creadas <b>vacías</b> (sin cuentas, sin temas del foro).',
        'wh_3' => 'El contenido estático del juego (items, jutsus, misiones, mapas) se mantendrá.',
        'wh_4' => '%d servidor(es) serán registrados.',
        'wh_5' => 'La cuenta ADM será creada y vinculada al primer servidor.',
        'wh_6' => 'El nombre del juego será cambiado a <b>%s</b> en todos los archivos.',
        'tip_download' => '📄 <b>Consejo:</b> descarga este resumen ahora para guardar las contraseñas en un lugar seguro.',
        'btn_download_txt' => '⬇ Descargar resumen (.txt)',
        'btn_edit_db' => '← Editar MySQL',
        'btn_edit_srv' => '← Editar Servidores',
        'btn_edit_adm' => '← Editar ADM',
        'btn_confirm_install' => '✓ Confirmar e instalar',
        'h_importing' => 'Importación en curso...',
        // Pantalla final
        'install_ok' => '<b>¡Instalación finalizada con éxito!</b> El archivo <code>config/database.php</code> ahora apunta a MySQL.',
        'install_remove_folder' => 'Por seguridad, la carpeta <code>install/</code> debe ser eliminada ahora.',
        'install_log_warn' => '⚠️ <b>Atención:</b> al finalizar, el archivo <code>install.log</code> también se borrará. Si quieres conservar el historial de auditoría, descárgalo antes con el botón de abajo.',
        'btn_finish' => 'Borrar carpeta install/ e ir al juego',
        'install_fail' => 'La instalación falló. Mira el log abajo, ajusta y <a href="%s" style="color:#fdd">reinicia</a>.',
        'log_title' => 'Log',
        'btn_download_log' => '⬇ Descargar log completo (auditoría)',
        'donation_h' => '♥ ¿Te gustó el proyecto?',
        'donation_p' => 'Si quieres contribuir, ¡cualquier cantidad es muy bienvenida!',
        'donation_thanks' => '¡Gracias por el apoyo! 🙏',
        'btn_copy' => 'Copiar',
        'btn_copied' => '✓ Copiado',
        'already_installed_top' => '<b>Atención:</b> ya parece existir una instalación MySQL en <code>config/database.php</code>. Continuar <b>recreará</b> las tablas y perderás los datos actuales de MySQL.',
        'footer_v' => '%s — instalador v1.2',
    ],
];

// ===== Bandeiras SVG inline para o seletor de idioma =====
// Pequenos SVGs proporcionais 24x18 (sem dependência externa).
$FLAG_SVG = [
    'pt' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 720 504" width="20" height="14" style="vertical-align:middle;border-radius:2px"><rect width="720" height="504" fill="#009b3a"/><polygon points="360,63 681,252 360,441 39,252" fill="#fedf00"/><circle cx="360" cy="252" r="90" fill="#002776"/><path d="M280 230 a100 100 0 0 1 160 0" stroke="#fff" stroke-width="6" fill="none"/></svg>',
    'en' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 7410 3900" width="20" height="14" style="vertical-align:middle;border-radius:2px"><rect width="7410" height="3900" fill="#b22234"/><path d="M0,450H7410M0,750H7410M0,1050H7410M0,1350H7410M0,1650H7410M0,1950H7410M0,2250H7410M0,2550H7410M0,2850H7410M0,3150H7410M0,3450H7410" stroke="#fff" stroke-width="300"/><rect width="2964" height="2100" fill="#3c3b6e"/></svg>',
    'es' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 750 500" width="20" height="14" style="vertical-align:middle;border-radius:2px"><rect width="750" height="500" fill="#aa151b"/><rect y="125" width="750" height="250" fill="#f1bf00"/></svg>',
];
if (isset($_GET['lang']) && isset($LANGS[$_GET['lang']])) {
    $_SESSION['_install']['lang'] = $_GET['lang'];
    if (!$showWelcome) { header('Location: ?step=' . $step); exit; }
    else { header('Location: install.php'); exit; }
}
$lang = $_SESSION['_install']['lang'] ?? 'pt';
if (!isset($LANGS[$lang])) $lang = 'pt';
$L = $LANGS[$lang];
function t(string $k): string { global $L; return $L[$k] ?? $k; }

// ===== Log persistente do instalador =====
// Grava em install/install.log eventos importantes (passos, erros, importação).
// NUNCA grave senhas. O arquivo é apagado junto com a pasta install/ no fim.
if (!function_exists('install_log')) {
    function install_log(string $evento, string $detalhes = ''): void {
        $linha = sprintf(
            "[%s] [%s] %s%s%s\n",
            date('Y-m-d H:i:s'),
            $_SERVER['REMOTE_ADDR'] ?? '?',
            $evento,
            $detalhes !== '' ? ' - ' : '',
            $detalhes
        );
        @file_put_contents(__DIR__ . '/install.log', $linha, FILE_APPEND | LOCK_EX);
    }
}

// ===== Bloqueio por IP =====
// O primeiro visitante "tranca" o instalador ao seu próprio IP.
// Qualquer outra máquina recebe 403 até a sessão expirar ou o
// instalador ser concluído / removido.
//
// Normaliza IPs equivalentes para evitar falsos 403:
// - 127.0.0.1, ::1 e ::ffff:127.0.0.1 são todos "localhost".
// - PHP-S, proxies reversos e clientes IPv6 podem misturar essas formas
//   no mesmo navegador, então tratamos todos os loopbacks como iguais.
if (!function_exists('install_normalize_ip')) {
    function install_normalize_ip(string $ip): string {
        if ($ip === '' || $ip === '0.0.0.0') return $ip;
        // IPv4-mapped em IPv6 (::ffff:127.0.0.1) -> 127.0.0.1
        if (stripos($ip, '::ffff:') === 0) {
            $maybe = substr($ip, 7);
            if (filter_var($maybe, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) $ip = $maybe;
        }
        // Loopback IPv4/IPv6 -> token único
        if ($ip === '::1' || $ip === '127.0.0.1' || strpos($ip, '127.') === 0) return 'loopback';
        return $ip;
    }
}
$clientIp    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$clientIpKey = install_normalize_ip($clientIp);
$lockedIpKey = install_normalize_ip($_SESSION['_install']['ip_lock'] ?? '');
if (empty($_SESSION['_install']['ip_lock'])) {
    $_SESSION['_install']['ip_lock'] = $clientIp;
    $_SESSION['_install']['ip_lock_em'] = date('d/m/Y H:i:s');
    install_log('INSTALADOR INICIADO', "IP travado: $clientIp UA=" . substr($_SERVER['HTTP_USER_AGENT'] ?? '?', 0, 80));
} elseif ($lockedIpKey !== $clientIpKey) {
    install_log('ACESSO BLOQUEADO', "IP $clientIp tentou acessar (autorizado: " . $_SESSION['_install']['ip_lock'] . ")");
    http_response_code(403);
    header('Content-Type: text/html; charset=UTF-8');
    $ipDono = htmlspecialchars($_SESSION['_install']['ip_lock'], ENT_QUOTES);
    $quando = htmlspecialchars($_SESSION['_install']['ip_lock_em'] ?? '', ENT_QUOTES);
    $ipSeu  = htmlspecialchars($clientIp, ENT_QUOTES);
    echo '<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><title>Acesso negado</title>'
       . '<style>body{background:#0b0b0b;color:#eee;font-family:Arial;text-align:center;padding:60px 15px;margin:0}'
       . '.box{max-width:520px;margin:0 auto;background:#1a1a1a;border:2px solid #c33;border-radius:10px;padding:30px;'
       . 'box-shadow:0 0 24px rgba(204,51,51,.35)}h1{color:#f88;margin-top:0}'
       . 'code{background:#000;padding:2px 6px;border-radius:3px;color:#9fd}</style></head><body>'
       . '<div class="box"><h1>🔒 Acesso negado</h1>'
       . '<p>O instalador está em uso por outra máquina.</p>'
       . '<p style="margin:14px 0;color:#bbb">IP autorizado: <code>' . $ipDono . '</code><br>'
       . 'Travado em: <code>' . $quando . '</code><br>'
       . 'Seu IP: <code>' . $ipSeu . '</code></p>'
       . '<p style="color:#999;font-size:12px">Se foi você que iniciou a instalação em outro lugar, '
       . 'continue de lá ou aguarde a sessão expirar.</p></div></body></html>';
    exit;
}

// ===== Endpoint para baixar o log persistente =====
if (isset($_GET['export']) && $_GET['export'] === 'log') {
    $logFile = __DIR__ . '/install.log';
    if (!is_file($logFile)) { http_response_code(404); echo 'Sem log ainda.'; exit; }
    header('Content-Type: text/plain; charset=UTF-8');
    header('Content-Disposition: attachment; filename="naruto-by-anubis_install_' . date('Y-m-d_His') . '.log"');
    readfile($logFile);
    exit;
}

// ===== Endpoint para download do resumo da instalação em .txt =====
if (isset($_GET['export']) && $_GET['export'] === 'txt') {
    if (empty($_SESSION['_install']['mysql']) || empty($_SESSION['_install']['adm'])) {
        header('Location: install.php?step=2'); exit;
    }
    $cfg = $_SESSION['_install']['mysql'];
    $adm = $_SESSION['_install']['adm'];
    $servs = $_SESSION['_install']['servidores'] ?? [];
    $nomeJogo = $_SESSION['_install']['nome_jogo'] ?? 'narutoHIT';

    $linhas = [];
    $linhas[] = '====================================================';
    $linhas[] = '  RESUMO DA INSTALAÇÃO — ' . INSTALL_TITLE;
    $linhas[] = '  Gerado em: ' . date('d/m/Y H:i:s');
    $linhas[] = '====================================================';
    $linhas[] = '';
    $linhas[] = '[ BANCO MYSQL ]';
    $linhas[] = '  Host / Porta : ' . $cfg['host'] . ':' . $cfg['port'];
    $linhas[] = '  Usuário      : ' . $cfg['user'];
    $linhas[] = '  Senha        : ' . ($cfg['pass'] === '' ? '(vazia)' : $cfg['pass']);
    $linhas[] = '  Banco jogo   : ' . $cfg['db'];
    $linhas[] = '  Banco fórum  : ' . (($cfg['db_forum'] !== '' && $cfg['db_forum'] !== $cfg['db']) ? $cfg['db_forum'] : '(mesmo do jogo)');
    $linhas[] = '  Nome do jogo : ' . $nomeJogo;
    $linhas[] = '';
    $linhas[] = '[ SERVIDORES (' . count($servs) . ') ]';
    foreach ($servs as $i => $srv) {
        $linhas[] = '  #' . ($i + 1) . ' — ' . $srv['nome'] . ' (capacidade: ' . $srv['capacidade'] . ' jogadores)' . ($i === 0 ? '  <-- ADM aqui' : '');
    }
    $linhas[] = '';
    $linhas[] = '[ CONTA DE ADMINISTRADOR ]';
    $linhas[] = '  Usuário         : ' . $adm['usuario'];
    $linhas[] = '  Email           : ' . $adm['email'];
    $linhas[] = '  Senha           : ' . $adm['senha'];
    $linhas[] = '  Servidor inicial: ' . ($servs[0]['nome'] ?? '—');
    $linhas[] = '  Cargo           : ADM (nível 1)';
    $linhas[] = '';
    $linhas[] = '====================================================';
    $linhas[] = '  IMPORTANTE: guarde este arquivo em local seguro.';
    $linhas[] = '  Ele contém a senha do MySQL e do administrador.';
    $linhas[] = '====================================================';

    $conteudo = implode("\r\n", $linhas) . "\r\n";
    $nomeArq = 'naruto-by-anubis_resumo-instalacao_' . date('Y-m-d_His') . '.txt';

    header('Content-Type: text/plain; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $nomeArq . '"');
    header('Content-Length: ' . strlen($conteudo));
    header('Cache-Control: no-store');
    echo $conteudo;
    exit;
}

// ===== Endpoint AJAX para checagem animada de requisitos =====
if (isset($_GET['ajax']) && $_GET['ajax'] === 'check') {
    header('Content-Type: application/json; charset=UTF-8');
    $key = $_GET['k'] ?? '';
    $result = ['ok' => false, 'msg' => 'desconhecido'];
    switch ($key) {
        case 'php':
            $result = ['ok' => PHP_VERSION_ID >= 80000, 'msg' => PHP_VERSION];
            break;
        case 'pdo_mysql':
            $ok = extension_loaded('pdo_mysql');
            $result = ['ok' => $ok, 'msg' => $ok ? 'instalada' : 'AUSENTE'];
            break;
        case 'db_sql':
            $ok = file_exists($ROOT . '/database.sql');
            $result = ['ok' => $ok, 'msg' => $ok ? 'sim' : 'NÃO encontrado'];
            break;
        case 'forum_sql':
            $ok = file_exists($ROOT . '/forum.sql');
            $result = ['ok' => $ok, 'msg' => $ok ? 'sim' : 'NÃO encontrado'];
            break;
        case 'config_writable':
            $ok = is_writable($ROOT . '/config');
            $result = ['ok' => $ok, 'msg' => $ok ? 'sim' : 'NÃO gravável'];
            break;
        case 'install_writable':
            $ok = is_writable(__DIR__);
            $result = ['ok' => $ok, 'msg' => $ok ? 'sim' : 'NÃO gravável'];
            break;
    }
    echo json_encode($result);
    exit;
}

// Trava de segurança: se já houver instalação MySQL ativa e a flag de re-install
// não for passada, alerta. (Para reinstalar: ?step=1&force=1)
$cfgFile = $ROOT . '/config/database.php';
if (file_exists($cfgFile)) {
    $cfg = include $cfgFile;
    if (($cfg['driver'] ?? 'mysql') === 'mysql' && empty($_GET['force']) && !isset($_POST['mysql_user'])) {
        $_SESSION['_install']['ja_instalado'] = true;
    }
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url_step($n) { return '?step=' . $n; }

// ===== Processamento POST =====
$erros = [];

if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['mysql_host'] ?? '127.0.0.1');
    $port = (int)($_POST['mysql_port'] ?? 3306);
    $user = trim($_POST['mysql_user'] ?? 'root');
    $pass = (string)($_POST['mysql_pass'] ?? '');
    $db   = trim($_POST['mysql_db']   ?? '');
    $dbForum = trim($_POST['mysql_db_forum'] ?? '');
    $nomeJogo = trim($_POST['nome_jogo'] ?? BRAND_INTERNAL);

    if ($db === '' || !preg_match('/^[A-Za-z0-9_]+$/', $db)) {
        $erros[] = 'Nome do banco principal inválido. Use apenas letras, números e underline.';
    }
    if ($dbForum !== '' && !preg_match('/^[A-Za-z0-9_]+$/', $dbForum)) {
        $erros[] = 'Nome do banco do fórum inválido. Use apenas letras, números e underline (ou deixe vazio).';
    }
    if ($dbForum !== '' && $dbForum === $db) {
        $erros[] = 'O banco do fórum deve ter nome diferente do banco principal (ou deixe vazio para usar o mesmo).';
    }
    if ($user === '') $erros[] = 'Usuário do MySQL é obrigatório.';
    if ($nomeJogo === '' || mb_strlen($nomeJogo) > 30) {
        $erros[] = 'Nome do jogo deve ter entre 1 e 30 caracteres.';
    }

    if (empty($erros)) {
        try {
            $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            if ($dbForum !== '') {
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbForum` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
            $_SESSION['_install']['mysql'] = compact('host', 'port', 'user', 'pass', 'db');
            $_SESSION['_install']['mysql']['db_forum'] = $dbForum;
            $_SESSION['_install']['nome_jogo'] = $nomeJogo;
            install_log('PASSO 2 OK', "MySQL $user@$host:$port db=$db forum=" . ($dbForum ?: '(mesmo)') . " jogo=$nomeJogo");
            header('Location: ' . url_step(3));
            exit;
        } catch (PDOException $e) {
            install_log('PASSO 2 ERRO', 'Conexao MySQL: ' . $e->getMessage());
            $erros[] = 'Falha ao conectar / criar o banco: ' . $e->getMessage();
        }
    }
}

if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Configuração obrigatória de servidores
    $qtd = (int)($_POST['qtd_servidores'] ?? 0);
    if ($qtd < 1 || $qtd > 10) {
        $erros[] = 'Quantidade de servidores deve ser entre 1 e 10.';
    }
    $servidores = [];
    if (empty($erros)) {
        for ($i = 0; $i < $qtd; $i++) {
            $nome = trim($_POST["srv_nome_$i"] ?? '');
            $cap  = (int)($_POST["srv_cap_$i"] ?? 0);
            if ($nome === '' || mb_strlen($nome) > 50) {
                $erros[] = "Servidor #" . ($i + 1) . ": nome obrigatório (máx. 50 caracteres).";
                break;
            }
            if ($cap < 1 || $cap > 100000) {
                $erros[] = "Servidor #" . ($i + 1) . ": capacidade deve estar entre 1 e 100000.";
                break;
            }
            $servidores[] = ['nome' => $nome, 'capacidade' => $cap];
        }
    }
    if (empty($erros)) {
        $_SESSION['_install']['servidores'] = $servidores;
        install_log('PASSO 3 OK', count($servidores) . ' servidor(es) configurado(s)');
        header('Location: ' . url_step(4));
        exit;
    } elseif (!empty($erros)) {
        install_log('PASSO 3 ERRO', implode(' | ', $erros));
    }
}

if ($step === 4 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../_inc/personagens_catalogo.php';
    // só aceita personagens que têm avatares reais no repositório (1.jpg..9.jpg).
    $personagens_validos = array_filter(
        array_merge(personagens_iniciais(), array_keys(personagens_catalogo())),
        'personagem_tem_avatares'
    );

    $usuario    = trim($_POST['adm_user']  ?? '');
    $email      = trim($_POST['adm_email'] ?? '');
    $senha      = (string)($_POST['adm_pass']  ?? '');
    $senha2     = (string)($_POST['adm_pass2'] ?? '');
    $personagem = trim((string)($_POST['adm_personagem'] ?? 'naruto'));

    if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $usuario)) $erros[] = 'Usuário deve ter 3-20 letras/números/underline.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))      $erros[] = 'Email inválido.';
    if (strlen($senha) < 6)                               $erros[] = 'Senha deve ter ao menos 6 caracteres.';
    if ($senha !== $senha2)                               $erros[] = 'As senhas não coincidem.';
    if (!in_array($personagem, $personagens_validos, true)) $erros[] = 'Personagem inválido.';

    if (empty($erros)) {
        $_SESSION['_install']['adm'] = compact('usuario', 'email', 'senha', 'personagem');
        install_log('PASSO 4 OK', "ADM usuario=$usuario email=$email personagem=$personagem");
        header('Location: ' . url_step(5));
        exit;
    } else {
        install_log('PASSO 4 ERRO', implode(' | ', $erros));
    }
}

// ===== Render =====
?><!DOCTYPE html>
<html lang="<?= $lang === 'pt' ? 'pt-br' : ($lang === 'es' ? 'es' : 'en') ?>"><head>
<meta charset="UTF-8" />
<title><?= h(t('install_title')) ?> — <?= h(t('page_title')) ?> <?= $showWelcome ? '' : '#' . $step ?></title>
<link rel="icon" href="../_img/favicon.ico" />
<style>
*{box-sizing:border-box}
body{
  background:url('../_img/background.jpg') #0b0b0b;
  background-size:cover;background-attachment:fixed;
  color:#d8d8d8;font-family:Arial,Helvetica,sans-serif;font-size:13px;
  margin:0;padding:30px 15px;min-height:100vh;
}
.box{
  max-width:820px;margin:0 auto;
  background:linear-gradient(180deg, rgba(20,20,20,.96), rgba(10,10,10,.96));
  border:2px solid #ff8c1a;border-radius:10px;
  box-shadow:0 0 24px rgba(255,140,26,.35), inset 0 0 40px rgba(0,0,0,.6);
  padding:0 25px 25px;
  position:relative;
}
.brand{
  text-align:center;padding:18px 0 6px;
  border-bottom:1px solid #3a2a14;margin-bottom:18px;
}
.brand img{max-width:240px;width:60%;height:auto;filter:drop-shadow(0 2px 6px #000)}
.lang-switch{position:absolute;top:14px;right:18px;display:flex;align-items:center;gap:6px;font-size:12px}
.lang-switch a{color:#888;text-decoration:none;padding:3px 8px;border:1px solid #333;border-radius:4px;background:#0f0f0f}
.lang-switch a:hover{color:#fff;border-color:#ff8c1a}
.lang-switch a.active{color:#fff;background:#ff8c1a;border-color:#ff8c1a;font-weight:bold}
h1{
  color:#ff8c1a;margin:8px 0 0;font-size:22px;letter-spacing:1px;
  text-shadow:0 0 10px rgba(255,140,26,.6), 0 2px 0 #000;
}
h1 small{display:block;color:#bbb;font-size:11px;letter-spacing:2px;margin-top:4px;text-shadow:none}
h2{color:#ffaa44;border-bottom:1px solid #3a2a14;padding-bottom:6px;margin-top:24px}
.steps{display:flex;gap:6px;margin:18px 0 22px}
.steps span{
  flex:1;padding:10px 6px;text-align:center;
  background:#171717;border:1px solid #2c2c2c;border-radius:5px;font-size:12px;color:#888;
}
.steps span.on{
  background:url('../_img/fundo_botao.jpg') center;background-size:cover;
  color:#fff;font-weight:bold;border-color:#000;
  text-shadow:1px 1px 0 #000;
}
.steps span.done{background:#1a3a1a;border-color:#5c5;color:#9f9}
label{display:block;margin:14px 0 4px;font-weight:bold;color:#ffd9a0}
input[type=text],input[type=password],input[type=email],input[type=number]{
  width:100%;padding:10px;border:1px solid #555;background:#1a1a1a;color:#fff;
  border-radius:4px;font-size:14px;
}
input:focus{outline:none;border-color:#ff8c1a;box-shadow:0 0 6px rgba(255,140,26,.4)}
button,.btn{
  background:url('../_img/fundo_botao.jpg') left center;background-size:auto 100%;
  color:#fff;border:1px solid #000;padding:11px 26px;border-radius:4px;
  cursor:pointer;font-weight:bold;font-size:14px;text-decoration:none;
  display:inline-block;margin-top:18px;text-shadow:1px 1px 0 #000;
  transition:filter .15s;
}
button:hover,.btn:hover{filter:brightness(1.2)}
button:disabled{opacity:.5;cursor:not-allowed;filter:grayscale(.6)}
.erro{background:#5a1a1a;border:1px solid #c33;padding:10px;border-radius:4px;margin:10px 0;color:#fdd}
.ok  {background:#1a4a1a;border:1px solid #5c5;padding:10px;border-radius:4px;margin:10px 0;color:#dfd}
.info{background:#1a3a4a;border:1px solid #59c;padding:10px;border-radius:4px;margin:10px 0;color:#dde}
.aviso{background:#5a3a1a;border:1px solid #c93;padding:10px;border-radius:4px;margin:10px 0;color:#feb}

/* ===== Tela de Boas-vindas ===== */
.welcome{margin:10px 0 0}
.welcome-hero{
  position:relative;border-radius:8px;overflow:hidden;
  border:2px solid #ff8c1a;box-shadow:0 0 18px rgba(255,140,26,.35);
  margin-bottom:18px;background:#000;
}
.welcome-hero img{width:100%;display:block;max-height:240px;object-fit:cover;opacity:.85}
.welcome-hero-overlay{
  position:absolute;inset:0;display:flex;flex-direction:column;justify-content:flex-end;
  padding:20px;
  background:linear-gradient(180deg,rgba(0,0,0,.1) 0%,rgba(0,0,0,.85) 100%);
}
.welcome-hero-overlay h2{
  color:#ff8c1a;margin:0 0 4px;font-size:26px;letter-spacing:1px;
  text-shadow:0 0 12px rgba(255,140,26,.7), 2px 2px 0 #000;border:0;padding:0;
}
.welcome-hero-overlay p{margin:0;color:#fff;font-size:13px;text-shadow:1px 1px 0 #000}

.welcome-steps{list-style:none;padding:0;margin:18px 0}
.welcome-steps li{
  display:flex;align-items:center;gap:12px;padding:10px 12px;margin:6px 0;
  background:#161616;border:1px solid #2a2a2a;border-left:4px solid #ff8c1a;border-radius:5px;
}
.welcome-steps .num{
  display:inline-flex;align-items:center;justify-content:center;
  width:28px;height:28px;border-radius:50%;background:#ff8c1a;color:#000;
  font-weight:bold;font-size:13px;flex:0 0 28px;
}
.welcome-steps b{color:#ffd9a0;flex:0 0 130px}
.welcome-steps .d{color:#aaa;font-size:12px;flex:1}

.welcome-meta{
  display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:8px;
  margin:14px 0;padding:12px;
  background:#0e0e0e;border:1px solid #333;border-radius:5px;font-size:12px;color:#bbb;
}
.welcome-meta b{color:#ffd9a0}

.welcome-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:18px;justify-content:center}
.welcome-actions .btn-primary{font-size:16px;padding:14px 32px}
.welcome-actions .btn-secondary{background:#333;font-size:13px}

/* ===== Lista animada de checagens ===== */
#checklist{list-style:none;padding:0;margin:14px 0}
#checklist li{
  display:flex;align-items:center;gap:14px;
  padding:12px 14px;margin-bottom:8px;
  background:#161616;border:1px solid #2a2a2a;border-radius:6px;
  opacity:.45;transition:all .35s;
}
#checklist li.active{opacity:1;border-color:#ff8c1a;box-shadow:0 0 10px rgba(255,140,26,.25)}
#checklist li.ok{opacity:1;border-color:#3a8c3a;background:#101a10}
#checklist li.fail{opacity:1;border-color:#a33;background:#1a0d0d}
.chk-icon{
  width:34px;height:34px;flex:0 0 34px;border-radius:50%;
  background:#0a0a0a;border:2px solid #444;position:relative;
}
.chk-icon::before{
  content:"";position:absolute;inset:3px;border-radius:50%;
  background:#222;
}
li.pending .chk-icon{border-color:#666}
li.active .chk-icon{
  border-color:#3aa3ff transparent #3aa3ff transparent;
  animation:spin .8s linear infinite;
}
li.ok .chk-icon{
  border-color:#5c5;background:radial-gradient(circle,#1c4a1c,#0a200a);
}
li.ok .chk-icon::after{
  content:"";position:absolute;left:10px;top:5px;width:9px;height:16px;
  border:solid #9f9;border-width:0 3px 3px 0;transform:rotate(45deg);
}
li.fail .chk-icon{
  border-color:#f55;background:radial-gradient(circle,#4a1010,#200505);
  animation:shake .35s linear 2;
}
li.fail .chk-icon::after{
  content:"✕";position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
  color:#fbb;font-weight:bold;font-size:20px;text-shadow:0 0 4px #f00;
}
.chk-label{flex:1}
.chk-label b{display:block;color:#fff}
.chk-label .v{font-size:11px;color:#aaa}
li.ok .chk-label .v{color:#9f9}
li.fail .chk-label .v{color:#f99}

@keyframes spin{to{transform:rotate(360deg)}}
@keyframes shake{
  0%,100%{transform:translateX(0)}
  25%{transform:translateX(-3px)}
  75%{transform:translateX(3px)}
}

.row{display:flex;gap:12px}.row>div{flex:1}
.muted{color:#999;font-size:12px}
.log{background:#0a0a0a;border:1px solid #333;padding:14px;font-family:monospace;
  font-size:12px;max-height:340px;overflow:auto;white-space:pre-wrap;border-radius:4px;color:#cfc}

/* ===== Doação ===== */
.donation{
  margin-top:24px;background:#0e0e0e;border:2px solid #ff8c1a;border-radius:8px;
  padding:18px;text-align:center;
  box-shadow:0 0 14px rgba(255,140,26,.3);
}
.donation h3{color:#ff8c1a;margin:0 0 6px;font-size:18px;text-shadow:0 0 6px rgba(255,140,26,.5)}
.donation p{color:#ddd;margin:6px 0 14px}
.wallet{
  display:flex;align-items:center;gap:10px;justify-content:center;
  background:#1a1a1a;border:1px solid #333;border-radius:5px;
  padding:8px 10px;margin:6px 0;font-family:monospace;font-size:12px;
  color:#9fd;word-break:break-all;text-align:left;
}
.wallet .tag{
  background:#ff8c1a;color:#000;font-weight:bold;font-family:Arial;
  padding:3px 8px;border-radius:3px;font-size:11px;flex:0 0 auto;
}
.wallet .copy{
  background:#333;color:#fff;border:0;padding:4px 10px;font-size:11px;border-radius:3px;
  cursor:pointer;flex:0 0 auto;margin-top:0;
}
.wallet .copy:hover{background:#ff8c1a;color:#000}
</style>
</head><body>
<div class="box">
<div class="brand">
  <img src="../_img/logo.jpg" alt="logo" onerror="this.style.display='none'" />
  <h1><?= h(t('install_title')) ?><small><?= h(t('subtitle')) ?></small></h1>
  <div class="lang-switch">
    <span class="muted"><?= h(t('lang_label')) ?>:</span>
    <?php foreach ($LANGS as $code => $info): ?>
      <a href="?lang=<?= $code ?><?= $showWelcome ? '' : '&step=' . $step ?>" class="<?= $code === $lang ? 'active' : '' ?>" title="<?= h($info['lang_name']) ?>"><?= $FLAG_SVG[$code] ?? '' ?> <?= strtoupper($code) ?></a>
    <?php endforeach; ?>
  </div>
</div>

<?php if ($showWelcome): ?>
  <?php
    // Detecta se já há instalação em andamento (sessão ativa) para mostrar "continuar"
    $temProgresso = !empty($_SESSION['_install']['mysql']) || !empty($_SESSION['_install']['servidores']) || !empty($_SESSION['_install']['adm']);
    $jaInstalado = is_file(__DIR__ . '/../config/database.php')
      && strpos((string)@file_get_contents(__DIR__ . '/../config/database.php'), 'mysql') !== false;
  ?>
  <div class="welcome">
    <div class="welcome-hero">
      <img src="../_img/menu.jpg" alt="" onerror="this.style.display='none'" />
      <div class="welcome-hero-overlay">
        <h2><?= t('welcome_h') ?></h2>
        <p><?= h(t('install_title')) ?></p>
      </div>
    </div>

    <div class="welcome-body">
      <p style="font-size:14px;line-height:1.55;color:#ddd"><?= t('welcome_intro') ?></p>

      <ul class="welcome-steps">
        <li><span class="num">1</span><b><?= h(t('step_req')) ?></b><span class="d"><?= h(t('step_req_d')) ?></span></li>
        <li><span class="num">2</span><b><?= h(t('step_db')) ?></b><span class="d"><?= h(t('step_db_d')) ?></span></li>
        <li><span class="num">3</span><b><?= h(t('step_srv')) ?></b><span class="d"><?= h(t('step_srv_d')) ?></span></li>
        <li><span class="num">4</span><b><?= h(t('step_adm')) ?></b><span class="d"><?= h(t('step_adm_d')) ?></span></li>
        <li><span class="num">5</span><b><?= h(t('step_imp')) ?></b><span class="d"><?= h(t('step_imp_d')) ?></span></li>
      </ul>

      <div class="welcome-meta">
        <div><?= t('meta_time') ?></div>
        <div><?= t('meta_sec') ?></div>
        <div><?= t('meta_safe') ?></div>
      </div>

      <?php if ($jaInstalado): ?>
        <div class="aviso"><?= t('already_installed') ?></div>
      <?php endif; ?>

      <div class="welcome-actions">
        <a href="?step=1" class="btn btn-primary"><?= h($temProgresso ? t('btn_continue') : t('btn_start')) ?></a>
        <a href="?step=0" class="btn btn-secondary"><?= h(t('btn_diag')) ?></a>
      </div>

      <p class="muted" style="text-align:center;margin-top:18px"><?= h(t('installer_v')) ?>: <b>v1.2</b> &nbsp;·&nbsp; PHP <b><?= h(PHP_VERSION) ?></b></p>
    </div>
  </div>

<?php else: ?>

<div class="steps">
  <?php if ($step === 0): ?><span class="on">0. <?= h(t('step_diag')) ?></span><?php endif; ?>
  <span class="<?= $step==1?'on':($step>1?'done':'') ?>">1. <?= h(t('step_req')) ?></span>
  <span class="<?= $step==2?'on':($step>2?'done':'') ?>">2. <?= h(t('step_db')) ?></span>
  <span class="<?= $step==3?'on':($step>3?'done':'') ?>">3. <?= h(t('step_srv')) ?></span>
  <span class="<?= $step==4?'on':($step>4?'done':'') ?>">4. <?= h(t('step_adm')) ?></span>
  <span class="<?= $step==5?'on':'' ?>">5. <?= h(t('step_imp_full')) ?></span>
</div>

<?php if (!empty($_SESSION['_install']['ja_instalado'])): ?>
  <div class="aviso"><?= t('already_installed_top') ?></div>
<?php endif; ?>

<?php foreach ($erros as $e): ?>
  <div class="erro"><?= h($e) ?></div>
<?php endforeach; ?>

<?php if ($step === 0):
    // ===== Passo 0 (opcional): Diagnóstico de permissões de pastas =====
    $checkDirs = [
        ['path' => '../config',        'desc' => 'config/',        'crit' => true,  'motivo' => 'Será reescrito com as credenciais do MySQL.'],
        ['path' => '.',                'desc' => 'install/',       'crit' => true,  'motivo' => 'Precisa apagar a si mesmo após a instalação e gravar o log de auditoria.'],
        ['path' => '../_cache',        'desc' => '_cache/',        'crit' => false, 'motivo' => 'Cache de dados do jogo (gerado em runtime).'],
        ['path' => '../_img/anuncios', 'desc' => '_img/anuncios/', 'crit' => false, 'motivo' => 'Upload de banners de anúncios pelo painel ADM.'],
        ['path' => '../forum',         'desc' => 'forum/',         'crit' => false, 'motivo' => 'Anexos e avatares do fórum.'],
    ];
    $diag = [];
    $todasOk = true;
    foreach ($checkDirs as $d) {
        $abs = realpath(__DIR__ . '/' . $d['path']) ?: (__DIR__ . '/' . $d['path']);
        $existe = is_dir($abs);
        $gravavel = $existe && is_writable($abs);
        // Teste real: tenta criar e apagar um arquivo
        $teste = false;
        if ($gravavel) {
            $tmp = $abs . '/.install_test_' . bin2hex(random_bytes(4));
            $teste = @file_put_contents($tmp, 'x') !== false;
            if ($teste) @unlink($tmp);
        }
        $ok = $existe && $gravavel && $teste;
        if ($d['crit'] && !$ok) $todasOk = false;
        $diag[] = ['desc' => $d['desc'], 'abs' => $abs, 'existe' => $existe, 'gravavel' => $gravavel, 'teste' => $teste, 'ok' => $ok, 'crit' => $d['crit'], 'motivo' => $d['motivo']];
    }
?>
  <h2><?= h(t('h_diag')) ?></h2>
  <p class="muted"><?= t('p_diag') ?></p>

  <ul class="checks" style="list-style:none;padding:0">
    <?php foreach ($diag as $d): ?>
      <li class="<?= $d['ok'] ? 'ok' : ($d['crit'] ? 'fail' : 'warn') ?>" style="display:flex;align-items:center;gap:12px;padding:10px 14px;margin:8px 0;border-radius:6px;background:<?= $d['ok'] ? 'rgba(40,120,40,.18)' : ($d['crit'] ? 'rgba(160,40,40,.22)' : 'rgba(160,120,40,.18)') ?>;border-left:4px solid <?= $d['ok'] ? '#4caf50' : ($d['crit'] ? '#e53935' : '#ffa726') ?>">
        <div style="font-size:22px;width:28px;text-align:center"><?= $d['ok'] ? '✓' : ($d['crit'] ? '✗' : '⚠') ?></div>
        <div style="flex:1">
          <div><b><?= h($d['desc']) ?></b> <?= $d['crit'] ? '<span style="color:#ff8c1a;font-size:11px">' . h(t('critical')) . '</span>' : '<span class="muted" style="font-size:11px">' . h(t('optional')) . '</span>' ?></div>
          <div class="muted" style="font-size:11px"><?= h($d['motivo']) ?></div>
          <?php if (!$d['ok']): ?>
            <div style="color:#fdd;font-size:11px;margin-top:4px">
              <?php if (!$d['existe']): ?>Pasta não existe: <code><?= h($d['abs']) ?></code><?php
              elseif (!$d['gravavel']): ?>Sem permissão de escrita. No Linux: <code>chmod 755 <?= h($d['desc']) ?></code> (ou 775 se necessário).<?php
              else: ?>Existe e parece gravável, mas o teste de escrita falhou.<?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>

  <?php if ($todasOk): ?>
    <div class="ok"><b><?= t('all_ok') ?></b></div>
  <?php else: ?>
    <div class="erro"><b><?= t('has_issues') ?></b></div>
  <?php endif; ?>

  <div class="aviso" style="margin-top:14px">
    <b>Dica (Linux/Apache):</b><br>
    <code>sudo chown -R www-data:www-data /caminho/para/o/jogo</code><br>
    <code>sudo chmod -R 755 /caminho/para/o/jogo</code>
  </div>

  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px">
    <a href="<?= url_step(0) ?>" class="btn" style="background:#555"><?= h(t('btn_recheck')) ?></a>
    <a href="<?= url_step(1) ?>" class="btn"><?= h(t('btn_to_req')) ?></a>
  </div>

<?php elseif ($step === 1): ?>
  <h2><?= h(t('h_env')) ?></h2>
  <p class="muted"><?= h(t('p_env')) ?></p>
  <p style="text-align:right;margin-top:-8px">
    <a href="<?= url_step(0) ?>" class="muted" style="font-size:11px;text-decoration:underline"><?= h(t('btn_diag')) ?></a>
  </p>

  <ul id="checklist">
    <li class="pending" data-key="php"><div class="chk-icon"></div><div class="chk-label"><b>PHP &gt;= 8.0</b><span class="v">aguardando...</span></div></li>
    <li class="pending" data-key="pdo_mysql"><div class="chk-icon"></div><div class="chk-label"><b>Extensão pdo_mysql</b><span class="v">aguardando...</span></div></li>
    <li class="pending" data-key="db_sql"><div class="chk-icon"></div><div class="chk-label"><b>database.sql presente</b><span class="v">aguardando...</span></div></li>
    <li class="pending" data-key="forum_sql"><div class="chk-icon"></div><div class="chk-label"><b>forum.sql presente</b><span class="v">aguardando...</span></div></li>
    <li class="pending" data-key="config_writable"><div class="chk-icon"></div><div class="chk-label"><b>config/ gravável</b><span class="v">aguardando...</span></div></li>
    <li class="pending" data-key="install_writable"><div class="chk-icon"></div><div class="chk-label"><b>install/ gravável (auto-delete)</b><span class="v">aguardando...</span></div></li>
  </ul>

  <div id="finalBox" style="display:none">
    <div class="ok" id="okBox" style="display:none">Tudo pronto. Pode prosseguir.</div>
    <div class="erro" id="errBox" style="display:none">Resolva os itens em vermelho antes de continuar.</div>
    <a href="<?= url_step(2) ?>" id="btnNext" class="btn" style="display:none"><?= h(t('btn_next')) ?></a>
    <a href="<?= h($_SERVER['REQUEST_URI']) ?>" id="btnRetry" class="btn" style="display:none"><?= h(t('btn_recheck')) ?></a>
  </div>

  <script>
  (function(){
    var items = document.querySelectorAll('#checklist li');
    var i = 0, allOk = true;

    function next(){
      if (i >= items.length){
        finalize();
        return;
      }
      var li = items[i];
      var key = li.getAttribute('data-key');
      li.classList.remove('pending');
      li.classList.add('active');
      li.querySelector('.v').textContent = 'verificando...';

      var minDelay = 650; // tempo mínimo de animação por item
      var t0 = Date.now();

      fetch('?ajax=check&k=' + encodeURIComponent(key), {cache:'no-store'})
        .then(function(r){ return r.json(); })
        .then(function(d){
          var elapsed = Date.now() - t0;
          var wait = Math.max(0, minDelay - elapsed);
          setTimeout(function(){
            li.classList.remove('active');
            li.querySelector('.v').textContent = d.msg;
            if (d.ok){
              li.classList.add('ok');
            } else {
              li.classList.add('fail');
              allOk = false;
            }
            i++;
            setTimeout(next, 250);
          }, wait);
        })
        .catch(function(){
          li.classList.remove('active');
          li.classList.add('fail');
          li.querySelector('.v').textContent = 'erro de rede';
          allOk = false;
          i++;
          setTimeout(next, 250);
        });
    }

    function finalize(){
      var fb = document.getElementById('finalBox');
      fb.style.display = 'block';
      if (allOk){
        document.getElementById('okBox').style.display = 'block';
        document.getElementById('btnNext').style.display = 'inline-block';
      } else {
        document.getElementById('errBox').style.display = 'block';
        document.getElementById('btnRetry').style.display = 'inline-block';
      }
    }

    setTimeout(next, 350);
  })();
  </script>

<?php elseif ($step === 2): ?>
  <h2><?= h(t('h_mysql')) ?></h2>
  <p class="muted"><?= h(t('p_mysql')) ?></p>
  <form method="post">
    <div class="row">
      <div>
        <label><?= h(t('mysql_host')) ?></label>
        <input type="text" name="mysql_host" value="<?= h($_SESSION['_install']['mysql']['host'] ?? '127.0.0.1') ?>" required />
      </div>
      <div>
        <label><?= h(t('mysql_port')) ?></label>
        <input type="number" name="mysql_port" value="<?= h($_SESSION['_install']['mysql']['port'] ?? 3306) ?>" required />
      </div>
    </div>
    <div class="row">
      <div>
        <label><?= h(t('mysql_user')) ?></label>
        <input type="text" name="mysql_user" value="<?= h($_SESSION['_install']['mysql']['user'] ?? 'root') ?>" required />
      </div>
      <div>
        <label><?= h(t('mysql_pass')) ?></label>
        <input type="password" name="mysql_pass" placeholder="<?= h(t('mysql_pass_ph')) ?>" />
      </div>
    </div>
    <label><?= h(t('mysql_db_main')) ?></label>
    <input type="text" name="mysql_db" value="<?= h($_SESSION['_install']['mysql']['db'] ?? 'narutohit') ?>" required pattern="[A-Za-z0-9_]+" />

    <label><?= h(t('mysql_db_forum')) ?> <span class="muted"><?= h(t('mysql_db_forum_opt')) ?></span></label>
    <input type="text" name="mysql_db_forum" value="<?= h($_SESSION['_install']['mysql']['db_forum'] ?? '') ?>" pattern="[A-Za-z0-9_]+" placeholder="ex: narutohit_forum" />
    <p class="muted" style="margin-top:4px"><?= h(t('mysql_db_forum_hint')) ?></p>

    <label><?= h(t('game_name')) ?></label>
    <input type="text" name="nome_jogo" value="<?= h($_SESSION['_install']['nome_jogo'] ?? BRAND_INTERNAL) ?>" maxlength="30" required />
    <p class="muted" style="margin-top:4px"><?= sprintf(t('game_name_hint'), '<code>' . h(BRAND_INTERNAL) . '</code>', '<code>' . h(BRAND_INTERNAL) . '</code>') ?></p>

    <button type="submit"><?= t('btn_test_create') ?></button>
  </form>

<?php elseif ($step === 3): ?>
  <?php
    if (empty($_SESSION['_install']['mysql'])) { header('Location: ' . url_step(2)); exit; }
    $servSalvos = $_SESSION['_install']['servidores'] ?? [];
    $qtdSalva = count($servSalvos) > 0 ? count($servSalvos) : 1;
  ?>
  <h2><?= h(t('h_srv')) ?></h2>
  <p class="muted"><?= h(t('p_srv')) ?></p>

  <form method="post" id="formServ">
    <label><?= h(t('srv_qtd')) ?></label>
    <input type="number" name="qtd_servidores" id="qtdServ" min="1" max="10" value="<?= h($qtdSalva) ?>" required />

    <div id="listaServ" style="margin-top:14px"></div>

    <button type="submit"><?= h(t('btn_continue_arrow')) ?></button>
  </form>

  <script>
  (function(){
    // Nomes padrão temáticos (vilas Naruto). O dono pode editar livremente cada um.
    var nomesPadrao = ['Konoha', 'Suna', 'Kiri', 'Kumo', 'Iwa', 'Ame', 'Taki', 'Kusa', 'Yu', 'Hoshi'];
    var defaults = <?= json_encode($servSalvos ?: [['nome' => 'Konoha', 'capacidade' => 500]]) ?>;
    var labels   = <?= json_encode(['srv' => t('srv_label'), 'name' => t('srv_name'), 'cap' => t('srv_capacity')]) ?>;
    var lista = document.getElementById('listaServ');
    var input = document.getElementById('qtdServ');

    function render(){
      var n = parseInt(input.value, 10) || 1;
      if (n < 1) n = 1; if (n > 10) n = 10;
      lista.innerHTML = '';
      for (var i = 0; i < n; i++){
        var nome = (defaults[i] && defaults[i].nome)
                    ? defaults[i].nome
                    : (nomesPadrao[i] || (labels.srv + ' ' + ('0' + (i+1)).slice(-2)));
        var cap  = (defaults[i] && defaults[i].capacidade) ? defaults[i].capacidade : 500;
        var box = document.createElement('div');
        box.style.cssText = 'background:#161616;border:1px solid #333;border-radius:6px;padding:10px 12px;margin-bottom:10px';
        box.innerHTML =
          '<div style="color:#ffaa44;font-weight:bold;margin-bottom:6px">' + labels.srv + ' #' + (i+1) + '</div>' +
          '<div class="row">' +
            '<div><label style="margin-top:0">' + labels.name + '</label>' +
              '<input type="text" name="srv_nome_' + i + '" value="' + nome.replace(/"/g,'&quot;') + '" maxlength="50" required /></div>' +
            '<div><label style="margin-top:0">' + labels.cap + '</label>' +
              '<input type="number" name="srv_cap_' + i + '" value="' + cap + '" min="1" max="100000" required /></div>' +
          '</div>';
        lista.appendChild(box);
      }
    }
    input.addEventListener('input', render);
    render();
  })();
  </script>

<?php elseif ($step === 4): ?>
  <?php
    if (empty($_SESSION['_install']['mysql'])) { header('Location: ' . url_step(2)); exit; }
    if (empty($_SESSION['_install']['servidores'])) { header('Location: ' . url_step(3)); exit; }
  ?>
  <h2><?= h(t('h_adm')) ?></h2>
  <p class="muted"><?= sprintf(t('p_adm'), h($_SESSION['_install']['servidores'][0]['nome'])) ?></p>
  <form method="post">
    <label><?= h(t('adm_user')) ?></label>
    <input type="text" name="adm_user" value="<?= h($_SESSION['_install']['adm']['usuario'] ?? '') ?>" maxlength="20" required pattern="[A-Za-z0-9_]{3,20}" />
    <label><?= h(t('adm_email')) ?></label>
    <input type="email" name="adm_email" value="<?= h($_SESSION['_install']['adm']['email'] ?? '') ?>" required />
    <div class="row">
      <div>
        <label><?= h(t('adm_pass')) ?></label>
        <input type="password" name="adm_pass" required minlength="6" />
      </div>
      <div>
        <label><?= h(t('adm_pass2')) ?></label>
        <input type="password" name="adm_pass2" required minlength="6" />
      </div>
    </div>
    <?php
      require_once __DIR__ . '/../_inc/personagens_catalogo.php';
      $iniciais  = personagens_iniciais();
      $catalogo  = personagens_catalogo();
      $sel       = $_SESSION['_install']['adm']['personagem'] ?? 'naruto';
    ?>
    <label style="margin-top:14px;">Personagem do administrador no jogo</label>
    <p class="muted" style="margin:4px 0 8px;font-size:12px;">
      O ADM começa no nível 99, então pode usar qualquer personagem (incluindo VIP) já no primeiro login.
      Para evitar confusão na primeira partida, recomendamos um dos 4 iniciais.
    </p>
    <div style="background:#0f0f0f;border:1px solid #333;border-radius:6px;padding:10px;max-height:340px;overflow-y:auto;">
      <div style="color:#ff8c1a;font-weight:bold;margin-bottom:4px;">Iniciais</div>
      <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
        <?php foreach ($iniciais as $chave): ?>
          <label style="display:inline-block;width:108px;text-align:center;cursor:pointer;border:1px solid <?= $sel===$chave?'#ff8c1a':'#222' ?>;border-radius:6px;padding:6px;background:<?= $sel===$chave?'#1d130a':'#0a0a0a' ?>;">
            <img src="../_img/personagens/reg_<?= h($chave) ?>.jpg" alt="<?= h($chave) ?>" style="width:96px;height:auto;border-radius:4px;" /><br />
            <input type="radio" name="adm_personagem" value="<?= h($chave) ?>" <?= $sel===$chave?'checked':'' ?> />
            <span style="font-size:12px;text-transform:capitalize;"><?= h($chave) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
      <div style="color:#ff8c1a;font-weight:bold;margin-bottom:4px;">Desbloqueáveis (todos disponíveis para o ADM)</div>
      <div style="display:flex;flex-wrap:wrap;gap:8px;">
        <?php foreach ($catalogo as $chave => $info): ?>
          <?php if (!personagem_tem_avatares($chave)) continue; /* esconde personagens sem avatares reais (1.jpg..9.jpg) */ ?>
          <label style="display:inline-block;width:108px;text-align:center;cursor:pointer;border:1px solid <?= $sel===$chave?'#ff8c1a':'#222' ?>;border-radius:6px;padding:6px;background:<?= $sel===$chave?'#1d130a':'#0a0a0a' ?>;">
            <img src="../_img/personagens/unlock_<?= h($chave) ?>.jpg" alt="<?= h($chave) ?>" style="width:96px;height:auto;border-radius:4px;" /><br />
            <input type="radio" name="adm_personagem" value="<?= h($chave) ?>" <?= $sel===$chave?'checked':'' ?> />
            <span style="font-size:12px;"><?= h($info['nome']) ?></span>
            <?php if (!empty($info['vip'])): ?><span style="color:#ffc14d;font-size:10px;">[VIP]</span><?php endif; ?>
            <span style="display:block;font-size:10px;color:#888;">Nv. <?= (int)$info['nivel'] ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>
    <button type="submit" style="margin-top:14px;"><?= h(t('btn_continue_arrow')) ?></button>
  </form>

<?php elseif ($step === 5): ?>
  <?php
    if (empty($_SESSION['_install']['mysql']) || empty($_SESSION['_install']['adm'])) {
      header('Location: ' . url_step(2)); exit;
    }

    $cfg = $_SESSION['_install']['mysql'];
    $adm = $_SESSION['_install']['adm'];
    $servidoresCfg = $_SESSION['_install']['servidores'] ?? [];
    $nomeJogo = $_SESSION['_install']['nome_jogo'] ?? BRAND_INTERNAL;

    // Token anti-CSRF: gera (uma vez) e valida o envio do formulário de confirmação
    if (empty($_SESSION['_install']['csrf'])) {
        $_SESSION['_install']['csrf'] = bin2hex(random_bytes(32));
    }
    $csrfToken = $_SESSION['_install']['csrf'];

    $confirmado = false;
    $csrfErro = false;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['confirmar'])) {
        $enviado = (string)($_POST['csrf'] ?? '');
        if (hash_equals($csrfToken, $enviado)) {
            $confirmado = true;
            unset($_SESSION['_install']['csrf']);
            install_log('CONFIRMACAO', 'Token CSRF valido, iniciando importacao');
        } else {
            $csrfErro = true;
            install_log('CSRF FALHOU', 'Token invalido na confirmacao do passo 5');
        }
    }
  ?>

  <?php if ($csrfErro): ?>
    <div class="erro"><?= t('csrf_fail') ?></div>
  <?php endif; ?>

  <?php if (!$confirmado): ?>
    <h2><?= h(t('h_resumo')) ?></h2>
    <p class="muted"><?= t('p_resumo') ?></p>

    <div style="background:#161616;border:1px solid #ff8c1a;border-radius:6px;padding:14px;margin:14px 0">
      <div style="color:#ffaa44;font-weight:bold;font-size:15px;margin-bottom:8px;border-bottom:1px solid #3a2a14;padding-bottom:6px"><?= t('box_db') ?></div>
      <table style="width:100%">
        <tr><td style="color:#aaa;width:35%"><?= h(t('lbl_host_port')) ?></td><td><b><?= h($cfg['host']) ?>:<?= h($cfg['port']) ?></b></td></tr>
        <tr><td style="color:#aaa"><?= h(t('lbl_user')) ?></td><td><b><?= h($cfg['user']) ?></b></td></tr>
        <tr><td style="color:#aaa"><?= h(t('lbl_pass')) ?></td><td><b><?= $cfg['pass'] === '' ? '<i style="color:#999">' . h(t('lbl_empty')) . '</i>' : str_repeat('•', min(12, strlen($cfg['pass']))) ?></b></td></tr>
        <tr><td style="color:#aaa"><?= h(t('lbl_db_game')) ?></td><td><b style="color:#9fd"><?= h($cfg['db']) ?></b></td></tr>
        <tr><td style="color:#aaa"><?= h(t('lbl_db_forum')) ?></td><td><b style="color:#9fd"><?= $cfg['db_forum'] !== '' && $cfg['db_forum'] !== $cfg['db'] ? h($cfg['db_forum']) : '<i style="color:#999">' . h(t('lbl_same_as_game')) . '</i>' ?></b></td></tr>
        <tr><td style="color:#aaa"><?= h(t('lbl_game_name')) ?></td><td><b><?= h($nomeJogo) ?></b><?= strcasecmp($nomeJogo, BRAND_INTERNAL) !== 0 ? ' <span class="muted">' . h(t('lbl_applied_site')) . '</span>' : '' ?></td></tr>
      </table>
    </div>

    <div style="background:#161616;border:1px solid #ff8c1a;border-radius:6px;padding:14px;margin:14px 0">
      <div style="color:#ffaa44;font-weight:bold;font-size:15px;margin-bottom:8px;border-bottom:1px solid #3a2a14;padding-bottom:6px"><?= t('box_srv') ?> (<?= count($servidoresCfg) ?>)</div>
      <table style="width:100%">
        <tr style="background:#0e0e0e"><td style="color:#aaa;padding:6px"><b>#</b></td><td style="color:#aaa"><b><?= h(t('col_name')) ?></b></td><td style="color:#aaa;text-align:right"><b><?= h(t('col_cap')) ?></b></td></tr>
        <?php foreach ($servidoresCfg as $i => $srv): ?>
          <tr><td style="padding:6px">#<?= $i + 1 ?></td><td><b><?= h($srv['nome']) ?></b><?= $i === 0 ? ' <span style="background:#ff8c1a;color:#000;padding:1px 6px;border-radius:3px;font-size:10px;font-weight:bold">ADM</span>' : '' ?></td><td style="text-align:right;color:#9fd"><?= number_format($srv['capacidade'], 0, ',', '.') ?> <?= h(t('players_suffix')) ?></td></tr>
        <?php endforeach; ?>
      </table>
    </div>

    <div style="background:#161616;border:1px solid #ff8c1a;border-radius:6px;padding:14px;margin:14px 0">
      <div style="color:#ffaa44;font-weight:bold;font-size:15px;margin-bottom:8px;border-bottom:1px solid #3a2a14;padding-bottom:6px"><?= t('box_adm') ?></div>
      <table style="width:100%">
        <tr><td style="color:#aaa;width:35%"><?= h(t('lbl_user')) ?></td><td><b><?= h($adm['usuario']) ?></b></td></tr>
        <tr><td style="color:#aaa"><?= h(t('adm_email')) ?></td><td><b><?= h($adm['email']) ?></b></td></tr>
        <tr><td style="color:#aaa"><?= h(t('lbl_pass')) ?></td><td><b><?= str_repeat('•', min(12, strlen($adm['senha']))) ?></b></td></tr>
        <tr><td style="color:#aaa"><?= h(t('lbl_init_srv')) ?></td><td><b style="color:#9fd"><?= h($servidoresCfg[0]['nome'] ?? '—') ?></b></td></tr>
        <tr><td style="color:#aaa"><?= h(t('lbl_role')) ?></td><td><b style="color:#ff8c1a"><?= h(t('lbl_role_val')) ?></b></td></tr>
      </table>
    </div>

    <div class="info">
      <b><?= h(t('what_happens')) ?></b>
      <ul style="margin:6px 0 0 18px;padding:0">
        <li><?= t('wh_1') ?></li>
        <li><?= t('wh_2') ?></li>
        <li><?= t('wh_3') ?></li>
        <li><?= sprintf(t('wh_4'), count($servidoresCfg)) ?></li>
        <li><?= t('wh_5') ?></li>
        <?php if (strcasecmp($nomeJogo, BRAND_INTERNAL) !== 0): ?>
          <li><?= sprintf(t('wh_6'), h($nomeJogo)) ?></li>
        <?php endif; ?>
      </ul>
    </div>

    <div class="aviso" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <span><?= t('tip_download') ?></span>
      <a href="?export=txt" class="btn" style="margin-top:0;background:#3a5a8a"><?= h(t('btn_download_txt')) ?></a>
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <a href="<?= url_step(2) ?>" class="btn" style="background:#333"><?= h(t('btn_edit_db')) ?></a>
      <a href="<?= url_step(3) ?>" class="btn" style="background:#333"><?= h(t('btn_edit_srv')) ?></a>
      <a href="<?= url_step(4) ?>" class="btn" style="background:#333"><?= h(t('btn_edit_adm')) ?></a>
      <form method="post" style="display:inline;margin:0">
        <input type="hidden" name="csrf" value="<?= h($csrfToken) ?>" />
        <button type="submit" name="confirmar" value="1" style="font-size:15px"><?= h(t('btn_confirm_install')) ?></button>
      </form>
    </div>

  <?php else:
    require __DIR__ . '/lib_sql_import.php';
    require __DIR__ . '/lib_rename.php';
    require_once $ROOT . '/_inc/security.php';

    $log = [];
    $sucesso = false;

    echo '<h2>' . h(t('h_importing')) . '</h2>';
    @ob_flush(); @flush();

    try {
      $mysql = new PDO(
        "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['db']};charset=utf8mb4",
        $cfg['user'], $cfg['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
      );

      $dbForum = $cfg['db_forum'] ?? '';
      if ($dbForum !== '' && $dbForum !== $cfg['db']) {
        $mysqlForum = new PDO(
          "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$dbForum};charset=utf8mb4",
          $cfg['user'], $cfg['pass'],
          [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $log[] = "[INFO] Fórum será instalado em banco separado: `$dbForum`.";
      } else {
        $mysqlForum = $mysql;
        $log[] = "[INFO] Fórum compartilhará o mesmo banco do jogo (`{$cfg['db']}`).";
      }

      // Tabelas que devem ser criadas VAZIAS (apenas estrutura, sem dados).
      // Mantemos com dados apenas o conteúdo estático do jogo: itens, jutsus,
      // missões, mapas, ícones, configurações, atualizações, livro, etc.
      $skipDataMain = [
        // contas e progresso
        'usuarios', 'personagens', 'usuario_jutsus', 'inventario',
        'amigos', 'block', 'mensagens', 'membros',
        // pvp / hunt / batalha
        'relatorios', 'spam', 'hunt', 'ataques_invasao',
        // organizações / clãs
        'organizacoes',
        // chat
        'chat_messages', 'chat_online', 'chat_bans',
        // logs / histórico
        'admin_logs', 'ban_historico', 'tickets', 'ticket_mensagens',
        // notícias e leituras
        'noticias', 'noticia_lida',
        // banners e visualizações
        'banner_invasao_visualizado',
        // invasões ativas (a configuração fica nos painéis admin)
        'invasoes',
        // posições e estado de mapa
        'players_positions', 'mapa_usuarios',
        // economia / vip
        'vip', 'vendas', 'ramen',
        // buffs / fragmentos / forja
        'buff_ativos', 'buff_fragmentos', 'fragmentos',
        // permissões GM (sem GMs ainda)
        'gm_permissions',
        // logs do sistema fair-play
        'provably_fair_logs', 'provably_fair_seeds',
        // servidores: serão preenchidos pela configuração do passo 3
        'servidores',
        // criador de personagem (referências de jogadores)
        'criador_refs',
      ];

      // Fórum: cria tudo vazio EXCETO categorias (estrutura de seções é estática)
      $skipDataForum = [
        'topicos', 'postagens', 'curtidas', 'reacoes',
        'notificacoes', 'seguir_topicos', 'topicos_lidos',
      ];

      // Importa dump do banco principal (database.sql)
      if (file_exists($ROOT . '/database.sql')) {
        $log[] = "==== database.sql -> MySQL (`{$cfg['db']}`) ====";
        importar_dump_mysql($mysql, $ROOT . '/database.sql', $log, $skipDataMain);

        // Ajusta colunas que precisam de tamanho maior do que o esquema original
        // (ex.: `senha` é VARCHAR(32) no esquema antigo, mas password_hash() gera 60 chars)
        try {
          $mysql->exec("ALTER TABLE `usuarios` MODIFY `senha` VARCHAR(255) NOT NULL DEFAULT ''");
          $log[] = "[OK] Coluna `usuarios.senha` ajustada para VARCHAR(255) (compatível com bcrypt).";
        } catch (PDOException $e) {
          $log[] = "[AVISO] Não foi possível redimensionar `usuarios.senha`: " . $e->getMessage();
        }
      } else {
        $log[] = "[ERRO] Arquivo database.sql não encontrado em " . $ROOT;
      }

      // Importa dump do fórum (forum.sql)
      if (file_exists($ROOT . '/forum.sql')) {
        $log[] = "==== forum.sql -> MySQL (`" . ($dbForum !== '' ? $dbForum : $cfg['db']) . "`) ====";
        importar_dump_mysql($mysqlForum, $ROOT . '/forum.sql', $log, $skipDataForum);
      } else {
        $log[] = "[AVISO] Arquivo forum.sql não encontrado em " . $ROOT;
      }

      // ===== Cria os servidores configurados no passo 3 =====
      $servidoresCfg = $_SESSION['_install']['servidores'] ?? [];
      $primeiroServidorId = null;
      if (!empty($servidoresCfg)) {
        $log[] = "==== Criando servidores configurados ====";
        try {
          $insSrv = $mysql->prepare(
            "INSERT INTO `servidores` (nome, capacidade, ativo, criado_em) VALUES (?, ?, 1, NOW())"
          );
          foreach ($servidoresCfg as $i => $srv) {
            $insSrv->execute([$srv['nome'], $srv['capacidade']]);
            $sid = (int)$mysql->lastInsertId();
            if ($i === 0) $primeiroServidorId = $sid;
            $log[] = "[OK] Servidor '{$srv['nome']}' criado (id=$sid, capacidade={$srv['capacidade']}).";
          }
        } catch (PDOException $e) {
          $log[] = "[AVISO] Falha ao criar servidores: " . $e->getMessage();
        }
      }

      $log[] = "==== Conta ADM ====";
      $stmt = $mysql->prepare("SELECT id FROM `usuarios` WHERE usuario = ? LIMIT 1");
      $stmt->execute([$adm['usuario']]);
      $existente = $stmt->fetchColumn();
      $hash = senha_hash($adm['senha']);
      $admPersonagem = $adm['personagem'] ?? 'naruto';

      if ($existente) {
        // Atualiza incluindo servidor_id e personagem se as colunas existirem
        try {
          $up = $mysql->prepare("UPDATE `usuarios` SET senha=?, email=?, personagem=?, adm=1, status='ativo', servidor_id=? WHERE id=?");
          $up->execute([$hash, $adm['email'], $admPersonagem, $primeiroServidorId, $existente]);
        } catch (PDOException $e) {
          $up = $mysql->prepare("UPDATE `usuarios` SET senha=?, email=?, personagem=?, adm=1, status='ativo' WHERE id=?");
          $up->execute([$hash, $adm['email'], $admPersonagem, $existente]);
        }
        $admId = (int)$existente;
        $log[] = "[OK] Usuário '{$adm['usuario']}' já existia — promovido a ADM (id=$admId, personagem=$admPersonagem).";
      } else {
        $admId = 0;
        try {
          $ins = $mysql->prepare(
            "INSERT INTO `usuarios` (usuario, senha, email, status, adm, nivel, avatar, vila, personagem, reg, servidor_id)
             VALUES (?, ?, ?, 'ativo', 1, 99, 1, 1, ?, NOW(), ?)"
          );
          $ins->execute([$adm['usuario'], $hash, $adm['email'], $admPersonagem, $primeiroServidorId]);
          $admId = (int)$mysql->lastInsertId();
          $log[] = "[OK] Conta ADM '{$adm['usuario']}' criada (id=$admId, servidor_id=$primeiroServidorId, personagem=$admPersonagem).";
        } catch (PDOException $e) {
          $log[] = "[AVISO] Insert completo falhou ({$e->getMessage()}), tentando sem servidor_id.";
          try {
            $ins = $mysql->prepare(
              "INSERT INTO `usuarios` (usuario, senha, email, status, adm, nivel, avatar, vila, personagem, reg)
               VALUES (?, ?, ?, 'ativo', 1, 99, 1, 1, ?, NOW())"
            );
            $ins->execute([$adm['usuario'], $hash, $adm['email'], $admPersonagem]);
            $admId = (int)$mysql->lastInsertId();
            $log[] = "[OK] Conta ADM '{$adm['usuario']}' criada (id=$admId, personagem=$admPersonagem).";
          } catch (PDOException $e2) {
            $log[] = "[AVISO] Insert completo falhou ({$e2->getMessage()}), tentando minimal.";
            $ins = $mysql->prepare("INSERT INTO `usuarios` (usuario, senha, email, status, adm) VALUES (?,?,?,'ativo',1)");
            $ins->execute([$adm['usuario'], $hash, $adm['email']]);
            $admId = (int)$mysql->lastInsertId();
            $log[] = "[OK] Conta ADM criada (modo mínimo, id=$admId).";
          }
        }
      }

      // Garante linha em `personagens` para o ADM e desbloqueia o personagem escolhido (caso seja não-inicial).
      if ($admId > 0) {
        try {
          require_once __DIR__ . '/../_inc/personagens_catalogo.php';
          // Garantir esquema mínimo da tabela personagens (executa o mesmo migrator do mysql_compat).
          $tem_usuarioid = false;
          try {
            $r = $mysql->query("SHOW COLUMNS FROM `personagens` LIKE 'usuarioid'");
            $tem_usuarioid = (bool)($r && $r->fetch(PDO::FETCH_ASSOC));
          } catch (PDOException $e) {}
          if (!$tem_usuarioid) {
            try { $mysql->exec("DROP TABLE IF EXISTS `personagens`"); } catch (PDOException $e) {}
            $cols = ["`id` INT AUTO_INCREMENT PRIMARY KEY", "`usuarioid` INT NOT NULL"];
            foreach (personagens_lista_chaves() as $chave) {
              $cols[] = "`{$chave}` TINYINT(1) NOT NULL DEFAULT 0";
            }
            $cols[] = "UNIQUE KEY `uniq_usuario` (`usuarioid`)";
            $mysql->exec("CREATE TABLE `personagens` (" . implode(",\n  ", $cols)
              . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
          }
          $insP = $mysql->prepare("INSERT IGNORE INTO `personagens` (`usuarioid`) VALUES (?)");
          $insP->execute([$admId]);

          // Se escolheu um não-inicial, marca como desbloqueado.
          if (!in_array($admPersonagem, personagens_iniciais(), true) && array_key_exists($admPersonagem, personagens_catalogo())) {
            $upP = $mysql->prepare("UPDATE `personagens` SET `{$admPersonagem}`=1 WHERE `usuarioid`=?");
            $upP->execute([$admId]);
            $log[] = "[OK] Personagem '$admPersonagem' desbloqueado para o ADM.";
          }
        } catch (PDOException $e) {
          $log[] = "[AVISO] Falha ao preparar tabela personagens para o ADM: " . $e->getMessage();
        }
      }

      $log[] = "==== Gravando config/database.php ====";
      $php  = "<?php\n// Gerado pelo instalador em " . date('Y-m-d H:i:s') . "\nreturn [\n";
      $php .= "    'driver' => 'mysql',\n";
      $php .= "    'mysql'  => [\n";
      foreach (['host'=>$cfg['host'],'port'=>(string)$cfg['port'],'dbname'=>$cfg['db'],'user'=>$cfg['user'],'pass'=>$cfg['pass'],'charset'=>'utf8mb4'] as $k => $v) {
        $php .= "        " . var_export($k, true) . " => " . var_export($v, true) . ",\n";
      }
      $php .= "    ],\n";
      if ($dbForum !== '' && $dbForum !== $cfg['db']) {
        $php .= "    'mysql_forum' => [\n";
        $php .= "        'host'    => " . var_export($cfg['host'], true) . ",\n";
        $php .= "        'port'    => " . var_export((string)$cfg['port'], true) . ",\n";
        $php .= "        'dbname'  => " . var_export($dbForum, true) . ",\n";
        $php .= "        'user'    => " . var_export($cfg['user'], true) . ",\n";
        $php .= "        'pass'    => " . var_export($cfg['pass'], true) . ",\n";
        $php .= "        'charset' => 'utf8mb4',\n";
        $php .= "    ],\n";
      }
      $php .= "];\n";

      if (file_put_contents($cfgFile, $php) === false) {
        throw new RuntimeException('Não foi possível gravar config/database.php');
      }
      @chmod($cfgFile, 0640);
      $log[] = "[OK] config/database.php atualizado.";

      $nomeJogo = $_SESSION['_install']['nome_jogo'] ?? BRAND_INTERNAL;
      if ($nomeJogo !== '' && strcasecmp($nomeJogo, BRAND_INTERNAL) !== 0) {
        $log[] = "==== Renomeando marca para \"$nomeJogo\" ====";
        renomear_projeto($ROOT, $nomeJogo, $log);
      } else {
        $log[] = "[skip] Nome do jogo mantido como \"" . BRAND_INTERNAL . "\".";
      }

      $sucesso = true;
      install_log('IMPORTACAO OK', count($log) . ' linhas no log de importacao');

    } catch (Throwable $e) {
      $log[] = "[FATAL] " . $e->getMessage();
      install_log('IMPORTACAO FALHOU', $e->getMessage());
    }

    $_SESSION['_install']['log'] = $log;
    $_SESSION['_install']['sucesso'] = $sucesso;
  ?>

  <?php if ($sucesso): ?>
    <div class="ok"><?= t('install_ok') ?></div>
    <div class="info"><?= t('install_remove_folder') ?></div>
    <div class="aviso" style="margin:10px 0"><?= t('install_log_warn') ?></div>
    <form method="post" action="concluir.php">
      <button type="submit" name="apagar" value="1"><?= h(t('btn_finish')) ?></button>
    </form>

    <div class="donation">
      <h3><?= h(t('donation_h')) ?></h3>
      <p><?= h(t('donation_p')) ?></p>
      <div class="wallet">
        <span class="tag">BTC</span>
        <span id="w-btc">18iyP6iuCuXoJUXpJGLsgPYgcKiLTHAfur</span>
        <button type="button" class="copy" onclick="copiar('w-btc',this)"><?= h(t('btn_copy')) ?></button>
      </div>
      <div class="wallet">
        <span class="tag">ETH</span>
        <span id="w-eth">0x9911897abd8a7798bc44300a8421c95e4078e397</span>
        <button type="button" class="copy" onclick="copiar('w-eth',this)"><?= h(t('btn_copy')) ?></button>
      </div>
      <div class="wallet">
        <span class="tag">USDT</span>
        <span id="w-usdt">0x9911897abd8a7798bc44300a8421c95e4078e397</span>
        <button type="button" class="copy" onclick="copiar('w-usdt',this)"><?= h(t('btn_copy')) ?></button>
      </div>
      <p class="muted" style="margin-top:10px"><?= h(t('donation_thanks')) ?></p>
    </div>
    <script>
    var COPIED_LABEL = <?= json_encode(t('btn_copied')) ?>;
    function copiar(id, btn){
      var t = document.getElementById(id).textContent;
      if (navigator.clipboard) navigator.clipboard.writeText(t);
      else { var ta=document.createElement('textarea');ta.value=t;document.body.appendChild(ta);ta.select();document.execCommand('copy');ta.remove(); }
      var o = btn.textContent; btn.textContent = COPIED_LABEL; setTimeout(function(){btn.textContent=o;},1500);
    }
    </script>
  <?php else: ?>
    <div class="erro"><?= sprintf(t('install_fail'), h(url_step(1))) ?></div>
  <?php endif; ?>

  <h2 style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
    <span><?= h(t('log_title')) ?></span>
    <a href="?export=log" class="btn" style="background:#3a5a8a;font-size:12px;padding:6px 12px;margin-top:0"><?= h(t('btn_download_log')) ?></a>
  </h2>
  <div class="log"><?php foreach ($log as $l) echo h($l) . "\n"; ?></div>

  <?php endif; // fim do if (!$confirmado) ?>

<?php endif; ?>

<?php endif; // fim do if ($showWelcome) ... else ?>

<p class="muted" style="margin-top:24px;text-align:center"><?= h(sprintf(t('footer_v'), t('install_title'))) ?></p>
</div>
</body></html>
