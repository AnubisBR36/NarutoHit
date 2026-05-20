<?php
// Habilitar exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
 * ============================================================
 * ROTEADOR DE MÓDULOS STANDALONE
 * ============================================================
 * Padroniza todas as URLs do painel admin no formato:
 *     adm.php?modulo=<nome>
 *
 * Cada chave abaixo redireciona para o arquivo standalone
 * correspondente (que cuida da sua própria autenticação,
 * conexão, header e footer). O include é feito ANTES do
 * pipeline normal de adm.php para evitar duplicação de
 * `session_start`, header HTML, etc.
 *
 * Para adicionar um novo módulo standalone, basta acrescentar
 * uma linha aqui e atualizar os links nos menus para usar
 * `?modulo=<nome>`.
 */
$adm_modulos_standalone = [
    'equipamentos'      => 'gerenciar_equipamentos.php',
    'clas'              => 'gerenciar_clas.php',
    'cristais'          => 'cristal.php',
    'editor_database'   => 'editor_database.php',
    'manutencao'        => 'admin_manutencao.php',
    'tickets'           => 'tickets.php',
    'limpar_itens'      => 'limpar_itens.php',
    'invasao_completa'  => 'gerenciar_invasao.php',
    'personagens'       => 'gerenciar_personagens.php',
    'contatos'          => 'gerenciar_contatos.php',
    'config_jogo'       => 'config_jogo.php',
    'eventos_bonus'     => 'eventos_bonus.php',
    'ranking_pvp'       => 'ranking_pvp.php',
    'despertar_admin'   => 'despertar_admin.php',
    'jutsus'            => 'gerenciar_jutsus.php',
    // Standalones extras (funcionalidades distintas das versões inline):
    'limpar_banco_full' => 'limpar_banco.php',
    'desbloquear_ips'   => 'limpar_ip.php',
    'backup'            => 'backup.php',
];
$_adm_req_modulo = isset($_GET['modulo']) ? (string)$_GET['modulo'] : '';
if ($_adm_req_modulo !== '' && isset($adm_modulos_standalone[$_adm_req_modulo])) {
    $_adm_target = __DIR__ . '/' . $adm_modulos_standalone[$_adm_req_modulo];
    if (is_file($_adm_target)) {
        // Garante que paths relativos (`require 'adm_header.php'` etc.)
        // resolvam corretamente, já que CWD do PHP-built-in pode variar.
        chdir(__DIR__);
        include $_adm_target;
        exit;
    }
}

// Verificar se o arquivo de conexão existe
if (!file_exists('../_inc/conexao.php')) {
    die("Erro: Arquivo de conexão não encontrado em '../_inc/conexao.php'");
}

require_once('../_inc/conexao.php');

// Verificar se a conexão foi estabelecida
if (!isset($conexao)) {
    die("Erro: Conexão com banco de dados não foi estabelecida");
}

// Iniciar sessão se não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
if(!isset($_SESSION['logado']) && !isset($_SESSION['userid']) && !isset($_SESSION['uid'])) {
    echo "<script>window.location.href='../index.php';</script>";
    exit;
}

// Determinar o ID do usuário logado
$user_id = $_SESSION['logado'] ?? $_SESSION['userid'] ?? $_SESSION['uid'];

// Buscar dados do usuário logado
try {
    $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $usuario_logado = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Erro ao buscar dados do usuário: " . $e->getMessage());
}

// Verificar se o usuário é administrador ou moderador
if(!$usuario_logado || ($usuario_logado['adm'] != 1 && $usuario_logado['adm'] != 2)) {
    echo "<div style='background:#1a0a00;border-left:4px solid #ff6600;padding:8px 12px;font-weight:bold;color:#FFD700;'>⛔ Acesso Negado</div>";
    echo "<div style='background:#111;border-left:1px solid #333;border-right:1px solid #333;padding:10px 12px;color:#BBBBBB;'>Você não tem permissão para acessar esta área.</div>";
    echo "<div style='background:#1a0a00;border-left:1px solid #333;border-right:1px solid #333;border-bottom:2px solid #ff6600;height:8px;'></div>";
    exit;
}

$is_admin = ($usuario_logado['adm'] == 1);
$is_mod = ($usuario_logado['adm'] == 2);

// Módulos disponíveis para GM e seus labels
$gm_modulos_disponiveis = [
    'contas'       => '👥 Editar Contas',
    'ban'          => '🔨 Banir/Desbanir Jogadores',
    'noticias'     => '📰 Administrar Notícias',
    'invasao'      => '🔥 Gerenciar Invasões',
    'clas'         => '🏛️ Gerenciar Clãs',
    'manutencao'   => '🔧 Gerenciar Manutenção',
    'equipamentos' => '⚔️ Gerenciar Equipamentos',
    'cristais'     => '💎 Gerenciar Cristais',
    'servidores'   => '🖥️ Gerenciar Servidores',
    'tickets'      => '🎫 Suporte / Tickets',
    'personagens'  => '🥷 Liberar Personagens',
    'backup'       => '💾 Backup Automático',
];
// Criar/migrar tabela de permissões de GM (por usuario_id + modulo)
$gm_perms = [];
try {
    // Verificar estrutura atual de forma portátil (MySQL)
    $cols = Database::tableColumns($conexao, 'gm_permissions');
    $has_uid = in_array('usuario_id', $cols, true);
    if(!$has_uid) {
        // Tabela ausente ou com estrutura antiga — derrubar e recriar
        $conexao->exec("DROP TABLE IF EXISTS gm_permissions");
        $conexao->exec("CREATE TABLE gm_permissions (
            usuario_id INTEGER NOT NULL,
            modulo VARCHAR(60) NOT NULL,
            permitido INTEGER DEFAULT 0,
            PRIMARY KEY (usuario_id, modulo)
        )");
    }
    // Carregar permissões do usuário atual (somente para GM)
    if($is_mod) {
        $stmt_gmp = $conexao->prepare("SELECT modulo, permitido FROM gm_permissions WHERE usuario_id = ?");
        $stmt_gmp->execute([$user_id]);
        foreach($stmt_gmp->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $gm_perms[$row['modulo']] = (bool)$row['permitido'];
        }
    }
} catch(Exception $e) {}

// ── Tabela de logs administrativos ──────────────────────────────────────────
try {
    $pkAL = Database::autoIncPK('id');
    $defaultTs = Database::isMysql() ? 'CURRENT_TIMESTAMP' : '(CURRENT_TIMESTAMP)';
    $conexao->exec("CREATE TABLE IF NOT EXISTS admin_logs (
        $pkAL,
        autor_id INTEGER NOT NULL,
        autor_nome VARCHAR(60) NOT NULL,
        acao VARCHAR(60) NOT NULL,
        alvo_id INTEGER DEFAULT NULL,
        alvo_nome VARCHAR(60) DEFAULT NULL,
        detalhes TEXT DEFAULT NULL,
        data_hora DATETIME DEFAULT $defaultTs
    )");
} catch(Exception $e) {}

// Função para registrar log de ação administrativa
function adm_log($conexao, $autor_id, $autor_nome, $acao, $alvo_id = null, $alvo_nome = null, $detalhes = null) {
    try {
        $st = $conexao->prepare("INSERT INTO admin_logs (autor_id, autor_nome, acao, alvo_id, alvo_nome, detalhes) VALUES (?,?,?,?,?,?)");
        $st->execute([$autor_id, $autor_nome, $acao, $alvo_id, $alvo_nome, $detalhes]);
    } catch(Exception $e) {}
}

// Helper: verifica se o usuário atual pode acessar um módulo
function gm_pode($modulo, $is_admin, $gm_perms) {
    if($is_admin) return true;
    return !empty($gm_perms[$modulo]);
}

// Gerenciar módulos administrativos
$modulo = isset($_GET['modulo']) ? $_GET['modulo'] : 'home';

// Verificar parâmetro p para integração com sistema de notícias
$p = isset($_GET['p']) ? $_GET['p'] : null;

// Processar ações
if(isset($_POST['action'])) {
    if($_POST['action'] == 'ban_user' && gm_pode('ban', $is_admin, $gm_perms) && isset($_POST['user_id']) && isset($_POST['ban_days']) && isset($_POST['ban_motivo'])) {
        $user_id_ban = (int)$_POST['user_id'];
        $ban_days = (int)$_POST['ban_days'];
        $ban_motivo = trim($_POST['ban_motivo']);

        if($ban_days > 0 && !empty($ban_motivo)) {
            try {
                // Usar CURRENT_TIMESTAMP para definir ban_data, ban_duracao e ban_motivo
                $stmt = $conexao->prepare("UPDATE usuarios SET status = 'banido', ban_data = CURRENT_TIMESTAMP, ban_duracao = ?, ban_motivo = ? WHERE id = ?");
                if($stmt->execute([$ban_days, $ban_motivo, $user_id_ban])) {
                    // Registrar no histórico de bans
                    try {
                        $stmt_hist = $conexao->prepare("INSERT INTO ban_historico (usuario_id, motivo, duracao, ban_data, ban_fim) VALUES (?, ?, ?, CURRENT_TIMESTAMP, " . Database::dateOffsetParam('+', 'days') . ")");
                        $stmt_hist->execute([$user_id_ban, $ban_motivo, $ban_days, $ban_days]);
                    } catch (Exception $e) {}
                    $nm_ban = $conexao->prepare("SELECT usuario FROM usuarios WHERE id=?"); $nm_ban->execute([$user_id_ban]); $nm_ban_r = $nm_ban->fetchColumn();
                    adm_log($conexao, $user_id, $usuario_logado['usuario'] ?? '?', 'Ban', $user_id_ban, $nm_ban_r ?: '', "$ban_days dias | Motivo: $ban_motivo");
                    echo "<div style='color: green; margin: 10px 0;'>✅ Usuário banido com sucesso por $ban_days dias!</div>";
                } else {
                    echo "<div style='color: red; margin: 10px 0;'>❌ Erro ao executar o banimento.</div>";
                }
            } catch (Exception $e) {
                echo "<div style='color: red; margin: 10px 0;'>❌ Erro ao banir usuário: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        } else {
            echo "<div style='color: red; margin: 10px 0;'>❌ Dias de ban e motivo são obrigatórios!</div>";
        }
    }

    if($_POST['action'] == 'edit_ban' && gm_pode('ban', $is_admin, $gm_perms) && isset($_POST['user_id']) && isset($_POST['ban_days']) && isset($_POST['ban_motivo'])) {
        $user_id_ban = (int)$_POST['user_id'];
        $ban_days = (int)$_POST['ban_days'];
        $ban_motivo = trim($_POST['ban_motivo']);

        if($ban_days > 0 && !empty($ban_motivo)) {
            try {
                // Atualizar apenas a duração e motivo do ban, mantendo a data original
                $stmt = $conexao->prepare("UPDATE usuarios SET ban_duracao = ?, ban_motivo = ? WHERE id = ? AND status = 'banido'");
                if($stmt->execute([$ban_days, $ban_motivo, $user_id_ban])) {
                    $nm_editban = $conexao->prepare("SELECT usuario FROM usuarios WHERE id=?"); $nm_editban->execute([$user_id_ban]); $nm_editban_r = $nm_editban->fetchColumn();
                    adm_log($conexao, $user_id, $usuario_logado['usuario'] ?? '?', 'Editar Ban', $user_id_ban, $nm_editban_r ?: '', "$ban_days dias | Motivo: $ban_motivo");
                    echo "<div style='color: green; margin: 10px 0;'>✅ Tempo de ban atualizado com sucesso para $ban_days dias!</div>";
                } else {
                    echo "<div style='color: red; margin: 10px 0;'>❌ Erro ao atualizar o banimento.</div>";
                }
            } catch (Exception $e) {
                echo "<div style='color: red; margin: 10px 0;'>❌ Erro ao editar ban: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        } else {
            echo "<div style='color: red; margin: 10px 0;'>❌ Dias de ban e motivo são obrigatórios!</div>";
        }
    }

    if($_POST['action'] == 'unban_user' && gm_pode('ban', $is_admin, $gm_perms) && isset($_POST['user_id'])) {
        $user_id_unban = (int)$_POST['user_id'];

        try {
            // Salvar dados do ban atual antes de limpar (para histórico)
            $stmt_cur = $conexao->prepare("SELECT ban_data, ban_duracao, ban_motivo FROM usuarios WHERE id = ? AND status = 'banido'");
            $stmt_cur->execute([$user_id_unban]);
            $ban_atual = $stmt_cur->fetch(PDO::FETCH_ASSOC);
            if ($ban_atual) {
                try {
                    $stmt_hist = $conexao->prepare("INSERT INTO ban_historico (usuario_id, motivo, duracao, ban_data, ban_fim) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
                    $stmt_hist->execute([$user_id_unban, $ban_atual['ban_motivo'], $ban_atual['ban_duracao'], $ban_atual['ban_data']]);
                } catch (Exception $e) {}
            }
            $stmt = $conexao->prepare("UPDATE usuarios SET status = 'ativo', ban_data = NULL, ban_duracao = NULL, ban_motivo = NULL, ban_aceite_pendente = 1, ban_penalty_ate = NULL WHERE id = ?");
            if($stmt->execute([$user_id_unban])) {
                $nm_unban = $conexao->prepare("SELECT usuario FROM usuarios WHERE id=?"); $nm_unban->execute([$user_id_unban]); $nm_unban_r = $nm_unban->fetchColumn();
                adm_log($conexao, $user_id, $usuario_logado['usuario'] ?? '?', 'Desban', $user_id_unban, $nm_unban_r ?: '', '');
                echo "<div style='color: green; margin: 10px 0;'>✅ Usuário desbanido com sucesso! O jogador precisará aceitar os termos ao logar.</div>";
            } else {
                echo "<div style='color: red; margin: 10px 0;'>❌ Erro ao executar o desbanimento.</div>";
            }
        } catch (Exception $e) {
            echo "<div style='color: red; margin: 10px 0;'>❌ Erro ao desbanir usuário: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }

    if($_POST['action'] == 'purge_audit_log' && $is_admin) {
        $al_dias = max(1, (int)($_POST['al_dias'] ?? 30));
        $al_deletados = 0;
        try {
            $st_purge = $conexao->prepare("DELETE FROM admin_logs WHERE data_hora < " . (Database::isMysql() ? "DATE_SUB(NOW(), INTERVAL ? DAY)" : "datetime('now', ? || ' days')"));
            $st_purge->execute([$al_dias]);
            $al_deletados = $st_purge->rowCount();
            adm_log($conexao, $user_id, $usuario_logado['usuario'] ?? '?', 'Limpar Log Auditoria', null, null, "Registros com mais de {$al_dias} dias removidos ($al_deletados entradas)");
            echo "<div class='al-purge-msg al-purge-ok'>✅ {$al_deletados} registro(s) removido(s) (mais de {$al_dias} dias).</div>";
        } catch(Exception $e) {
            echo "<div class='al-purge-msg al-purge-err'>❌ Erro ao limpar: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }

    if($_POST['action'] == 'save_ban_penalty' && $is_admin) {
        $penalty_minutes = max(1, (int)($_POST['penalty_minutes'] ?? 5));
        $config_content = "<?php\nreturn [\n    'penalty_minutes' => " . $penalty_minutes . "\n];\n";
        if (file_put_contents('../config/ban_penalty.php', $config_content) !== false) {
            echo "<div style='color: green; margin: 10px 0;'>✅ Tempo de penalidade salvo: {$penalty_minutes} minuto(s)!</div>";
        } else {
            echo "<div style='color: red; margin: 10px 0;'>❌ Erro ao salvar configuração.</div>";
        }
    }

    if($_POST['action'] == 'edit_user' && ($is_admin || ($is_mod && gm_pode('contas', $is_admin, $gm_perms))) && isset($_POST['user_id'])) {
        $user_id_edit = (int)$_POST['user_id'];
        $nivel        = max(1, (int)$_POST['nivel']);
        $energiamax   = isset($_POST['energiamax']) ? max(1, (int)$_POST['energiamax']) : ($nivel * 100);
        $energia      = min((int)$_POST['energia'], $energiamax);
        $taijutsu = (int)$_POST['taijutsu'];
        $ninjutsu = (int)$_POST['ninjutsu'];
        $genjutsu = (int)$_POST['genjutsu'];
        $exp = (int)$_POST['exp'];
        $yens = (int)$_POST['yens'];
        $personagem = $_POST['personagem'];
        $vila = (int)$_POST['vila'];
        $vitorias = (int)$_POST['vitorias'];
        $derrotas = (int)$_POST['derrotas'];
        $empates = (int)$_POST['empates'];
        $batalhas = (int)$_POST['batalhas'];
        $missoes_longas = (int)$_POST['missoes_longas'];
        $energia_travada = isset($_POST['energia_travada']) ? 1 : 0;

        // GM não pode editar contas ADM
        $alvo_chk = $conexao->prepare("SELECT adm, usuario FROM usuarios WHERE id = ?");
        $alvo_chk->execute([$user_id_edit]);
        $alvo_row = $alvo_chk->fetch(PDO::FETCH_ASSOC);
        if($is_mod && !$is_admin && isset($alvo_row['adm']) && $alvo_row['adm'] == 1) {
            echo "<div style='color: red; margin: 10px 0;'>❌ GMs não podem editar contas de Administrador.</div>";
        } else {
            // Akatsuki é um status separado (checkbox), não uma vila.
            // O jogador renegado mantém sua vila de origem.
            $renegado = isset($_POST['renegado']) ? 'sim' : 'nao';

            // Detectar se a coluna missoes_longas existe (pode faltar em DBs antigos)
            $has_missoes_col = false;
            try { $conexao->query("SELECT missoes_longas FROM usuarios LIMIT 0"); $has_missoes_col = true; } catch(PDOException $e){}

            if($has_missoes_col){
                $stmt = $conexao->prepare("UPDATE usuarios SET energia = ?, energiamax = ?, taijutsu = ?, ninjutsu = ?, genjutsu = ?, exp = ?, nivel = ?, yens = ?, personagem = ?, vila = ?, vitorias = ?, derrotas = ?, empates = ?, batalhas = ?, missoes_longas = ?, renegado = ?, energia_travada = ? WHERE id = ?");
                $ok = $stmt->execute([$energia, $energiamax, $taijutsu, $ninjutsu, $genjutsu, $exp, $nivel, $yens, $personagem, $vila, $vitorias, $derrotas, $empates, $batalhas, $missoes_longas, $renegado, $energia_travada, $user_id_edit]);
            } else {
                $stmt = $conexao->prepare("UPDATE usuarios SET energia = ?, energiamax = ?, taijutsu = ?, ninjutsu = ?, genjutsu = ?, exp = ?, nivel = ?, yens = ?, personagem = ?, vila = ?, vitorias = ?, derrotas = ?, empates = ?, batalhas = ?, renegado = ?, energia_travada = ? WHERE id = ?");
                $ok = $stmt->execute([$energia, $energiamax, $taijutsu, $ninjutsu, $genjutsu, $exp, $nivel, $yens, $personagem, $vila, $vitorias, $derrotas, $empates, $batalhas, $renegado, $energia_travada, $user_id_edit]);
            }
            if($ok) {
                $edit_user['energia']    = $energia;
                $edit_user['energiamax'] = $energiamax;
                $edit_user['energia_travada'] = $energia_travada;
                echo "<div style='color: green; margin: 10px 0;'>✅ Atributos do usuário editados com sucesso!</div>";
                adm_log($conexao, $user_id, $usuario_logado['usuario'] ?? 'desconhecido', 'Editar Conta', $user_id_edit, $alvo_row['usuario'] ?? '', "Energia=$energia, Nível=$nivel, Yens=$yens");
            }
        }
    }
    
    // Handler para editar VIP
    if($_POST['action'] == 'edit_vip' && $is_admin && isset($_POST['user_id']) && isset($_POST['vip_action'])) {
        $user_id_vip = (int)$_POST['user_id'];
        $vip_action = $_POST['vip_action'];
        
        // Calcular dias com validação
        $duration = $_POST['vip_duration'] ?? '30';
        if($duration == 'custom') {
            $days = (int)($_POST['vip_custom_days'] ?? 30);
        } else {
            $days = (int)$duration;
        }
        
        // Validar dias: mínimo 1, máximo 3650 (10 anos)
        $days = max(1, min(3650, $days));
        
        try {
            if($vip_action == 'remove') {
                // Remover VIP - usar NULL para indicar sem VIP
                $stmt = $conexao->prepare("UPDATE usuarios SET vip = NULL, vip_inicio = NULL WHERE id = ?");
                if($stmt->execute([$user_id_vip])) {
                    echo "<div style='color: green; margin: 10px 0;'>✅ VIP removido com sucesso!</div>";
                }
            } elseif($vip_action == 'set') {
                // Definir VIP para X dias a partir de agora
                $stmt = $conexao->prepare("UPDATE usuarios SET vip_inicio = CURRENT_TIMESTAMP, vip = " . Database::dateOffsetParam('+', 'days') . " WHERE id = ?");
                if($stmt->execute([$days, $user_id_vip])) {
                    echo "<div style='color: green; margin: 10px 0;'>✅ VIP definido para $days dias a partir de agora!</div>";
                }
            } elseif($vip_action == 'add') {
                // Adicionar dias ao VIP existente (ou ativar se expirado)
                // Usar comparação SQL para evitar problemas de timezone
                $stmt = $conexao->prepare("SELECT CASE WHEN vip IS NOT NULL AND vip > CURRENT_TIMESTAMP THEN 1 ELSE 0 END as vip_ativo FROM usuarios WHERE id = ?");
                $stmt->execute([$user_id_vip]);
                $current = $stmt->fetch(PDO::FETCH_ASSOC);
                $vip_ativo = $current['vip_ativo'] ?? 0;
                
                // Se VIP expirado ou NULL, começar de agora
                if($vip_ativo == 0) {
                    $stmt = $conexao->prepare("UPDATE usuarios SET vip_inicio = CURRENT_TIMESTAMP, vip = " . Database::dateOffsetParam('+', 'days') . " WHERE id = ?");
                } else {
                    // Adicionar ao VIP existente (somar dias ao valor atual da coluna vip)
                    $stmt = $conexao->prepare("UPDATE usuarios SET vip = DATE_ADD(vip, INTERVAL ? DAY) WHERE id = ?");
                }
                if($stmt->execute([$days, $user_id_vip])) {
                    echo "<div style='color: green; margin: 10px 0;'>✅ $days dias de VIP adicionados com sucesso!</div>";
                }
            }
        } catch (Exception $e) {
            echo "<div style='color: red; margin: 10px 0;'>❌ Erro ao modificar VIP: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    
    // Handler: liberar/bloquear personagens para um jogador (ADM ou GM com permissão)
    if($_POST['action'] == 'set_personagens'
        && ($is_admin || ($is_mod && gm_pode('personagens', $is_admin, $gm_perms)))
        && isset($_POST['user_id'])) {
        $user_id_pers = (int)$_POST['user_id'];
        require_once __DIR__ . '/../_inc/personagens_catalogo.php';
        try {
            personagens_garantir_linha($conexao, $user_id_pers);
            $catalogo = personagens_catalogo();
            $marcados = isset($_POST['personagens']) && is_array($_POST['personagens']) ? $_POST['personagens'] : [];
            $sets = [];
            $vals = [];
            foreach ($catalogo as $chave => $info) {
                $sets[] = "`{$chave}` = ?";
                $vals[] = in_array($chave, $marcados, true) ? 1 : 0;
            }
            if (!empty($sets)) {
                $vals[] = $user_id_pers;
                $sql = "UPDATE personagens SET " . implode(', ', $sets) . " WHERE usuarioid = ?";
                $stmt = $conexao->prepare($sql);
                if($stmt->execute($vals)) {
                    $nm_p = $conexao->prepare("SELECT usuario FROM usuarios WHERE id=?");
                    $nm_p->execute([$user_id_pers]);
                    $nm_pr = $nm_p->fetchColumn();
                    adm_log($conexao, $user_id, $usuario_logado['usuario'], 'liberar_personagens',
                        $user_id_pers, $nm_pr, count($marcados) . ' liberados de ' . count($catalogo));
                    echo "<div style='color: green; margin: 10px 0;'>✅ Personagens atualizados com sucesso (" . count($marcados) . " liberados).</div>";
                }
            }
        } catch (Exception $e) {
            echo "<div style='color: red; margin: 10px 0;'>❌ Erro ao atualizar personagens: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }

    // Handler para deletar item do inventário
    if($_POST['action'] == 'delete_item' && $is_admin && isset($_POST['user_id']) && isset($_POST['inv_id'])) {
        $user_id_item = (int)$_POST['user_id'];
        $inv_id = (int)$_POST['inv_id'];
        
        try {
            // Verificar se o item pertence ao usuário
            $stmt = $conexao->prepare("DELETE FROM inventario WHERE id = ? AND usuarioid = ?");
            if($stmt->execute([$inv_id, $user_id_item])) {
                echo "<div style='color: green; margin: 10px 0;'>✅ Item removido do inventário com sucesso!</div>";
            }
        } catch (Exception $e) {
            echo "<div style='color: red; margin: 10px 0;'>❌ Erro ao remover item: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    
    // Handler para adicionar fragmento
    if($_POST['action'] == 'add_fragment' && $is_admin && isset($_POST['user_id']) && isset($_POST['item_id'])) {
        $user_id_frag = (int)$_POST['user_id'];
        $frag_item_id = (int)$_POST['item_id'];
        $frag_qty     = max(1, min(99, (int)($_POST['frag_qty'] ?? 1)));
        if($frag_item_id > 0) {
            try {
                $stmt_check = $conexao->prepare("SELECT id, nome FROM table_itens WHERE id = ?");
                $stmt_check->execute([$frag_item_id]);
                $item_exists = $stmt_check->fetch(PDO::FETCH_ASSOC);
                if(!$item_exists) {
                    echo "<div style='color:red;margin:10px 0;'>❌ Item não encontrado!</div>";
                } else {
                    $upsertFrag = Database::isMysql()
                        ? "INSERT INTO fragmentos (usuarioid, itemid, quantidade) VALUES (?, ?, ?)
                           ON DUPLICATE KEY UPDATE quantidade = quantidade + ?"
                        : "INSERT INTO fragmentos (usuarioid, itemid, quantidade) VALUES (?, ?, ?)
                           ON CONFLICT(usuarioid, itemid) DO UPDATE SET quantidade = quantidade + ?";
                    $stmt = $conexao->prepare($upsertFrag);
                    $stmt->execute([$user_id_frag, $frag_item_id, $frag_qty, $frag_qty]);
                    echo "<div style='color:green;margin:10px 0;'>✅ " . $frag_qty . "x fragmento(s) de '" . htmlspecialchars($item_exists['nome']) . "' adicionado(s)!</div>";
                }
            } catch(Exception $e) {
                echo "<div style='color:red;margin:10px 0;'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        } else {
            echo "<div style='color:red;margin:10px 0;'>❌ Selecione um item válido!</div>";
        }
    }
    
    // Handler para mudar cargo (ADM only)
    if($_POST['action'] == 'change_role' && $is_admin && isset($_POST['user_id']) && isset($_POST['novo_cargo'])) {
        $uid_role  = (int)$_POST['user_id'];
        $novo_cargo = (int)$_POST['novo_cargo'];
        if(!in_array($novo_cargo, [0, 1, 2])) $novo_cargo = 0;
        $my_uid = $_SESSION['logado'] ?? $_SESSION['userid'] ?? $_SESSION['uid'];
        if($uid_role == (int)$my_uid) {
            echo "<div style='color:red;margin:10px 0;'>❌ Você não pode alterar seu próprio cargo!</div>";
        } else {
            $stmt_cr = $conexao->prepare("SELECT adm, usuario FROM usuarios WHERE id = ?");
            $stmt_cr->execute([$uid_role]);
            $alvo_cr = $stmt_cr->fetch(PDO::FETCH_ASSOC);
            $pode = true;
            if($alvo_cr && $alvo_cr['adm'] == 1 && $novo_cargo != 1) {
                $total_adm = (int)$conexao->query("SELECT COUNT(*) FROM usuarios WHERE adm = 1")->fetchColumn();
                if($total_adm <= 1) {
                    echo "<div style='color:red;margin:10px 0;'>❌ Não é possível rebaixar o único ADM do sistema!</div>";
                    $pode = false;
                }
            }
            if($pode && $alvo_cr) {
                $conexao->prepare("UPDATE usuarios SET adm = ? WHERE id = ?")->execute([$novo_cargo, $uid_role]);
                $nomes_cargo = [0 => 'Player', 1 => 'ADM', 2 => 'GM'];
                adm_log($conexao, $user_id, $usuario_logado['usuario'] ?? '?', 'Alterar Cargo', $uid_role, $alvo_cr['usuario'], "Cargo anterior: " . $nomes_cargo[$alvo_cr['adm']] . " → Novo: " . $nomes_cargo[$novo_cargo]);
                echo "<div style='color:green;margin:10px 0;'>✅ Cargo de '<strong>" . htmlspecialchars($alvo_cr['usuario']) . "</strong>' alterado para <strong>" . $nomes_cargo[$novo_cargo] . "</strong>!</div>";
                // Atualizar para refletir novo cargo na página
                $edit_user['adm'] = $novo_cargo;
            }
        }
    }

    // Handler para salvar permissões de GM por usuario (ADM only)
    if($_POST['action'] == 'save_gm_perms' && $is_admin) {
        $gm_uid = (int)($_POST['gm_user_id'] ?? 0);
        $modulos_permitidos = $_POST['gm_mod'] ?? [];
        if($gm_uid > 0) {
            try {
                // Zerar todas as permissões deste GM e reinserir
                $conexao->prepare("DELETE FROM gm_permissions WHERE usuario_id = ?")->execute([$gm_uid]);
                foreach(array_keys($gm_modulos_disponiveis) as $mod_key) {
                    $permitido = in_array($mod_key, $modulos_permitidos) ? 1 : 0;
                    $conexao->prepare("INSERT INTO gm_permissions (usuario_id, modulo, permitido) VALUES (?, ?, ?)")->execute([$gm_uid, $mod_key, $permitido]);
                }
                echo "<div style='color:green;margin:10px 0;'>✅ Permissões salvas com sucesso para o GM!</div>";
            } catch(Exception $e) {
                echo "<div style='color:red;margin:10px 0;'>❌ Erro ao salvar permissões: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        } else {
            echo "<div style='color:red;margin:10px 0;'>❌ Selecione um GM para configurar.</div>";
        }
    }

    // Handler para adicionar item ao inventário
    if($_POST['action'] == 'add_item' && $is_admin && isset($_POST['user_id']) && isset($_POST['item_id'])) {
        $user_id_item = (int)$_POST['user_id'];
        $item_id = (int)$_POST['item_id'];
        $upgrade = (int)($_POST['item_upgrade'] ?? 0);
        $qty = (int)($_POST['item_qty'] ?? 1);
        
        // Validar limites: upgrade 0-15, qty 1-99
        $upgrade = max(0, min(15, $upgrade));
        $qty = max(1, min(99, $qty));
        
        if($item_id > 0) {
            try {
                // Verificar se o item existe na tabela de itens
                $stmt_check = $conexao->prepare("SELECT id, nome FROM table_itens WHERE id = ?");
                $stmt_check->execute([$item_id]);
                $item_exists = $stmt_check->fetch(PDO::FETCH_ASSOC);
                
                if(!$item_exists) {
                    echo "<div style='color: red; margin: 10px 0;'>❌ Item não encontrado na base de dados!</div>";
                } else {
                    // Usar transação para garantir consistência
                    $conexao->beginTransaction();
                    
                    $stmt = $conexao->prepare("INSERT INTO inventario (usuarioid, itemid, upgrade, status) VALUES (?, ?, ?, 'off')");
                    $added = 0;
                    for($i = 0; $i < $qty; $i++) {
                        if($stmt->execute([$user_id_item, $item_id, $upgrade])) {
                            $added++;
                        }
                    }
                    
                    $conexao->commit();
                    
                    if($added > 0) {
                        $item_nome = htmlspecialchars($item_exists['nome']);
                        echo "<div style='color: green; margin: 10px 0;'>✅ $added x '$item_nome' adicionado(s) ao inventário com sucesso!</div>";
                    }
                }
            } catch (Exception $e) {
                if($conexao->inTransaction()) {
                    $conexao->rollBack();
                }
                echo "<div style='color: red; margin: 10px 0;'>❌ Erro ao adicionar item: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        } else {
            echo "<div style='color: red; margin: 10px 0;'>❌ Selecione um item válido!</div>";
        }
    }
}

// Buscar usuário para editar se especificado
$edit_user = null;
if(isset($_GET['edit']) && ($is_admin || ($is_mod && gm_pode('contas', $is_admin, $gm_perms)))) {
    $edit_id = (int)$_GET['edit'];
    if($is_admin) {
        $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$edit_id]);
    } else {
        // GM não pode carregar conta ADM
        $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE id = ? AND adm != 1");
        $stmt->execute([$edit_id]);
    }
    $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Definir array de vilas (IDs reais usados em todo o jogo)
// 1=Folha, 2=Areia, 3=Som, 4=Chuva, 5=Nuvem, 6=Névoa, 8=Pedra
// Akatsuki NÃO é uma vila — é um status (renegado='sim') controlado por checkbox separado.
$vilas = [1 => 'Folha', 2 => 'Areia', 3 => 'Som', 4 => 'Chuva', 5 => 'Nuvem', 6 => 'Névoa', 8 => 'Pedra'];

// Buscar lista de usuários
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where_parts = [];
$params = [];
if(!empty($search)) {
    $where_parts[] = "usuario LIKE ?";
    $params[] = "%$search%";
}
// GM não vê contas ADM na lista
if($is_mod && !$is_admin) {
    $where_parts[] = "adm != 1";
}
$where_clause = $where_parts ? "WHERE " . implode(" AND ", $where_parts) : "";

$stmt = $conexao->prepare("SELECT COUNT(*) as total FROM usuarios $where_clause");
$stmt->execute($params);
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_users / $per_page);

$params_list = array_merge($params, [$per_page, $offset]);
$stmt = $conexao->prepare("SELECT * FROM usuarios $where_clause ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->execute($params_list);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Administração - <?php echo nome_servidor(); ?></title>
    <link rel="stylesheet" href="../_css/naruto.css">
    <style>
    body {
        margin: 0; padding: 0;
        background: url('../_img/background.jpg') repeat center top #1a1a1a;
        font-family: Arial, Verdana, sans-serif;
        font-size: 12px;
        color: #BBBBBB;
    }
    
    .admin-container {
        max-width: 100%;
        margin: 0 auto;
    }
    
    .admin-menu {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 6px;
        margin: 10px 0;
    }
    
    .admin-menu a, .admin-menu button {
        display: block;
        text-align: center;
        padding: 10px 15px;
        background: url(../_img/fundo_botao.jpg);
        border: 2px solid #ff6600;
        color: #FFFFFF;
        text-decoration: none;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .admin-menu a:hover, .admin-menu button:hover {
        border-color: #ff8833;
        transform: scale(1.02);
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        background: #2a2a2a;
        padding: 15px;
        border: 1px solid #666;
        margin: 10px 0;
    }
    
    .form-section {
        background: #1a1a1a;
        padding: 10px;
        border: 1px dotted #888;
    }
    
    .form-section h4 {
        color: #ff6600;
        margin-top: 0;
        padding-bottom: 5px;
        border-bottom: 1px solid #666;
    }
    
    .form-section label {
        display: block;
        margin: 8px 0;
        color: #BBBBBB;
    }
    
    .user-avatar {
        display: flex;
        align-items: center;
        background: #1a1a1a;
        padding: 10px;
        border: 1px solid #666;
        margin: 10px 0;
    }
    
    .user-avatar img {
        margin-right: 15px;
        border: 2px solid #ff6600;
    }
    
    .user-avatar-info {
        color: #BBBBBB;
    }
    
    .user-avatar-info strong {
        color: #ff6600;
    }
    
    .alert-success {
        background: #2a4a2a;
        border: 1px dotted #4CAF50;
        color: #4CAF50;
        padding: 10px;
        margin: 10px 0;
    }
    
    .alert-error {
        background: #4a2a2a;
        border: 1px dotted #f44336;
        color: #f44336;
        padding: 10px;
        margin: 10px 0;
    }
    
    .alert-warning {
        background: #4a4a2a;
        border: 1px dotted #FFFF99;
        color: #FFFF99;
        padding: 10px;
        margin: 10px 0;
    }
    
    .table-admin {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
        background: #1a1a1a;
    }
    
    .table-admin th {
        background: #2a2a2a;
        color: #ff6600;
        padding: 10px;
        border: 1px solid #666;
        font-weight: bold;
    }
    
    .table-admin td {
        padding: 8px;
        border: 1px solid #666;
        color: #BBBBBB;
    }
    
    .table-admin tr:hover {
        background: #2C2C2C;
    }
    
    .btn-action {
        display: inline-block;
        padding: 5px 10px;
        margin: 2px;
        background: url(../_img/fundo_botao.jpg);
        border: 1px solid #ff6600;
        color: #FFFFFF;
        text-decoration: none;
        font-size: 11px;
        cursor: pointer;
    }
    
    .btn-action:hover {
        border-color: #ff8833;
    }
    
    .pagination {
        text-align: center;
        margin: 20px 0;
    }
    
    .pagination a {
        display: inline-block;
        padding: 5px 10px;
        margin: 0 2px;
        background: url(../_img/fundo_botao.jpg);
        border: 1px solid #666;
        color: #FFFFFF;
        text-decoration: none;
    }
    
    .pagination a.active {
        border-color: #ff6600;
    }
    
    .pagination a:hover {
        border-color: #ff8833;
    }
    
    .search-form {
        margin: 15px 0;
        text-align: center;
    }
    
    .texto-eterno {
        text-shadow: 
            -1px -1px 0 #000,
            1px -1px 0 #000,
            -1px 1px 0 #000,
            1px 1px 0 #000;
    }
    
    .data-amarela {
        color: #FFD700 !important;
        text-shadow: 
            -1px -1px 0 #000,
            1px -1px 0 #000,
            -1px 1px 0 #000,
            1px 1px 0 #000;
        font-weight: bold;
    }
    
    textarea.sql-editor {
        width: 100%;
        min-height: 150px;
        background: #1a1a1a;
        color: #4CAF50;
        border: 1px solid #666;
        padding: 10px;
        font-family: 'Courier New', monospace;
    }
    .box_top, .box2_top {
        background: #1a0a00 !important;
        border-left: 4px solid #ff6600 !important;
        border-bottom: 1px solid #444 !important;
        height: auto !important; line-height: normal !important;
        padding: 7px 10px 7px 12px !important;
        font-weight: bold;
        color: #FFD700 !important;
        font-size: 13px;
    }
    .box_middle, .box2_middle {
        background: #111111 !important;
        border-left: 1px solid #333 !important;
        border-right: 1px solid #333 !important;
        padding: 10px 12px !important;
    }
    .box_bottom, .box2_bottom {
        background: #1a0a00 !important;
        border-left: 1px solid #333 !important;
        border-right: 1px solid #333 !important;
        border-bottom: 2px solid #ff6600 !important;
        height: 8px !important;
    }
    </style>
</head>
<body>
<div align="center">
<table align="center" cellpadding="0" cellspacing="0" width="760">
    <tr>
        <td width="20" rowspan="6" style="background:url('../_img/border_left.jpg') repeat-y right;">&nbsp;</td>
        <td height="130" valign="bottom" style="background:url('../_img/logo2.jpg') no-repeat center;">&nbsp;</td>
        <td width="20" rowspan="6" style="background:url('../_img/border_right.jpg') repeat-y;">&nbsp;</td>
    </tr>
    <tr>
        <td valign="top" style="background:url('../_img/border_top.jpg') repeat-x top; height:8px;">&nbsp;</td>
    </tr>
    <tr>
        <td valign="top" bgcolor="#444444">
        <div style="background:#1a0a00; border-left:5px solid #ff6600; border-bottom:1px solid #ff6600; height:35px; line-height:35px; padding-left:12px; font-weight:bold; color:#FFD700; font-size:13px;">
            Ferramentas de Administração —
            <?php if($is_admin): ?>
                <span style="color:#FFD700;">Administrador</span>
            <?php else: ?>
                <span style="color:#87CEFA;">Moderador</span>
            <?php endif; ?>
            <span style="float:right; margin-right:10px;">
                <a href="../?p=home" style="color:#FFD700; font-size:11px; text-decoration:none; background:rgba(0,0,0,0.4); padding:3px 8px; border:1px solid #555;">Voltar ao Site</a>
            </span>
        </div>
        <div style="background:url('../_img/menu.jpg') repeat-x; padding:4px 8px; border-bottom:1px solid #ff6600;">
            <a href="adm.php" style="color:#FFD700; text-decoration:none; font-size:11px; padding:2px 6px; border:1px solid #555; background:rgba(0,0,0,0.3);">Painel</a>
        </div>
        <div class="admin-container" style="padding: 6px;">

<?php if(isset($_GET['debug'])): ?>
<div class="aviso">
    <strong>Debug:</strong>
    Session logado: <?php echo isset($_SESSION['logado']) ? $_SESSION['logado'] : 'não definido'; ?> |
    User ID: <?php echo $user_id; ?> |
    ADM Level: <?php echo $usuario_logado['adm'] ?? 'não definido'; ?>
</div>
<?php endif; ?>

<div style="padding: 0 4px;">

    <?php if($p == 'admin_noticias' && gm_pode('noticias', $is_admin, $gm_perms)): ?>
        <!-- Administração de Notícias -->
        <fieldset>
            <legend>📰 Administração de Notícias</legend>
            <div style="margin-bottom: 8px;">
            
            <?php
            require_once('../noticia/model/NoticiaRepository.php');
            require_once('../noticia/controllers/AdminController.php');
            
            $repository = new NoticiaRepository($conexao);
            $controller = new AdminNoticiasController($repository, $usuario_logado);
            
            $acao = $_GET['acao'] ?? 'listar';
            $page_noticia = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $id_noticia = isset($_GET['id']) ? (int)$_GET['id'] : null;
            
            switch($acao) {
                case 'nova':
                    $controller->exibirFormulario();
                    break;
                case 'editar':
                    $controller->exibirFormulario($id_noticia);
                    break;
                case 'salvar':
                    $controller->salvar();
                    break;
                case 'deletar':
                    $controller->deletar();
                    break;
                default:
                    $controller->listar($page_noticia);
            }
            ?>
        </fieldset>
    
    <?php elseif($edit_user && ($is_admin || ($is_mod && gm_pode('contas', $is_admin, $gm_perms)))): ?>
        <?php
        // Buscar itens do inventário do jogador (somente ADM usa, mas carregar mesmo assim)
        $user_items = [];
        $stmt_inv = $conexao->prepare("SELECT i.*, t.nome, t.imagem, t.categoria, t.taijutsu, t.ninjutsu, t.genjutsu 
                                       FROM inventario i 
                                       LEFT JOIN table_itens t ON i.itemid = t.id 
                                       WHERE i.usuarioid = ? 
                                       ORDER BY t.categoria, t.nome");
        $stmt_inv->execute([$edit_user['id']]);
        $user_items = $stmt_inv->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar todos os itens disponíveis para adicionar
        $stmt_all_items = $conexao->prepare("SELECT * FROM table_itens ORDER BY categoria, nome");
        $stmt_all_items->execute();
        $all_items = $stmt_all_items->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular status VIP (VIP é válido se não for NULL e for no futuro)
        $vip_ativo = (!empty($edit_user['vip']) && $edit_user['vip'] > date('Y-m-d H:i:s'));
        $vip_data = $edit_user['vip'];
        ?>
        
        <!-- Estilos específicos para abas -->
        <style>
        .edit-tabs {
            display: flex;
            border-bottom: 2px solid #ff6600;
            margin-bottom: 0;
        }
        .edit-tab {
            padding: 12px 25px;
            background: #1a1a1a;
            border: 2px solid #666;
            border-bottom: none;
            color: #BBBBBB;
            cursor: pointer;
            margin-right: 5px;
            border-radius: 8px 8px 0 0;
            font-weight: bold;
            transition: all 0.3s;
        }
        .edit-tab:hover {
            background: #2a2a2a;
            color: #ff8833;
        }
        .edit-tab.active {
            background: #2a2a2a;
            border-color: #ff6600;
            color: #ff6600;
        }
        .tab-content {
            display: none;
            background: #2a2a2a;
            border: 2px solid #ff6600;
            border-top: none;
            padding: 20px;
        }
        .tab-content.active {
            display: block;
        }
        .vip-status {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .vip-active {
            background: linear-gradient(135deg, #2a4a2a, #1a3a1a);
            border: 2px solid #4CAF50;
        }
        .vip-inactive {
            background: linear-gradient(135deg, #4a2a2a, #3a1a1a);
            border: 2px solid #f44336;
        }
        .item-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        .item-card {
            background: #1a1a1a;
            border: 1px solid #666;
            border-radius: 5px;
            padding: 10px;
            text-align: center;
            position: relative;
        }
        .item-card img {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }
        .item-card .item-name {
            font-size: 11px;
            color: #BBBBBB;
            margin-top: 5px;
        }
        .item-card .item-stats {
            font-size: 10px;
            color: #888;
        }
        .item-card .item-status {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 3px;
            margin-top: 5px;
            display: inline-block;
        }
        .item-card .equipped {
            background: #4CAF50;
            color: white;
        }
        .item-card .not-equipped {
            background: #666;
            color: #ccc;
        }
        .item-card .delete-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #e74c3c;
            color: white;
            border: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 12px;
            line-height: 1;
        }
        .item-card .delete-btn:hover {
            background: #c0392b;
        }
        .add-item-section {
            background: #1a1a1a;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .add-item-section h4 {
            color: #ff6600;
            margin-top: 0;
        }
        </style>
        
        <!-- Formulário de Edição com Abas -->
        <fieldset>
            <legend>📝 Editando: <?php echo htmlspecialchars($edit_user['usuario']); ?></legend>

            <div class="user-avatar">
                <?php
                $avatar_path = '../_img/personagens/no_avatar.jpg';
                if($edit_user['personagem'] && $edit_user['avatar']) {
                    $avatar_path = "../_img/personagens/" . $edit_user['personagem'] . "/" . $edit_user['avatar'] . ".jpg";
                }
                ?>
                <img src="<?php echo $avatar_path; ?>" style="width: 64px; height: 64px;">
                <div class="user-avatar-info">
                    <strong>Usuário:</strong> <?php echo htmlspecialchars($edit_user['usuario']); ?><br>
                    <strong>Vila:</strong> 
                    <?php 
                    $vilas = [1 => 'Folha', 2 => 'Areia', 3 => 'Som', 4 => 'Chuva', 5 => 'Nuvem', 6 => 'Névoa', 8 => 'Pedra'];
                    $vila_nome = $vilas[$edit_user['vila']] ?? 'Desconhecida';
                    if(($edit_user['renegado'] ?? 'nao') == 'sim') $vila_nome .= ' <span style="color:#ff6347">(Akatsuki)</span>';
                    echo $vila_nome;
                    ?><br>
                    <strong>Status:</strong> <?php echo ucfirst($edit_user['status']); ?>
                    <?php if($vip_ativo): ?>
                        <span style="color: #FFD700; font-weight: bold;"> | VIP ATIVO</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Abas -->
            <div class="edit-tabs">
                <div class="edit-tab active" onclick="showTab('atributos')">⚡ Atributos</div>
                <?php if($is_admin): ?>
                <div class="edit-tab" onclick="showTab('vip')">👑 VIP</div>
                <div class="edit-tab" onclick="showTab('itens')">🎒 Itens</div>
                <div class="edit-tab" onclick="showTab('personagens')">🥷 Personagens</div>
                <div class="edit-tab" onclick="showTab('cargo')">🛡️ Cargo</div>
                <?php else: ?>
                <?php if(gm_pode('personagens', $is_admin, $gm_perms)): ?>
                <div class="edit-tab" onclick="showTab('personagens')">🥷 Personagens</div>
                <?php endif; ?>
                <div class="edit-tab" onclick="showTab('cargo')">🛡️ Cargo (visualizar)</div>
                <?php endif; ?>
            </div>
            <?php if($is_mod && !$is_admin): ?>
            <div style="background:#1a1a30;border:1px solid #87CEFA;color:#87CEFA;padding:8px 12px;font-size:11px;margin:6px 0;">
                🛡️ <strong>Modo GM:</strong> Você pode editar atributos. VIP e itens são exclusivos para Administradores. Não é possível alterar cargos.
            </div>
            <?php endif; ?>

            <!-- Conteúdo da Aba Atributos -->
            <div id="tab-atributos" class="tab-content active">
                <form method="POST" class="form-grid">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">

                    <div class="form-section">
                        <h4>⚡ Atributos de Combate</h4>
                        <label style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            Energia máx:
                            <input type="number" name="energiamax" id="adm_energiamax" value="<?php echo (int)$edit_user['energiamax']; ?>" min="1" max="999999" class="input" style="width:90px;">
                            <button type="button" onclick="document.getElementById('adm_energiamax').value=parseInt(document.getElementById('adm_nivel_field').value||1)*100;document.getElementById('adm_energia').max=document.getElementById('adm_energiamax').value;" style="font-size:11px;padding:2px 8px;cursor:pointer;background:#444;color:#adf;border:1px solid #558;border-radius:3px;" title="Calcular automaticamente: nível × 100">Auto (nv×100)</button>
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            Energia:
                            <input type="number" name="energia" id="adm_energia" value="<?php echo $edit_user['energia']; ?>" min="0" max="<?php echo (int)$edit_user['energiamax']; ?>" class="input" style="width:90px;">
                            <button type="button" onclick="document.getElementById('adm_energia').value=0;" style="font-size:11px;padding:2px 8px;cursor:pointer;background:#333;color:#fff;border:1px solid #666;border-radius:3px;">Min</button>
                            <button type="button" onclick="document.getElementById('adm_energia').value=document.getElementById('adm_energiamax').value;" style="font-size:11px;padding:2px 8px;cursor:pointer;background:#333;color:#fff;border:1px solid #666;border-radius:3px;">Max</button>
                            <label style="display:flex;align-items:center;gap:4px;margin:0;cursor:pointer;" title="Quando travada, a energia não cai ao ser atacado">
                                <input type="checkbox" name="energia_travada" value="1" <?php echo (!empty($edit_user['energia_travada']) && $edit_user['energia_travada']==1) ? 'checked' : ''; ?> style="width:auto;">
                                <?php if(!empty($edit_user['energia_travada']) && $edit_user['energia_travada']==1): ?>
                                    <span style="color:#2ecc71;font-weight:bold;font-size:12px;">🔒 Travada</span>
                                <?php else: ?>
                                    <span style="color:#888;font-size:12px;">🔓 Travar</span>
                                <?php endif; ?>
                            </label>
                        </label>
                        <label>Taijutsu: <input type="number" name="taijutsu" value="<?php echo $edit_user['taijutsu']; ?>" min="1" max="99999" class="input"></label>
                        <label>Ninjutsu: <input type="number" name="ninjutsu" value="<?php echo $edit_user['ninjutsu']; ?>" min="1" max="99999" class="input"></label>
                        <label>Genjutsu: <input type="number" name="genjutsu" value="<?php echo $edit_user['genjutsu']; ?>" min="1" max="99999" class="input"></label>
                    </div>

                    <div class="form-section">
                        <h4>📊 Progressão</h4>
                        <label>Nível: <input type="number" name="nivel" id="adm_nivel_field" value="<?php echo $edit_user['nivel']; ?>" min="1" max="999" class="input"></label>
                        <label>Experiência: <input type="number" name="exp" value="<?php echo $edit_user['exp']; ?>" min="0" class="input"></label>
                        <label>Yens: <input type="number" name="yens" value="<?php echo $edit_user['yens']; ?>" min="0" class="input"></label>
                    </div>

                    <div class="form-section">
                        <h4>👤 Personagem & Vila</h4>
                        <label>Personagem: 
                            <select name="personagem">
                                <option value="naruto" <?php echo $edit_user['personagem'] == 'naruto' ? 'selected' : ''; ?>>Naruto</option>
                                <option value="sasuke" <?php echo $edit_user['personagem'] == 'sasuke' ? 'selected' : ''; ?>>Sasuke</option>
                                <option value="sakura" <?php echo $edit_user['personagem'] == 'sakura' ? 'selected' : ''; ?>>Sakura</option>
                                <option value="kakashi" <?php echo $edit_user['personagem'] == 'kakashi' ? 'selected' : ''; ?>>Kakashi</option>
                            </select>
                        </label><br>

                        <label>Vila: 
                            <select name="vila">
                                <?php foreach($vilas as $id => $nome): ?>
                                    <option value="<?php echo $id; ?>" <?php echo $edit_user['vila'] == $id ? 'selected' : ''; ?>><?php echo $nome; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label><br>

                        <label title="Marca o jogador como renegado da Akatsuki (mantém a vila de origem).">
                            <input type="checkbox" name="renegado" value="sim" <?php echo (($edit_user['renegado'] ?? 'nao') == 'sim') ? 'checked' : ''; ?> />
                            Akatsuki (Renegado)
                        </label><br>
                    </div>

                    <div class="form-section">
                        <h4>🏆 Estatísticas</h4>
                        <label>Vitórias: <input type="number" name="vitorias" value="<?php echo $edit_user['vitorias']; ?>" min="0" class="input"></label>
                        <label>Derrotas: <input type="number" name="derrotas" value="<?php echo $edit_user['derrotas']; ?>" min="0" class="input"></label>
                        <label>Empates: <input type="number" name="empates" value="<?php echo $edit_user['empates']; ?>" min="0" class="input"></label>
                        <label>Batalhas totais: <input type="number" name="batalhas" value="<?php echo $edit_user['batalhas'] ?? 0; ?>" min="0" class="input"></label>
                        <label>Missões longas (≥10h): <input type="number" name="missoes_longas" value="<?php echo $edit_user['missoes_longas'] ?? 0; ?>" min="0" class="input"></label>
                    </div>

                    <div style="grid-column: 1 / -1; text-align: center;">
                        <button type="submit" class="botao">💾 Salvar Atributos</button>
                    </div>
                </form>
            </div>

            <!-- Conteúdo da Aba VIP -->
            <div id="tab-vip" class="tab-content">
                <div class="vip-status <?php echo $vip_ativo ? 'vip-active' : 'vip-inactive'; ?>">
                    <?php if($vip_ativo): ?>
                        <h3 style="color: #FFD700; margin: 0;">👑 VIP ATIVO</h3>
                        <p style="color: #4CAF50; margin: 10px 0 0 0;">
                            Expira em: <strong><?php echo date('d/m/Y H:i', strtotime($vip_data)); ?></strong>
                        </p>
                    <?php else: ?>
                        <h3 style="color: #f44336; margin: 0;">❌ VIP INATIVO</h3>
                        <p style="color: #888; margin: 10px 0 0 0;">Este jogador não possui VIP ativo.</p>
                    <?php endif; ?>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="edit_vip">
                    <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                    
                    <div class="form-section" style="margin-bottom: 15px;">
                        <h4>⏱️ Adicionar/Modificar VIP</h4>
                        
                        <label style="display: block; margin: 10px 0;">
                            <strong>Duração:</strong>
                            <select name="vip_duration" class="input" style="margin-left: 10px;">
                                <option value="7">7 dias</option>
                                <option value="15">15 dias</option>
                                <option value="30" selected>30 dias (1 mês)</option>
                                <option value="90">90 dias (3 meses)</option>
                                <option value="180">180 dias (6 meses)</option>
                                <option value="365">365 dias (1 ano)</option>
                                <option value="custom">Personalizado...</option>
                            </select>
                        </label>
                        
                        <label id="custom-days-label" style="display: none; margin: 10px 0;">
                            <strong>Dias personalizados:</strong>
                            <input type="number" name="vip_custom_days" min="1" max="3650" value="30" class="input" style="width: 100px; margin-left: 10px;">
                            <span style="color: #888; font-size: 10px;">(máx. 10 anos)</span>
                        </label>
                        
                        <div style="margin-top: 15px;">
                            <button type="submit" name="vip_action" value="add" class="botao" style="background: #4CAF50;">➕ Adicionar VIP</button>
                            <button type="submit" name="vip_action" value="set" class="botao" style="background: #2196F3; margin-left: 10px;">📅 Definir VIP</button>
                            <?php if($vip_ativo): ?>
                                <button type="submit" name="vip_action" value="remove" class="botao" style="background: #f44336; margin-left: 10px;">🗑️ Remover VIP</button>
                            <?php endif; ?>
                        </div>
                        
                        <p style="color: #888; font-size: 11px; margin-top: 10px;">
                            <strong>Adicionar:</strong> Soma dias ao VIP atual (ou ativa se expirado)<br>
                            <strong>Definir:</strong> Define o VIP para expirar daqui a X dias<br>
                            <strong>Remover:</strong> Remove o VIP imediatamente
                        </p>
                    </div>
                </form>
            </div>

            <!-- Conteúdo da Aba Itens -->
            <div id="tab-itens" class="tab-content">
                <h4 style="color: #ff6600; margin-top: 0;">🎒 Inventário do Jogador (<?php echo count($user_items); ?> itens)</h4>
                
                <?php if(empty($user_items)): ?>
                    <p style="color: #888; text-align: center; padding: 20px;">Este jogador não possui itens no inventário.</p>
                <?php else: ?>
                    <div class="item-grid">
                        <?php foreach($user_items as $item): ?>
                            <div class="item-card">
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja remover este item?');">
                                    <input type="hidden" name="action" value="delete_item">
                                    <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                                    <input type="hidden" name="inv_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="delete-btn">✕</button>
                                </form>
                                <img src="../_img/equipamentos/<?php echo htmlspecialchars($item['imagem'] ?? 'default'); ?>.png" 
                                     onerror="this.src='../_img/equipamentos/default.png'">
                                <div class="item-name"><?php echo htmlspecialchars($item['nome'] ?? 'Item #' . $item['itemid']); ?></div>
                                <div class="item-stats">
                                    <?php 
                                    $stats = [];
                                    if($item['taijutsu'] > 0) $stats[] = "T+" . $item['taijutsu'];
                                    if($item['ninjutsu'] > 0) $stats[] = "N+" . $item['ninjutsu'];
                                    if($item['genjutsu'] > 0) $stats[] = "G+" . $item['genjutsu'];
                                    echo implode(' ', $stats) ?: 'Sem bônus';
                                    ?>
                                    <?php if($item['upgrade'] > 0): ?>
                                        <span style="color: #FFD700;">+<?php echo $item['upgrade']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="item-status <?php echo $item['status'] == 'on' ? 'equipped' : 'not-equipped'; ?>">
                                    <?php echo $item['status'] == 'on' ? 'Equipado' : 'No inventário'; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Seção para adicionar itens -->
                <div class="add-item-section">
                    <h4>➕ Adicionar Item ao Inventário</h4>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_item">
                        <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                        
                        <label style="display: block; margin: 10px 0;">
                            <strong>Selecionar Item:</strong>
                            <select name="item_id" class="input" style="width: 100%; max-width: 400px; margin-top: 5px;">
                                <option value="">-- Selecione um item --</option>
                                <?php 
                                $current_cat = '';
                                foreach($all_items as $item): 
                                    if($item['categoria'] != $current_cat) {
                                        if($current_cat != '') echo '</optgroup>';
                                        $current_cat = $item['categoria'];
                                        echo '<optgroup label="' . ucfirst($current_cat) . '">';
                                    }
                                ?>
                                    <option value="<?php echo $item['id']; ?>">
                                        <?php echo htmlspecialchars($item['nome']); ?> 
                                        (T:<?php echo $item['taijutsu']; ?> N:<?php echo $item['ninjutsu']; ?> G:<?php echo $item['genjutsu']; ?>)
                                    </option>
                                <?php endforeach; ?>
                                <?php if($current_cat != '') echo '</optgroup>'; ?>
                            </select>
                        </label>
                        
                        <label style="display: inline-block; margin: 10px 10px 10px 0;">
                            <strong>Upgrade:</strong>
                            <input type="number" name="item_upgrade" min="0" max="15" value="0" class="input" style="width: 60px;">
                        </label>
                        
                        <label style="display: inline-block; margin: 10px 0;">
                            <strong>Quantidade:</strong>
                            <input type="number" name="item_qty" min="1" max="99" value="1" class="input" style="width: 60px;">
                        </label>
                        
                        <div style="margin-top: 15px;">
                            <button type="submit" class="botao">➕ Adicionar Item</button>
                        </div>
                    </form>
                </div>
                
                <!-- Seção para adicionar fragmentos -->
                <div class="add-item-section" style="margin-top:15px;border-top:1px solid #444;padding-top:15px;">
                    <h4>🧩 Adicionar Fragmentos ao Jogador</h4>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_fragment">
                        <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                        <label style="display: block; margin: 10px 0;">
                            <strong>Selecionar Item (fragmento):</strong>
                            <select name="item_id" class="input" style="width: 100%; max-width: 400px; margin-top: 5px;">
                                <option value="">-- Selecione um item --</option>
                                <?php 
                                $current_cat2 = '';
                                foreach($all_items as $item): 
                                    if($item['categoria'] != $current_cat2) {
                                        if($current_cat2 != '') echo '</optgroup>';
                                        $current_cat2 = $item['categoria'];
                                        echo '<optgroup label="' . ucfirst($current_cat2) . '">';
                                    }
                                ?>
                                    <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['nome']); ?></option>
                                <?php endforeach; ?>
                                <?php if($current_cat2 != '') echo '</optgroup>'; ?>
                            </select>
                        </label>
                        <label style="display: inline-block; margin: 10px 0;">
                            <strong>Quantidade:</strong>
                            <input type="number" name="frag_qty" min="1" max="99" value="1" class="input" style="width: 60px;">
                        </label>
                        <div style="margin-top: 10px;">
                            <button type="submit" class="botao">🧩 Adicionar Fragmentos</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Conteúdo da Aba Personagens -->
            <?php
            $pode_pers = ($is_admin || ($is_mod && gm_pode('personagens', $is_admin, $gm_perms)));
            if ($pode_pers):
                require_once __DIR__ . '/../_inc/personagens_catalogo.php';
                personagens_garantir_linha($conexao, (int)$edit_user['id']);
                $pers_atual = personagens_carregar($conexao, (int)$edit_user['id']);
                $pers_catalogo = personagens_catalogo();
                $pers_eh_vip = personagem_jogador_eh_vip($edit_user);
                $pers_nivel = (int)($edit_user['nivel'] ?? 1);
            ?>
            <div id="tab-personagens" class="tab-content">
                <div style="background:#1a1a1a;border:1px solid #444;border-radius:6px;padding:14px;margin-bottom:14px;">
                    <h4 style="color:#FFD700;margin:0 0 8px 0;">🥷 Liberar Personagens para <?php echo htmlspecialchars($edit_user['usuario']); ?></h4>
                    <p style="color:#aaa;font-size:12px;margin:0;">
                        Marque os personagens que devem ficar <strong style="color:#2ecc71;">desbloqueados</strong> para este jogador.
                        Útil para suporte, eventos e premiações. Personagens iniciais (Naruto, Sasuke, Sakura, Kakashi) já estão sempre disponíveis.<br>
                        <span style="color:#FFD700;">★</span> = exige VIP no jogo (ADM ignora ao liberar manualmente).
                        Nível atual do jogador: <strong style="color:#87CEFA;"><?php echo $pers_nivel; ?></strong>
                        — VIP: <strong style="color:<?php echo $pers_eh_vip ? '#FFD700' : '#888'; ?>;"><?php echo $pers_eh_vip ? 'Ativo' : 'Inativo'; ?></strong>.
                    </p>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="set_personagens">
                    <input type="hidden" name="user_id" value="<?php echo (int)$edit_user['id']; ?>">

                    <div style="margin-bottom:10px;">
                        <button type="button" class="botao" style="background:#2ecc71;" onclick="document.querySelectorAll('#tab-personagens input.pers-chk').forEach(c=>c.checked=true);">✅ Marcar todos</button>
                        <button type="button" class="botao" style="background:#888;margin-left:6px;" onclick="document.querySelectorAll('#tab-personagens input.pers-chk').forEach(c=>c.checked=false);">⬜ Desmarcar todos</button>
                        <button type="button" class="botao" style="background:#3498db;margin-left:6px;" onclick="document.querySelectorAll('#tab-personagens input.pers-chk').forEach(c=>{var n=parseInt(c.dataset.nivel||'1',10);c.checked=(<?php echo $pers_nivel; ?>>=n);});">📈 Liberar até nível atual (<?php echo $pers_nivel; ?>)</button>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;">
                        <?php foreach($pers_catalogo as $chave => $info):
                            $val = (int)($pers_atual[$chave] ?? 0);
                            $marcado = ($val == 1);
                            $img = "../_img/personagens/unlock_" . $chave . ".jpg";
                            $img_alt = "../_img/personagens/reg_" . $chave . ".jpg";
                        ?>
                            <label style="display:block;background:<?php echo $marcado ? '#1a3a1a' : '#1a1a1a'; ?>;border:2px solid <?php echo $marcado ? '#2ecc71' : '#444'; ?>;border-radius:6px;padding:8px;cursor:pointer;text-align:center;transition:all 0.15s;" onclick="setTimeout(()=>{this.style.background=this.querySelector('input').checked?'#1a3a1a':'#1a1a1a';this.style.borderColor=this.querySelector('input').checked?'#2ecc71':'#444';},0);">
                                <div style="position:relative;display:inline-block;">
                                    <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($info['nome']); ?>"
                                         style="width:60px;height:60px;object-fit:cover;border-radius:4px;<?php echo $marcado ? '' : 'filter:grayscale(70%) brightness(0.6);'; ?>"
                                         onerror="this.onerror=null;this.src='<?php echo $img_alt; ?>';">
                                    <?php if(!empty($info['vip'])): ?>
                                        <span style="position:absolute;top:-4px;right:-4px;background:#FFD700;color:#000;font-size:10px;font-weight:bold;padding:1px 4px;border-radius:8px;">★</span>
                                    <?php endif; ?>
                                </div>
                                <div style="margin-top:6px;font-size:12px;color:#fff;font-weight:bold;"><?php echo htmlspecialchars($info['nome']); ?></div>
                                <div style="font-size:10px;color:#888;">Nv. <?php echo (int)$info['nivel']; ?></div>
                                <div style="margin-top:4px;">
                                    <input type="checkbox" class="pers-chk" name="personagens[]" value="<?php echo htmlspecialchars($chave); ?>"
                                           data-nivel="<?php echo (int)$info['nivel']; ?>"
                                           <?php echo $marcado ? 'checked' : ''; ?> style="width:auto;cursor:pointer;">
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div style="margin-top:16px;text-align:center;">
                        <button type="submit" class="botao" style="background:#FFD700;color:#000;font-weight:bold;" onclick="return confirm('Salvar lista de personagens liberados para este jogador?');">💾 Salvar Personagens Liberados</button>
                    </div>
                </form>
            </div>
            <?php endif; // pode_pers ?>

            <div style="margin-top: 20px;">

            <!-- Conteúdo da Aba Cargo -->
            <div id="tab-cargo" class="tab-content">
                <?php
                $cargo_atual = (int)($edit_user['adm'] ?? 0);
                $nomes_cargo_display = [0 => '👤 Player', 1 => '👑 ADM', 2 => '🛡️ GM'];
                $cores_cargo = [0 => '#888', 1 => '#FFD700', 2 => '#87CEFA'];
                $my_uid_check = (int)($_SESSION['logado'] ?? $_SESSION['userid'] ?? $_SESSION['uid']);
                ?>
                <div style="background:#1a1a1a;border:1px solid #444;border-radius:6px;padding:16px;margin-bottom:16px;text-align:center;">
                    <div style="font-size:13px;color:#aaa;margin-bottom:6px;">Cargo atual de <strong style="color:#FFD700;"><?php echo htmlspecialchars($edit_user['usuario']); ?></strong>:</div>
                    <div style="font-size:22px;font-weight:bold;color:<?php echo $cores_cargo[$cargo_atual]; ?>;">
                        <?php echo $nomes_cargo_display[$cargo_atual]; ?>
                    </div>
                </div>

                <?php if(!$is_admin): ?>
                    <div style="color:#87CEFA;background:#0a0a2a;border:1px solid #87CEFA;padding:10px;border-radius:4px;text-align:center;">
                        🛡️ GMs não podem alterar cargos. Apenas Administradores têm esta permissão.
                    </div>
                <?php elseif($edit_user['id'] == $my_uid_check): ?>
                    <div style="color:#f39c12;background:#2a2200;border:1px solid #f39c12;padding:10px;border-radius:4px;text-align:center;">
                        ⚠️ Você não pode alterar seu próprio cargo.
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="change_role">
                        <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                        <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center;margin-bottom:16px;">
                            <label style="display:flex;align-items:center;gap:8px;background:#1a1a1a;border:2px solid <?php echo $cargo_atual==0?'#ff6600':'#444'; ?>;border-radius:8px;padding:14px 22px;cursor:pointer;transition:border-color 0.2s;">
                                <input type="radio" name="novo_cargo" value="0" <?php echo $cargo_atual==0?'checked':''; ?> style="width:auto;">
                                <span style="font-size:15px;color:#888;">👤 Player</span>
                            </label>
                            <label style="display:flex;align-items:center;gap:8px;background:#1a1a1a;border:2px solid <?php echo $cargo_atual==2?'#ff6600':'#444'; ?>;border-radius:8px;padding:14px 22px;cursor:pointer;transition:border-color 0.2s;">
                                <input type="radio" name="novo_cargo" value="2" <?php echo $cargo_atual==2?'checked':''; ?> style="width:auto;">
                                <span style="font-size:15px;color:#87CEFA;">🛡️ GM</span>
                            </label>
                            <label style="display:flex;align-items:center;gap:8px;background:#1a1a1a;border:2px solid <?php echo $cargo_atual==1?'#ff6600':'#444'; ?>;border-radius:8px;padding:14px 22px;cursor:pointer;transition:border-color 0.2s;">
                                <input type="radio" name="novo_cargo" value="1" <?php echo $cargo_atual==1?'checked':''; ?> style="width:auto;">
                                <span style="font-size:15px;color:#FFD700;">👑 ADM</span>
                            </label>
                        </div>
                        <div style="text-align:center;">
                            <button type="submit" class="botao" onclick="return confirm('Tem certeza que deseja alterar o cargo deste usuário?');">🛡️ Salvar Cargo</button>
                        </div>
                        <p style="color:#888;font-size:11px;text-align:center;margin-top:10px;">⚠️ Somente ADMs podem alterar cargos. Não é possível rebaixar o único ADM do sistema.</p>
                    </form>
                <?php endif; ?>
            </div>

        </fieldset>
        
        <script>
        function showTab(tabName) {
            // Remove active de todas as abas
            document.querySelectorAll('.edit-tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Adiciona active na aba selecionada
            event.target.classList.add('active');
            document.getElementById('tab-' + tabName).classList.add('active');
        }
        
        // Mostrar campo de dias personalizados quando selecionado
        document.querySelector('select[name="vip_duration"]').addEventListener('change', function() {
            document.getElementById('custom-days-label').style.display = this.value === 'custom' ? 'block' : 'none';
        });
        </script>
    <?php else: ?>

        <?php if($modulo == 'home'): ?>
            <!-- Dashboard de Estatísticas -->
            <?php if($is_admin): ?>
            <?php
            // Coletar estatísticas com segurança
            $stat_total  = 0; $stat_online = 0; $stat_cacando = 0;
            $stat_missao = 0; $stat_treino = 0; $stat_yens = 0;
            $stat_gms    = 0; $stat_eventos = 0; $stat_novos = 0;
            try { $stat_total  = (int)$conexao->query("SELECT COUNT(*) FROM usuarios")->fetchColumn(); } catch(Exception $e) {}
            try {
                $onl_sql = Database::isMysql()
                    ? "SELECT COUNT(*) FROM usuarios WHERE timestamp >= (UNIX_TIMESTAMP() - 300)"
                    : "SELECT COUNT(*) FROM usuarios WHERE timestamp >= (strftime('%s','now') - 300)";
                $stat_online = (int)$conexao->query($onl_sql)->fetchColumn();
            } catch(Exception $e) {}
            try { $stat_cacando = (int)$conexao->query("SELECT COUNT(*) FROM usuarios WHERE hunt = 1")->fetchColumn(); } catch(Exception $e) {}
            try { $stat_missao  = (int)$conexao->query("SELECT COUNT(*) FROM usuarios WHERE missao > 900")->fetchColumn(); } catch(Exception $e) {}
            try { $stat_treino  = (int)$conexao->query("SELECT COUNT(*) FROM usuarios WHERE treino > 0")->fetchColumn(); } catch(Exception $e) {}
            try { $stat_yens    = (float)$conexao->query("SELECT COALESCE(SUM(yens),0) FROM usuarios")->fetchColumn(); } catch(Exception $e) {}
            try { $stat_gms     = (int)$conexao->query("SELECT COUNT(*) FROM usuarios WHERE adm > 0")->fetchColumn(); } catch(Exception $e) {}
            try {
                $now_ev = date('Y-m-d H:i:s');
                $stat_eventos = (int)$conexao->query("SELECT COUNT(*) FROM eventos_bonus WHERE inicio <= '$now_ev' AND fim >= '$now_ev'")->fetchColumn();
            } catch(Exception $e) {}
            try {
                $novos_sql = Database::isMysql()
                    ? "SELECT COUNT(*) FROM usuarios WHERE DATE(criado_em) >= DATE(NOW() - INTERVAL 7 DAY)"
                    : "SELECT COUNT(*) FROM usuarios WHERE date(criado_em) >= date('now','-7 days')";
                $stat_novos = (int)$conexao->query($novos_sql)->fetchColumn();
            } catch(Exception $e) {}
            ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:6px;margin-bottom:10px;">
                <?php
                $cards = [
                    ['Total de Jogadores', number_format($stat_total,0,'.',','), '#ff6600'],
                    ['Online Agora',        $stat_online,                          '#90ee90'],
                    ['Caçando Agora',       $stat_cacando,                         '#87CEFA'],
                    ['Em Missão',           $stat_missao,                          '#FFD700'],
                    ['Em Treino',           $stat_treino,                          '#DDA0DD'],
                    ['Yens em Circ.',       'Y '.number_format($stat_yens,0,'.',','), '#FFD700'],
                    ['Novos (7 dias)',       $stat_novos,                           '#90ee90'],
                    ['GMs Ativos',          $stat_gms,                             '#87CEFA'],
                    ['Eventos Ativos',      $stat_eventos > 0 ? '<b style="color:#90ee90">'.$stat_eventos.' ativo'.($stat_eventos>1?'s':'').'</b>' : '<span style="color:#555">nenhum</span>', '#ff6600'],
                ];
                foreach ($cards as [$lbl, $val, $cor]): ?>
                <div style="background:#1a1200;border:1px solid #333;border-top:2px solid <?php echo $cor; ?>;padding:8px 10px;text-align:center;">
                    <div style="font-size:18px;font-weight:bold;color:<?php echo $cor; ?>;"><?php echo $val; ?></div>
                    <div style="font-size:10px;color:#666;margin-top:2px;"><?php echo $lbl; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Menu Principal de Administração -->
            <fieldset>
                <legend>Ferramentas de Administração</legend>
                <p style="color: #BBBBBB;">Ferramentas para gerenciamento do banco de dados e sistema.</p>
                <div class="admin-menu">
                    <?php if(gm_pode('invasao', $is_admin, $gm_perms)): ?><a href="?modulo=invasao_completa">Gerenciar Invasões</a><?php endif; ?>
                    <?php if($is_admin): ?><a href="?modulo=limpar_banco">Limpar Banco</a><?php endif; ?>
                    <?php if($is_admin): ?><a href="?modulo=limpar_itens">Limpar Itens</a><?php endif; ?>
                    <?php if($is_admin): ?><a href="?modulo=limpar_ip">Limpar IPs</a><?php endif; ?>
                    <?php if(gm_pode('servidores', $is_admin, $gm_perms)): ?><a href="?modulo=servidores">Gerenciar Servidores</a><?php endif; ?>
                    <?php if(gm_pode('contas', $is_admin, $gm_perms)): ?><a href="?modulo=contas">Editar Contas</a><?php endif; ?>
                    <?php if($is_admin): ?><a href="?modulo=ban_penalty">Penalidade de Ban</a><?php endif; ?>
                    <?php if($is_admin): ?><a href="?modulo=audit_log" style="border-color:#87CEFA;color:#87CEFA;">Log de Ações</a><?php endif; ?>
                    <?php if($is_admin || gm_pode('despertar_admin', $is_admin, $gm_perms)): ?><a href="?modulo=despertar_admin" style="border-color:#9966CC;color:#CC99FF;">🩸 Despertar</a><?php endif; ?>
                    <?php if(gm_pode('clas', $is_admin, $gm_perms)): ?><a href="?modulo=clas">Gerenciar Clãs</a><?php endif; ?>
                    <?php if(gm_pode('manutencao', $is_admin, $gm_perms)): ?><a href="?modulo=manutencao">Gerenciar Manutenção</a><?php endif; ?>
                    <?php if(gm_pode('equipamentos', $is_admin, $gm_perms)): ?><a href="?modulo=equipamentos">Gerenciar Equipamentos</a><?php endif; ?>
                    <?php if(gm_pode('cristais', $is_admin, $gm_perms)): ?><a href="?modulo=cristais">Gerenciar Cristais</a><?php endif; ?>
                    <?php if($is_admin): ?><a href="?modulo=jutsus">Gerenciar Jutsus</a><?php endif; ?>
                    <?php if($is_admin): ?><a href="?modulo=personagens">Gerenciar Personagens</a><?php endif; ?>
                    <?php if($is_admin): ?><a href="?modulo=contatos">Canais de Contato</a><?php endif; ?>
                    <?php if($is_admin): ?><a href="recompensa_diaria.php">Login Diário</a><?php endif; ?>
                    <?php if($is_admin): ?><a href="?modulo=config_jogo">Config. do Jogo</a><?php endif; ?>
                    <?php if($is_admin): ?><a href="?modulo=eventos_bonus">Eventos de Bônus</a><?php endif; ?>
                    <a href="?modulo=ranking_pvp">Ranking PVP</a>
                    <?php if(gm_pode('tickets', $is_admin, $gm_perms)):
                        $tk_pendentes = 0;
                        try { $tk_pendentes = (int)$conexao->query("SELECT COUNT(*) FROM tickets WHERE nao_lido_staff = 1 AND status IN ('aberto','atendimento')")->fetchColumn(); } catch(Exception $e) {}
                    ?><a href="?modulo=tickets">Suporte / Tickets<?php if($tk_pendentes>0): ?> <span style="background:#ff3333;color:#fff;padding:1px 6px;border-radius:8px;font-size:10px;font-weight:bold;"><?php echo $tk_pendentes; ?></span><?php endif; ?></a><?php endif; ?>
                    <?php if($is_admin): ?><a href="?modulo=gm_perms" style="border-color:#87CEFA;color:#87CEFA;">Permissões GM</a><?php endif; ?>
                    <?php if(gm_pode('backup', $is_admin, $gm_perms)): ?><a href="?modulo=backup" style="border-color:#9eff9e;color:#9eff9e;">Backup Automático</a><?php endif; ?>
                    <?php if($is_admin):
                        $den_pendentes = 0;
                        try { $den_pendentes = (int)$conexao->query("SELECT COUNT(*) FROM spam")->fetchColumn(); } catch(Exception $e) {}
                    ?><a href="?modulo=denuncias">Denúncias<?php if($den_pendentes>0): ?> <span style="background:#ff3333;color:#fff;padding:1px 6px;border-radius:8px;font-size:10px;font-weight:bold;"><?php echo $den_pendentes; ?></span><?php endif; ?></a><?php endif; ?>
                    <?php if($is_admin):
                        $cri_total = 0;
                        try { $cri_total = (int)$conexao->query("SELECT COUNT(*) FROM usuarios WHERE criador_conteudo = 1")->fetchColumn(); } catch(Exception $e) {}
                    ?><a href="?modulo=criadores" style="border-color:#fff;color:#fff;">Criadores<?php if($cri_total>0): ?> <span style="background:#cc0000;color:#fff;padding:1px 6px;border-radius:8px;font-size:10px;font-weight:bold;"><?php echo $cri_total; ?></span><?php endif; ?></a><?php endif; ?>
                </div>
            </fieldset>

            <!-- Sistema de Notícias -->
            <?php if(gm_pode('noticias', $is_admin, $gm_perms)): ?>
            <fieldset>
                <legend>📰 Sistema de Notícias</legend>
                <p style="color: #BBBBBB;">Gerencie as notícias que aparecem no site do jogo.</p>
                <div style="margin: 10px 0;">
                    <a href="?p=admin_noticias" class="botao" style="margin-right: 10px;">🛠️ Administrar Notícias</a>
                    <a href="../?p=news" class="botao" target="_blank">👁️ Ver Notícias</a>
                </div>
            </fieldset>
            <?php endif; ?>

        <?php elseif($modulo == 'database'): ?>
            <div style="background:#1a0a00; border-left:4px solid #ff6600; border-bottom:1px solid #444; padding:7px 12px; font-weight:bold; color:#FFD700; font-size:13px; margin-bottom:8px;">🗄️ Administrador do Banco de Dados MySQL</div>
            <div style="background:#111; border-left:1px solid #333; border-right:1px solid #333; padding:12px;">

                <?php
                // Executar consultas SQL
                if (isset($_POST['sql_query']) && $is_admin) {
                    $query = trim($_POST['sql_query']);
                    if (!empty($query)) {
                        try {
                            if (stripos($query, 'SELECT') === 0) {
                                $stmt = $conexao->prepare($query);
                                $stmt->execute();
                                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                if ($results) {
                                    echo "<div style='background: #e8f5e8; padding: 10px; margin: 10px 0; border-radius: 3px;'>";
                                    echo "<h4>Resultado da Consulta:</h4>";
                                    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                                    echo "<tr>";
                                    foreach (array_keys($results[0]) as $column) {
                                        echo "<th style='padding: 8px; background: #f0f0f0;'>" . htmlspecialchars($column) . "</th>";
                                    }
                                    echo "</tr>";
                                    foreach ($results as $row) {
                                        echo "<tr>";
                                        foreach ($row as $value) {
                                            echo "<td style='padding: 8px;'>" . htmlspecialchars($value) . "</td>";
                                        }
                                        echo "</tr>";
                                    }
                                    echo "</table>";
                                    echo "</div>";
                                } else {
                                    echo "<div style='color: orange; margin: 10px 0;'>Nenhum resultado encontrado.</div>";
                                }
                            } else {
                                $stmt = $conexao->prepare($query);
                                $result = $stmt->execute();
                                $affected = $stmt->rowCount();
                                echo "<div style='color: green; margin: 10px 0;'>✅ Consulta executada com sucesso! Linhas afetadas: $affected</div>";
                            }
                        } catch (Exception $e) {
                            echo "<div style='color: red; margin: 10px 0;'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</div>";
                        }
                    }
                }
                ?>

                <form method="POST" style="margin:12px 0;">
                    <label><strong>Consulta SQL:</strong></label><br>
                    <textarea name="sql_query" rows="6" style="width:100%;font-family:monospace;box-sizing:border-box;" placeholder="Digite sua consulta SQL aqui..."><?php echo isset($_POST['sql_query']) ? htmlspecialchars($_POST['sql_query']) : ''; ?></textarea><br><br>
                    <button type="submit" class="botao btn-success">🔍 Executar</button>
                </form>
                <div class="alert-warning"><strong>⚠️ Atenção:</strong> Use com cuidado! Consultas UPDATE/DELETE afetam dados permanentemente.</div>
            </div>
            <div style="background:#1a0a00; border-left:1px solid #333; border-right:1px solid #333; border-bottom:2px solid #ff6600; height:8px;"></div>

        <?php elseif($modulo == 'gerenciar_invasao' && $is_admin): ?>
            <div style="background:#1a0a00; border-left:4px solid #ff6600; border-bottom:1px solid #444; padding:7px 12px; font-weight:bold; color:#FFD700; font-size:13px; margin-bottom:8px;">🔥 Gerenciar Sistema de Invasões</div>
            <div style="background:#111; border-left:1px solid #333; border-right:1px solid #333; padding:12px;">

                <?php
                // Initialize variables to prevent undefined warnings
                $invasao_ativa = false;
                $bijuu_atual = 'Uma Cauda';
                ?>

                <?php
                if (isset($_POST['acao_invasao'])) {
                    switch ($_POST['acao_invasao']) {
                        case 'ativar':
                            try {
                                $bijuu = $_POST['bijuu'] ?? 'Uma Cauda';
                                $stmt = $conexao->prepare("UPDATE configuracoes SET valor = '1' WHERE nome = 'invasao_ativa'");
                                $stmt->execute();
                                $stmt = $conexao->prepare("UPDATE configuracoes SET valor = ? WHERE nome = 'invasao_bijuu'");
                                $stmt->execute([$bijuu]);
                                echo "<div style='color: green; margin: 10px 0;'>✅ Invasão ativada com sucesso!</div>";
                            } catch (Exception $e) {
                                echo "<div style='color: red; margin: 10px 0;'>❌ Erro: " . $e->getMessage() . "</div>";
                            }
                            break;
                        case 'desativar':
                            try {
                                $stmt = $conexao->prepare("UPDATE configuracoes SET valor = '0' WHERE nome = 'invasao_ativa'");
                                $stmt->execute();
                                echo "<div style='color: green; margin: 10px 0;'>✅ Invasão desativada com sucesso!</div>";
                            } catch (Exception $e) {
                                echo "<div style='color: red; margin: 10px 0;'>❌ Erro: " . $e->getMessage() . "</div>";
                            }
                            break;
                    }
                }

                // Verificar se a tabela configuracoes existe e criar se necessário
                try {
                    $pkConf = Database::autoIncPK('id');
                    $stmt = $conexao->prepare("CREATE TABLE IF NOT EXISTS configuracoes (
                        $pkConf,
                        nome VARCHAR(100) NOT NULL UNIQUE,
                        valor TEXT NOT NULL,
                        descricao TEXT
                    )");
                    $stmt->execute();

                    // Inserir configurações padrão se não existirem
                    $insIgnore = Database::isMysql() ? "INSERT IGNORE INTO" : "INSERT OR IGNORE INTO";
                    $stmt = $conexao->prepare("$insIgnore configuracoes (nome, valor, descricao) VALUES 
                        ('invasao_ativa', '0', 'Status da invasão: 0=inativa, 1=ativa'),
                        ('invasao_bijuu', 'Uma Cauda', 'Bijuu atual da invasão')");
                    $stmt->execute();

                    // Verificar status atual
                    $stmt = $conexao->prepare("SELECT * FROM configuracoes WHERE nome IN ('invasao_ativa', 'invasao_bijuu')");
                    $stmt->execute();
                    $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $invasao_ativa = false;
                    $bijuu_atual = 'Uma Cauda';
                    foreach ($configs as $config) {
                        if ($config['nome'] == 'invasao_ativa') $invasao_ativa = $config['valor'] == '1';
                        if ($config['nome'] == 'invasao_bijuu') $bijuu_atual = $config['valor'];
                    }
                } catch (Exception $e) {
                    echo "<div style='color: red;'>Erro ao verificar configurações: " . $e->getMessage() . "</div>";
                    $invasao_ativa = false;
                    $bijuu_atual = 'Uma Cauda';
                }
                ?>

                <div class="alert-warning" style="margin-bottom:10px;">
                    ⚠️ <strong>Sistema Antigo de Invasão.</strong> Para uma experiência completa, use o novo sistema:
                    <a href="?modulo=invasao_completa" class="botao btn-danger" style="margin-left:8px;">🔥 Gerenciador Completo</a>
                </div>

                <div style="background:#222; border:1px solid #444; padding:10px; margin-bottom:10px; border-radius:3px;">
                    <strong>Status Atual:</strong>
                    <?php echo $invasao_ativa ? '<span style="color:#90EE90;">⚡ ATIVA</span>' : '<span style="color:#FFAAAA;">💤 INATIVA</span>'; ?>
                    <?php if ($invasao_ativa): ?>
                        &nbsp;·&nbsp; <strong>Bijuu:</strong> <?php echo htmlspecialchars($bijuu_atual); ?>
                    <?php endif; ?>
                </div>

                <form method="POST" style="margin:10px 0;">
                    <label style="display:block;margin-bottom:6px;"><input type="radio" name="acao_invasao" value="ativar" required> Ativar Invasão</label>
                    <label style="display:block;margin-bottom:10px;">
                        Bijuu:
                        <select name="bijuu" style="margin-left:8px;">
                            <?php foreach(['Uma Cauda','Duas Caudas','Três Caudas','Quatro Caudas','Cinco Caudas','Seis Caudas','Sete Caudas','Oito Caudas','Nove Caudas'] as $b): ?>
                            <option value="<?php echo $b; ?>"><?php echo $b; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label style="display:block;margin-bottom:10px;"><input type="radio" name="acao_invasao" value="desativar"> Desativar Invasão</label>
                    <button type="submit" class="botao btn-danger">🔥 Aplicar</button>
                </form>
            </div>
            <div style="background:#1a0a00; border-left:1px solid #333; border-right:1px solid #333; border-bottom:2px solid #ff6600; height:8px;"></div>

        <?php elseif($modulo == 'limpar_banco' && $is_admin): ?>
            <div style="background:#1a0a00; border-left:4px solid #cc0000; border-bottom:1px solid #444; padding:7px 12px; font-weight:bold; color:#FFAAAA; font-size:13px; margin-bottom:8px;">🧹 Limpar Banco de Dados</div>
            <div style="background:#111; border-left:1px solid #333; border-right:1px solid #333; padding:12px;">
                <?php
                if (isset($_POST['confirmar_limpeza']) && $_POST['confirmar_limpeza'] == 'CONFIRMO') {
                    try {
                        // Desabilitar foreign keys para permitir deleção sem conflitos
                        Database::setForeignKeys($conexao, false);

                        // Limpar tabelas de mensagens e relatórios
                        $tabelas_limpar = ['mensagens', 'relatorios'];
                        foreach ($tabelas_limpar as $tabela) {
                            try {
                                $stmt = $conexao->prepare("DELETE FROM $tabela");
                                $stmt->execute();
                            } catch (Exception $ex) {
                                // Tabela pode não existir, ignorar
                            }
                        }

                        // Remover contas de teste e todos os dados relacionados
                        $stmt_ids = $conexao->prepare("SELECT id FROM usuarios WHERE usuario LIKE 'teste%'");
                        $stmt_ids->execute();
                        $ids_teste = $stmt_ids->fetchAll(PDO::FETCH_COLUMN);

                        if (!empty($ids_teste)) {
                            $placeholders = implode(',', array_fill(0, count($ids_teste), '?'));
                            // Tabelas dependentes de usuarios
                            $tabelas_dep = ['membros','amigos','inventario','jutsus','natureza',
                                            'personagens','quests','ramen','usaveis','book',
                                            'relatorios','seguranca','spam','vendas','verificador',
                                            'vip','atualizacoes','configuracoes','salas'];
                            foreach ($tabelas_dep as $td) {
                                try {
                                    $conexao->prepare("DELETE FROM $td WHERE id IN ($placeholders)")->execute($ids_teste);
                                } catch (Exception $ex) { /* tabela não existe */ }
                            }
                            // Deletar as contas de teste
                            $conexao->prepare("DELETE FROM usuarios WHERE id IN ($placeholders)")->execute($ids_teste);
                        }

                        // Reabilitar foreign keys
                        Database::setForeignKeys($conexao, true);

                        echo "<div style='color: green; margin: 10px 0;'>✅ Limpeza do banco realizada com sucesso!</div>";
                    } catch (Exception $e) {
                        Database::setForeignKeys($conexao, true);
                        echo "<div style='color: red; margin: 10px 0;'>❌ Erro: " . $e->getMessage() . "</div>";
                    }
                }
                ?>

                <div class="aviso">⚠️ <strong>ATENÇÃO — OPERAÇÃO PERIGOSA:</strong> Esta operação irá:
                    <ul style="margin:4px 0 0 16px;">
                        <li>Remover todas as mensagens</li>
                        <li>Remover todos os relatórios de ataques</li>
                        <li>Remover contas de teste (teste*)</li>
                    </ul>
                    <strong>Esta operação NÃO PODE ser desfeita!</strong>
                </div>
                <form method="POST" style="margin:12px 0;" onsubmit="return confirm('Tem certeza? Esta ação não pode ser desfeita!')">
                    <label style="color:#BBBBBB;">Digite exatamente: <strong style="color:#FFD700;">CONFIRMO</strong></label><br>
                    <input type="text" name="confirmar_limpeza" placeholder="Digite CONFIRMO" required style="margin:8px 0; padding:5px; width:200px;"><br>
                    <button type="submit" class="botao btn-danger">🧹 Executar Limpeza</button>
                </form>
            </div>
            <div style="background:#1a0a00; border-left:1px solid #333; border-right:1px solid #333; border-bottom:2px solid #cc0000; height:8px;"></div>

        <?php elseif($modulo == 'limpar_ip' && $is_admin): ?>
            <div style="background:#1a0a00; border-left:4px solid #ff6600; border-bottom:1px solid #444; padding:7px 12px; font-weight:bold; color:#FFD700; font-size:13px; margin-bottom:8px;">🌐 Limpar Registros de IP</div>
            <div style="background:#111; border-left:1px solid #333; border-right:1px solid #333; padding:12px;">
                <?php
                if (isset($_POST['limpar_ips'])) {
                    try {
                        $stmt = $conexao->prepare("UPDATE usuarios SET ip = '', ultimo_ip = ''");
                        $affected = $stmt->execute();
                        $count = $stmt->rowCount();
                        echo "<div class='alert-success'>✅ IPs limpos de $count usuários!</div>";
                    } catch (Exception $e) {
                        echo "<div class='alert-error'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</div>";
                    }
                }
                ?>

                <p style="color:#BBBBBB;">Esta operação irá limpar todos os registros de IP dos usuários do banco de dados.</p>
                <form method="POST" style="margin:10px 0;">
                    <button type="submit" name="limpar_ips" class="botao btn-danger" onclick="return confirm('Confirma a limpeza de todos os IPs?')">🌐 Limpar Todos os IPs</button>
                </form>
            </div>
            <div style="background:#1a0a00; border-left:1px solid #333; border-right:1px solid #333; border-bottom:2px solid #ff6600; height:8px;"></div>

        <?php elseif($modulo == 'servidores' && $is_admin): ?>
            <!-- Gerenciar Servidores -->
            <?php
            $srv_msg = '';
            $srv_msg_type = 'success';

            // Garantir que a tabela existe
            try {
                $pkSrv = Database::autoIncPK('id');
                $defaultTsSrv = Database::isMysql() ? 'CURRENT_TIMESTAMP' : '(CURRENT_TIMESTAMP)';
                $conexao->exec("CREATE TABLE IF NOT EXISTS servidores (
                    $pkSrv,
                    nome VARCHAR(50) NOT NULL,
                    capacidade INTEGER NOT NULL DEFAULT 100,
                    ativo INTEGER NOT NULL DEFAULT 1,
                    criado_em DATETIME DEFAULT $defaultTsSrv
                )");
            } catch (Exception $e) {}

            // Processar ações POST
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['srv_action'])) {
                $srv_action = $_POST['srv_action'];

                if ($srv_action === 'criar') {
                    $nome = trim($_POST['srv_nome'] ?? '');
                    $cap  = (int)($_POST['srv_capacidade'] ?? 100);
                    $ativo = isset($_POST['srv_ativo']) ? 1 : 0;
                    $total_srv_atual = (int)$conexao->query("SELECT COUNT(*) FROM servidores")->fetchColumn();
                    if ($nome === '') {
                        $srv_msg = 'O nome do servidor não pode estar vazio.';
                        $srv_msg_type = 'error';
                    } elseif ($cap < 1) {
                        $srv_msg = 'A capacidade deve ser maior que zero.';
                        $srv_msg_type = 'error';
                    } elseif ($total_srv_atual >= 10) {
                        $srv_msg = 'Limite máximo de 10 servidores atingido. Exclua um servidor antes de criar outro.';
                        $srv_msg_type = 'error';
                    } else {
                        // Encontrar o próximo ID disponível de 0 a 9
                        $next_id = null;
                        for ($i = 0; $i <= 9; $i++) {
                            $chk = $conexao->prepare("SELECT COUNT(*) FROM servidores WHERE id = ?");
                            $chk->execute([$i]);
                            if ((int)$chk->fetchColumn() === 0) { $next_id = $i; break; }
                        }
                        if ($next_id === null) {
                            $srv_msg = 'Nenhum ID disponível (0-9). Exclua um servidor antes de criar outro.';
                            $srv_msg_type = 'error';
                        } else {
                            $conexao->prepare("INSERT INTO servidores (id, nome, capacidade, ativo) VALUES (?, ?, ?, ?)")
                                    ->execute([$next_id, $nome, $cap, $ativo]);
                            $srv_msg = "Servidor \"$nome\" criado com sucesso! (ID: $next_id)";
                        }
                    }
                } elseif ($srv_action === 'editar') {
                    $sid  = isset($_POST['srv_id']) ? (int)$_POST['srv_id'] : -1;
                    $nome = trim($_POST['srv_nome'] ?? '');
                    $cap  = (int)($_POST['srv_capacidade'] ?? 100);
                    $ativo = isset($_POST['srv_ativo']) ? 1 : 0;
                    if ($sid < 0 || $sid > 9 || $nome === '' || $cap < 1) {
                        $srv_msg = 'Dados inválidos.';
                        $srv_msg_type = 'error';
                    } else {
                        $conexao->prepare("UPDATE servidores SET nome = ?, capacidade = ?, ativo = ? WHERE id = ?")
                                ->execute([$nome, $cap, $ativo, $sid]);
                        $srv_msg = "Servidor atualizado com sucesso!";
                    }
                } elseif ($srv_action === 'excluir') {
                    $sid = isset($_POST['srv_id']) ? (int)$_POST['srv_id'] : -1;
                    $total_srv = (int)$conexao->query("SELECT COUNT(*) FROM servidores")->fetchColumn();
                    if ($total_srv <= 1) {
                        $srv_msg = 'Não é possível excluir o único servidor existente.';
                        $srv_msg_type = 'error';
                    } elseif ($sid < 0 || $sid > 9) {
                        $srv_msg = 'ID de servidor inválido.';
                        $srv_msg_type = 'error';
                    } else {
                        // Migrar jogadores para o servidor alternativo disponível
                        $srv_alt = $conexao->prepare("SELECT id FROM servidores WHERE id != ? ORDER BY id ASC LIMIT 1");
                        $srv_alt->execute([$sid]);
                        $srv_alt_id = $srv_alt->fetchColumn();
                        $conexao->prepare("UPDATE usuarios SET servidor_id = ? WHERE servidor_id = ?")->execute([$srv_alt_id, $sid]);
                        $conexao->prepare("DELETE FROM servidores WHERE id = ?")->execute([$sid]);
                        $srv_msg = "Servidor excluído. Jogadores migrados para o servidor padrão.";
                    }
                }
            }

            // Buscar lista de servidores com contagem de jogadores
            $servidores_lista = $conexao->query("
                SELECT s.id, s.nome, s.capacidade, s.ativo, s.criado_em,
                    (SELECT COUNT(*) FROM usuarios u WHERE u.servidor_id = s.id) AS total_players
                FROM servidores s ORDER BY s.id ASC
            ")->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <div style="background:#1a0a00; border-left:4px solid #ff6600; border-bottom:1px solid #444; padding:7px 12px; font-weight:bold; color:#FFD700; font-size:13px; margin-bottom:8px;">🖥️ Gerenciar Servidores <span style="font-weight:normal; font-size:11px; color:#888;">(<?php echo count($servidores_lista); ?>/10 · IDs: 0–9)</span></div>
            <div style="background:#111; border-left:1px solid #333; border-right:1px solid #333; padding:12px;">

            <div class="alert-warning">ℹ️ <strong>Regras:</strong> Máximo de <strong>10 servidores</strong>. Os IDs são atribuídos automaticamente de <strong>0 a 9</strong>. Cada servidor é totalmente isolado.</div>

            <?php if ($srv_msg): ?>
            <div class="alert-<?php echo $srv_msg_type; ?>"><?php echo htmlspecialchars($srv_msg); ?></div>
            <?php endif; ?>

            <table class="adm-table" style="margin-bottom:15px;">
                <thead>
                    <tr>
                        <th style="padding:8px;border:1px solid #555;">ID</th>
                        <th style="padding:8px;border:1px solid #555;">Nome</th>
                        <th style="padding:8px;border:1px solid #555;">Capacidade</th>
                        <th style="padding:8px;border:1px solid #555;">Jogadores</th>
                        <th style="padding:8px;border:1px solid #555;">Vagas</th>
                        <th style="padding:8px;border:1px solid #555;">Status</th>
                        <th style="padding:8px;border:1px solid #555;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($servidores_lista as $srv): ?>
                    <?php
                    $vagas = $srv['capacidade'] - $srv['total_players'];
                    $pct   = $srv['capacidade'] > 0 ? round(($srv['total_players'] / $srv['capacidade']) * 100) : 0;
                    $cor   = $vagas <= 0 ? '#c62828' : ($pct >= 80 ? '#e65100' : '#2e7d32');
                    ?>
                    <tr>
                        <td style="text-align:center;"><?php echo $srv['id']; ?></td>
                        <td><?php echo htmlspecialchars($srv['nome']); ?></td>
                        <td style="text-align:center;"><?php echo $srv['capacidade']; ?></td>
                        <td style="text-align:center;"><?php echo $srv['total_players']; ?></td>
                        <td style="text-align:center;font-weight:bold;">
                            <?php echo $vagas <= 0 ? '<span style="color:#FFAAAA;">Cheio</span>' : '<span style="color:#90EE90;">' . $vagas . '</span>'; ?>
                            <div style="background:#333;height:5px;margin-top:3px;"><div style="width:<?php echo min($pct,100); ?>%;background:<?php echo $cor; ?>;height:5px;"></div></div>
                        </td>
                        <td style="text-align:center;">
                            <?php echo $srv['ativo'] ? '<span style="color:#90EE90;">✅ Ativo</span>' : '<span style="color:#FFAAAA;">❌ Inativo</span>'; ?>
                        </td>
                        <td style="text-align:center;">
                            <button onclick="abrirEdicao(<?php echo htmlspecialchars(json_encode($srv)); ?>)" class="botao btn-success" style="font-size:10px; padding:3px 8px;">✏️</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Excluir este servidor?');">
                                <input type="hidden" name="srv_action" value="excluir">
                                <input type="hidden" name="srv_id" value="<?php echo $srv['id']; ?>">
                                <button type="submit" class="botao btn-danger" style="font-size:10px; padding:3px 8px;">🗑️</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <fieldset>
                <legend>➕ Criar Novo Servidor</legend>
                <form method="POST">
                    <input type="hidden" name="srv_action" value="criar">
                    <label style="display:block;margin-bottom:8px;">
                        <span>Nome do servidor:</span><br>
                        <input type="text" name="srv_nome" maxlength="50" placeholder="Ex: Servidor 02" required style="width:250px;margin-top:4px;">
                    </label>
                    <label style="display:block;margin-bottom:8px;">
                        <span>Capacidade máxima:</span><br>
                        <input type="number" name="srv_capacidade" min="1" max="99999" value="100" required style="width:120px;margin-top:4px;">
                    </label>
                    <label style="margin-bottom:12px;display:block;">
                        <input type="checkbox" name="srv_ativo" checked> Ativo (visível no login)
                    </label>
                    <button type="submit" class="botao btn-success">✅ Criar Servidor</button>
                </form>
            </fieldset>
            </div>
            <div style="background:#1a0a00; border-left:1px solid #333; border-right:1px solid #333; border-bottom:2px solid #ff6600; height:8px;"></div>

            <!-- Modal de edição -->
            <div id="editSrvModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:9999;align-items:center;justify-content:center;">
                <div style="background:#222;border:1px solid #FF9800;border-radius:6px;padding:25px;width:350px;max-width:95%;">
                    <h3 style="color:#FF9800;margin-top:0;">✏️ Editar Servidor</h3>
                    <form method="POST">
                        <input type="hidden" name="srv_action" value="editar">
                        <input type="hidden" name="srv_id" id="edit_srv_id">
                        <label style="display:block;margin-bottom:8px;">
                            <span style="color:#ccc;">Nome:</span><br>
                            <input type="text" name="srv_nome" id="edit_srv_nome" maxlength="50" required style="width:100%;padding:6px;background:#333;color:#fff;border:1px solid #666;border-radius:3px;box-sizing:border-box;margin-top:4px;">
                        </label>
                        <label style="display:block;margin-bottom:8px;">
                            <span style="color:#ccc;">Capacidade máxima:</span><br>
                            <input type="number" name="srv_capacidade" id="edit_srv_cap" min="1" max="99999" required style="width:120px;padding:6px;background:#333;color:#fff;border:1px solid #666;border-radius:3px;margin-top:4px;">
                        </label>
                        <label style="margin-bottom:15px;display:block;">
                            <input type="checkbox" name="srv_ativo" id="edit_srv_ativo"> <span style="color:#ccc;">Ativo</span>
                        </label>
                        <button type="submit" style="background:#FF9800;color:white;border:none;padding:8px 18px;border-radius:3px;cursor:pointer;font-weight:bold;">💾 Salvar</button>
                        <button type="button" onclick="fecharEdicao()" style="background:#555;color:white;border:none;padding:8px 18px;border-radius:3px;cursor:pointer;margin-left:8px;">Cancelar</button>
                    </form>
                </div>
            </div>

            <script>
            function abrirEdicao(srv) {
                document.getElementById('edit_srv_id').value = srv.id;
                document.getElementById('edit_srv_nome').value = srv.nome;
                document.getElementById('edit_srv_cap').value = srv.capacidade;
                document.getElementById('edit_srv_ativo').checked = srv.ativo == 1;
                document.getElementById('editSrvModal').style.display = 'flex';
            }
            function fecharEdicao() {
                document.getElementById('editSrvModal').style.display = 'none';
            }
            document.getElementById('editSrvModal').addEventListener('click', function(e) {
                if (e.target === this) fecharEdicao();
            });
            </script>

        <?php elseif($modulo == 'audit_log' && $is_admin): ?>
            <div style="background:#1a0a00; border-left:4px solid #87CEFA; border-bottom:1px solid #444; padding:7px 12px; font-weight:bold; color:#87CEFA; font-size:13px; margin-bottom:8px;">Log de Ações Administrativas</div>
            <div style="background:#111; border-left:1px solid #333; border-right:1px solid #333; padding:12px;">
            <?php
            // Filtros
            $al_autor  = trim($_GET['al_autor']  ?? '');
            $al_acao   = trim($_GET['al_acao']   ?? '');
            $al_alvo   = trim($_GET['al_alvo']   ?? '');
            $al_de     = trim($_GET['al_de']     ?? '');
            $al_ate    = trim($_GET['al_ate']    ?? '');
            $al_pg     = max(1, (int)($_GET['al_pg'] ?? 1));
            $al_pp     = 50;
            $al_offset = ($al_pg - 1) * $al_pp;

            // Validar datas
            $al_de_val  = ($al_de  !== '' && strtotime($al_de))  ? $al_de  : '';
            $al_ate_val = ($al_ate !== '' && strtotime($al_ate)) ? $al_ate : '';

            // Construir WHERE
            $al_where  = [];
            $al_params = [];
            if ($al_autor  !== '') { $al_where[] = 'autor_nome LIKE ?';      $al_params[] = "%$al_autor%"; }
            if ($al_acao   !== '') { $al_where[] = 'acao LIKE ?';            $al_params[] = "%$al_acao%"; }
            if ($al_alvo   !== '') { $al_where[] = 'alvo_nome LIKE ?';       $al_params[] = "%$al_alvo%"; }
            if ($al_de_val  !== '') { $al_where[] = 'data_hora >= ?';        $al_params[] = $al_de_val . ' 00:00:00'; }
            if ($al_ate_val !== '') { $al_where[] = 'data_hora <= ?';        $al_params[] = $al_ate_val . ' 23:59:59'; }
            $al_sql_where = $al_where ? ('WHERE ' . implode(' AND ', $al_where)) : '';

            // Total
            $al_total = 0;
            try {
                $st_ct = $conexao->prepare("SELECT COUNT(*) FROM admin_logs $al_sql_where");
                $st_ct->execute($al_params);
                $al_total = (int)$st_ct->fetchColumn();
            } catch(Exception $e) {}

            $al_pages = max(1, (int)ceil($al_total / $al_pp));

            // Registros
            $al_rows = [];
            try {
                $st_al = $conexao->prepare("SELECT * FROM admin_logs $al_sql_where ORDER BY id DESC LIMIT $al_pp OFFSET $al_offset");
                $st_al->execute($al_params);
                $al_rows = $st_al->fetchAll(PDO::FETCH_ASSOC);
            } catch(Exception $e) {}

            // Ações distintas para o filtro
            $al_acoes_list = [];
            try {
                $al_acoes_list = $conexao->query("SELECT DISTINCT acao FROM admin_logs ORDER BY acao ASC")->fetchAll(PDO::FETCH_COLUMN);
            } catch(Exception $e) {}

            // URL base para filtros (mantém os outros parâmetros)
            $al_base_url = '?modulo=audit_log';
            if ($al_autor  !== '') $al_base_url .= '&al_autor=' . urlencode($al_autor);
            if ($al_acao   !== '') $al_base_url .= '&al_acao='  . urlencode($al_acao);
            if ($al_alvo   !== '') $al_base_url .= '&al_alvo='  . urlencode($al_alvo);
            if ($al_de_val  !== '') $al_base_url .= '&al_de='   . urlencode($al_de_val);
            if ($al_ate_val !== '') $al_base_url .= '&al_ate='  . urlencode($al_ate_val);

            // Cores por categoria de ação
            function al_cor($acao) {
                $a = strtolower($acao);
                if (strpos($a,'ban') !== false)         return '#ff6666';
                if (strpos($a,'desban') !== false)      return '#66ff99';
                if (strpos($a,'config') !== false)      return '#87CEFA';
                if (strpos($a,'limpar') !== false)      return '#ffaa44';
                if (strpos($a,'cargo') !== false)       return '#e0aaff';
                if (strpos($a,'desbloquear') !== false) return '#66ddff';
                return '#bbbbbb';
            }
            ?>

            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;margin-bottom:12px;">
            <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
                <input type="hidden" name="modulo" value="audit_log">
                <div>
                    <label style="display:block;font-size:11px;color:#aaa;margin-bottom:2px;">Admin/GM</label>
                    <input type="text" name="al_autor" value="<?php echo htmlspecialchars($al_autor); ?>" placeholder="Nome..." style="width:110px;">
                </div>
                <div>
                    <label style="display:block;font-size:11px;color:#aaa;margin-bottom:2px;">Ação</label>
                    <select name="al_acao" style="height:26px;">
                        <option value="">Todas</option>
                        <?php foreach($al_acoes_list as $ac): ?>
                            <option value="<?php echo htmlspecialchars($ac); ?>" <?php echo ($al_acao===$ac)?'selected':''; ?>><?php echo htmlspecialchars($ac); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:11px;color:#aaa;margin-bottom:2px;">Alvo</label>
                    <input type="text" name="al_alvo" value="<?php echo htmlspecialchars($al_alvo); ?>" placeholder="Jogador..." style="width:110px;">
                </div>
                <div>
                    <label style="display:block;font-size:11px;color:#aaa;margin-bottom:2px;">De</label>
                    <input type="date" id="al_de_inp" name="al_de" value="<?php echo htmlspecialchars($al_de_val); ?>" style="width:130px;height:26px;box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block;font-size:11px;color:#aaa;margin-bottom:2px;">Até</label>
                    <input type="date" id="al_ate_inp" name="al_ate" value="<?php echo htmlspecialchars($al_ate_val); ?>" style="width:130px;height:26px;box-sizing:border-box;">
                </div>
                <button type="submit" class="botao" style="height:26px;padding:0 12px;">Filtrar</button>
                <?php if($al_autor||$al_acao||$al_alvo||$al_de_val||$al_ate_val): ?>
                    <a href="?modulo=audit_log" class="botao" style="height:26px;line-height:26px;padding:0 10px;background:#333;">Limpar</a>
                <?php endif; ?>
            </form>
            <div style="display:flex;gap:5px;flex-wrap:wrap;margin-top:6px;margin-bottom:2px;">
                <span style="font-size:11px;color:#666;align-self:center;">Período rápido:</span>
                <button type="button" onclick="alPeriodo(0,0)"    class="botao" style="padding:2px 8px;font-size:11px;height:22px;">Hoje</button>
                <button type="button" onclick="alPeriodo(6,0)"    class="botao" style="padding:2px 8px;font-size:11px;height:22px;">Últimos 7 dias</button>
                <button type="button" onclick="alPeriodo(29,0)"   class="botao" style="padding:2px 8px;font-size:11px;height:22px;">Últimos 30 dias</button>
                <button type="button" onclick="alMes(0)"          class="botao" style="padding:2px 8px;font-size:11px;height:22px;">Este mês</button>
                <button type="button" onclick="alMes(-1)"         class="botao" style="padding:2px 8px;font-size:11px;height:22px;">Mês anterior</button>
            </div>
            <script>
            function alFmt(d) {
                return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
            }
            function alPeriodo(diasAtras, diasAFrente) {
                var hoje = new Date();
                var de  = new Date(hoje); de.setDate(hoje.getDate() - diasAtras);
                var ate = new Date(hoje); ate.setDate(hoje.getDate() + diasAFrente);
                document.getElementById('al_de_inp').value  = alFmt(de);
                document.getElementById('al_ate_inp').value = alFmt(ate);
            }
            function alMes(offset) {
                var d = new Date();
                d.setDate(1);
                d.setMonth(d.getMonth() + offset);
                var ini = new Date(d.getFullYear(), d.getMonth(), 1);
                var fim = new Date(d.getFullYear(), d.getMonth() + 1, 0);
                document.getElementById('al_de_inp').value  = alFmt(ini);
                document.getElementById('al_ate_inp').value = alFmt(fim);
            }
            </script>
            <?php
            $al_csv_url = 'export_audit_log.php?';
            if ($al_autor   !== '') $al_csv_url .= 'al_autor=' . urlencode($al_autor)   . '&';
            if ($al_acao    !== '') $al_csv_url .= 'al_acao='  . urlencode($al_acao)    . '&';
            if ($al_alvo    !== '') $al_csv_url .= 'al_alvo='  . urlencode($al_alvo)    . '&';
            if ($al_de_val  !== '') $al_csv_url .= 'al_de='    . urlencode($al_de_val)  . '&';
            if ($al_ate_val !== '') $al_csv_url .= 'al_ate='   . urlencode($al_ate_val) . '&';
            $al_csv_url = rtrim($al_csv_url, '?&');
            ?>
            <a href="<?php echo htmlspecialchars($al_csv_url); ?>"
               style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;background:#1a3a1a;border:1px solid #3a7a3a;color:#9eff9e;text-decoration:none;font-size:12px;border-radius:3px;white-space:nowrap;align-self:flex-end;"
               title="Exportar registros filtrados como CSV (compatível com Excel)">
                &#8659; Exportar CSV
            </a>
            </div>

            <div style="color:#888;font-size:11px;margin-bottom:8px;"><?php echo $al_total; ?> registro(s) encontrado(s)</div>

            <?php if($al_rows): ?>
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <thead>
                    <tr style="background:#1a0a00;color:#FFD700;">
                        <th style="padding:5px 8px;text-align:left;border-bottom:1px solid #444;">Data/Hora</th>
                        <th style="padding:5px 8px;text-align:left;border-bottom:1px solid #444;">Admin/GM</th>
                        <th style="padding:5px 8px;text-align:left;border-bottom:1px solid #444;">Ação</th>
                        <th style="padding:5px 8px;text-align:left;border-bottom:1px solid #444;">Alvo</th>
                        <th style="padding:5px 8px;text-align:left;border-bottom:1px solid #444;">Detalhes</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($al_rows as $i => $row): ?>
                    <tr style="background:<?php echo ($i%2===0)?'#1a1a1a':'#111'; ?>;">
                        <td style="padding:4px 8px;color:#888;white-space:nowrap;"><?php echo htmlspecialchars($row['data_hora']); ?></td>
                        <td style="padding:4px 8px;color:#FFD700;font-weight:bold;"><?php echo htmlspecialchars($row['autor_nome']); ?></td>
                        <td style="padding:4px 8px;"><span style="color:<?php echo al_cor($row['acao']); ?>;font-weight:bold;"><?php echo htmlspecialchars($row['acao']); ?></span></td>
                        <td style="padding:4px 8px;color:#ccc;"><?php echo $row['alvo_nome'] ? htmlspecialchars($row['alvo_nome']) : '<span style="color:#555">—</span>'; ?></td>
                        <td style="padding:4px 8px;color:#aaa;font-size:11px;"><?php echo htmlspecialchars($row['detalhes'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php if($al_pages > 1): ?>
            <div style="margin-top:10px;display:flex;gap:4px;flex-wrap:wrap;">
                <?php for($p=1;$p<=$al_pages;$p++): ?>
                    <a href="<?php echo $al_base_url; ?>&al_pg=<?php echo $p; ?>"
                       style="padding:3px 8px;background:<?php echo ($p===$al_pg)?'#ff6600':'#333'; ?>;color:<?php echo ($p===$al_pg)?'#fff':'#ccc'; ?>;text-decoration:none;border-radius:3px;font-size:11px;">
                        <?php echo $p; ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

            <?php else: ?>
                <div style="color:#666;padding:20px;text-align:center;">Nenhum registro encontrado.</div>
            <?php endif; ?>

            <!-- Limpeza de logs antigos -->
            <div style="margin-top:20px;border-top:1px solid #333;padding-top:14px;">
                <div style="font-size:12px;font-weight:bold;color:#f39c12;margin-bottom:6px;">Limpar Registros Antigos</div>
                <?php
                // Exibir resultado do purge se veio via POST nesta mesma carga
                // (a mensagem já foi emitida no topo via echo, então só mostramos se vier via GET redirect)
                $al_purge_ok  = isset($_GET['al_purge_ok'])  ? (int)$_GET['al_purge_ok']  : null;
                $al_purge_err = isset($_GET['al_purge_err']) ? htmlspecialchars($_GET['al_purge_err']) : null;
                if ($al_purge_ok !== null):
                ?>
                    <div style="color:#66ff99;font-size:12px;margin-bottom:8px;">✅ <?php echo $al_purge_ok; ?> registro(s) removido(s).</div>
                <?php elseif ($al_purge_err): ?>
                    <div style="color:#ff6666;font-size:12px;margin-bottom:8px;">❌ <?php echo $al_purge_err; ?></div>
                <?php endif; ?>
                <form method="POST" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;"
                      onsubmit="return confirm('Confirma a exclusão dos logs com mais de ' + document.getElementById('al_dias_input').value + ' dias?');">
                    <input type="hidden" name="action" value="purge_audit_log">
                    <label style="color:#aaa;font-size:12px;">
                        Remover registros com mais de
                        <input type="number" id="al_dias_input" name="al_dias" value="30" min="1" max="3650"
                               style="width:60px;margin:0 4px;">
                        dias
                    </label>
                    <button type="submit" class="botao" style="background:#7a2a00;border-color:#cc4400;color:#ffcc99;padding:3px 12px;font-size:12px;">Limpar</button>
                </form>
                <?php
                // Mostrar contagem de registros por faixa de idade
                try {
                    $al_counts = [];
                    foreach ([7,30,60,90,180,365] as $d) {
                        $st_c = $conexao->prepare("SELECT COUNT(*) FROM admin_logs WHERE data_hora < " . (Database::isMysql() ? "DATE_SUB(NOW(), INTERVAL ? DAY)" : "datetime('now', ? || ' days')"));
                        $st_c->execute([$d]);
                        $al_counts[$d] = (int)$st_c->fetchColumn();
                    }
                    $al_count_total = (int)$conexao->query("SELECT COUNT(*) FROM admin_logs")->fetchColumn();
                ?>
                <div style="margin-top:8px;font-size:11px;color:#666;display:flex;gap:12px;flex-wrap:wrap;">
                    <span>Total: <strong style="color:#aaa;"><?php echo $al_count_total; ?></strong></span>
                    <?php foreach($al_counts as $d => $c): if($c > 0): ?>
                    <span>&gt;<?php echo $d; ?>d: <strong style="color:#ff9944;"><?php echo $c; ?></strong></span>
                    <?php endif; endforeach; ?>
                </div>
                <?php } catch(Exception $e) {} ?>
            </div>
            </div>
            <div style="background:#1a0a00; border-left:1px solid #333; border-right:1px solid #333; border-bottom:2px solid #87CEFA; height:8px;"></div>

        <?php elseif($modulo == 'ban_penalty' && $is_admin): ?>
            <div style="background:#1a0a00; border-left:4px solid #ff6600; border-bottom:1px solid #444; padding:7px 12px; font-weight:bold; color:#FFD700; font-size:13px; margin-bottom:8px;">⚙️ Penalidade por Rejeição de Termos</div>
            <div style="background:#111; border-left:1px solid #333; border-right:1px solid #333; padding:12px;">
            <p class="sub2">Tempo que o jogador fica impedido de logar caso negue os termos ao ser desbanido.</p>
            <?php
            $ban_penalty_cfg2 = file_exists('../config/ban_penalty.php') ? require('../config/ban_penalty.php') : ['penalty_minutes' => 5];
            $penalty_minutes_cfg2 = (int)($ban_penalty_cfg2['penalty_minutes'] ?? 5);
            ?>
            <fieldset>
                <legend>Configuração</legend>
                <form method="POST" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <input type="hidden" name="action" value="save_ban_penalty">
                    <label>
                        Tempo de penalidade:
                        <input type="number" name="penalty_minutes" value="<?php echo $penalty_minutes_cfg2; ?>" min="1" max="1440" style="width:70px;margin-left:8px;" />
                        <span style="color:#888;font-size:12px;margin-left:4px;">minuto(s)</span>
                    </label>
                    <button type="submit" class="botao btn-success">💾 Salvar</button>
                </form>
            </fieldset>
            </div>
            <div style="background:#1a0a00; border-left:1px solid #333; border-right:1px solid #333; border-bottom:2px solid #ff6600; height:8px;"></div>

        <?php elseif($modulo == 'gm_perms' && $is_admin): ?>
            <?php
            // Buscar todos os GMs
            $stmt_gms = $conexao->query("SELECT id, usuario FROM usuarios WHERE adm = 2 ORDER BY usuario ASC");
            $lista_gms = $stmt_gms->fetchAll(PDO::FETCH_ASSOC);
            // GM selecionado via GET
            $gm_sel_id = isset($_GET['gm_id']) ? (int)$_GET['gm_id'] : 0;
            $gm_sel_perms = [];
            if($gm_sel_id > 0) {
                $stmt_sel = $conexao->prepare("SELECT modulo, permitido FROM gm_permissions WHERE usuario_id = ?");
                $stmt_sel->execute([$gm_sel_id]);
                foreach($stmt_sel->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $gm_sel_perms[$r['modulo']] = (bool)$r['permitido'];
                }
            }
            ?>
            <div style="background:#1a0a00; border-left:4px solid #87CEFA; border-bottom:1px solid #444; padding:7px 12px; font-weight:bold; color:#87CEFA; font-size:13px; margin-bottom:8px;">🛡️ Permissões por GM</div>
            <div style="background:#111; border-left:1px solid #333; border-right:1px solid #333; padding:16px;">
                <p style="color:#aaa;margin-top:0;">Configure permissões individuais para cada GM. Cada GM pode ter acesso a seções diferentes do painel.</p>

                <?php if(empty($lista_gms)): ?>
                    <div style="color:#f39c12;background:#2a2200;border:1px solid #f39c12;padding:12px;border-radius:4px;text-align:center;">
                        ⚠️ Não há nenhum GM cadastrado. Acesse <a href="?modulo=contas" style="color:#87CEFA;">Editar Contas</a> e promova um usuário para GM primeiro.
                    </div>
                <?php else: ?>
                    <!-- Lista de GMs -->
                    <div style="margin-bottom:16px;">
                        <strong style="color:#ccc;">Selecionar GM:</strong>
                        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;">
                            <?php foreach($lista_gms as $gm): ?>
                            <a href="?modulo=gm_perms&gm_id=<?php echo $gm['id']; ?>"
                               style="padding:8px 16px;background:<?php echo $gm_sel_id==$gm['id'] ? '#1a3a5a' : '#1a1a1a'; ?>;border:2px solid <?php echo $gm_sel_id==$gm['id'] ? '#87CEFA' : '#444'; ?>;border-radius:6px;color:<?php echo $gm_sel_id==$gm['id'] ? '#87CEFA' : '#888'; ?>;text-decoration:none;font-weight:bold;font-size:12px;">
                                🛡️ <?php echo htmlspecialchars($gm['usuario']); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if($gm_sel_id > 0): ?>
                        <?php
                        $gm_nome_sel = '';
                        foreach($lista_gms as $g) { if($g['id']==$gm_sel_id) { $gm_nome_sel=$g['usuario']; break; } }
                        ?>
                        <div style="background:#1a2a1a;border:1px solid #87CEFA;border-radius:6px;padding:16px;margin-top:8px;">
                            <h4 style="color:#87CEFA;margin-top:0;border-bottom:1px solid #333;padding-bottom:8px;">
                                Permissões de: <strong><?php echo htmlspecialchars($gm_nome_sel); ?></strong>
                            </h4>
                            <form method="POST" action="?modulo=gm_perms&gm_id=<?php echo $gm_sel_id; ?>">
                                <input type="hidden" name="action" value="save_gm_perms">
                                <input type="hidden" name="gm_user_id" value="<?php echo $gm_sel_id; ?>">
                                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:10px;margin-bottom:16px;">
                                    <?php foreach($gm_modulos_disponiveis as $mod_key => $mod_label):
                                        $ativo = !empty($gm_sel_perms[$mod_key]);
                                    ?>
                                    <label style="display:flex;align-items:center;gap:10px;background:#111;border:2px solid <?php echo $ativo ? '#87CEFA' : '#444'; ?>;border-radius:6px;padding:12px 14px;cursor:pointer;">
                                        <input type="checkbox" name="gm_mod[]" value="<?php echo htmlspecialchars($mod_key); ?>" <?php echo $ativo ? 'checked' : ''; ?> style="width:18px;height:18px;cursor:pointer;accent-color:#87CEFA;">
                                        <span style="color:<?php echo $ativo ? '#87CEFA' : '#888'; ?>;font-size:12px;font-weight:bold;"><?php echo $mod_label; ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                                <div style="text-align:center;">
                                    <button type="submit" class="botao" style="border-color:#87CEFA;color:#87CEFA;">💾 Salvar Permissões de <?php echo htmlspecialchars($gm_nome_sel); ?></button>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <div style="color:#888;text-align:center;padding:20px;border:1px dashed #444;border-radius:6px;">
                            👆 Selecione um GM acima para configurar suas permissões
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div style="margin-top:16px;background:#1a0a00;border:1px solid #555;border-radius:4px;padding:10px;color:#888;font-size:11px;">
                    <strong style="color:#f39c12;">⚠️ Nota:</strong> Funções exclusivas de ADM (SQL, Limpar Banco, Limpar IPs, Log de Ações, Penalidade de Ban, Alterar Cargo) nunca são acessíveis para GM.
                </div>
            </div>
            <div style="background:#1a0a00; border-left:1px solid #333; border-right:1px solid #333; border-bottom:2px solid #87CEFA; height:8px;"></div>

        <?php elseif($modulo == 'contas' && gm_pode('contas', $is_admin, $gm_perms)): ?>
            <div style="background:#1a0a00; border-left:4px solid #ff6600; border-bottom:1px solid #444; padding:7px 12px; font-weight:bold; color:#FFD700; font-size:13px; margin-bottom:8px;">👥 Editar Contas</div>
            <div style="background:#111; border-left:1px solid #333; border-right:1px solid #333; padding:12px;">
            <?php
            $srv_map = [];
            try {
                $srv_rows = $conexao->query("SELECT id, nome FROM servidores ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($srv_rows as $sr) $srv_map[$sr['id']] = $sr['nome'];
            } catch (Exception $e) {}
            ?>
            <form method="GET" style="margin-bottom:10px;">
                <input type="hidden" name="p" value="adm">
                <input type="hidden" name="modulo" value="contas">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Buscar usuário...">
                <button type="submit" class="botao">🔍 Buscar</button>
            </form>
            <div style="overflow-x:auto;">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuário</th>
                            <th>Servidor</th>
                            <th>Nível</th>
                            <th>Vila</th>
                            <th>Status</th>
                            <th>Yens</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <?php
                                    $u_adm = (int)($user['adm'] ?? 0);
                                    if($u_adm == 1) {
                                        echo '<span style="color:#FFD700;font-weight:bold;" title="Administrador">[ADM] ' . htmlspecialchars($user['usuario']) . '</span>';
                                    } elseif($u_adm == 2) {
                                        echo '<span style="color:#87CEFA;font-weight:bold;" title="Game Master">[GM] ' . htmlspecialchars($user['usuario']) . '</span>';
                                    } else {
                                        echo htmlspecialchars($user['usuario']);
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($srv_map[$user['servidor_id'] ?? 0] ?? 'S' . ($user['servidor_id'] ?? '?')); ?></td>
                                <td><?php echo $user['nivel']; ?></td>
                                <td><?php echo $vilas[$user['vila']] ?? 'N/A'; ?></td>
                                <td>
                                    <?php if($user['status'] == 'banido'): ?>
                                        <span style="color:#FFAAAA;">Banido</span>
                                        <?php if($user['ban_data'] && $user['ban_duracao']): ?>
                                            <?php $ban_fim2 = date('Y-m-d H:i:s', strtotime($user['ban_data'] . ' +' . $user['ban_duracao'] . ' days')); ?>
                                            <?php if($ban_fim2 > date('Y-m-d H:i:s')): ?>
                                                <br><small style="color:#ff9800;">Até: <?php echo date('d/m/Y H:i', strtotime($ban_fim2)); ?></small>
                                            <?php else: ?>
                                                <br><small style="color:#ff9800;">Expirado</small>
                                            <?php endif; ?>
                                            <br><small style="color:#aaa;">Motivo: <?php echo htmlspecialchars($user['ban_motivo'] ?: 'N/A'); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:#90EE90;"><?php echo ucfirst($user['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($user['yens']); ?></td>
                                <td>
                                    <?php if($is_admin || ($is_mod && gm_pode('contas', $is_admin, $gm_perms) && ($user['adm'] ?? 0) != 1)): ?>
                                        <a href="?p=adm&edit=<?php echo $user['id']; ?>" class="botao btn-success" style="font-size:10px;padding:2px 6px;text-decoration:none;" title="Editar">✏️</a>
                                    <?php endif; ?>
                                    <?php if(gm_pode('ban', $is_admin, $gm_perms)): ?>
                                        <?php if($user['status'] == 'banido'): ?>
                                            <button onclick="showEditBanForm(<?php echo $user['id']; ?>, '<?php echo addslashes($user['usuario']); ?>', <?php echo $user['ban_duracao'] ?: 1; ?>, '<?php echo addslashes($user['ban_motivo'] ?: ''); ?>')" class="botao" style="font-size:10px;padding:2px 6px;">⏰</button>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="unban_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="botao btn-success" style="font-size:10px;padding:2px 6px;">✅</button>
                                            </form>
                                        <?php else: ?>
                                            <button onclick="showBanForm(<?php echo $user['id']; ?>, '<?php echo addslashes($user['usuario']); ?>')" class="botao btn-danger" style="font-size:10px;padding:2px 6px;">🔨</button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if($total_pages > 1): ?>
                <div style="text-align:center;margin:12px 0;color:#ccc;">
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if($i == $page): ?><strong style="color:#FF9800;"><?php echo $i; ?></strong><?php else: ?><a href="?p=adm&modulo=contas&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" style="color:#ccc;"><?php echo $i; ?></a><?php endif; ?>
                        <?php if($i < $total_pages) echo " | "; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
            </div>
            <div style="background:#1a0a00; border-left:1px solid #333; border-right:1px solid #333; border-bottom:2px solid #ff6600; height:8px;"></div>

        <?php elseif($modulo == 'denuncias' && $is_admin): ?>
            <?php
            // Ações: deletar uma denúncia ou todas de um alvo
            if(isset($_GET['del_den']) && (int)$_GET['del_den'] > 0) {
                $del_id = (int)$_GET['del_den'];
                try {
                    $conexao->prepare("DELETE FROM spam WHERE id = ?")->execute([$del_id]);
                    adm_log($conexao, $user_id, $usuario_logado['usuario'] ?? '?', 'Excluir Denúncia', $del_id, null, "Denúncia #$del_id removida");
                    echo "<div style='color:#4CAF50;margin:8px 0;'>✅ Denúncia #$del_id excluída.</div>";
                } catch(Exception $e) {}
            }
            if(isset($_GET['del_alvo']) && (int)$_GET['del_alvo'] > 0) {
                $del_alvo = (int)$_GET['del_alvo'];
                try {
                    $st_nm = $conexao->prepare("SELECT usuario FROM usuarios WHERE id=?"); $st_nm->execute([$del_alvo]);
                    $nm_alvo = $st_nm->fetchColumn() ?: '';
                    $stD = $conexao->prepare("DELETE FROM spam WHERE usuarioid = ?");
                    $stD->execute([$del_alvo]);
                    adm_log($conexao, $user_id, $usuario_logado['usuario'] ?? '?', 'Excluir Denúncias (alvo)', $del_alvo, $nm_alvo, "Todas as denúncias do alvo removidas");
                    echo "<div style='color:#4CAF50;margin:8px 0;'>✅ Todas as denúncias do alvo foram excluídas.</div>";
                } catch(Exception $e) {}
            }
            // Apagar apresentação do jogador denunciado
            if(isset($_GET['clear_aps']) && (int)$_GET['clear_aps'] > 0) {
                $alvo_id = (int)$_GET['clear_aps'];
                try {
                    $st_nm = $conexao->prepare("SELECT usuario FROM usuarios WHERE id=?"); $st_nm->execute([$alvo_id]);
                    $nm_alvo = $st_nm->fetchColumn() ?: '';
                    $conexao->prepare("UPDATE usuarios SET config_apresentacao = '' WHERE id = ?")->execute([$alvo_id]);
                    adm_log($conexao, $user_id, $usuario_logado['usuario'] ?? '?', 'Limpar Apresentação', $alvo_id, $nm_alvo, "Apresentação removida via denúncias");
                    echo "<div style='color:#4CAF50;margin:8px 0;'>✅ Apresentação de <b>".htmlspecialchars($nm_alvo)."</b> apagada.</div>";
                } catch(Exception $e) {}
            }
            // Enviar mensagem direta ao alvo denunciado
            if(isset($_GET['msg_alvo']) && (int)$_GET['msg_alvo'] > 0) {
                $alvo_id = (int)$_GET['msg_alvo'];
                try {
                    $st_nm = $conexao->prepare("SELECT usuario FROM usuarios WHERE id=?"); $st_nm->execute([$alvo_id]);
                    $nm_alvo = $st_nm->fetchColumn() ?: '';
                    $assunto = "[Automática] Aviso da Administração";
                    $corpo = "<i>⚠️ Esta é uma mensagem automática do sistema, não é necessário responder.</i>\n\nSua apresentação foi denunciada pela comunidade e analisada pela equipe. Por favor, revise o conteúdo do seu perfil para que esteja de acordo com as regras do jogo. Reincidências podem resultar em penalidades.\n\nAtenciosamente,\nEquipe Naruto O Game.";
                    $stI = $conexao->prepare("INSERT INTO mensagens (origem, destino, assunto, msg) VALUES (?, ?, ?, ?)");
                    $stI->execute([0, $alvo_id, $assunto, $corpo]);
                    adm_log($conexao, $user_id, $usuario_logado['usuario'] ?? '?', 'Mensagem ao Denunciado', $alvo_id, $nm_alvo, "Aviso enviado");
                    echo "<div style='color:#4CAF50;margin:8px 0;'>✉️ Mensagem enviada para <b>".htmlspecialchars($nm_alvo)."</b>.</div>";
                } catch(Exception $e) {}
            }
            // Responder em massa aos denunciantes do alvo
            if(isset($_GET['reply_den']) && (int)$_GET['reply_den'] > 0 && isset($_GET['tipo'])) {
                $alvo_id = (int)$_GET['reply_den'];
                $tipo = (int)$_GET['tipo'];
                try {
                    $st_nm = $conexao->prepare("SELECT usuario FROM usuarios WHERE id=?"); $st_nm->execute([$alvo_id]);
                    $nm_alvo = $st_nm->fetchColumn() ?: '';
                    $aviso_auto = "<i>⚠️ Esta é uma mensagem automática do sistema, não é necessário responder.</i>\n\n";
                    if($tipo == 1) {
                        $assunto = "[Automática] Resposta da denúncia";
                        $corpo = $aviso_auto."Obrigado pela sua denúncia contra o jogador <b>".$nm_alvo."</b>. Após análise, o mesmo foi penalizado pela equipe.\n\nAgradecemos a colaboração para manter o jogo um ambiente saudável!\n\nEquipe Naruto O Game.";
                    } else {
                        $assunto = "[Automática] Resposta da denúncia";
                        $corpo = $aviso_auto."Obrigado pela sua denúncia contra o jogador <b>".$nm_alvo."</b>. Ao analisarmos o perfil, não encontramos nada que quebre as regras do jogo no momento.\n\nAgradecemos a colaboração e pedimos que continue reportando comportamentos suspeitos.\n\nEquipe Naruto O Game.";
                    }
                    $stD = $conexao->prepare("SELECT DISTINCT informanteid FROM spam WHERE usuarioid = ? AND informanteid > 0");
                    $stD->execute([$alvo_id]);
                    $ids = $stD->fetchAll(PDO::FETCH_COLUMN);
                    $stI = $conexao->prepare("INSERT INTO mensagens (origem, destino, assunto, msg) VALUES (?, ?, ?, ?)");
                    $enviados = 0;
                    foreach($ids as $iid) {
                        $stI->execute([0, (int)$iid, $assunto, $corpo]);
                        $enviados++;
                    }
                    adm_log($conexao, $user_id, $usuario_logado['usuario'] ?? '?', 'Resposta em Massa Denúncia', $alvo_id, $nm_alvo, "Tipo $tipo — $enviados destinatários");
                    echo "<div style='color:#4CAF50;margin:8px 0;'>📨 Resposta enviada para <b>".$enviados."</b> denunciante(s) do alvo <b>".htmlspecialchars($nm_alvo)."</b>.</div>";
                } catch(Exception $e) {}
            }

            $den_filtro = trim($_GET['den_filtro'] ?? '');
            $den_view   = $_GET['den_view'] ?? 'agrupado'; // 'agrupado' ou 'lista'
            $den_page   = max(1, (int)($_GET['den_page'] ?? 1));
            $den_per    = 30;
            $den_off    = ($den_page - 1) * $den_per;
            ?>
            <div style="background:#1a0a00; border-left:4px solid #ff3333; border-bottom:1px solid #444; padding:7px 12px; font-weight:bold; color:#fff; font-size:13px; margin-bottom:8px;">🚨 Denúncias de Apresentação (Spam)</div>
            <div style="background:#111; border-left:1px solid #333; border-right:1px solid #333; padding:12px;">
                <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;align-items:center;">
                    <input type="hidden" name="p" value="adm">
                    <input type="hidden" name="modulo" value="denuncias">
                    <input type="text" name="den_filtro" value="<?php echo htmlspecialchars($den_filtro); ?>" placeholder="Filtrar por nome (alvo ou informante)..." class="input" style="width:260px;">
                    <select name="den_view" class="input" style="width:140px;">
                        <option value="agrupado" <?php if($den_view=='agrupado') echo 'selected'; ?>>Agrupado por alvo</option>
                        <option value="lista"    <?php if($den_view=='lista')    echo 'selected'; ?>>Lista completa</option>
                    </select>
                    <button type="submit" class="botao">🔍 Filtrar</button>
                    <a href="?p=adm&modulo=denuncias" class="botao" style="text-decoration:none;">✖ Limpar</a>
                </form>

                <?php if($den_view == 'agrupado'): ?>
                    <?php
                    $w=""; $par=[];
                    if($den_filtro){ $w = "WHERE usuario LIKE ? OR informante LIKE ?"; $par=["%$den_filtro%","%$den_filtro%"]; }
                    $tot_stmt = $conexao->prepare("SELECT COUNT(*) FROM (SELECT usuarioid FROM spam $w GROUP BY usuarioid) AS sub");
                    $tot_stmt->execute($par);
                    $den_total = (int)$tot_stmt->fetchColumn();
                    $den_pages = max(1, ceil($den_total / $den_per));

                    $par_l = array_merge($par, [$den_per, $den_off]);
                    $sql_g = "SELECT usuarioid, usuario, COUNT(*) as total, COUNT(DISTINCT informanteid) as denunciantes, MAX(created_at) as ultima
                              FROM spam $w GROUP BY usuarioid, usuario
                              ORDER BY total DESC, ultima DESC LIMIT ? OFFSET ?";
                    $g_stmt = $conexao->prepare($sql_g);
                    $g_stmt->execute($par_l);
                    $grupos = $g_stmt->fetchAll(PDO::FETCH_ASSOC);
                    // Histórico de respostas automáticas enviadas (admin_logs)
                    $resp_hist = []; // [alvo_id => ['1' => data, '2' => data]]
                    try {
                        $hst = $conexao->query("SELECT alvo_id, detalhes, MAX(data_hora) as quando FROM admin_logs WHERE acao = 'Resposta em Massa Denúncia' GROUP BY alvo_id, detalhes");
                        foreach($hst->fetchAll(PDO::FETCH_ASSOC) as $h) {
                            $tp = (preg_match('/Tipo (\d)/', $h['detalhes'] ?? '', $m)) ? $m[1] : '?';
                            $resp_hist[(int)$h['alvo_id']][$tp] = $h['quando'];
                        }
                    } catch(Exception $e) {}
                    ?>
                    <div style="color:#888;font-size:11px;margin-bottom:8px;">Total: <?php echo $den_total; ?> alvo(s) com denúncias.</div>
                    <?php if(empty($grupos)): ?>
                        <div style="color:#888;text-align:center;padding:20px;">Nenhuma denúncia encontrada.</div>
                    <?php else: ?>
                    <table class="adm-table" style="font-size:12px;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Alvo (denunciado)</th>
                                <th>Total</th>
                                <th>Denunciantes únicos</th>
                                <th>Última denúncia</th>
                                <th>Histórico de Respostas</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($grupos as $gp):
                            $aid = (int)$gp['usuarioid'];
                            $rt1 = $resp_hist[$aid]['1'] ?? null;
                            $rt2 = $resp_hist[$aid]['2'] ?? null;
                            $cf1 = $rt1 ? "Já foi enviada resposta \\'Penalizado\\' em ".date('d/m/Y H:i', strtotime($rt1)).". Enviar novamente?" : "Responder denunciantes informando que o jogador foi penalizado?";
                            $cf2 = $rt2 ? "Já foi enviada resposta \\'Sem irregularidade\\' em ".date('d/m/Y H:i', strtotime($rt2)).". Enviar novamente?" : "Responder denunciantes informando que nada foi encontrado?";
                        ?>
                            <tr>
                                <td style="color:#555;"><?php echo $aid; ?></td>
                                <td style="color:#FFD700;font-weight:bold;"><?php echo htmlspecialchars($gp['usuario']); ?></td>
                                <td style="color:#fff;font-weight:bold;text-align:center;"><?php echo (int)$gp['total']; ?></td>
                                <td style="color:#87CEFA;text-align:center;"><?php echo (int)$gp['denunciantes']; ?></td>
                                <td style="color:#888;white-space:nowrap;"><?php echo date('d/m/Y H:i', strtotime($gp['ultima'])); ?></td>
                                <td style="font-size:10px;white-space:nowrap;">
                                    <?php if($rt1): ?><div style="color:#4CAF50;">📨 Penalizado<br><small style="color:#888;"><?php echo date('d/m/Y H:i', strtotime($rt1)); ?></small></div><?php endif; ?>
                                    <?php if($rt2): ?><div style="color:#87CEFA;margin-top:3px;">📨 Sem irregular.<br><small style="color:#888;"><?php echo date('d/m/Y H:i', strtotime($rt2)); ?></small></div><?php endif; ?>
                                    <?php if(!$rt1 && !$rt2): ?><span style="color:#555;">—</span><?php endif; ?>
                                </td>
                                <td style="white-space:nowrap;">
                                    <a href="../?p=view&view=<?php echo urlencode(strtolower($gp['usuario'])); ?>" target="_blank" class="botao" style="font-size:10px;padding:2px 6px;">🔍 Perfil</a>
                                    <a href="?p=adm&modulo=denuncias&clear_aps=<?php echo (int)$gp['usuarioid']; ?>" class="botao btn-danger" style="font-size:10px;padding:2px 6px;" onclick="return confirm('Apagar a apresentação deste jogador?');">🧹 Apagar Aps.</a>
                                    <a href="?p=adm&modulo=denuncias&msg_alvo=<?php echo (int)$gp['usuarioid']; ?>" class="botao" style="font-size:10px;padding:2px 6px;color:#FFD700;border-color:#FFD700;" onclick="return confirm('Enviar mensagem de aviso ao denunciado?');">✉️ Avisar</a>
                                    <a href="?p=adm&modulo=denuncias&reply_den=<?php echo $aid; ?>&tipo=1" class="botao" style="font-size:10px;padding:2px 6px;color:#4CAF50;border-color:#4CAF50;<?php if($rt1) echo 'opacity:0.6;'; ?>" onclick="return confirm('<?php echo $cf1; ?>');">📨 Penalizado<?php if($rt1) echo ' ✔'; ?></a>
                                    <a href="?p=adm&modulo=denuncias&reply_den=<?php echo $aid; ?>&tipo=2" class="botao" style="font-size:10px;padding:2px 6px;color:#87CEFA;border-color:#87CEFA;<?php if($rt2) echo 'opacity:0.6;'; ?>" onclick="return confirm('<?php echo $cf2; ?>');">📨 Sem irregular.<?php if($rt2) echo ' ✔'; ?></a>
                                    <a href="?p=adm&modulo=denuncias&del_alvo=<?php echo (int)$gp['usuarioid']; ?>" class="botao btn-danger" style="font-size:10px;padding:2px 6px;" onclick="return confirm('Remover TODAS as denúncias contra este alvo?');">🗑️ Limpar denúncias</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>

                <?php else: ?>
                    <?php
                    $w=""; $par=[];
                    if($den_filtro){ $w = "WHERE usuario LIKE ? OR informante LIKE ?"; $par=["%$den_filtro%","%$den_filtro%"]; }
                    $tot_stmt = $conexao->prepare("SELECT COUNT(*) FROM spam $w");
                    $tot_stmt->execute($par);
                    $den_total = (int)$tot_stmt->fetchColumn();
                    $den_pages = max(1, ceil($den_total / $den_per));

                    $par_l = array_merge($par, [$den_per, $den_off]);
                    $sql_l = "SELECT * FROM spam $w ORDER BY id DESC LIMIT ? OFFSET ?";
                    $l_stmt = $conexao->prepare($sql_l);
                    $l_stmt->execute($par_l);
                    $denuncias = $l_stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <div style="color:#888;font-size:11px;margin-bottom:8px;">Total: <?php echo $den_total; ?> denúncia(s).</div>
                    <?php if(empty($denuncias)): ?>
                        <div style="color:#888;text-align:center;padding:20px;">Nenhuma denúncia encontrada.</div>
                    <?php else: ?>
                    <table class="adm-table" style="font-size:11px;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Data</th>
                                <th>Alvo</th>
                                <th>Informante</th>
                                <th>Mensagem denunciada</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($denuncias as $den): ?>
                            <tr>
                                <td style="color:#555;"><?php echo (int)$den['id']; ?></td>
                                <td style="color:#888;white-space:nowrap;"><?php echo date('d/m/Y H:i', strtotime($den['created_at'])); ?></td>
                                <td style="color:#FFD700;font-weight:bold;"><?php echo htmlspecialchars($den['usuario'] ?? ''); ?> <small style="color:#555;">#<?php echo (int)$den['usuarioid']; ?></small></td>
                                <td style="color:#87CEFA;"><?php echo htmlspecialchars($den['informante'] ?? ''); ?> <small style="color:#555;">#<?php echo (int)$den['informanteid']; ?></small></td>
                                <td style="color:#ccc;max-width:420px;word-break:break-word;">
                                    <details>
                                        <summary style="cursor:pointer;color:#FFD700;">👁️ Visualizar como aparece no perfil</summary>
                                        <div style="background:#000;border:1px dashed #555;padding:8px;margin-top:6px;max-height:260px;overflow:auto;">
                                            <?php echo $den['mensagem'] ?? ''; ?>
                                        </div>
                                    </details>
                                    <details style="margin-top:4px;">
                                        <summary style="cursor:pointer;color:#87CEFA;">📝 Ver código (HTML/texto)</summary>
                                        <pre style="background:#000;border:1px dashed #555;padding:8px;margin-top:6px;max-height:200px;overflow:auto;color:#ccc;font-size:10px;white-space:pre-wrap;word-break:break-word;"><?php echo htmlspecialchars($den['mensagem'] ?? ''); ?></pre>
                                    </details>
                                </td>
                                <td style="white-space:nowrap;">
                                    <a href="../?p=view&view=<?php echo urlencode(strtolower($den['usuario'] ?? '')); ?>" target="_blank" class="botao" style="font-size:10px;padding:2px 6px;">🔍 Perfil</a>
                                    <a href="?p=adm&modulo=denuncias&den_view=lista&den_filtro=<?php echo urlencode($den_filtro); ?>&clear_aps=<?php echo (int)$den['usuarioid']; ?>" class="botao btn-danger" style="font-size:10px;padding:2px 6px;" onclick="return confirm('Apagar a apresentação deste jogador?');">🧹 Apagar Aps.</a>
                                    <a href="?p=adm&modulo=denuncias&den_view=lista&den_filtro=<?php echo urlencode($den_filtro); ?>&msg_alvo=<?php echo (int)$den['usuarioid']; ?>" class="botao" style="font-size:10px;padding:2px 6px;color:#FFD700;border-color:#FFD700;" onclick="return confirm('Enviar mensagem de aviso ao denunciado?');">✉️ Avisar</a>
                                    <a href="?p=adm&modulo=denuncias&den_view=lista&den_filtro=<?php echo urlencode($den_filtro); ?>&reply_den=<?php echo (int)$den['usuarioid']; ?>&tipo=1" class="botao" style="font-size:10px;padding:2px 6px;color:#4CAF50;border-color:#4CAF50;" onclick="return confirm('Responder denunciantes informando que o jogador foi penalizado?');">📨 Penalizado</a>
                                    <a href="?p=adm&modulo=denuncias&den_view=lista&den_filtro=<?php echo urlencode($den_filtro); ?>&reply_den=<?php echo (int)$den['usuarioid']; ?>&tipo=2" class="botao" style="font-size:10px;padding:2px 6px;color:#87CEFA;border-color:#87CEFA;" onclick="return confirm('Responder denunciantes informando que nada foi encontrado?');">📨 Sem irregular.</a>
                                    <a href="?p=adm&modulo=denuncias&den_view=lista&den_filtro=<?php echo urlencode($den_filtro); ?>&del_den=<?php echo (int)$den['id']; ?>" class="botao btn-danger" style="font-size:10px;padding:2px 6px;" onclick="return confirm('Excluir esta denúncia?');">🗑️ Excluir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if($den_pages > 1): ?>
                    <div style="text-align:center;margin:10px 0;color:#ccc;">
                        <?php for($i = 1; $i <= $den_pages; $i++): ?>
                            <?php if($i == $den_page): ?>
                                <strong style="color:#FF9800;"><?php echo $i; ?></strong>
                            <?php else: ?>
                                <a href="?p=adm&modulo=denuncias&den_page=<?php echo $i; ?>&den_view=<?php echo urlencode($den_view); ?>&den_filtro=<?php echo urlencode($den_filtro); ?>" style="color:#ccc;"><?php echo $i; ?></a>
                            <?php endif; ?>
                            <?php if($i < $den_pages) echo " | "; ?>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div style="background:#1a0a00; border-left:1px solid #333; border-right:1px solid #333; border-bottom:2px solid #ff3333; height:8px;"></div>

        <?php elseif($modulo == 'criadores' && $is_admin): ?>
            <div style="background:#1a0000; border-left:4px solid #ff4444; border-bottom:1px solid #444; padding:7px 12px; font-weight:bold; color:#fff; font-size:13px; margin-bottom:8px;">🎬 Criadores de Conteúdo — Parcerias e Presentes</div>
            <div style="background:#111; border-left:1px solid #333; border-right:1px solid #333; padding:12px;">
            <?php
            $cri_msg = '';
            $autor_id   = (int)($usuario_logado['id'] ?? 0);
            $autor_nome = (string)($usuario_logado['usuario'] ?? '?');

            // Helper: gerar slug único de ref_link a partir do nome do criador
            if(!function_exists('cri_gerar_ref_link')){
                function cri_gerar_ref_link($conexao, $usuario){
                    $base = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $usuario));
                    if($base === '') $base = 'criador';
                    $slug = $base;
                    $i = 0;
                    while(true){
                        $stt = $conexao->prepare("SELECT COUNT(*) FROM usuarios WHERE ref_link=?");
                        $stt->execute([$slug]);
                        if((int)$stt->fetchColumn() === 0) return $slug;
                        $i++;
                        $slug = $base.$i;
                        if($i > 9999) return $base.time();
                    }
                }
            }

            // AÇÃO: Tornar/Remover criador
            if(isset($_GET['toggle_criador'])){
                $tid = (int)$_GET['toggle_criador'];
                $stt = $conexao->prepare("SELECT id, usuario, criador_conteudo, ref_link FROM usuarios WHERE id=?");
                $stt->execute([$tid]);
                $alvo = $stt->fetch(PDO::FETCH_ASSOC);
                if($alvo){
                    $novo = $alvo['criador_conteudo'] ? 0 : 1;
                    // Ao promover, garantir ref_link único automático
                    if($novo === 1 && empty($alvo['ref_link'])){
                        $novo_slug = cri_gerar_ref_link($conexao, $alvo['usuario']);
                        $upd = $conexao->prepare("UPDATE usuarios SET criador_conteudo=?, ref_link=? WHERE id=?");
                        $upd->execute([$novo, $novo_slug, $tid]);
                    } else {
                        $upd = $conexao->prepare("UPDATE usuarios SET criador_conteudo=? WHERE id=?");
                        $upd->execute([$novo, $tid]);
                    }
                    $acao = $novo ? 'Tornar Criador de Conteúdo' : 'Remover Criador de Conteúdo';
                    adm_log($conexao, $autor_id, $autor_nome, $acao, $tid, $alvo['usuario'], '');
                    // Mensagem ao jogador
                    $assunto = $novo ? '[Automática] Você agora é Criador de Conteúdo!' : '[Automática] Status de Criador removido';
                    $corpo   = $novo
                        ? '⚠️ Esta é uma mensagem automática do sistema, não é necessário responder.<br /><br />Parabéns! Você foi promovido a <b>🎬 Criador de Conteúdo</b> do '.nome_servidor().'.<br />Agora seu canal aparece em destaque no Top Criadores e você poderá receber presentes especiais de parceria da Administração.'
                        : '⚠️ Esta é uma mensagem automática do sistema, não é necessário responder.<br /><br />Seu status de Criador de Conteúdo foi removido.';
                    try {
                        $sm = $conexao->prepare("INSERT INTO mensagens (origem, destino, assunto, msg, data, status) VALUES (0, ?, ?, ?, CURRENT_TIMESTAMP, 'naolido')");
                        $sm->execute([$tid, $assunto, $corpo]);
                    } catch(Exception $e) {}
                    $cri_msg = '<div class="aviso" style="background:#1a3d1a;border-color:#4caf50;color:#4caf50;">Status atualizado para <b>'.htmlspecialchars($alvo['usuario']).'</b>.</div>';
                }
            }

            // Helper: entrega presente a um único criador (atualiza inventário/yens, envia mensagem em anexo, loga)
            if(!function_exists('cri_entregar_presente')){
                function cri_entregar_presente($conexao, $autor_id, $autor_nome, $dest, $tipo, $qtd, $itens_cache){
                    $detalhe_msg = '';
                    $log_alvo_extra = '';
                    if($tipo === 'yens'){
                        $upd = $conexao->prepare("UPDATE usuarios SET yens = yens + ? WHERE id=?");
                        $upd->execute([$qtd, $dest['id']]);
                        $detalhe_msg = '<b>'.number_format($qtd,0,',','.').' yens</b>';
                        $log_alvo_extra = $qtd.' yens';
                    } elseif(strpos($tipo, 'item_') === 0){
                        $itemid = (int)substr($tipo, 5);
                        $nome_item = isset($itens_cache[$itemid]) ? $itens_cache[$itemid] : null;
                        if($nome_item === null){
                            $stit = $conexao->prepare("SELECT nome FROM table_usaveis WHERE id=?");
                            $stit->execute([$itemid]);
                            $rowItem = $stit->fetch(PDO::FETCH_ASSOC);
                            if($rowItem) $nome_item = $rowItem['nome'];
                        }
                        if($nome_item){
                            $ins = $conexao->prepare("INSERT INTO usaveis (usuarioid, itemid, status) VALUES (?, ?, 'off')");
                            for($k=0; $k<$qtd; $k++){ $ins->execute([$dest['id'], $itemid]); }
                            $detalhe_msg = '<b>'.$qtd.'x '.htmlspecialchars($nome_item).'</b>';
                            $log_alvo_extra = $qtd.'x '.$nome_item;
                        }
                    }
                    if($detalhe_msg === '') return false;
                    // Mensagem em anexo (sobre a parceria + o que ganhou)
                    $assunto = '[Parceria] 🎁 Presente de Parceria recebido!';
                    $corpo = '⚠️ Esta é uma mensagem automática do programa de parcerias do '.nome_servidor().', não é necessário responder.<br /><br />'
                           . 'Olá <b>'.htmlspecialchars($dest['usuario']).'</b>!<br /><br />'
                           . 'Como agradecimento por fazer parte do nosso programa de <b>🎬 Criadores de Conteúdo</b> e divulgar o '.nome_servidor().' para sua comunidade, '
                           . 'a Administração está te enviando o presente de parceria abaixo:<br /><br />'
                           . '🎁 '.$detalhe_msg.'<br /><br />'
                           . 'Continue produzindo conteúdo incrível, compartilhe seu link de parceria personalizado para que sua comunidade entre no jogo, '
                           . 'e fique de olho — quanto mais você se destacar, mais brindes especiais podem chegar diretamente para você.<br /><br />'
                           . 'Obrigado por fazer parte do '.nome_servidor().'! 🥷';
                    try {
                        $sm = $conexao->prepare("INSERT INTO mensagens (origem, destino, assunto, msg, data, status) VALUES (0, ?, ?, ?, CURRENT_TIMESTAMP, 'naolido')");
                        $sm->execute([$dest['id'], $assunto, $corpo]);
                    } catch(Exception $e) {}
                    adm_log($conexao, $autor_id, $autor_nome, 'Presente a Criador', $dest['id'], $dest['usuario'], $log_alvo_extra);
                    return $detalhe_msg;
                }
            }

            // AÇÃO: Excluir log de referral por data (somente datas com mais de 30 dias)
            if(isset($_GET['del_ref_date'], $_GET['logs'])){
                $cid_del = (int)$_GET['logs'];
                $data_del = (string)$_GET['del_ref_date'];
                if(preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_del)){
                    $limite30 = date('Y-m-d', strtotime('-30 days'));
                    if($data_del < $limite30){
                        try {
                            $delr = $conexao->prepare("DELETE FROM criador_refs WHERE criador_id=? AND DATE(data)=?");
                            $delr->execute([$cid_del, $data_del]);
                            $stq = $conexao->prepare("SELECT usuario FROM usuarios WHERE id=?");
                            $stq->execute([$cid_del]);
                            $rowQ = $stq->fetch(PDO::FETCH_ASSOC);
                            adm_log($conexao, $autor_id, $autor_nome, 'Limpar Log Refs Criador', $cid_del, $rowQ['usuario'] ?? '?', 'Data: '.$data_del);
                            $cri_msg = '<div class="aviso" style="background:#1a3d1a;border-color:#4caf50;color:#4caf50;">Logs de <b>'.htmlspecialchars($data_del).'</b> apagados.</div>';
                        } catch(Exception $e) {}
                    } else {
                        $cri_msg = '<div class="aviso" style="background:#3d1a1a;border-color:#fff;color:#fff;">Só é possível apagar logs com mais de 30 dias.</div>';
                    }
                }
            }

            // AÇÃO: Enviar presente (modo único, busca por nome ou todos)
            if(isset($_POST['enviar_presente'])){
                $modo = (string)($_POST['modo_envio'] ?? 'unico');
                $tipo = (string)($_POST['tipo'] ?? '');
                $qtd  = max(1, (int)($_POST['qtd'] ?? 0));
                if($qtd > 1000) $qtd = 1000;

                // Cache de itens para evitar lookups repetidos
                $itens_cache_send = [];
                try {
                    foreach($conexao->query("SELECT id, nome FROM table_usaveis")->fetchAll(PDO::FETCH_ASSOC) as $itc){
                        $itens_cache_send[(int)$itc['id']] = $itc['nome'];
                    }
                } catch(Exception $e) {}

                // Em modo "todos" o admin pode selecionar vários tipos para enviar de uma vez
                $tipos_multi = [];
                if($modo === 'todos' && !empty($_POST['tipos']) && is_array($_POST['tipos'])){
                    foreach($_POST['tipos'] as $tt){
                        $tt = (string)$tt;
                        if($tt !== '' && !in_array($tt, $tipos_multi, true)) $tipos_multi[] = $tt;
                    }
                }
                if(empty($tipos_multi) && $tipo !== '') $tipos_multi = [$tipo];

                $alvos = [];
                if($modo === 'todos'){
                    try {
                        $alvos = $conexao->query("SELECT id, usuario FROM usuarios WHERE criador_conteudo = 1 ORDER BY usuario ASC")->fetchAll(PDO::FETCH_ASSOC);
                    } catch(Exception $e) { $alvos = []; }
                } elseif($modo === 'busca'){
                    $busca_nome = trim((string)($_POST['busca_nome'] ?? ''));
                    if($busca_nome !== ''){
                        $stB = $conexao->prepare("SELECT id, usuario FROM usuarios WHERE criador_conteudo = 1 AND LOWER(usuario) LIKE LOWER(?) ORDER BY usuario LIMIT 1");
                        $stB->execute(['%'.$busca_nome.'%']);
                        $rowB = $stB->fetch(PDO::FETCH_ASSOC);
                        if($rowB) $alvos = [$rowB];
                    }
                } else {
                    // unico
                    $dest_id = (int)($_POST['dest_id'] ?? 0);
                    $stt = $conexao->prepare("SELECT id, usuario FROM usuarios WHERE id=? AND criador_conteudo=1");
                    $stt->execute([$dest_id]);
                    $rowU = $stt->fetch(PDO::FETCH_ASSOC);
                    if($rowU) $alvos = [$rowU];
                }

                if(empty($alvos)){
                    $cri_msg = '<div class="aviso" style="background:#3d1a1a;border-color:#fff;color:#fff;">Nenhum Criador de Conteúdo válido encontrado para este envio.</div>';
                } elseif(empty($tipos_multi)){
                    $cri_msg = '<div class="aviso" style="background:#3d1a1a;border-color:#fff;color:#fff;">Selecione o tipo de presente.</div>';
                } else {
                    $total_entregas = 0;
                    $detalhes_envio = [];
                    foreach($alvos as $alvoX){
                        foreach($tipos_multi as $tipo_um){
                            $r = cri_entregar_presente($conexao, $autor_id, $autor_nome, $alvoX, $tipo_um, $qtd, $itens_cache_send);
                            if($r !== false){
                                $total_entregas++;
                                if(!isset($detalhes_envio[$tipo_um])) $detalhes_envio[$tipo_um] = $r;
                            }
                        }
                    }
                    if($total_entregas > 0){
                        $lista_itens = implode(' + ', $detalhes_envio);
                        if(count($alvos) > 1){
                            $cri_msg = '<div class="aviso" style="background:#1a3d1a;border-color:#4caf50;color:#4caf50;">Enviado <b>'.count($tipos_multi).' tipo(s)</b> de presente ('.$lista_itens.') para <b>'.count($alvos).' criador(es)</b> — total de '.$total_entregas.' entrega(s)!</div>';
                        } else {
                            $cri_msg = '<div class="aviso" style="background:#1a3d1a;border-color:#4caf50;color:#4caf50;">Presente(s) '.$lista_itens.' enviado(s) para <b>'.htmlspecialchars($alvos[0]['usuario']).'</b>!</div>';
                        }
                    } else {
                        $cri_msg = '<div class="aviso" style="background:#3d1a1a;border-color:#fff;color:#fff;">Tipo de presente inválido.</div>';
                    }
                }
            }

            if($cri_msg) echo $cri_msg;

            // Buscar criadores ativos
            try {
                $criadores = $conexao->query("SELECT id, usuario, nivel, vila, config_youtube, config_okyoutube, ref_link FROM usuarios WHERE criador_conteudo = 1 ORDER BY nivel DESC, usuario ASC")->fetchAll(PDO::FETCH_ASSOC);
                // Garantir ref_link para criadores antigos que ainda não têm
                foreach($criadores as &$crFix){
                    if(empty($crFix['ref_link'])){
                        $novo_slug_fix = cri_gerar_ref_link($conexao, $crFix['usuario']);
                        $upd_fix = $conexao->prepare("UPDATE usuarios SET ref_link=? WHERE id=?");
                        $upd_fix->execute([$novo_slug_fix, $crFix['id']]);
                        $crFix['ref_link'] = $novo_slug_fix;
                    }
                }
                unset($crFix);
                // URL pública do site (para montar o link de referência)
                $site_url_ref = '';
                try {
                    $stU = $conexao->prepare("SELECT valor FROM configuracoes WHERE nome='site_url' LIMIT 1");
                    $stU->execute();
                    $rowU = $stU->fetch(PDO::FETCH_ASSOC);
                    if($rowU) $site_url_ref = rtrim($rowU['valor'], '/');
                } catch(Exception $e) {}
                if($site_url_ref === '') $site_url_ref = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https://' : 'http://').($_SERVER['HTTP_HOST'] ?? '');
            } catch(Exception $e){ $criadores = []; }

            // Buscar itens disponíveis para presente
            try {
                $itens_presente = $conexao->query("SELECT id, nome, categoria FROM table_usaveis WHERE categoria IN ('cristal','cristal_buff','cristal_craft') ORDER BY categoria, id")->fetchAll(PDO::FETCH_ASSOC);
            } catch(Exception $e){ $itens_presente = []; }

            // Busca de jogadores
            $cri_search = trim($_GET['search'] ?? '');
            $busca = [];
            if($cri_search !== ''){
                $stb = $conexao->prepare("SELECT id, usuario, nivel, criador_conteudo FROM usuarios WHERE LOWER(usuario) LIKE LOWER(?) ORDER BY usuario LIMIT 30");
                $stb->execute(['%'.$cri_search.'%']);
                $busca = $stb->fetchAll(PDO::FETCH_ASSOC);
            }
            ?>

            <fieldset style="border:1px solid #FF4444;background:#1a0505;margin-bottom:14px;">
                <legend style="color:#fff;">🎬 Gerenciar Criadores</legend>
                <form method="GET" style="margin-bottom:10px;">
                    <input type="hidden" name="p" value="adm" />
                    <input type="hidden" name="modulo" value="criadores" />
                    <input type="text" name="search" value="<?php echo htmlspecialchars($cri_search); ?>" placeholder="Buscar jogador por nome..." style="width:260px;padding:4px;background:#222;color:#eee;border:1px solid #555;" />
                    <button type="submit" class="botao">🔍 Buscar</button>
                    <?php if($cri_search !== ''): ?><a href="?p=adm&modulo=criadores" class="botao">✖ Limpar</a><?php endif; ?>
                </form>

                <?php if($cri_search !== ''): ?>
                    <div style="color:#FFD700;font-weight:bold;margin:8px 0 4px;">Resultados da busca: <?php echo count($busca); ?></div>
                    <table width="100%" cellpadding="4" cellspacing="0" style="background:#0f0f0f;border:1px solid #333;font-size:11px;">
                        <tr style="background:#222;color:#FFD700;"><th align="left" style="padding:5px;">Jogador</th><th>Nível</th><th>Status</th><th>Ação</th></tr>
                        <?php foreach($busca as $bp): ?>
                        <tr style="border-top:1px solid #222;">
                            <td style="padding:5px;"><a href="?p=view&view=<?php echo strtolower($bp['usuario']); ?>" target="_blank" style="color:#87CEFA;"><?php echo htmlspecialchars($bp['usuario']); ?></a></td>
                            <td align="center">Nv. <?php echo (int)$bp['nivel']; ?></td>
                            <td align="center"><?php if($bp['criador_conteudo']) echo '<span style="color:#fff;">🎬 Criador</span>'; else echo '<span style="color:#888;">—</span>'; ?></td>
                            <td align="center">
                                <a href="?p=adm&modulo=criadores&toggle_criador=<?php echo (int)$bp['id']; ?>&search=<?php echo urlencode($cri_search); ?>" class="botao" style="font-size:10px;padding:3px 8px;<?php if($bp['criador_conteudo']) echo 'color:#fff;border-color:#fff;'; else echo 'color:#4caf50;border-color:#4caf50;'; ?>" onclick="return confirm('<?php echo $bp['criador_conteudo'] ? 'Remover status de Criador de '.addslashes($bp['usuario']).'?' : 'Tornar '.addslashes($bp['usuario']).' Criador de Conteúdo?'; ?>');">
                                    <?php echo $bp['criador_conteudo'] ? '➖ Remover' : '➕ Tornar Criador'; ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($busca)): ?>
                        <tr><td colspan="4" align="center" style="padding:10px;color:#888;">Nenhum jogador encontrado.</td></tr>
                        <?php endif; ?>
                    </table>
                    <div style="margin-top:14px;border-top:1px dashed #333;padding-top:10px;"></div>
                <?php endif; ?>

                <div style="color:#FFD700;font-weight:bold;margin:8px 0 4px;">Criadores ativos: <?php echo count($criadores); ?></div>
                <?php if(empty($criadores)): ?>
                    <div style="color:#888;padding:10px;">Nenhum criador cadastrado. Use a busca acima para promover jogadores.</div>
                <?php else: ?>
                <table width="100%" cellpadding="4" cellspacing="0" style="background:#0f0f0f;border:1px solid #333;font-size:11px;">
                    <tr style="background:#222;color:#FFD700;"><th align="left" style="padding:5px;">Jogador</th><th>Nível</th><th>YouTube</th><th>Link de Referência</th><th>Ações</th></tr>
                    <?php foreach($criadores as $cr): $ref_url_cr = $site_url_ref.'/?p=reg&nlink='.urlencode($cr['ref_link']); ?>
                    <tr style="border-top:1px solid #222;">
                        <td style="padding:5px;"><a href="?p=view&view=<?php echo strtolower($cr['usuario']); ?>" target="_blank" style="color:#87CEFA;">🎬 <?php echo htmlspecialchars($cr['usuario']); ?></a></td>
                        <td align="center">Nv. <?php echo (int)$cr['nivel']; ?></td>
                        <td align="center" style="font-size:10px;">
                            <?php if($cr['config_youtube'] !== '' && $cr['config_okyoutube'] === 'sim'): ?>
                                <span style="color:#4caf50;">✓ Ativo</span>
                            <?php else: ?>
                                <span style="color:#888;">— sem canal</span>
                            <?php endif; ?>
                        </td>
                        <td align="center" style="font-size:10px;">
                            <input type="text" readonly value="<?php echo htmlspecialchars($ref_url_cr); ?>" onclick="this.select();" style="width:220px;background:#222;color:#FFD700;border:1px solid #555;padding:2px 4px;font-size:10px;" title="Link de referência personalizado" />
                        </td>
                        <td align="center" style="white-space:nowrap;">
                            <button type="button" class="botao" style="font-size:10px;padding:3px 8px;color:#FFD700;border-color:#FFD700;" onclick="(function(){var t='<?php echo addslashes($ref_url_cr); ?>';if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(t);}else{var i=document.createElement('input');i.value=t;document.body.appendChild(i);i.select();document.execCommand('copy');i.remove();}alert('Link copiado!');})();">🔗 Link</button>
                            <a href="?p=adm&modulo=criadores&logs=<?php echo (int)$cr['id']; ?>" class="botao" style="font-size:10px;padding:3px 8px;color:#87CEFA;border-color:#87CEFA;">📊 Logs</a>
                            <a href="?p=adm&modulo=criadores&toggle_criador=<?php echo (int)$cr['id']; ?>" class="botao" style="font-size:10px;padding:3px 8px;color:#fff;border-color:#fff;" onclick="return confirm('Remover status de Criador de <?php echo addslashes($cr['usuario']); ?>?');">➖ Remover</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
            </fieldset>

            <?php
            // Visualização de Logs de Referrals do criador
            if(isset($_GET['logs'])):
                $log_cid = (int)$_GET['logs'];
                $stCri = $conexao->prepare("SELECT id, usuario, ref_link FROM usuarios WHERE id=? AND criador_conteudo=1");
                $stCri->execute([$log_cid]);
                $criLog = $stCri->fetch(PDO::FETCH_ASSOC);
                if($criLog):
                    $stRefs = $conexao->prepare("SELECT DATE(data) AS dia, COUNT(*) AS total, GROUP_CONCAT(novo_usuario_nome, ', ') AS jogadores FROM criador_refs WHERE criador_id=? GROUP BY DATE(data) ORDER BY dia DESC");
                    $stRefs->execute([$log_cid]);
                    $refs_por_dia = $stRefs->fetchAll(PDO::FETCH_ASSOC);
                    $stTotalRefs = $conexao->prepare("SELECT COUNT(*) FROM criador_refs WHERE criador_id=?");
                    $stTotalRefs->execute([$log_cid]);
                    $total_refs = (int)$stTotalRefs->fetchColumn();
                    $limite_30d = date('Y-m-d', strtotime('-30 days'));
            ?>
            <fieldset style="border:1px solid #87CEFA;background:#0a1520;margin-bottom:14px;">
                <legend style="color:#87CEFA;">📊 Logs de Referrals — 🎬 <?php echo htmlspecialchars($criLog['usuario']); ?></legend>
                <div style="color:#FFD700;margin-bottom:8px;">
                    Total de jogadores que entraram pelo link deste criador: <b><?php echo $total_refs; ?></b><br />
                    <span style="color:#888;font-size:10px;">Datas com mais de 30 dias podem ser apagadas (limite atual: <?php echo $limite_30d; ?>).</span>
                </div>
                <?php if(empty($refs_por_dia)): ?>
                    <div style="color:#888;padding:10px;">Nenhum jogador foi registrado pelo link deste criador ainda.</div>
                <?php else: ?>
                <table width="100%" cellpadding="4" cellspacing="0" style="background:#0f0f0f;border:1px solid #333;font-size:11px;">
                    <tr style="background:#222;color:#FFD700;"><th align="left" style="padding:5px;">Data</th><th>Cadastros</th><th align="left">Jogadores</th><th>Ação</th></tr>
                    <?php foreach($refs_por_dia as $rd): $pode_apagar = ($rd['dia'] < $limite_30d); ?>
                    <tr style="border-top:1px solid #222;">
                        <td style="padding:5px;"><?php echo htmlspecialchars($rd['dia']); ?></td>
                        <td align="center"><b style="color:#4caf50;"><?php echo (int)$rd['total']; ?></b></td>
                        <td style="padding:5px;color:#ccc;font-size:10px;"><?php echo htmlspecialchars($rd['jogadores']); ?></td>
                        <td align="center">
                            <?php if($pode_apagar): ?>
                            <a href="?p=adm&modulo=criadores&logs=<?php echo $log_cid; ?>&del_ref_date=<?php echo urlencode($rd['dia']); ?>" class="botao" style="font-size:10px;padding:3px 8px;color:#fff;border-color:#fff;" onclick="return confirm('Apagar logs do dia <?php echo $rd['dia']; ?>?');">🗑 Apagar</a>
                            <?php else: ?>
                            <span style="color:#666;font-size:10px;">— ainda 30 dias</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php endif; ?>
                <div style="margin-top:10px;"><a href="?p=adm&modulo=criadores" class="botao">⬅ Fechar Logs</a></div>
            </fieldset>
            <?php endif; endif; ?>

            <fieldset style="border:1px solid #FFD700;background:#1a1500;">
                <legend style="color:#FFD700;">🎁 Enviar Presente de Parceria</legend>
                <?php if(empty($criadores)): ?>
                    <div style="color:#888;padding:10px;">Promova ao menos um Criador para poder enviar presentes.</div>
                <?php else: ?>
                <form method="POST" action="?p=adm&modulo=criadores" onsubmit="return confirm('Confirma o envio do presente?');">
                    <input type="hidden" name="enviar_presente" value="1" />

                    <div style="display:flex;gap:6px;border-bottom:1px solid #333;margin-bottom:10px;">
                        <button type="button" class="cri-tab botao" data-tab="unico" style="border-color:#FFD700;color:#FFD700;font-weight:bold;border-radius:4px 4px 0 0;">👤 Criador específico</button>
                        <button type="button" class="cri-tab botao" data-tab="busca" style="border-color:#87CEFA;color:#87CEFA;border-radius:4px 4px 0 0;">🔍 Buscar por nome</button>
                        <button type="button" class="cri-tab botao" data-tab="todos" style="border-color:#fff;color:#fff;border-radius:4px 4px 0 0;">📢 Enviar para TODOS</button>
                    </div>
                    <input type="hidden" name="modo_envio" id="cri_modo_envio" value="unico" />

                    <table cellpadding="6" cellspacing="0" style="font-size:12px;">
                        <tr class="cri-row-unico">
                            <td style="color:#FFD700;font-weight:bold;">Destinatário (lista):</td>
                            <td>
                                <select name="dest_id" style="background:#222;color:#eee;border:1px solid #555;padding:4px;min-width:240px;">
                                    <option value="">— Selecione um Criador —</option>
                                    <?php foreach($criadores as $cr): ?>
                                        <option value="<?php echo (int)$cr['id']; ?>">🎬 <?php echo htmlspecialchars($cr['usuario']); ?> (Nv. <?php echo (int)$cr['nivel']; ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <span style="color:#888;font-size:10px;">Para enviar a um único criador.</span>
                            </td>
                        </tr>
                        <tr class="cri-row-busca" style="display:none;">
                            <td style="color:#87CEFA;font-weight:bold;">Nome do Criador:</td>
                            <td>
                                <input type="text" name="busca_nome" placeholder="ex: anubisbr" style="background:#222;color:#eee;border:1px solid #87CEFA;padding:4px;min-width:240px;" />
                                <span style="color:#888;font-size:10px;">Use para enviar um brinde extra ao criador que mais se destacou.</span>
                            </td>
                        </tr>
                        <tr class="cri-row-todos" style="display:none;">
                            <td style="color:#fff;font-weight:bold;">Destinatários:</td>
                            <td style="color:#FFD700;">
                                📢 Enviar para <b><?php echo count($criadores); ?></b> Criador(es) de Conteúdo de uma só vez.<br />
                                <span style="color:#888;font-size:10px;">Cada um receberá a quantidade abaixo + a mensagem em anexo sobre a parceria.</span>
                            </td>
                        </tr>
                        <tr class="cri-row-tipo-unico">
                            <td style="color:#FFD700;font-weight:bold;">Tipo de Presente:</td>
                            <td>
                                <select name="tipo" style="background:#222;color:#eee;border:1px solid #555;padding:4px;min-width:240px;">
                                    <option value="">— Selecione —</option>
                                    <option value="yens">💰 Yens</option>
                                    <optgroup label="💎 Cristais de Buff (atributos +5%)">
                                        <?php foreach($itens_presente as $it): if($it['categoria']==='cristal_buff'): ?>
                                            <option value="item_<?php echo (int)$it['id']; ?>"><?php echo htmlspecialchars($it['nome']); ?></option>
                                        <?php endif; endforeach; ?>
                                    </optgroup>
                                    <optgroup label="⚒️ Cristais de Aprimoramento">
                                        <?php foreach($itens_presente as $it): if($it['categoria']==='cristal'): ?>
                                            <option value="item_<?php echo (int)$it['id']; ?>"><?php echo htmlspecialchars($it['nome']); ?></option>
                                        <?php endif; endforeach; ?>
                                    </optgroup>
                                    <optgroup label="⚙️ Cristais de Craft">
                                        <?php foreach($itens_presente as $it): if($it['categoria']==='cristal_craft'): ?>
                                            <option value="item_<?php echo (int)$it['id']; ?>"><?php echo htmlspecialchars($it['nome']); ?></option>
                                        <?php endif; endforeach; ?>
                                    </optgroup>
                                </select>
                            </td>
                        </tr>
                        <tr class="cri-row-tipo-multi" style="display:none;">
                            <td style="color:#FFD700;font-weight:bold;vertical-align:top;">Itens p/ enviar:</td>
                            <td>
                                <div style="background:#161616;border:1px solid #555;padding:8px;max-height:240px;overflow-y:auto;max-width:520px;">
                                    <div style="margin-bottom:6px;">
                                        <label style="color:#FFD700;font-size:11px;cursor:pointer;"><input type="checkbox" name="tipos[]" value="yens" /> 💰 Yens</label>
                                    </div>
                                    <?php
                                    $grupos_multi = [
                                        'cristal_buff'  => '💎 Cristais de Buff (atributos +5%)',
                                        'cristal'       => '⚒️ Cristais de Aprimoramento',
                                        'cristal_craft' => '⚙️ Cristais de Craft',
                                    ];
                                    foreach($grupos_multi as $cat => $titulo):
                                        $tem_grp = false;
                                        foreach($itens_presente as $it){ if($it['categoria']===$cat){ $tem_grp = true; break; } }
                                        if(!$tem_grp) continue;
                                    ?>
                                    <div style="border-top:1px dashed #333;margin:6px 0;padding-top:6px;">
                                        <div style="color:#87CEFA;font-size:11px;font-weight:bold;margin-bottom:3px;"><?php echo $titulo; ?></div>
                                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:3px 10px;">
                                            <?php foreach($itens_presente as $it): if($it['categoria']===$cat): ?>
                                                <label style="color:#ddd;font-size:11px;cursor:pointer;"><input type="checkbox" name="tipos[]" value="item_<?php echo (int)$it['id']; ?>" /> <?php echo htmlspecialchars($it['nome']); ?></label>
                                            <?php endif; endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <div style="margin-top:4px;">
                                    <button type="button" class="botao" style="font-size:10px;padding:2px 6px;" onclick="document.querySelectorAll('.cri-row-tipo-multi input[type=checkbox]').forEach(function(c){c.checked=true;});">✓ Marcar todos</button>
                                    <button type="button" class="botao" style="font-size:10px;padding:2px 6px;" onclick="document.querySelectorAll('.cri-row-tipo-multi input[type=checkbox]').forEach(function(c){c.checked=false;});">✗ Desmarcar</button>
                                    <span style="color:#888;font-size:10px;">Selecione um ou vários itens — cada criador receberá uma mensagem por item.</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="color:#FFD700;font-weight:bold;">Quantidade:</td>
                            <td>
                                <input type="number" name="qtd" value="1" min="1" max="1000" required style="background:#222;color:#eee;border:1px solid #555;padding:4px;width:100px;" />
                                <span style="color:#888;font-size:10px;">(yens: 1 a 1000 unidades; itens: 1 a 1000 cópias)</span>
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td><button type="submit" class="botao" style="background:#cc0000;color:#fff;border-color:#fff;font-weight:bold;">🎁 Enviar Presente</button></td>
                        </tr>
                    </table>
                </form>
                <script>
                (function(){
                    var tabs = document.querySelectorAll('.cri-tab');
                    var hiddenInput = document.getElementById('cri_modo_envio');
                    function showTab(name){
                        hiddenInput.value = name;
                        document.querySelectorAll('.cri-row-unico,.cri-row-busca,.cri-row-todos').forEach(function(r){ r.style.display='none'; });
                        document.querySelectorAll('.cri-row-'+name).forEach(function(r){ r.style.display=''; });
                        // Em modo "todos" mostra a seleção múltipla; nos outros, o select único
                        var rowUnico = document.querySelector('.cri-row-tipo-unico');
                        var rowMulti = document.querySelector('.cri-row-tipo-multi');
                        if(name === 'todos'){
                            if(rowUnico) rowUnico.style.display = 'none';
                            if(rowMulti) rowMulti.style.display = '';
                        } else {
                            if(rowUnico) rowUnico.style.display = '';
                            if(rowMulti) rowMulti.style.display = 'none';
                        }
                        tabs.forEach(function(t){
                            if(t.getAttribute('data-tab')===name){ t.style.background='#332200'; t.style.fontWeight='bold'; }
                            else { t.style.background=''; t.style.fontWeight='normal'; }
                        });
                    }
                    tabs.forEach(function(t){ t.addEventListener('click', function(){ showTab(t.getAttribute('data-tab')); }); });
                    showTab('unico');
                })();
                </script>
                <div style="margin-top:10px;color:#888;font-size:10px;">
                    💡 O(s) criador(es) receberá(ão) uma <b>mensagem em anexo</b> falando sobre a parceria e detalhando o presente recebido.<br />
                    Todas as ações ficam registradas nos Logs Administrativos.
                </div>
                <?php endif; ?>
            </fieldset>

            <div style="margin-top:14px;">
                <a href="?p=adm" class="botao">⬅ Voltar</a>
            </div>
            </div>

        <?php elseif($modulo == 'admin_logs' && $is_admin): ?>
            <div style="background:#1a0a00; border-left:4px solid #FFD700; border-bottom:1px solid #444; padding:7px 12px; font-weight:bold; color:#FFD700; font-size:13px; margin-bottom:8px;">📋 Logs de Ações Administrativas</div>
            <div style="background:#111; border-left:1px solid #333; border-right:1px solid #333; padding:12px;">
            <?php
            // Filtros
            $log_page = max(1, (int)($_GET['log_page'] ?? 1));
            $log_per  = 30;
            $log_off  = ($log_page - 1) * $log_per;
            $log_acao = trim($_GET['log_acao'] ?? '');
            $log_autor = trim($_GET['log_autor'] ?? '');
            $log_alvo  = trim($_GET['log_alvo'] ?? '');

            $lw = []; $lp = [];
            if($log_acao)  { $lw[] = "acao LIKE ?";       $lp[] = "%$log_acao%"; }
            if($log_autor) { $lw[] = "autor_nome LIKE ?";  $lp[] = "%$log_autor%"; }
            if($log_alvo)  { $lw[] = "alvo_nome LIKE ?";  $lp[] = "%$log_alvo%"; }
            $lw_str = $lw ? "WHERE " . implode(" AND ", $lw) : "";

            $lc_stmt = $conexao->prepare("SELECT COUNT(*) FROM admin_logs $lw_str");
            $lc_stmt->execute($lp);
            $log_total = (int)$lc_stmt->fetchColumn();
            $log_pages = ceil($log_total / $log_per);

            $lp_list = array_merge($lp, [$log_per, $log_off]);
            $ll_stmt = $conexao->prepare("SELECT * FROM admin_logs $lw_str ORDER BY id DESC LIMIT ? OFFSET ?");
            $ll_stmt->execute($lp_list);
            $logs = $ll_stmt->fetchAll(PDO::FETCH_ASSOC);

            $acao_cores = [
                'Ban' => '#ff4444', 'Desban' => '#4CAF50', 'Editar Ban' => '#ff9800',
                'Editar Conta' => '#87CEFA', 'Alterar Cargo' => '#FFD700',
                'VIP' => '#9b59b6', 'Manutenção' => '#e67e22',
            ];
            ?>
            <!-- Filtros -->
            <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
                <input type="hidden" name="p" value="adm">
                <input type="hidden" name="modulo" value="admin_logs">
                <input type="text" name="log_acao" value="<?php echo htmlspecialchars($log_acao); ?>" placeholder="Filtrar por ação..." class="input" style="width:140px;">
                <input type="text" name="log_autor" value="<?php echo htmlspecialchars($log_autor); ?>" placeholder="Filtrar por autor..." class="input" style="width:140px;">
                <input type="text" name="log_alvo" value="<?php echo htmlspecialchars($log_alvo); ?>" placeholder="Filtrar por alvo..." class="input" style="width:140px;">
                <button type="submit" class="botao">🔍 Filtrar</button>
                <a href="?p=adm&modulo=admin_logs" class="botao" style="text-decoration:none;">✖ Limpar</a>
            </form>
            <div style="color:#888;font-size:11px;margin-bottom:8px;">Total: <?php echo $log_total; ?> registro(s)</div>
            <?php if(empty($logs)): ?>
                <div style="color:#888;text-align:center;padding:20px;">Nenhum log encontrado.</div>
            <?php else: ?>
            <div style="overflow-x:auto;">
            <table class="adm-table" style="font-size:11px;">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Data/Hora</th>
                        <th>Autor</th>
                        <th>Ação</th>
                        <th>Alvo</th>
                        <th>Detalhes</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($logs as $log):
                    $cor = '#aaa';
                    foreach($acao_cores as $k => $c) { if(stripos($log['acao'], $k) !== false) { $cor = $c; break; } }
                ?>
                    <tr>
                        <td style="color:#555;"><?php echo $log['id']; ?></td>
                        <td style="white-space:nowrap;color:#888;"><?php echo date('d/m/Y H:i:s', strtotime($log['data_hora'])); ?></td>
                        <td style="color:#FFD700;font-weight:bold;"><?php echo htmlspecialchars($log['autor_nome']); ?></td>
                        <td><span style="color:<?php echo $cor; ?>;font-weight:bold;"><?php echo htmlspecialchars($log['acao']); ?></span></td>
                        <td style="color:#ccc;"><?php echo $log['alvo_nome'] ? htmlspecialchars($log['alvo_nome']) . ' <small style="color:#555;">#' . $log['alvo_id'] . '</small>' : '—'; ?></td>
                        <td style="color:#999;font-size:10px;"><?php echo htmlspecialchars($log['detalhes'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php if($log_pages > 1): ?>
                <div style="text-align:center;margin:10px 0;color:#ccc;">
                    <?php for($i = 1; $i <= $log_pages; $i++): ?>
                        <?php if($i == $log_page): ?>
                            <strong style="color:#FF9800;"><?php echo $i; ?></strong>
                        <?php else: ?>
                            <a href="?p=adm&modulo=admin_logs&log_page=<?php echo $i; ?>&log_acao=<?php echo urlencode($log_acao); ?>&log_autor=<?php echo urlencode($log_autor); ?>&log_alvo=<?php echo urlencode($log_alvo); ?>" style="color:#ccc;"><?php echo $i; ?></a>
                        <?php endif; ?>
                        <?php if($i < $log_pages) echo " | "; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
            <?php endif; ?>
            </div>
            <div style="background:#1a0a00; border-left:1px solid #333; border-right:1px solid #333; border-bottom:2px solid #FFD700; height:8px;"></div>

        <?php else: ?>
            <div class="aviso" style="text-align:center;">
                <strong>❌ Acesso Negado.</strong> Você não tem permissão para acessar este módulo.
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>
<div class="box_bottom"></div>


<!-- Modal de Ban -->
<div id="banModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:1000;align-items:center;justify-content:center;">
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#222;border:1px solid #ff6600;padding:20px;border-radius:5px;max-width:400px;width:90%;color:#ddd;">
        <h3 style="color:#FF9800;margin-top:0;">🔨 Banir Usuário</h3>
        <form method="POST" id="banForm">
            <input type="hidden" name="action" value="ban_user">
            <input type="hidden" name="user_id" id="ban_user_id">
            <p><strong>Usuário:</strong> <span id="ban_username" style="color:#FF9800;"></span></p>
            <label style="display:block;margin-bottom:10px;">Dias de ban:
                <select name="ban_days" required style="margin-left:8px;">
                    <option value="1">1 dia</option>
                    <option value="3">3 dias</option>
                    <option value="7">7 dias</option>
                    <option value="15">15 dias</option>
                    <option value="30">30 dias</option>
                    <option value="90">90 dias</option>
                    <option value="365">365 dias</option>
                    <option value="3650">Eterno</option>
                </select>
            </label>
            <label style="display:block;margin-bottom:10px;">Motivo:<br>
                <textarea name="ban_motivo" required style="width:100%;height:70px;box-sizing:border-box;margin-top:4px;" placeholder="Motivo do banimento..."></textarea>
            </label>
            <button type="submit" class="botao btn-danger">🔨 Confirmar Ban</button>
            <button type="button" onclick="closeBanModal()" class="botao" style="margin-left:8px;">❌ Cancelar</button>
        </form>
    </div>
</div>

<div id="editBanModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:1000;">
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#222;border:1px solid #FF9800;padding:20px;border-radius:5px;max-width:400px;width:90%;color:#ddd;">
        <h3 style="color:#FF9800;margin-top:0;">⏰ Editar Tempo de Ban</h3>
        <form method="POST" id="editBanForm">
            <input type="hidden" name="action" value="edit_ban">
            <input type="hidden" name="user_id" id="edit_ban_user_id">
            <p><strong>Usuário:</strong> <span id="edit_ban_username" style="color:#FF9800;"></span></p>
            <label style="display:block;margin-bottom:10px;">Novo tempo:
                <select name="ban_days" id="edit_ban_days" required style="margin-left:8px;">
                    <option value="1">1 dia</option>
                    <option value="3">3 dias</option>
                    <option value="7">7 dias</option>
                    <option value="15">15 dias</option>
                    <option value="30">30 dias</option>
                    <option value="90">90 dias</option>
                    <option value="365">365 dias</option>
                    <option value="3650">Eterno</option>
                </select>
            </label>
            <label style="display:block;margin-bottom:10px;">Motivo:<br>
                <textarea name="ban_motivo" id="edit_ban_motivo" required style="width:100%;height:70px;box-sizing:border-box;margin-top:4px;" placeholder="Motivo do banimento..."></textarea>
            </label>
            <button type="submit" class="botao btn-success">⏰ Atualizar</button>
            <button type="button" onclick="closeEditBanModal()" class="botao" style="margin-left:8px;">❌ Cancelar</button>
        </form>
    </div>
</div>

<script>
function showBanForm(userId, username) {
    document.getElementById('ban_user_id').value = userId;
    document.getElementById('ban_username').textContent = username;
    document.getElementById('banModal').style.display = 'block';
}

function closeBanModal() {
    document.getElementById('banModal').style.display = 'none';
    // Limpar campos
    document.getElementById('ban_motivo').value = '';
    document.getElementById('ban_days').value = '';
}

function showEditBanForm(userId, username, currentDays, currentMotivo) {
    document.getElementById('edit_ban_user_id').value = userId;
    document.getElementById('edit_ban_username').textContent = username;
    document.getElementById('edit_ban_days').value = currentDays;
    document.getElementById('edit_ban_motivo').value = currentMotivo;
    document.getElementById('editBanModal').style.display = 'block';
}

function closeEditBanModal() {
    document.getElementById('editBanModal').style.display = 'none';
    // Limpar campos
    document.getElementById('edit_ban_motivo').value = '';
    document.getElementById('edit_ban_days').value = '';
}

// Fechar modal ao clicar fora
document.addEventListener('click', function(event) {
    var banModal = document.getElementById('banModal');
    var editBanModal = document.getElementById('editBanModal');

    if (event.target === banModal) {
        closeBanModal();
    }
    if (event.target === editBanModal) {
        closeEditBanModal();
    }
});

// Fechar modal com ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeBanModal();
        closeEditBanModal();
    }
});
</script>

</div> <!-- Fecha conteúdo -->

        </div> <!-- Fecha admin-container inner -->
        </td>
    </tr>
    <tr>
        <td valign="bottom" style="background:url('../_img/border_bottom.jpg') repeat-x bottom; height:10px;">&nbsp;</td>
    </tr>
</table>
</div>
</body>
</html>