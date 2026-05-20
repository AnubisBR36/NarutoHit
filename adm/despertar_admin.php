<?php
require_once('../_inc/conexao.php');
if(session_status() == PHP_SESSION_NONE) session_start();
if(!isset($_SESSION['logado']) && !isset($_SESSION['userid']) && !isset($_SESSION['uid'])){
    header('Location: ../index.php'); exit;
}
$user_id = $_SESSION['logado'] ?? $_SESSION['userid'] ?? $_SESSION['uid'];
try {
    $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $usuario_logado = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(Exception $e){ header('Location: adm.php'); exit; }
require_once('_gm_auth.php');

if(empty($is_admin_ext) && empty($is_mod_ext)){
    echo "<p style='color:red;padding:20px;'>Acesso negado.</p>"; exit;
}

if(!function_exists('adm_log')){
    function adm_log($conexao, $autor_id, $autor_nome, $acao, $alvo_id=null, $alvo_nome=null, $detalhes=null){
        try {
            $conexao->prepare("INSERT INTO admin_logs (autor_id, autor_nome, acao, alvo_id, alvo_nome, detalhes) VALUES (?,?,?,?,?,?)")
                ->execute([$autor_id, $autor_nome, $acao, $alvo_id, $alvo_nome, $detalhes]);
        } catch(Exception $e){}
    }
}

$msg  = '';
$erro = '';

// ─── SALVAR CONFIGURAÇÃO DAS METAS ──────────────────────────────────────────
if(isset($_POST['acao']) && $_POST['acao'] === 'salvar_config' && $is_admin_ext){
    $campos_cfg = [
        'despertar_req_batalhas' => max(1, (int)$_POST['cfg_batalhas']),
        'despertar_req_vitorias' => max(1, (int)$_POST['cfg_vitorias']),
        'despertar_req_missoes'  => max(1, (int)$_POST['cfg_missoes']),
    ];
    $alteracoes = [];
    foreach($campos_cfg as $nome => $novo_val){
        try {
            $old = $conexao->prepare("SELECT valor FROM configuracoes WHERE nome=?");
            $old->execute([$nome]);
            $val_ant = $old->fetchColumn();
            $conexao->prepare("UPDATE configuracoes SET valor=? WHERE nome=?")->execute([$novo_val, $nome]);
            $alteracoes[] = "{$nome}: {$val_ant}→{$novo_val}";
        } catch(PDOException $e){ $erro = "Erro ao salvar config: " . $e->getMessage(); }
    }
    if(!$erro){
        adm_log($conexao, $usuario_logado['id'], $usuario_logado['usuario'], 'despertar_config', null, null, implode(' | ', $alteracoes));
        $msg = "Configurações dos requisitos salvas com sucesso.";
    }
}

// ─── EXPORTAR HISTÓRICO CSV ─────────────────────────────────────────────────
if(isset($_GET['export_csv']) && $_GET['export_csv'] === '1'){
    $filtro_csv = $_GET['filtro_hist'] ?? 'todos';
    $where_csv  = [];
    if($filtro_csv === 'sucesso') $where_csv[] = "h.resultado > 0";
    if($filtro_csv === 'falha')   $where_csv[] = "h.resultado = 0";
    $w_csv = $where_csv ? "WHERE " . implode(" AND ", $where_csv) : "";
    try {
        $rows_csv = $conexao->query("
            SELECT u.usuario, h.resultado, h.batalhas, h.vitorias, h.missoes_longas, h.data
            FROM despertar_historico h
            JOIN usuarios u ON u.id = h.usuario_id
            {$w_csv}
            ORDER BY h.data DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        $dj_nomes_csv = [0=>'Falhou', 1=>'Sharingan', 2=>'Byakugan', 3=>'Rinnegan'];
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="historico_despertar_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
        fputcsv($out, ['Jogador','Resultado','Batalhas','Vitórias','Missões Longas','Data'], ';');
        foreach($rows_csv as $r){
            fputcsv($out, [
                $r['usuario'],
                $dj_nomes_csv[$r['resultado']] ?? '?',
                $r['batalhas'],
                $r['vitorias'],
                $r['missoes_longas'],
                $r['data'],
            ], ';');
        }
        fclose($out);
        exit;
    } catch(PDOException $e){ /* fallthrough */ }
}

// ─── LER METAS ATUAIS ───────────────────────────────────────────────────────
try {
    $cfg_atual = $conexao->query("SELECT nome, valor FROM configuracoes WHERE nome IN ('despertar_req_batalhas','despertar_req_vitorias','despertar_req_missoes')")->fetchAll(PDO::FETCH_KEY_PAIR);
} catch(Exception $e){ $cfg_atual = []; }
$cfg_bat = max(1, (int)($cfg_atual['despertar_req_batalhas'] ?? 100));
$cfg_vit = max(1, (int)($cfg_atual['despertar_req_vitorias'] ?? 10));
$cfg_mis = max(1, (int)($cfg_atual['despertar_req_missoes']  ?? 150));

// ─── HISTÓRICO: PARÂMETROS E QUERY ──────────────────────────────────────────
$filtro_hist  = $_GET['filtro_hist'] ?? 'todos';
$pg_hist      = max(1, (int)($_GET['pg_hist'] ?? 1));
$pp_hist      = 20;
$off_hist     = ($pg_hist - 1) * $pp_hist;
$where_hist   = [];
if($filtro_hist === 'sucesso') $where_hist[] = "h.resultado > 0";
if($filtro_hist === 'falha')   $where_hist[] = "h.resultado = 0";
$w_hist = $where_hist ? "WHERE " . implode(" AND ", $where_hist) : "";
try {
    $total_hist  = (int)$conexao->query("SELECT COUNT(*) FROM despertar_historico h {$w_hist}")->fetchColumn();
    $rows_hist   = $conexao->query("
        SELECT h.id, u.id AS uid, u.usuario, h.resultado, h.batalhas, h.vitorias, h.missoes_longas, h.data
        FROM despertar_historico h
        JOIN usuarios u ON u.id = h.usuario_id
        {$w_hist}
        ORDER BY h.data DESC
        LIMIT {$pp_hist} OFFSET {$off_hist}
    ")->fetchAll(PDO::FETCH_ASSOC);
    $stats_hist  = $conexao->query("
        SELECT
            COUNT(*) AS total,
            SUM(resultado > 0) AS sucessos,
            SUM(resultado = 0) AS falhas,
            SUM(resultado = 1) AS sharingan,
            SUM(resultado = 2) AS byakugan,
            SUM(resultado = 3) AS rinnegan
        FROM despertar_historico
    ")->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e){
    $total_hist = 0; $rows_hist = []; $stats_hist = [];
}
$total_pag_hist = max(1, ceil($total_hist / $pp_hist));

// ─── SALVAR EDIÇÃO ──────────────────────────────────────────────────────────
if(isset($_POST['acao']) && $_POST['acao'] === 'editar'){
    $alvo_id   = (int)$_POST['alvo_id'];
    $batalhas  = max(0, (int)$_POST['batalhas']);
    $vitorias  = max(0, (int)$_POST['vitorias']);
    $missoes   = max(0, (int)$_POST['missoes_longas']);
    $reset_cd  = isset($_POST['reset_cooldown']);
    $reset_dj  = isset($_POST['reset_doujutsu']);

    try {
        $stmt = $conexao->prepare("SELECT usuario, batalhas, vitorias, missoes_longas, doujutsu, doujutsu_proxima_tentativa FROM usuarios WHERE id=?");
        $stmt->execute([$alvo_id]);
        $alvo = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$alvo){ $erro = "Jogador não encontrado."; }
        else {
            $campos = "batalhas=?, vitorias=?, missoes_longas=?";
            $vals   = [$batalhas, $vitorias, $missoes];

            if($reset_cd){
                $campos .= ", doujutsu_proxima_tentativa=NULL, doujutsu_despertar_cooldown=NULL";
            }
            if($reset_dj){
                $campos .= ", doujutsu=0, doujutsu_nivel=0, doujutsu_exp=0, doujutsu_expmax=0, doujutsu_proxima_tentativa=NULL, doujutsu_despertar_cooldown=NULL";
            }
            $vals[] = $alvo_id;
            $conexao->prepare("UPDATE usuarios SET {$campos} WHERE id=?")->execute($vals);

            $det = "batalhas:{$alvo['batalhas']}→{$batalhas}, vitorias:{$alvo['vitorias']}→{$vitorias}, missoes_longas:{$alvo['missoes_longas']}→{$missoes}";
            if($reset_cd) $det .= " | cooldown resetado";
            if($reset_dj) $det .= " | doujutsu removido";
            adm_log($conexao, $usuario_logado['id'], $usuario_logado['usuario'], 'despertar_editar', $alvo_id, $alvo['usuario'], $det);

            $msg = "Dados de <b>" . htmlspecialchars($alvo['usuario']) . "</b> atualizados com sucesso.";
        }
    } catch(PDOException $e){ $erro = "Erro ao salvar: " . $e->getMessage(); }
}

// ─── PARÂMETROS DE LISTAGEM ─────────────────────────────────────────────────
$busca     = trim($_GET['busca'] ?? '');
$filtro    = $_GET['filtro'] ?? 'todos';   // todos | prontos | bloqueados | com_dj
$pagina    = max(1, (int)($_GET['pg'] ?? 1));
$por_pagina = 25;
$offset    = ($pagina - 1) * $por_pagina;
$editar_id = isset($_GET['editar']) ? (int)$_GET['editar'] : 0;

// ─── QUERY ──────────────────────────────────────────────────────────────────
$where = ["nivel >= 20"];
$params = [];

if($busca !== ''){
    $where[] = "usuario LIKE ?";
    $params[] = "%{$busca}%";
}
if($filtro === 'prontos'){
    $where[] = "batalhas >= 100 AND vitorias >= 10 AND missoes_longas >= 150 AND doujutsu = 0";
    $where[] = "(doujutsu_proxima_tentativa IS NULL OR doujutsu_proxima_tentativa <= NOW())";
}
if($filtro === 'bloqueados'){
    $where[] = "doujutsu = 0";
    $where[] = "doujutsu_proxima_tentativa > NOW()";
}
if($filtro === 'com_dj'){
    $where[] = "doujutsu > 0";
}

$sql_where = "WHERE " . implode(" AND ", $where);

try {
    $total = $conexao->prepare("SELECT COUNT(*) FROM usuarios {$sql_where}");
    $total->execute($params);
    $total_rows = (int)$total->fetchColumn();

    $stmt = $conexao->prepare("
        SELECT id, usuario, nivel, batalhas, vitorias, missoes_longas,
               doujutsu, doujutsu_nivel, doujutsu_proxima_tentativa, doujutsu_despertar_cooldown
        FROM usuarios {$sql_where}
        ORDER BY usuario ASC
        LIMIT {$por_pagina} OFFSET {$offset}
    ");
    $stmt->execute($params);
    $jogadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e){
    $jogadores = []; $total_rows = 0;
    $erro = "Erro na consulta: " . $e->getMessage();
}

$total_paginas = max(1, ceil($total_rows / $por_pagina));

// ─── JOGADOR PARA EDIÇÃO ────────────────────────────────────────────────────
$editar_jogador = null;
if($editar_id > 0){
    try {
        $stmtE = $conexao->prepare("SELECT id, usuario, nivel, batalhas, vitorias, missoes_longas, doujutsu, doujutsu_nivel, doujutsu_proxima_tentativa FROM usuarios WHERE id=?");
        $stmtE->execute([$editar_id]);
        $editar_jogador = $stmtE->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e){ $editar_jogador = null; }
}

$dj_nomes = [0=>'Nenhum', 1=>'Sharingan', 2=>'Byakugan', 3=>'Rinnegan'];
$dj_cores  = [0=>'#555',  1=>'#CC2200',   2=>'#5599FF',  3=>'#9933CC'];

require_once('adm_header.php');
?>
<style>
.desp-table { width:100%; border-collapse:collapse; font-size:12px; }
.desp-table th { background:#1a0a00; color:#FF9900; padding:7px 8px; text-align:left; border-bottom:2px solid #4a2a00; white-space:nowrap; }
.desp-table td { padding:6px 8px; border-bottom:1px solid #1a1a1a; vertical-align:middle; }
.desp-table tr:hover td { background:#0f0800; }
.barra-wrap { background:#1a1a1a; border-radius:3px; height:6px; width:90px; display:inline-block; vertical-align:middle; overflow:hidden; }
.barra-fill { height:6px; border-radius:3px; }
.badge-dj { display:inline-block; padding:2px 8px; border-radius:10px; font-size:10px; font-weight:bold; border:1px solid; }
.filtro-btn { display:inline-block; padding:4px 12px; border-radius:12px; font-size:11px; cursor:pointer; text-decoration:none; border:1px solid #333; color:#888; margin-right:4px; }
.filtro-btn.ativo { border-color:#FF9900; color:#FF9900; background:#1a0e00; }
.edit-panel { background:#0f0800; border:1px solid #4a2a00; border-radius:6px; padding:18px 20px; margin-bottom:18px; }
.edit-panel h3 { color:#FF9900; margin:0 0 14px; font-size:14px; }
.edit-field { margin-bottom:12px; }
.edit-field label { display:block; color:#888; font-size:11px; margin-bottom:4px; }
.edit-field input[type=number] { background:#1a1a1a; border:1px solid #333; color:#EEE; padding:6px 10px; border-radius:4px; width:120px; font-size:13px; }
.edit-field input[type=number]:focus { border-color:#FF9900; outline:none; }
.check-label { font-size:12px; color:#AA6633; display:flex; align-items:center; gap:6px; cursor:pointer; }
.btn-salvar { background:linear-gradient(180deg,#8B4500,#4a2000); color:#FFD700; border:1px solid #FF6600; padding:9px 24px; cursor:pointer; border-radius:4px; font-weight:bold; font-size:13px; }
.btn-salvar:hover { background:linear-gradient(180deg,#AA5500,#662200); }
.btn-cancel { background:#111; color:#666; border:1px solid #333; padding:9px 18px; cursor:pointer; border-radius:4px; font-size:13px; margin-left:8px; }
</style>

<div style="padding:14px 18px;">
    <h2 style="color:#FF9900;margin:0 0 4px;font-size:17px;">🩸 Gerenciar Despertar da Linhagem</h2>
    <p style="color:#666;font-size:12px;margin:0 0 16px;">Visualize e edite os contadores de batalhas, vitórias e missões de cada jogador.</p>

    <?php if($msg): ?>
    <div style="background:#0a1500;border:1px solid #336600;color:#88FF88;padding:9px 14px;border-radius:4px;margin-bottom:14px;font-size:13px;"><?php echo $msg; ?></div>
    <?php endif; ?>
    <?php if($erro): ?>
    <div style="background:#150000;border:1px solid #660000;color:#FF8888;padding:9px 14px;border-radius:4px;margin-bottom:14px;font-size:13px;"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <!-- PAINEL DE CONFIGURAÇÃO DAS METAS -->
    <?php if($is_admin_ext): ?>
    <details style="margin-bottom:16px;" <?php if(isset($_POST['acao']) && $_POST['acao']==='salvar_config') echo 'open'; ?>>
        <summary style="cursor:pointer;background:#0f0800;border:1px solid #4a2a00;border-radius:6px;padding:10px 16px;color:#FF9900;font-size:13px;font-weight:bold;list-style:none;display:flex;align-items:center;gap:8px;">
            ⚙️ Configurar Requisitos do Ritual
            <span style="color:#555;font-size:11px;font-weight:normal;margin-left:4px;">(clique para expandir)</span>
        </summary>
        <div style="background:#0a0500;border:1px solid #4a2a00;border-top:none;border-radius:0 0 6px 6px;padding:16px 20px;">
            <p style="color:#666;font-size:12px;margin:0 0 14px;">Altere as metas globais exigidas para iniciar o Ritual de Despertar. A mudança entra em vigor imediatamente para todos os jogadores.</p>
            <form method="POST" action="?modulo=despertar_admin&pg=<?php echo $pagina; ?>&busca=<?php echo urlencode($busca); ?>&filtro=<?php echo $filtro; ?>">
                <input type="hidden" name="acao" value="salvar_config">
                <div style="display:flex;gap:24px;flex-wrap:wrap;align-items:flex-end;">
                    <div class="edit-field">
                        <label>⚔️ Batalhas mínimas</label>
                        <input type="number" name="cfg_batalhas" value="<?php echo $cfg_bat; ?>" min="1" max="99999">
                    </div>
                    <div class="edit-field">
                        <label>🏆 Vitórias mínimas</label>
                        <input type="number" name="cfg_vitorias" value="<?php echo $cfg_vit; ?>" min="1" max="99999">
                    </div>
                    <div class="edit-field">
                        <label>📜 Missões longas (≥10h)</label>
                        <input type="number" name="cfg_missoes" value="<?php echo $cfg_mis; ?>" min="1" max="99999">
                    </div>
                    <div class="edit-field" style="padding-bottom:2px;">
                        <button type="submit" class="btn-salvar" style="padding:8px 22px;">💾 Salvar Metas</button>
                    </div>
                </div>
            </form>
        </div>
    </details>
    <?php endif; ?>

    <!-- PAINEL DE EDIÇÃO -->
    <?php if($editar_jogador): ?>
    <div class="edit-panel">
        <h3>✏️ Editando: <?php echo htmlspecialchars($editar_jogador['usuario']); ?> <span style="color:#555;font-size:11px;">(ID <?php echo $editar_jogador['id']; ?> · Nv. <?php echo $editar_jogador['nivel']; ?>)</span></h3>
        <form method="POST" action="?modulo=despertar_admin&pg=<?php echo $pagina; ?>&busca=<?php echo urlencode($busca); ?>&filtro=<?php echo $filtro; ?>">
            <input type="hidden" name="acao" value="editar">
            <input type="hidden" name="alvo_id" value="<?php echo $editar_jogador['id']; ?>">
            <div style="display:flex;gap:24px;flex-wrap:wrap;">
                <div class="edit-field">
                    <label>⚔️ Batalhas realizadas</label>
                    <input type="number" name="batalhas" value="<?php echo (int)$editar_jogador['batalhas']; ?>" min="0" max="999999">
                    <span style="color:#444;font-size:11px;margin-left:6px;">meta: <?php echo $cfg_bat; ?></span>
                </div>
                <div class="edit-field">
                    <label>🏆 Vitórias em batalha</label>
                    <input type="number" name="vitorias" value="<?php echo (int)$editar_jogador['vitorias']; ?>" min="0" max="999999">
                    <span style="color:#444;font-size:11px;margin-left:6px;">meta: <?php echo $cfg_vit; ?></span>
                </div>
                <div class="edit-field">
                    <label>📜 Missões longas (≥10h)</label>
                    <input type="number" name="missoes_longas" value="<?php echo (int)$editar_jogador['missoes_longas']; ?>" min="0" max="999999">
                    <span style="color:#444;font-size:11px;margin-left:6px;">meta: 150</span>
                </div>
            </div>
            <div style="display:flex;gap:20px;margin-top:8px;flex-wrap:wrap;">
                <?php if(!empty($editar_jogador['doujutsu_proxima_tentativa'])): ?>
                <label class="check-label">
                    <input type="checkbox" name="reset_cooldown" value="1">
                    Remover cooldown de 15 dias
                    <span style="color:#555;font-size:10px;">(bloqueado até <?php echo date('d/m/Y', strtotime($editar_jogador['doujutsu_proxima_tentativa'])); ?>)</span>
                </label>
                <?php endif; ?>
                <?php if($editar_jogador['doujutsu'] > 0): ?>
                <label class="check-label" style="color:#CC3333;">
                    <input type="checkbox" name="reset_doujutsu" value="1">
                    Remover Doujutsu atual
                    <span style="color:#555;font-size:10px;">(<?php echo $dj_nomes[$editar_jogador['doujutsu']] ?? 'desconhecido'; ?> será apagado)</span>
                </label>
                <?php endif; ?>
            </div>
            <div style="margin-top:16px;">
                <button type="submit" class="btn-salvar">💾 Salvar Alterações</button>
                <a href="?modulo=despertar_admin&pg=<?php echo $pagina; ?>&busca=<?php echo urlencode($busca); ?>&filtro=<?php echo $filtro; ?>" class="btn-cancel">Cancelar</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- FILTROS E BUSCA -->
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:12px;">
        <div>
            <?php
            $link_base = "?modulo=despertar_admin&busca=" . urlencode($busca);
            $filtros = ['todos'=>'Todos (Nv.20+)', 'prontos'=>'✦ Prontos p/ ritual', 'bloqueados'=>'⏳ Em cooldown', 'com_dj'=>'👁 Com Doujutsu'];
            foreach($filtros as $k=>$v):
                $ativo = ($filtro === $k) ? ' ativo' : '';
            ?>
            <a href="<?php echo $link_base; ?>&filtro=<?php echo $k; ?>" class="filtro-btn<?php echo $ativo; ?>"><?php echo $v; ?></a>
            <?php endforeach; ?>
        </div>
        <form method="GET" style="display:flex;gap:6px;">
            <input type="hidden" name="modulo" value="despertar_admin">
            <input type="hidden" name="filtro" value="<?php echo htmlspecialchars($filtro); ?>">
            <input type="text" name="busca" value="<?php echo htmlspecialchars($busca); ?>" placeholder="Buscar por nome..." style="background:#1a1a1a;border:1px solid #333;color:#EEE;padding:5px 10px;border-radius:4px;font-size:12px;width:180px;">
            <button type="submit" style="background:#2a1a00;color:#FF9900;border:1px solid #664400;padding:5px 12px;cursor:pointer;border-radius:4px;font-size:12px;">Buscar</button>
            <?php if($busca): ?>
            <a href="?modulo=despertar_admin&filtro=<?php echo $filtro; ?>" style="background:#111;color:#666;border:1px solid #333;padding:5px 10px;cursor:pointer;border-radius:4px;font-size:12px;text-decoration:none;">✕</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- ESTATÍSTICAS RÁPIDAS -->
    <?php
    try {
        $stats = $conexao->query("
            SELECT
                COUNT(*) as total_nv20,
                SUM(CASE WHEN batalhas>=100 AND vitorias>=10 AND missoes_longas>=150 AND doujutsu=0 AND (doujutsu_proxima_tentativa IS NULL OR doujutsu_proxima_tentativa<=NOW()) THEN 1 ELSE 0 END) as prontos,
                SUM(CASE WHEN doujutsu=1 THEN 1 ELSE 0 END) as sharingan,
                SUM(CASE WHEN doujutsu=2 THEN 1 ELSE 0 END) as byakugan,
                SUM(CASE WHEN doujutsu=3 THEN 1 ELSE 0 END) as rinnegan,
                SUM(CASE WHEN doujutsu_proxima_tentativa > NOW() THEN 1 ELSE 0 END) as bloqueados
            FROM usuarios WHERE nivel >= 20
        ")->fetch(PDO::FETCH_ASSOC);
    } catch(Exception $e){ $stats = null; }
    if($stats): ?>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
        <?php
        $cards = [
            ['Elegíveis (Nv.20+)', $stats['total_nv20'], '#666666'],
            ['Prontos p/ ritual',  $stats['prontos'],    '#FF9900'],
            ['Com Sharingan',      $stats['sharingan'],  '#CC2200'],
            ['Com Byakugan',       $stats['byakugan'],   '#5599FF'],
            ['Com Rinnegan',       $stats['rinnegan'],   '#9933CC'],
            ['Em cooldown',        $stats['bloqueados'], '#FF6600'],
        ];
        foreach($cards as $c): ?>
        <div style="background:#0f0800;border:1px solid #2a1a00;border-radius:6px;padding:10px 16px;text-align:center;min-width:100px;">
            <div style="font-size:20px;font-weight:bold;color:<?php echo $c[2]; ?>;"><?php echo (int)$c[1]; ?></div>
            <div style="font-size:10px;color:#555;margin-top:2px;"><?php echo $c[0]; ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- TABELA -->
    <div style="overflow-x:auto;">
    <table class="desp-table">
        <thead>
            <tr>
                <th>Jogador</th>
                <th>Nv.</th>
                <th>Batalhas</th>
                <th>Vitórias</th>
                <th>Missões ≥10h</th>
                <th>Status Ritual</th>
                <th>Doujutsu</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
        <?php if(empty($jogadores)): ?>
        <tr><td colspan="8" style="text-align:center;color:#444;padding:20px;">Nenhum jogador encontrado.</td></tr>
        <?php endif; ?>
        <?php foreach($jogadores as $j):
            $bat = (int)$j['batalhas'];
            $vit = (int)$j['vitorias'];
            $mis = (int)$j['missoes_longas'];
            $ok_bat = $bat >= 100;
            $ok_vit = $vit >= 10;
            $ok_mis = $mis >= 150;
            $em_cd  = !empty($j['doujutsu_proxima_tentativa']) && strtotime($j['doujutsu_proxima_tentativa']) > time();
            $dj     = (int)$j['doujutsu'];
            $pronto = $ok_bat && $ok_vit && $ok_mis && !$em_cd && $dj === 0;
            $editando = ($editar_id === (int)$j['id']);
        ?>
        <tr style="<?php echo $editando ? 'background:#150a00;' : ''; ?>">
            <td>
                <b style="color:#DDD;"><?php echo htmlspecialchars($j['usuario']); ?></b>
                <span style="color:#333;font-size:10px;"> #<?php echo $j['id']; ?></span>
            </td>
            <td style="color:#888;"><?php echo $j['nivel']; ?></td>
            <td>
                <span style="color:<?php echo $ok_bat ? '#66BB66' : '#AA6633'; ?>;"><?php echo $bat; ?>/100</span>
                <div class="barra-wrap"><div class="barra-fill" style="width:<?php echo min(100,round($bat/100*100)); ?>%;background:<?php echo $ok_bat?'#448844':'#664422'; ?>;"></div></div>
            </td>
            <td>
                <span style="color:<?php echo $ok_vit ? '#66BB66' : '#AA6633'; ?>;"><?php echo $vit; ?>/10</span>
                <div class="barra-wrap"><div class="barra-fill" style="width:<?php echo min(100,round($vit/10*100)); ?>%;background:<?php echo $ok_vit?'#448844':'#664422'; ?>;"></div></div>
            </td>
            <td>
                <span style="color:<?php echo $ok_mis ? '#66BB66' : '#AA6633'; ?>;"><?php echo $mis; ?>/150</span>
                <div class="barra-wrap"><div class="barra-fill" style="width:<?php echo min(100,round($mis/150*100)); ?>%;background:<?php echo $ok_mis?'#448844':'#664422'; ?>;"></div></div>
            </td>
            <td>
                <?php if($dj > 0): ?>
                    <span style="color:#555;font-size:11px;">Possui Doujutsu</span>
                <?php elseif($em_cd): ?>
                    <?php $dias_cd = ceil((strtotime($j['doujutsu_proxima_tentativa'])-time())/86400); ?>
                    <span style="color:#FF6600;font-size:11px;">⏳ <?php echo $dias_cd; ?>d de espera</span>
                <?php elseif($pronto): ?>
                    <span style="color:#FF9900;font-size:11px;">✦ Pronto para ritual</span>
                <?php else: ?>
                    <span style="color:#333;font-size:11px;">Em progresso</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if($dj > 0): ?>
                <span class="badge-dj" style="color:<?php echo $dj_cores[$dj]; ?>;border-color:<?php echo $dj_cores[$dj]; ?>;">
                    <?php echo $dj_nomes[$dj] ?? '?'; ?> <?php if($j['doujutsu_nivel']): ?>Nv.<?php echo $j['doujutsu_nivel']; ?><?php endif; ?>
                </span>
                <?php else: ?>
                <span style="color:#333;font-size:11px;">—</span>
                <?php endif; ?>
            </td>
            <td>
                <a href="?modulo=despertar_admin&editar=<?php echo $j['id']; ?>&pg=<?php echo $pagina; ?>&busca=<?php echo urlencode($busca); ?>&filtro=<?php echo $filtro; ?>"
                   style="font-size:11px;color:#FF9900;text-decoration:none;border:1px solid #4a2a00;padding:3px 9px;border-radius:3px;background:#0f0800;">
                    ✏️ Editar
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <!-- PAGINAÇÃO -->
    <?php if($total_paginas > 1): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px;font-size:12px;color:#555;">
        <span>Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?> (<?php echo $total_rows; ?> jogadores)</span>
        <div style="display:flex;gap:4px;">
            <?php
            $lnk = "?modulo=despertar_admin&busca=" . urlencode($busca) . "&filtro={$filtro}";
            if($pagina > 1) echo "<a href='{$lnk}&pg=1' style='color:#FF9900;text-decoration:none;border:1px solid #4a2a00;padding:3px 8px;border-radius:3px;background:#0f0800;'>«</a>";
            if($pagina > 1) echo "<a href='{$lnk}&pg=" . ($pagina-1) . "' style='color:#FF9900;text-decoration:none;border:1px solid #4a2a00;padding:3px 8px;border-radius:3px;background:#0f0800;'>‹</a>";
            $ini = max(1, $pagina-2); $fim = min($total_paginas, $pagina+2);
            for($p=$ini; $p<=$fim; $p++){
                $a = ($p==$pagina) ? "background:#4a2a00;color:#FFD700;" : "background:#0f0800;color:#FF9900;";
                echo "<a href='{$lnk}&pg={$p}' style='text-decoration:none;border:1px solid #4a2a00;padding:3px 8px;border-radius:3px;{$a}'>{$p}</a>";
            }
            if($pagina < $total_paginas) echo "<a href='{$lnk}&pg=" . ($pagina+1) . "' style='color:#FF9900;text-decoration:none;border:1px solid #4a2a00;padding:3px 8px;border-radius:3px;background:#0f0800;'>›</a>";
            if($pagina < $total_paginas) echo "<a href='{$lnk}&pg={$total_paginas}' style='color:#FF9900;text-decoration:none;border:1px solid #4a2a00;padding:3px 8px;border-radius:3px;background:#0f0800;'>»</a>";
            ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- HISTÓRICO DE RITUAIS -->
    <div style="margin-top:28px;border-top:2px solid #2a1a00;padding-top:20px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
            <h3 style="color:#FF9900;margin:0;font-size:15px;">📜 Histórico de Rituais</h3>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <!-- Filtros -->
                <?php
                $lnk_hist = "?modulo=despertar_admin&busca=" . urlencode($busca) . "&filtro={$filtro}&pg={$pagina}";
                $estilos_filt = function($f) use ($filtro_hist){
                    return $f === $filtro_hist
                        ? "display:inline-block;padding:4px 13px;border-radius:12px;font-size:11px;text-decoration:none;border:1px solid #FF9900;color:#FF9900;background:#1a0e00;"
                        : "display:inline-block;padding:4px 13px;border-radius:12px;font-size:11px;text-decoration:none;border:1px solid #333;color:#666;";
                };
                ?>
                <a href="<?php echo $lnk_hist; ?>&filtro_hist=todos" style="<?php echo $estilos_filt('todos'); ?>">Todos</a>
                <a href="<?php echo $lnk_hist; ?>&filtro_hist=sucesso" style="<?php echo $estilos_filt('sucesso'); ?>">Sucesso</a>
                <a href="<?php echo $lnk_hist; ?>&filtro_hist=falha" style="<?php echo $estilos_filt('falha'); ?>">Falha</a>
                <!-- Exportar CSV -->
                <a href="<?php echo $lnk_hist; ?>&filtro_hist=<?php echo $filtro_hist; ?>&export_csv=1"
                   style="display:inline-block;padding:4px 13px;border-radius:12px;font-size:11px;text-decoration:none;border:1px solid #336600;color:#88CC44;background:#0a1000;">
                    ⬇ Exportar CSV
                </a>
            </div>
        </div>

        <!-- Cards de Estatísticas -->
        <?php if(!empty($stats_hist) && (int)$stats_hist['total'] > 0): ?>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
            <?php
            $cards = [
                ['label'=>'Total de rituais', 'val'=>$stats_hist['total'],     'cor'=>'#FF9900'],
                ['label'=>'Sucessos',          'val'=>$stats_hist['sucessos'],  'cor'=>'#88FF88'],
                ['label'=>'Falhas',            'val'=>$stats_hist['falhas'],    'cor'=>'#FF6666'],
                ['label'=>'Sharingan',         'val'=>$stats_hist['sharingan'],'cor'=>'#CC2200'],
                ['label'=>'Byakugan',          'val'=>$stats_hist['byakugan'], 'cor'=>'#5599FF'],
                ['label'=>'Rinnegan',          'val'=>$stats_hist['rinnegan'], 'cor'=>'#9933CC'],
            ];
            foreach($cards as $c):
            ?>
            <div style="background:#0f0800;border:1px solid #2a1a00;border-radius:6px;padding:10px 16px;min-width:90px;text-align:center;">
                <div style="font-size:18px;font-weight:bold;color:<?php echo $c['cor']; ?>;"><?php echo (int)$c['val']; ?></div>
                <div style="font-size:10px;color:#555;margin-top:2px;"><?php echo $c['label']; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Tabela de histórico -->
        <?php if(empty($rows_hist)): ?>
        <p style="color:#444;font-size:13px;text-align:center;padding:24px 0;">Nenhum ritual registrado ainda.</p>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table class="desp-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Jogador</th>
                    <th>Resultado</th>
                    <th>Batalhas</th>
                    <th>Vitórias</th>
                    <th>Missões Longas</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $dj_nomes_h = [0=>'Falhou', 1=>'Sharingan', 2=>'Byakugan', 3=>'Rinnegan'];
            $dj_cores_h = [0=>'#664444', 1=>'#CC2200', 2=>'#5599FF', 3=>'#9933CC'];
            foreach($rows_hist as $i => $h):
                $res_nome = $dj_nomes_h[$h['resultado']] ?? '?';
                $res_cor  = $dj_cores_h[$h['resultado']] ?? '#888';
                $sucesso  = $h['resultado'] > 0;
            ?>
            <tr>
                <td style="color:#333;font-size:11px;"><?php echo ($off_hist + $i + 1); ?></td>
                <td>
                    <a href="?modulo=despertar_admin&editar=<?php echo $h['uid']; ?>"
                       style="color:#FF9900;text-decoration:none;font-size:12px;"><?php echo htmlspecialchars($h['usuario']); ?></a>
                </td>
                <td>
                    <span class="badge-dj" style="color:<?php echo $res_cor; ?>;border-color:<?php echo $res_cor; ?>;">
                        <?php echo $sucesso ? '✦ ' : '✗ '; echo $res_nome; ?>
                    </span>
                </td>
                <td style="color:#888;font-size:12px;"><?php echo (int)$h['batalhas']; ?></td>
                <td style="color:#888;font-size:12px;"><?php echo (int)$h['vitorias']; ?></td>
                <td style="color:#888;font-size:12px;"><?php echo (int)$h['missoes_longas']; ?></td>
                <td style="color:#555;font-size:11px;"><?php echo date('d/m/Y H:i', strtotime($h['data'])); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <!-- Paginação do histórico -->
        <?php if($total_pag_hist > 1): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;font-size:12px;color:#555;">
            <span>Página <?php echo $pg_hist; ?> de <?php echo $total_pag_hist; ?> (<?php echo $total_hist; ?> registros)</span>
            <div style="display:flex;gap:4px;">
                <?php
                $lnk_ph = "?modulo=despertar_admin&busca=" . urlencode($busca) . "&filtro={$filtro}&pg={$pagina}&filtro_hist={$filtro_hist}";
                if($pg_hist > 1) echo "<a href='{$lnk_ph}&pg_hist=1' style='color:#FF9900;text-decoration:none;border:1px solid #4a2a00;padding:3px 8px;border-radius:3px;background:#0f0800;'>«</a>";
                if($pg_hist > 1) echo "<a href='{$lnk_ph}&pg_hist=" . ($pg_hist-1) . "' style='color:#FF9900;text-decoration:none;border:1px solid #4a2a00;padding:3px 8px;border-radius:3px;background:#0f0800;'>‹</a>";
                $ini_h = max(1, $pg_hist-2); $fim_h = min($total_pag_hist, $pg_hist+2);
                for($p=$ini_h; $p<=$fim_h; $p++){
                    $a = ($p==$pg_hist) ? "background:#4a2a00;color:#FFD700;" : "background:#0f0800;color:#FF9900;";
                    echo "<a href='{$lnk_ph}&pg_hist={$p}' style='text-decoration:none;border:1px solid #4a2a00;padding:3px 8px;border-radius:3px;{$a}'>{$p}</a>";
                }
                if($pg_hist < $total_pag_hist) echo "<a href='{$lnk_ph}&pg_hist=" . ($pg_hist+1) . "' style='color:#FF9900;text-decoration:none;border:1px solid #4a2a00;padding:3px 8px;border-radius:3px;background:#0f0800;'>›</a>";
                if($pg_hist < $total_pag_hist) echo "<a href='{$lnk_ph}&pg_hist={$total_pag_hist}' style='color:#FF9900;text-decoration:none;border:1px solid #4a2a00;padding:3px 8px;border-radius:3px;background:#0f0800;'>»</a>";
                ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <div style="margin-top:20px;padding-top:12px;border-top:1px solid #1a1a1a;">
        <a href="adm.php" style="color:#555;font-size:12px;text-decoration:none;">← Voltar ao Painel Admin</a>
    </div>
</div>

<?php require_once('adm_footer.php'); ?>
