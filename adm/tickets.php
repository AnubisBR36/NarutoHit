<?php
require_once('../_inc/conexao.php');
if (session_status() == PHP_SESSION_NONE) session_start();

if(!isset($_SESSION['logado']) && !isset($_SESSION['userid']) && !isset($_SESSION['uid'])) {
    header('Location: ../index.php'); exit;
}
$user_id = $_SESSION['logado'] ?? $_SESSION['userid'] ?? $_SESSION['uid'];
$stmt = $conexao->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$usuario_logado = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$usuario_logado) { header('Location: ../index.php'); exit; }

$modulo_necessario = 'tickets';
require_once('_gm_auth.php');

$adm_nivel = (int)($usuario_logado['adm'] ?? 0);
$is_admin  = ($adm_nivel == 1);

$categorias = [
    'bug'         => '🐛 Bug / Erro do Jogo',
    'conta'       => '🔐 Conta / Login',
    'pagamento'   => '💳 Pagamento / VIP',
    'denuncia'    => '🚨 Denúncia de Jogador',
    'sugestao'    => '💡 Sugestão',
    'outros'      => '❓ Outros',
];

$status_badge = function($s) {
    $map = [
        'aberto'      => ['🟡 Aberto',        '#c9a227'],
        'atendimento' => ['🔵 Em atendimento','#2196f3'],
        'resolvido'   => ['🟢 Resolvido',     '#4caf50'],
        'fechado'     => ['⚫ Fechado',        '#888'],
    ];
    $r = $map[$s] ?? $map['aberto'];
    return '<span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:bold;border:1px solid '.$r[1].';color:'.$r[1].';">'.$r[0].'</span>';
};

$ticket_id = isset($_GET['ticket']) ? (int)$_GET['ticket'] : 0;
$msg_ok = $msg_err = '';

// Mudar somente o status (form independente)
if($ticket_id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mudar_status'])) {
    $novo_status = $_POST['status'] ?? '';
    if(in_array($novo_status, ['aberto','atendimento','resolvido','fechado'])) {
        try {
            $conexao->prepare("UPDATE tickets SET status = ?, atualizado_em = CURRENT_TIMESTAMP, atendente_id = COALESCE(atendente_id, ?) WHERE id = ?")
                    ->execute([$novo_status, $user_id, $ticket_id]);
        } catch(Exception $e) {}
    }
    header("Location: tickets.php?ticket=".$ticket_id);
    exit;
}

// Responder ticket
if($ticket_id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['responder'])) {
    $resp = trim($_POST['mensagem'] ?? '');
    if(strlen($resp) >= 1) {
        try {
            $stmt = $conexao->prepare("INSERT INTO ticket_mensagens (ticket_id, autor_id, autor_tipo, mensagem) VALUES (?, ?, 'staff', ?)");
            $stmt->execute([$ticket_id, $user_id, $resp]);
            $stmt2 = $conexao->prepare("UPDATE tickets SET nao_lido_player = 1, atualizado_em = CURRENT_TIMESTAMP, atendente_id = COALESCE(atendente_id, ?), status = CASE WHEN status = 'aberto' THEN 'atendimento' ELSE status END WHERE id = ?");
            $stmt2->execute([$user_id, $ticket_id]);
        } catch(Exception $e) { $msg_err = 'Erro ao responder: '.$e->getMessage(); }
    }
    header("Location: tickets.php?ticket=".$ticket_id);
    exit;
}

// Marcar como lido pelo staff ao abrir
if($ticket_id > 0) {
    try { $conexao->prepare("UPDATE tickets SET nao_lido_staff = 0 WHERE id = ?")->execute([$ticket_id]); } catch(Exception $e) {}
}

require_once('adm_header.php');
?>
<style>
    .tk-table { width:100%; border-collapse:collapse; }
    .tk-table th { background:#2a2a2a; color:#FFD700; padding:6px 8px; text-align:left; font-size:11px; border-bottom:1px solid #444; }
    .tk-table td { padding:6px 8px; border-bottom:1px solid #2a2a2a; font-size:12px; }
    .tk-table tr:hover td { background:rgba(255,255,255,0.05); }
    .tk-msg { margin:8px 0; padding:10px 12px; border-radius:6px; max-width:80%; line-height:1.45; word-wrap:break-word; }
    .tk-msg-player { background:#1a2a3a; border:1px solid #2c4a6e; margin-right:auto; }
    .tk-msg-staff  { background:#3a2a1a; border:1px solid #6e4a2c; margin-left:auto; }
    .tk-msg-meta { font-size:10px; color:#aaa; margin-bottom:4px; }
    .tk-msg-text { color:#eee; white-space:pre-wrap; }
    .tk-input, .tk-textarea, .tk-select { background:#1a1a1a; color:#eee; border:1px solid #444; padding:6px 8px; font-family:inherit; font-size:12px; border-radius:3px; box-sizing:border-box; }
    .tk-textarea { width:100%; min-height:90px; resize:vertical; }
    .tk-btn { display:inline-block; padding:6px 16px; background:#2a2a2a; color:#FFD700; border:1px solid #FFD700; cursor:pointer; font-size:12px; font-weight:bold; text-decoration:none; border-radius:3px; }
    .tk-btn:hover { background:#FFD700; color:#000; }
    .tk-filter-bar { background:#1a1a1a; border:1px solid #333; padding:8px; margin-bottom:10px; border-radius:4px; display:flex; gap:6px; flex-wrap:wrap; align-items:center; }
    .tk-filter-bar a { padding:4px 10px; background:#222; color:#ccc; border:1px solid #444; text-decoration:none; font-size:11px; border-radius:3px; }
    .tk-filter-bar a.active { background:#FFD700; color:#000; border-color:#FFD700; font-weight:bold; }
    .tk-info-card { background:#0f0f1a; border:1px solid #334; padding:10px; border-radius:4px; font-size:11px; color:#ccc; margin-bottom:10px; }
    .tk-info-card b { color:#FFD700; }
</style>

<h2 style="color:#FFD700;">🎫 Suporte / Tickets</h2>
<p><a href="adm.php" class="tk-btn">← Voltar ao Painel</a></p>

<?php if($ticket_id > 0):
    // === DETALHE DO TICKET (ADMIN) ===
    $stmt = $conexao->prepare("SELECT t.*, u.usuario AS player_nome, u.nivel, u.vila, u.vip, u.loginip, u.servidor_id, s.nome AS srv_nome, atend.usuario AS atendente_nome
                               FROM tickets t
                               LEFT JOIN usuarios u ON u.id = t.usuario_id
                               LEFT JOIN servidores s ON s.id = t.servidor_id
                               LEFT JOIN usuarios atend ON atend.id = t.atendente_id
                               WHERE t.id = ?");
    $stmt->execute([$ticket_id]);
    $tk = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$tk) { echo '<p>Ticket não encontrado.</p>'; require_once('adm_footer.php'); exit; }
    $is_player_vip = !empty($tk['vip']) && date('Y-m-d H:i:s') < $tk['vip'];

    $stmt_m = $conexao->prepare("SELECT m.*, u.usuario AS autor_nome FROM ticket_mensagens m LEFT JOIN usuarios u ON u.id = m.autor_id WHERE m.ticket_id = ? ORDER BY m.id ASC");
    $stmt_m->execute([$ticket_id]);
    $msgs = $stmt_m->fetchAll(PDO::FETCH_ASSOC);
?>
    <div class="tk-info-card">
        <b>Jogador:</b> <?php echo htmlspecialchars($tk['player_nome'] ?? '?'); ?>
        (#<?php echo (int)$tk['usuario_id']; ?>)
        · <b>Nível:</b> <?php echo (int)$tk['nivel']; ?>
        · <b>Vila:</b> <?php echo (int)$tk['vila']; ?>
        · <b>Servidor:</b> <?php echo htmlspecialchars($tk['srv_nome'] ?? '—'); ?>
        · <b>VIP:</b> <?php echo $is_player_vip ? '👑 Ativo' : 'Não'; ?>
        · <b>Último IP:</b> <?php echo htmlspecialchars((string)($tk['loginip'] ?? '0')); ?>
        <?php if($tk['atendente_nome']): ?> · <b>Atendente:</b> <?php echo htmlspecialchars($tk['atendente_nome']); ?><?php endif; ?>
    </div>

    <div style="background:#1a1a1a;border:1px solid #333;padding:10px 12px;margin-bottom:10px;border-radius:4px;">
        <div style="font-size:14px;font-weight:bold;color:#FFD700;margin-bottom:4px;">
            #<?php echo (int)$tk['id']; ?> — <?php echo htmlspecialchars($tk['assunto']); ?>
        </div>
        <div style="font-size:11px;color:#aaa;">
            <?php echo $categorias[$tk['categoria']] ?? '❓'; ?> ·
            <?php echo $status_badge($tk['status']); ?> ·
            Aberto em <?php echo date('d/m/Y H:i', strtotime($tk['criado_em'])); ?>
            <?php if($tk['prioridade'] > 0): ?> · <span style="background:#7a5800;color:#000;font-size:9px;padding:1px 6px;border-radius:6px;font-weight:bold;">PRIORIDADE VIP</span><?php endif; ?>
        </div>
    </div>

    <div style="display:flex;flex-direction:column;background:#0f0f0f;padding:10px;border:1px solid #2a2a2a;border-radius:4px;max-height:500px;overflow-y:auto;">
        <?php foreach($msgs as $m):
            $is_staff = ($m['autor_tipo'] === 'staff');
        ?>
        <div class="tk-msg <?php echo $is_staff ? 'tk-msg-staff' : 'tk-msg-player'; ?>">
            <div class="tk-msg-meta">
                <b><?php echo $is_staff ? '🛡️ '.htmlspecialchars($m['autor_nome'] ?? 'Equipe').' (Suporte)' : '👤 '.htmlspecialchars($m['autor_nome'] ?? 'Jogador'); ?></b>
                · <?php echo date('d/m/Y H:i', strtotime($m['criado_em'])); ?>
            </div>
            <div class="tk-msg-text"><?php echo htmlspecialchars($m['mensagem']); ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <form method="POST" action="tickets.php?ticket=<?php echo (int)$tk['id']; ?>" style="margin-top:10px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;background:#1a1a1a;border:1px solid #333;padding:8px 10px;border-radius:4px;">
        <label style="color:#aaa;font-size:11px;font-weight:bold;">Status do ticket:</label>
        <select name="status" class="tk-select" onchange="this.form.submit()">
            <option value="aberto"      <?php if($tk['status']==='aberto')      echo 'selected'; ?>>🟡 Aberto</option>
            <option value="atendimento" <?php if($tk['status']==='atendimento') echo 'selected'; ?>>🔵 Em atendimento</option>
            <option value="resolvido"   <?php if($tk['status']==='resolvido')   echo 'selected'; ?>>🟢 Resolvido</option>
            <option value="fechado"     <?php if($tk['status']==='fechado')     echo 'selected'; ?>>⚫ Fechado</option>
        </select>
        <input type="hidden" name="mudar_status" value="1" />
        <noscript><button type="submit" class="tk-btn">Atualizar Status</button></noscript>
        <span style="color:#888;font-size:10px;">(muda automaticamente ao selecionar)</span>
    </form>

    <form method="POST" action="tickets.php?ticket=<?php echo (int)$tk['id']; ?>" style="margin-top:10px;">
        <textarea name="mensagem" class="tk-textarea" placeholder="Resposta ao jogador..." maxlength="4000"></textarea>
        <div style="margin-top:6px;text-align:right;">
            <button type="submit" name="responder" value="1" class="tk-btn">📨 Enviar Resposta</button>
        </div>
    </form>

<?php else:
    // === LISTAGEM (ADMIN) ===
    $filtro_status = $_GET['status'] ?? 'ativos';
    $filtro_cat    = $_GET['cat'] ?? 'todas';

    $where = []; $args = [];
    if($filtro_status === 'ativos')      { $where[] = "t.status IN ('aberto','atendimento')"; }
    elseif($filtro_status === 'aberto')  { $where[] = "t.status = 'aberto'"; }
    elseif($filtro_status === 'atendimento') { $where[] = "t.status = 'atendimento'"; }
    elseif($filtro_status === 'resolvido')   { $where[] = "t.status = 'resolvido'"; }
    elseif($filtro_status === 'fechado') { $where[] = "t.status = 'fechado'"; }
    if($filtro_cat !== 'todas' && isset($categorias[$filtro_cat])) {
        $where[] = "t.categoria = ?"; $args[] = $filtro_cat;
    }
    $sql = "SELECT t.*, u.usuario AS player_nome, s.nome AS srv_nome
            FROM tickets t
            LEFT JOIN usuarios u ON u.id = t.usuario_id
            LEFT JOIN servidores s ON s.id = t.servidor_id";
    if($where) $sql .= " WHERE ".implode(' AND ', $where);
    $sql .= " ORDER BY t.prioridade DESC, t.nao_lido_staff DESC, t.atualizado_em DESC LIMIT 200";
    $stmt = $conexao->prepare($sql);
    $stmt->execute($args);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Contadores
    $cnt = $conexao->query("SELECT status, COUNT(*) c FROM tickets GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
    $cmap = ['aberto'=>0,'atendimento'=>0,'resolvido'=>0,'fechado'=>0];
    foreach($cnt as $c) $cmap[$c['status']] = (int)$c['c'];
?>
    <div class="tk-filter-bar">
        <b style="color:#FFD700;">Status:</b>
        <a href="?status=ativos" class="<?php if($filtro_status==='ativos') echo 'active'; ?>">🔥 Ativos (<?php echo $cmap['aberto']+$cmap['atendimento']; ?>)</a>
        <a href="?status=aberto" class="<?php if($filtro_status==='aberto') echo 'active'; ?>">🟡 Aberto (<?php echo $cmap['aberto']; ?>)</a>
        <a href="?status=atendimento" class="<?php if($filtro_status==='atendimento') echo 'active'; ?>">🔵 Atendendo (<?php echo $cmap['atendimento']; ?>)</a>
        <a href="?status=resolvido" class="<?php if($filtro_status==='resolvido') echo 'active'; ?>">🟢 Resolvido (<?php echo $cmap['resolvido']; ?>)</a>
        <a href="?status=fechado" class="<?php if($filtro_status==='fechado') echo 'active'; ?>">⚫ Fechado (<?php echo $cmap['fechado']; ?>)</a>
        <a href="?status=todos" class="<?php if($filtro_status==='todos') echo 'active'; ?>">Todos</a>
    </div>
    <div class="tk-filter-bar">
        <b style="color:#FFD700;">Categoria:</b>
        <a href="?status=<?php echo urlencode($filtro_status); ?>&cat=todas" class="<?php if($filtro_cat==='todas') echo 'active'; ?>">Todas</a>
        <?php foreach($categorias as $k=>$v): ?>
        <a href="?status=<?php echo urlencode($filtro_status); ?>&cat=<?php echo $k; ?>" class="<?php if($filtro_cat===$k) echo 'active'; ?>"><?php echo $v; ?></a>
        <?php endforeach; ?>
    </div>

    <?php if(empty($tickets)): ?>
        <p style="color:#888;font-style:italic;text-align:center;padding:30px;">Nenhum ticket encontrado com os filtros aplicados.</p>
    <?php else: ?>
        <table class="tk-table">
            <thead><tr>
                <th width="40">#</th>
                <th>Assunto</th>
                <th width="140">Jogador</th>
                <th width="160">Categoria</th>
                <th width="100">Servidor</th>
                <th width="120">Status</th>
                <th width="110">Atualizado</th>
            </tr></thead>
            <tbody>
            <?php foreach($tickets as $t): ?>
                <tr style="cursor:pointer;<?php if($t['nao_lido_staff']) echo 'background:rgba(255,215,0,0.05);'; ?>" onclick="window.location='tickets.php?ticket=<?php echo (int)$t['id']; ?>'">
                    <td>#<?php echo (int)$t['id']; ?></td>
                    <td>
                        <?php if($t['nao_lido_staff']): ?><span style="color:#ff6666;font-weight:bold;" title="Aguardando resposta">●</span> <?php endif; ?>
                        <?php echo htmlspecialchars($t['assunto']); ?>
                        <?php if($t['prioridade'] > 0): ?><span style="background:#7a5800;color:#000;font-size:9px;padding:1px 5px;border-radius:6px;font-weight:bold;">VIP</span><?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($t['player_nome'] ?? '?'); ?></td>
                    <td><?php echo $categorias[$t['categoria']] ?? '❓'; ?></td>
                    <td style="font-size:11px;color:#aaa;"><?php echo htmlspecialchars($t['srv_nome'] ?? '—'); ?></td>
                    <td><?php echo $status_badge($t['status']); ?></td>
                    <td style="font-size:11px;color:#aaa;"><?php echo date('d/m H:i', strtotime($t['atualizado_em'])); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

<?php endif; ?>

<?php require_once('adm_footer.php'); ?>
