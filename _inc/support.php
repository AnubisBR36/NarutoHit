<?php require_once('trava.php');

// Categorias disponíveis
$categorias = [
    'bug'         => '🐛 Bug / Erro do Jogo',
    'conta'       => '🔐 Conta / Login',
    'pagamento'   => '💳 Pagamento / VIP',
    'denuncia'    => '🚨 Denúncia de Jogador',
    'sugestao'    => '💡 Sugestão',
    'outros'      => '❓ Outros',
];

$categorias_label = function($k) use ($categorias) {
    return $categorias[$k] ?? '❓ Outros';
};

$status_badge = function($s) {
    $map = [
        'aberto'      => ['🟡 Aberto',     '#c9a227', '#3a2e00'],
        'atendimento' => ['🔵 Atendendo',  '#2196f3', '#0a2440'],
        'resolvido'   => ['🟢 Resolvido',  '#4caf50', '#0d2a10'],
        'fechado'     => ['⚫ Fechado',     '#888',    '#1a1a1a'],
    ];
    $r = $map[$s] ?? $map['aberto'];
    return '<span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:bold;border:1px solid '.$r[1].';color:'.$r[1].';background:'.$r[2].';white-space:nowrap;">'.$r[0].'</span>';
};

$user_id   = (int)($_SESSION['logado'] ?? 0);
$is_vip    = isset($db['vip']) && date('Y-m-d H:i:s') < $db['vip'];
$prio_user = $is_vip ? 1 : 0;
$srv_id    = isset($db['servidor_id']) ? (int)$db['servidor_id'] : null;

$action  = $_GET['action']   ?? '';
$ticket_id = isset($_GET['ticket']) ? (int)$_GET['ticket'] : 0;
$msg_ok = $msg_err = '';

// === Abrir novo ticket ===
if($action === 'novo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $cat     = $_POST['categoria'] ?? 'outros';
    $assunto = trim($_POST['assunto'] ?? '');
    $mensagem= trim($_POST['mensagem'] ?? '');
    if(!isset($categorias[$cat])) $cat = 'outros';
    if(strlen($assunto) < 4 || strlen($assunto) > 150) {
        $msg_err = 'Assunto deve ter entre 4 e 150 caracteres.';
    } elseif(strlen($mensagem) < 10 || strlen($mensagem) > 4000) {
        $msg_err = 'Mensagem deve ter entre 10 e 4000 caracteres.';
    } else {
        try {
            $stmt = $conexao->prepare("INSERT INTO tickets (usuario_id, servidor_id, categoria, assunto, status, prioridade, nao_lido_staff) VALUES (?, ?, ?, ?, 'aberto', ?, 1)");
            $stmt->execute([$user_id, $srv_id, $cat, $assunto, $prio_user]);
            $new_id = (int)$conexao->lastInsertId();
            $stmt2 = $conexao->prepare("INSERT INTO ticket_mensagens (ticket_id, autor_id, autor_tipo, mensagem) VALUES (?, ?, 'player', ?)");
            $stmt2->execute([$new_id, $user_id, $mensagem]);
            header("Location: index.php?p=support&ticket=".$new_id);
            exit;
        } catch (Exception $e) {
            $msg_err = 'Erro ao abrir ticket: '.htmlspecialchars($e->getMessage());
        }
    }
}

// === Responder ticket existente ===
if($ticket_id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['responder'])) {
    $resp = trim($_POST['mensagem'] ?? '');
    if(strlen($resp) >= 2 && strlen($resp) <= 4000) {
        try {
            // valida posse do ticket
            $stmt = $conexao->prepare("SELECT status FROM tickets WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$ticket_id, $user_id]);
            $tk = $stmt->fetch(PDO::FETCH_ASSOC);
            if($tk && $tk['status'] !== 'fechado') {
                $stmt2 = $conexao->prepare("INSERT INTO ticket_mensagens (ticket_id, autor_id, autor_tipo, mensagem) VALUES (?, ?, 'player', ?)");
                $stmt2->execute([$ticket_id, $user_id, $resp]);
                $stmt3 = $conexao->prepare("UPDATE tickets SET nao_lido_staff = 1, atualizado_em = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt3->execute([$ticket_id]);
            }
        } catch (Exception $e) {}
        header("Location: index.php?p=support&ticket=".$ticket_id);
        exit;
    } else {
        $msg_err = 'A resposta deve ter entre 2 e 4000 caracteres.';
    }
}

// === Fechar ticket pelo próprio jogador ===
if($ticket_id > 0 && isset($_GET['fechar']) && $_GET['fechar'] == '1') {
    try {
        $stmt = $conexao->prepare("UPDATE tickets SET status = 'fechado', atualizado_em = CURRENT_TIMESTAMP WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$ticket_id, $user_id]);
    } catch (Exception $e) {}
    header("Location: index.php?p=support&ticket=".$ticket_id);
    exit;
}
?>
<style>
    .tk-tabs { display:flex; gap:4px; margin-bottom:8px; }
    .tk-tab { display:inline-block; padding:6px 14px; background:#1f1f1f; color:#ddd; border:1px solid #444; border-bottom:none; text-decoration:none; font-size:12px; font-weight:bold; border-radius:4px 4px 0 0; }
    .tk-tab.active { background:#2a2a2a; color:#FFD700; border-color:#FFD700; }
    .tk-table { width:100%; border-collapse:collapse; }
    .tk-table th { background:#2a2a2a; color:#FFD700; padding:6px 8px; text-align:left; font-size:11px; border-bottom:1px solid #444; }
    .tk-table td { padding:6px 8px; border-bottom:1px solid #2a2a2a; font-size:12px; vertical-align:middle; }
    .tk-table tr:hover td { background:rgba(255,255,255,0.04); }
    .tk-empty { padding:30px 10px; text-align:center; color:#888; font-style:italic; }
    .tk-msg { margin:8px 0; padding:10px 12px; border-radius:6px; max-width:80%; line-height:1.45; word-wrap:break-word; }
    .tk-msg-player { background:#1a2a3a; border:1px solid #2c4a6e; margin-right:auto; }
    .tk-msg-staff  { background:#3a2a1a; border:1px solid #6e4a2c; margin-left:auto; }
    .tk-msg-meta { font-size:10px; color:#aaa; margin-bottom:4px; }
    .tk-msg-text { color:#eee; white-space:pre-wrap; }
    .tk-input, .tk-textarea, .tk-select { width:100%; box-sizing:border-box; background:#1a1a1a; color:#eee; border:1px solid #444; padding:6px 8px; font-family:inherit; font-size:12px; border-radius:3px; }
    .tk-textarea { min-height:80px; resize:vertical; }
    .tk-btn { display:inline-block; padding:6px 16px; background:#2a2a2a; color:#FFD700; border:1px solid #FFD700; cursor:pointer; font-size:12px; font-weight:bold; text-decoration:none; border-radius:3px; }
    .tk-btn:hover { background:#FFD700; color:#000; }
    .tk-btn-danger { color:#ff6666; border-color:#ff6666; }
    .tk-btn-danger:hover { background:#ff6666; color:#000; }
    .tk-alert { padding:8px 12px; margin:8px 0; border-radius:4px; font-size:12px; }
    .tk-alert-err { background:#3a1a1a; border:1px solid #ff6666; color:#ffaaaa; }
    .tk-faq { background:#1a1a2a; border:1px solid #333355; padding:8px 12px; margin-bottom:10px; font-size:11px; color:#aab; border-radius:4px; }
    .tk-vip-tag { display:inline-block; background:#7a5800; color:#000; font-size:9px; padding:1px 5px; border-radius:6px; font-weight:bold; margin-left:4px; vertical-align:middle; }
</style>

<div class="box_top">Suporte — Tickets</div>
<div class="box_middle">

    <?php if($msg_err): ?><div class="tk-alert tk-alert-err"><?php echo $msg_err; ?></div><?php endif; ?>

    <?php if($ticket_id > 0):
        // === VISUALIZAR UM TICKET ===
        $stmt = $conexao->prepare("SELECT * FROM tickets WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$ticket_id, $user_id]);
        $tk = $stmt->fetch(PDO::FETCH_ASSOC);
        if(!$tk):
            ?><div class="tk-empty">Ticket não encontrado.</div><?php
        else:
            // Marcar como lido pelo player
            try { $conexao->prepare("UPDATE tickets SET nao_lido_player = 0 WHERE id = ?")->execute([$ticket_id]); } catch(Exception $e) {}
            $stmt_m = $conexao->prepare("SELECT m.*, u.usuario AS autor_nome FROM ticket_mensagens m LEFT JOIN usuarios u ON u.id = m.autor_id WHERE m.ticket_id = ? ORDER BY m.id ASC");
            $stmt_m->execute([$ticket_id]);
            $msgs = $stmt_m->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div style="margin-bottom:8px;">
        <a href="?p=support" class="tk-btn">← Meus Tickets</a>
    </div>
    <div style="background:#1a1a1a;border:1px solid #333;padding:10px 12px;margin-bottom:10px;border-radius:4px;">
        <div style="font-size:14px;font-weight:bold;color:#FFD700;margin-bottom:4px;">#<?php echo (int)$tk['id']; ?> — <?php echo htmlspecialchars($tk['assunto']); ?></div>
        <div style="font-size:11px;color:#aaa;">
            <?php echo $categorias_label($tk['categoria']); ?> ·
            <?php echo $status_badge($tk['status']); ?> ·
            Aberto em <?php echo date('d/m/Y H:i', strtotime($tk['criado_em'])); ?>
            <?php if($tk['prioridade'] > 0): ?><span class="tk-vip-tag">VIP</span><?php endif; ?>
        </div>
    </div>

    <div style="display:flex;flex-direction:column;background:#0f0f0f;padding:10px;border:1px solid #2a2a2a;border-radius:4px;max-height:400px;overflow-y:auto;">
        <?php foreach($msgs as $m):
            $is_staff = ($m['autor_tipo'] === 'staff');
        ?>
        <div class="tk-msg <?php echo $is_staff ? 'tk-msg-staff' : 'tk-msg-player'; ?>">
            <div class="tk-msg-meta">
                <b><?php echo $is_staff ? '🛡️ Equipe de Suporte' : htmlspecialchars($m['autor_nome'] ?? 'Você'); ?></b>
                · <?php echo date('d/m/Y H:i', strtotime($m['criado_em'])); ?>
            </div>
            <div class="tk-msg-text"><?php echo htmlspecialchars($m['mensagem']); ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if($tk['status'] !== 'fechado'): ?>
    <form method="POST" action="?p=support&ticket=<?php echo (int)$tk['id']; ?>" style="margin-top:10px;">
        <textarea name="mensagem" class="tk-textarea" placeholder="Escreva sua resposta..." maxlength="4000" required></textarea>
        <div style="margin-top:6px;display:flex;justify-content:space-between;align-items:center;gap:8px;">
            <a href="?p=support&ticket=<?php echo (int)$tk['id']; ?>&fechar=1" class="tk-btn tk-btn-danger" onclick="return confirm('Tem certeza que deseja fechar este ticket?');">🗑️ Fechar Ticket</a>
            <button type="submit" name="responder" value="1" class="tk-btn">📨 Enviar Resposta</button>
        </div>
    </form>
    <?php else: ?>
    <div class="tk-alert" style="background:#1a1a1a;border:1px solid #555;color:#aaa;margin-top:10px;">
        Este ticket está <b>fechado</b>. Para um novo atendimento, abra outro ticket.
    </div>
    <?php endif; ?>

    <?php endif; // ticket exists
    elseif($action === 'novo'):
        // === FORMULÁRIO DE NOVO TICKET ===
    ?>
    <div style="margin-bottom:8px;">
        <a href="?p=support" class="tk-btn">← Meus Tickets</a>
    </div>
    <div class="tk-faq">
        💡 <b>Dica:</b> Antes de abrir um ticket, verifique se sua dúvida não está no <a href="?p=manual" style="color:#FFD700;">Manual</a> ou nas <a href="?p=news" style="color:#FFD700;">Notícias</a>.
        <?php if($is_vip): ?><br>👑 Você é <b>VIP</b> e tem prioridade no atendimento.<?php endif; ?>
    </div>
    <form method="POST" action="?p=support&action=novo">
        <fieldset style="border:1px solid #444;padding:10px;">
            <legend style="color:#FFD700;font-weight:bold;">Abrir Novo Ticket</legend>
            <div style="margin-bottom:8px;">
                <label style="display:block;font-size:11px;color:#aaa;margin-bottom:3px;">Categoria</label>
                <select name="categoria" class="tk-select" required>
                    <?php foreach($categorias as $k=>$v): ?>
                        <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom:8px;">
                <label style="display:block;font-size:11px;color:#aaa;margin-bottom:3px;">Assunto (4 a 150 caracteres)</label>
                <input type="text" name="assunto" class="tk-input" minlength="4" maxlength="150" required value="<?php echo htmlspecialchars($_POST['assunto'] ?? ''); ?>" />
            </div>
            <div style="margin-bottom:8px;">
                <label style="display:block;font-size:11px;color:#aaa;margin-bottom:3px;">Mensagem (10 a 4000 caracteres)</label>
                <textarea name="mensagem" class="tk-textarea" minlength="10" maxlength="4000" required placeholder="Descreva o problema, dúvida ou sugestão com o máximo de detalhes..."><?php echo htmlspecialchars($_POST['mensagem'] ?? ''); ?></textarea>
            </div>
            <div style="text-align:right;">
                <button type="submit" class="tk-btn">📨 Abrir Ticket</button>
            </div>
        </fieldset>
    </form>

    <?php else:
        // === LISTA DE TICKETS DO JOGADOR ===
        $stmt = $conexao->prepare("SELECT * FROM tickets WHERE usuario_id = ? ORDER BY (status='fechado') ASC, atualizado_em DESC LIMIT 100");
        $stmt->execute([$user_id]);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div class="tk-tabs">
        <span class="tk-tab active">📋 Meus Tickets (<?php echo count($tickets); ?>)</span>
        <a href="?p=support&action=novo" class="tk-tab">➕ Abrir Novo Ticket</a>
    </div>

    <div class="tk-faq">
        💡 Bem-vindo ao Suporte! Aqui você pode abrir tickets para reportar bugs, tirar dúvidas, denunciar jogadores ou enviar sugestões.
        <?php if($is_vip): ?><br>👑 Como <b>VIP</b>, seus tickets têm prioridade no atendimento.<?php endif; ?>
    </div>

    <?php if(empty($tickets)): ?>
        <div class="tk-empty">
            Você ainda não abriu nenhum ticket.<br><br>
            <a href="?p=support&action=novo" class="tk-btn">➕ Abrir Meu Primeiro Ticket</a>
        </div>
    <?php else: ?>
        <table class="tk-table">
            <thead><tr>
                <th width="40">#</th>
                <th>Assunto</th>
                <th width="160">Categoria</th>
                <th width="120">Status</th>
                <th width="110">Atualizado</th>
            </tr></thead>
            <tbody>
            <?php foreach($tickets as $t): ?>
                <tr style="cursor:pointer;" onclick="window.location='?p=support&ticket=<?php echo (int)$t['id']; ?>'">
                    <td>#<?php echo (int)$t['id']; ?></td>
                    <td>
                        <?php if($t['nao_lido_player']): ?><span style="color:#ff6666;font-weight:bold;" title="Nova resposta">●</span> <?php endif; ?>
                        <?php echo htmlspecialchars($t['assunto']); ?>
                        <?php if($t['prioridade'] > 0): ?><span class="tk-vip-tag">VIP</span><?php endif; ?>
                    </td>
                    <td><?php echo $categorias_label($t['categoria']); ?></td>
                    <td><?php echo $status_badge($t['status']); ?></td>
                    <td style="font-size:11px;color:#aaa;"><?php echo date('d/m H:i', strtotime($t['atualizado_em'])); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php endif; ?>

</div>
<div class="box_bottom"></div>
