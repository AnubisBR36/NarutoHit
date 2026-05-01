<?php
// ─── Salvar nota pessoal ───────────────────────────────────────────────────
if (isset($_POST['salvar_nota'])) {
    $bid  = (int)$_POST['book_id'];
    $nota = trim(strip_tags($_POST['nota']));
    $stmt = $conexao->prepare("UPDATE book SET nota = ? WHERE id = ? AND usuarioid = ?");
    $stmt->execute([$nota, $bid, $db['id']]);
    echo "<script>location.href='?p=book&msg=3'</script>";
    exit;
}

// ─── Remover entrada ──────────────────────────────────────────────────────
if (isset($_GET['remove'])) {
    try {
        $stmt = $conexao->prepare("SELECT usuarioid FROM book WHERE id = ?");
        $stmt->execute([$_GET['remove']]);
        if ($stmt->rowCount() == 0) { echo "<script>location.href='?p=home'</script>"; exit; }
        $dbr = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($dbr['usuarioid'] != $db['id']) { echo "<script>location.href='?p=home'</script>"; exit; }
        $conexao->prepare("DELETE FROM book WHERE id = ?")->execute([$_GET['remove']]);
        echo "<script>location.href='?p=book&msg=2'</script>";
    } catch (PDOException $e) {
        echo "<script>location.href='?p=home'</script>";
    }
    exit;
}

// ─── Helpers ──────────────────────────────────────────────────────────────
function getRank($nivel) {
    if ($nivel <= 10) return ['nome' => 'Genin',   'cor' => '#4CAF50', 'borda' => '#2e7d32'];
    if ($nivel <= 25) return ['nome' => 'Chūnin',  'cor' => '#42A5F5', 'borda' => '#1565c0'];
    if ($nivel <= 45) return ['nome' => 'Jōnin',   'cor' => '#FF9800', 'borda' => '#e65100'];
    if ($nivel <= 70) return ['nome' => 'ANBU',    'cor' => '#CE93D8', 'borda' => '#6a1b9a'];
    if ($nivel <= 90) return ['nome' => 'Kage',    'cor' => '#EF5350', 'borda' => '#b71c1c'];
    return                    ['nome' => 'S-Rank',  'cor' => '#FFD700', 'borda' => '#ff6f00'];
}

function getVilaNome($vila, $renegado) {
    $nomes = [1=>'Folha',2=>'Areia',3=>'Som',4=>'Chuva',5=>'Nuvem',6=>'Névoa',8=>'Pedra'];
    $nome  = $nomes[$vila] ?? 'Folha';
    return $renegado === 'sim' ? 'Akatsuki ('.$nome.')' : 'Vila da '.$nome;
}

function getVilaSlug($vila, $renegado) {
    $slugs = [1=>'folha',2=>'areia',3=>'som',4=>'chuva',5=>'nuvem',6=>'nevoa',8=>'pedra'];
    $slug  = $slugs[$vila] ?? 'folha';
    return $renegado === 'sim' ? 'akatsuki_'.$slug : $slug;
}

function getVilaCor($vila, $renegado) {
    if ($renegado === 'sim') return '#b71c1c';
    $cores = [1=>'#2e7d32',2=>'#f9a825',3=>'#7b1fa2',4=>'#1565c0',5=>'#546e7a',6=>'#00695c',8=>'#5d4037'];
    return $cores[$vila] ?? '#555';
}

// ─── Buscar lista principal (usa snapshot de stats congelados) ────────────
try {
    $stmt = $conexao->prepare("
        SELECT b.id, b.nota,
               b.snap_tai AS taijutsu, b.snap_nin AS ninjutsu, b.snap_gen AS genjutsu,
               b.snap_nivel AS nivel, b.snap_vila AS vila, b.snap_renegado AS renegado,
               b.snap_personagem AS personagem, b.snap_avatar AS avatar,
               u.id AS uid, u.usuario
        FROM book b
        LEFT JOIN usuarios u ON b.inimigoid = u.id
        WHERE b.usuarioid = ?
        ORDER BY u.usuario
    ");
    $stmt->execute([$db['id']]);
    $bookResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $bookResults = [];
}

// ─── Enriquecer com stats de batalha ─────────────────────────────────────
foreach ($bookResults as &$entry) {
    if (!$entry['uid']) { $entry['skip'] = true; continue; }
    $uid = $entry['uid'];
    $mid = $db['id'];

    try {
        $s = $conexao->prepare("SELECT MAX(data) FROM relatorios WHERE usuarioid = ? AND inimigoid = ?");
        $s->execute([$mid, $uid]);
        $entry['ultimo_ataque'] = $s->fetchColumn();

        $s = $conexao->prepare("SELECT COALESCE(SUM(yens),0) FROM relatorios WHERE usuarioid = ? AND inimigoid = ? AND vencedor = ?");
        $s->execute([$mid, $uid, $mid]);
        $entry['total_yens'] = (float)$s->fetchColumn();

        $s = $conexao->prepare("SELECT COUNT(*) FROM relatorios WHERE usuarioid = ? AND inimigoid = ? AND vencedor = ?");
        $s->execute([$mid, $uid, $mid]);
        $entry['vitorias'] = (int)$s->fetchColumn();

        $s = $conexao->prepare("SELECT COUNT(*) FROM relatorios WHERE usuarioid = ? AND inimigoid = ? AND vencedor = ?");
        $s->execute([$mid, $uid, $uid]);
        $entry['derrotas'] = (int)$s->fetchColumn();

        $s = $conexao->prepare("SELECT COUNT(*) FROM relatorios WHERE usuarioid = ? AND inimigoid = ?");
        $s->execute([$mid, $uid]);
        $entry['total'] = (int)$s->fetchColumn();
        $entry['empates'] = max(0, $entry['total'] - $entry['vitorias'] - $entry['derrotas']);
    } catch (PDOException $e) {
        $entry['ultimo_ataque'] = null;
        $entry['total_yens']    = 0;
        $entry['vitorias']      = 0;
        $entry['derrotas']      = 0;
        $entry['empates']       = 0;
    }
}
unset($entry);

$myid = $db['id'];
?>

<div class="box_top">Bingo Book</div>
<div class="box_middle">
<p style="color:#aaa;font-size:12px;margin:0 0 10px 0;">
    Seu registro de alvos. Use-o para planejar ataques e acompanhar sua caça.
</p>

<?php if (isset($_GET['msg'])): ?>
    <?php
    $msgs = [1 => 'Adicionado ao Bingo Book!', 2 => 'Alvo removido.', 3 => 'Nota salva!'];
    $m = $msgs[(int)$_GET['msg']] ?? '';
    ?>
    <?php if ($m): ?><div class="aviso" style="margin-bottom:10px;"><?php echo $m; ?></div><?php endif; ?>
<?php endif; ?>

<?php if (count($bookResults) === 0): ?>
    <div class="aviso">Seu Bingo Book está vazio. Visite o perfil dos usuários para adicioná-los!</div>
<?php else: ?>

<style>
.bb-grid { display:flex; flex-direction:column; gap:12px; }
.bb-card {
    background: linear-gradient(135deg, #1a1a1a 0%, #1f1f1f 100%);
    border-radius: 6px;
    overflow: hidden;
    position: relative;
}
.bb-card-inner { display:flex; gap:0; }
.bb-avatar-col {
    width: 80px;
    min-width: 80px;
    position: relative;
    overflow: hidden;
}
.bb-avatar-col img { display:block; width:80px; height:90px; object-fit:cover; }
.bb-vila-badge {
    position:absolute; bottom:0; left:0; right:0;
    text-align:center; font-size:9px; font-weight:bold;
    padding:2px 2px; color:#fff; white-space:nowrap;
    overflow:hidden; text-overflow:ellipsis;
}
.bb-main { flex:1; padding:8px 10px; min-width:0; }
.bb-header { display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-bottom:4px; }
.bb-name { color:#fff; font-size:14px; font-weight:bold; text-decoration:none; }
.bb-name:hover { color:#FFD700; }
.bb-rank-badge {
    font-size:10px; font-weight:bold; padding:1px 7px;
    border-radius:10px; border:1px solid; white-space:nowrap;
}
.bb-level { color:#aaa; font-size:11px; }
.bb-stats-row { display:flex; gap:10px; font-size:11px; color:#888; margin-bottom:5px; flex-wrap:wrap; }
.bb-stats-row span { white-space:nowrap; }
.bb-stats-row b { color:#bbb; }
.bb-divider { border:none; border-top:1px solid #333; margin:5px 0; }
.bb-info-row { display:flex; gap:12px; font-size:11px; flex-wrap:wrap; margin-bottom:5px; }
.bb-bounty { color:#FFD700; font-weight:bold; }
.bb-record { color:#aaa; }
.bb-record .v { color:#4CAF50; }
.bb-record .e { color:#aaa; }
.bb-record .d { color:#EF5350; }
.bb-last { color:#777; font-size:10px; }
.bb-note-section { margin-top:4px; }
.bb-note-section textarea {
    width:100%; box-sizing:border-box;
    background:#111; color:#ccc; border:1px solid #444;
    border-radius:3px; font-size:11px; padding:4px 6px;
    resize:vertical; min-height:36px; font-family:inherit;
}
.bb-note-section textarea:focus { border-color:#d4a04a; outline:none; }
.bb-actions { display:flex; align-items:center; gap:6px; margin-top:6px; flex-wrap:wrap; }
.bb-btn-attack {
    background:linear-gradient(to bottom, #8b1a1a, #5a0a0a);
    color:#fff; border:1px solid #c0392b;
    padding:4px 14px; border-radius:3px; cursor:pointer;
    font-size:12px; font-weight:bold; white-space:nowrap;
}
.bb-btn-attack:hover { background:linear-gradient(to bottom, #c0392b, #8b1a1a); }
.bb-btn-attack:disabled { background:#333; border-color:#555; color:#666; cursor:default; }
.bb-btn-nota {
    background:#2a2a2a; color:#aaa; border:1px solid #555;
    padding:3px 10px; border-radius:3px; cursor:pointer; font-size:11px;
}
.bb-btn-nota:hover { border-color:#d4a04a; color:#d4a04a; }
.bb-btn-remove {
    color:#EF5350; font-size:11px; text-decoration:none;
    padding:3px 8px; border:1px solid #555; border-radius:3px;
    margin-left:auto;
}
.bb-btn-remove:hover { border-color:#EF5350; background:#1a0000; }
.bb-countdown { font-size:11px; color:#FF9800; font-weight:bold; white-space:nowrap; }
.bb-stamp {
    position:absolute; top:6px; right:8px;
    font-size:9px; font-weight:bold; letter-spacing:1px;
    opacity:0.25; color:#fff; pointer-events:none;
    transform:rotate(-8deg); text-transform:uppercase;
}
</style>

<div class="bb-grid">
<?php foreach ($bookResults as $e):
    if (!empty($e['skip']) || !$e['uid']) continue;

    $rank       = getRank($e['nivel']);
    $avatarSrc  = '_img/personagens/'.$e['personagem'].'/'.$e['avatar'].'.jpg';
    $bandanaImg = getBandanaPath($db['bandana_estilo'], $e['renegado'], $e['vila']);

    // Cooldown: 12 horas do último ataque
    $cooldownSecs = 0;
    $podeAtacar   = true;
    if ($e['ultimo_ataque']) {
        $ts        = strtotime($e['ultimo_ataque']);
        $restante  = ($ts + 43200) - time();
        if ($restante > 0) { $podeAtacar = false; $cooldownSecs = $restante; }
    }

    $cardBorda = $rank['borda'];
    // S-Rank ganha glow especial
    $cardStyle = 'border-left:4px solid '.$cardBorda.';';
    if ($e['nivel'] > 90) $cardStyle .= 'box-shadow:0 0 10px rgba(255,215,0,0.3);';
?>
<div class="bb-card" style="<?php echo $cardStyle; ?>">
    <div class="bb-stamp">Bingo Book</div>
    <div class="bb-card-inner">

        <!-- Avatar -->
        <div class="bb-avatar-col">
            <a href="?p=view&view=<?php echo strtolower(htmlspecialchars($e['usuario'])); ?>">
                <img src="<?php echo $avatarSrc; ?>"
                     onerror="this.src='_img/personagens/<?php echo $e['personagem']; ?>/0.jpg'"
                     alt="<?php echo htmlspecialchars($e['usuario']); ?>">
            </a>
            <div class="bb-vila-badge" style="background:rgba(0,0,0,0.6);padding:3px 0;">
                <img src="<?php echo $bandanaImg; ?>"
                     onerror="this.style.display='none'"
                     style="width:62px;height:28px;object-fit:contain;display:block;margin:0 auto;"
                     title="<?php echo htmlspecialchars(getVilaNome($e['vila'], $e['renegado'])); ?>">
            </div>
        </div>

        <!-- Conteúdo -->
        <div class="bb-main">
            <div class="bb-header">
                <a class="bb-name" href="?p=view&view=<?php echo strtolower(htmlspecialchars($e['usuario'])); ?>">
                    <?php echo htmlspecialchars($e['usuario']); ?>
                </a>
                <span class="bb-rank-badge" style="color:<?php echo $rank['cor']; ?>;border-color:<?php echo $rank['cor']; ?>;">
                    <?php echo $rank['nome']; ?>
                </span>
                <span class="bb-level">Nv.&nbsp;<?php echo (int)$e['nivel']; ?></span>
            </div>

            <div class="bb-stats-row">
                <span>⚔ Tai: <b><?php echo (int)$e['taijutsu']; ?></b></span>
                <span>✦ Nin: <b><?php echo (int)$e['ninjutsu']; ?></b></span>
                <span>◈ Gen: <b><?php echo (int)$e['genjutsu']; ?></b></span>
            </div>

            <hr class="bb-divider">

            <div class="bb-info-row">
                <span class="bb-bounty">
                    💰 <?php echo number_format($e['total_yens'], 2, ',', '.'); ?> yens roubados
                </span>
                <span class="bb-record">
                    ⚔
                    <span class="v"><?php echo $e['vitorias']; ?>V</span> /
                    <span class="e"><?php echo $e['empates']; ?>E</span> /
                    <span class="d"><?php echo $e['derrotas']; ?>D</span>
                </span>
            </div>

            <div class="bb-last">
                🕐
                <?php if ($e['ultimo_ataque']): ?>
                    <?php
                    $parts = explode(' ', $e['ultimo_ataque']);
                    $d     = explode('-', $parts[0]);
                    echo 'Último ataque: '.$d[2].'/'.$d[1].'/'.$d[0].', às '.substr($parts[1],0,5);
                    ?>
                <?php else: ?>
                    Nunca atacado
                <?php endif; ?>
            </div>

            <!-- Nota pessoal -->
            <div class="bb-note-section">
                <form method="POST" action="?p=book" id="nota-form-<?php echo $e['id']; ?>">
                    <input type="hidden" name="book_id" value="<?php echo $e['id']; ?>">
                    <textarea name="nota"
                        placeholder="📝 Anotação pessoal (visível só para você)..."
                        rows="2"
                    ><?php echo htmlspecialchars($e['nota'] ?? ''); ?></textarea>
                </form>
                <div class="bb-actions">
                    <?php if ($podeAtacar): ?>
                        <form method="post" action="?p=hunt" onsubmit="this.querySelector('[type=submit]').value='Carregando...';this.querySelector('[type=submit]').disabled=true;" style="display:inline;">
                            <input type="hidden" name="hunt_1" value="<?php echo htmlspecialchars($e['usuario']); ?>">
                            <input type="hidden" name="hunt_tipo" value="<?php echo isset($c) ? $c->encode('1', $chaveuniversal) : '1'; ?>">
                            <button type="submit" class="bb-btn-attack">⚔ Atacar</button>
                        </form>
                    <?php else: ?>
                        <button class="bb-btn-attack" disabled>
                            <span class="bb-countdown" data-secs="<?php echo $cooldownSecs; ?>">⏱ calculando...</span>
                        </button>
                    <?php endif; ?>

                    <button type="submit" name="salvar_nota" form="nota-form-<?php echo $e['id']; ?>" class="bb-btn-nota">💾 Salvar nota</button>

                    <a class="bb-btn-remove" href="?p=book&remove=<?php echo $e['id']; ?>"
                       onclick="return confirm('Remover <?php echo htmlspecialchars($e['usuario']); ?> do Bingo Book?');">
                       🗑 Remover
                    </a>
                </div>
            </div>
        </div><!-- .bb-main -->
    </div><!-- .bb-card-inner -->
</div><!-- .bb-card -->
<?php endforeach; ?>
</div><!-- .bb-grid -->

<script>
(function() {
    function pad(n) { return String(n).padStart(2, '0'); }
    function formatTime(s) {
        var h = Math.floor(s / 3600);
        var m = Math.floor((s % 3600) / 60);
        var sec = s % 60;
        return (h > 0 ? h + 'h ' : '') + pad(m) + 'm ' + pad(sec) + 's';
    }
    var countdowns = document.querySelectorAll('.bb-countdown[data-secs]');
    countdowns.forEach(function(el) {
        var secs = parseInt(el.getAttribute('data-secs'), 10);
        if (isNaN(secs) || secs <= 0) { el.closest('button').disabled = false; return; }
        el.textContent = '⏱ ' + formatTime(secs);
        var interval = setInterval(function() {
            secs--;
            if (secs <= 0) {
                clearInterval(interval);
                location.reload();
            } else {
                el.textContent = '⏱ ' + formatTime(secs);
            }
        }, 1000);
    });
})();
</script>

<?php endif; ?>
</div><!-- .box_middle -->
<div class="box_bottom"></div>
