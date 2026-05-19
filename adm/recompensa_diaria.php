<?php
require_once('../_inc/conexao.php');
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['logado']) && !isset($_SESSION['userid']) && !isset($_SESSION['uid'])) {
    header('Location: ../index.php'); exit;
}
$user_id = $_SESSION['logado'] ?? $_SESSION['userid'] ?? $_SESSION['uid'];
try {
    $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $usuario_logado = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { header('Location: adm.php'); exit; }
require_once('_gm_auth.php');

$page_title = 'Recompensa Diária';
$msg = '';
$msg_tipo = '';

// Garantir tabelas e coluna icone
try {
    $conexao->exec("CREATE TABLE IF NOT EXISTS login_streak_config (
        dia TINYINT NOT NULL PRIMARY KEY,
        yens INTEGER NOT NULL DEFAULT 0,
        exp INTEGER NOT NULL DEFAULT 0
    )");
    // Adicionar coluna icone se não existir
    try { $conexao->exec("ALTER TABLE login_streak_config ADD COLUMN icone VARCHAR(100) NOT NULL DEFAULT ''"); } catch(PDOException $e) {}

    $cnt = (int)$conexao->query("SELECT COUNT(*) FROM login_streak_config")->fetchColumn();
    if ($cnt === 0) {
        $defaults = [[1,100,0],[2,150,0],[3,200,50],[4,300,0],[5,250,100],[6,400,0],[7,600,200]];
        $ins = $conexao->prepare("INSERT INTO login_streak_config (dia, yens, exp) VALUES (?,?,?)");
        foreach ($defaults as $d) $ins->execute($d);
    }
    $conexao->exec("CREATE TABLE IF NOT EXISTS login_streak (
        id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
        usuarioid INTEGER NOT NULL UNIQUE,
        streak INTEGER NOT NULL DEFAULT 1,
        ultimo_login DATE NOT NULL,
        total_logins INTEGER NOT NULL DEFAULT 1
    )");
} catch (PDOException $e) {}

// Salvar recompensas editadas
if (isset($_POST['salvar_recompensas'])) {
    try {
        $stmt_up = $conexao->prepare("UPDATE login_streak_config SET yens=?, exp=?, icone=? WHERE dia=?");
        for ($d = 1; $d <= 7; $d++) {
            $y = max(0, (int)($_POST["yens_$d"] ?? 0));
            $e = max(0, (int)($_POST["exp_$d"]  ?? 0));
            $ico = basename($_POST["icone_$d"] ?? '');
            // Validar que o arquivo existe
            if ($ico && !file_exists("../_img/Login/$ico")) $ico = '';
            $stmt_up->execute([$y, $e, $ico, $d]);
        }
        $msg = 'Recompensas atualizadas com sucesso!';
        $msg_tipo = 'success';
    } catch (PDOException $e) {
        $msg = 'Erro ao salvar: ' . $e->getMessage();
        $msg_tipo = 'error';
    }
}

// Resetar streak de um jogador
if (isset($_POST['reset_streak']) && is_numeric($_POST['reset_streak'])) {
    try {
        $conexao->prepare("DELETE FROM login_streak WHERE usuarioid=?")->execute([(int)$_POST['reset_streak']]);
        $msg = 'Streak resetado com sucesso.';
        $msg_tipo = 'success';
    } catch (PDOException $e) {
        $msg = 'Erro: ' . $e->getMessage();
        $msg_tipo = 'error';
    }
}

// Dar recompensa manual
if (isset($_POST['dar_recompensa'])) {
    $uid  = (int)($_POST['uid_recomp'] ?? 0);
    $yens = (int)($_POST['yens_manual'] ?? 0);
    $exp  = (int)($_POST['exp_manual']  ?? 0);
    if ($uid > 0 && ($yens > 0 || $exp > 0)) {
        try {
            if ($yens > 0)
                $conexao->prepare("UPDATE usuarios SET yens=yens+?, yens_fat=yens_fat+? WHERE id=?")->execute([$yens, $yens, $uid]);
            if ($exp > 0)
                $conexao->prepare("UPDATE usuarios SET exp=exp+?, exptotal=exptotal+? WHERE id=?")->execute([$exp, $exp, $uid]);
            $msg = "Entregue: {$yens} yens + {$exp} exp ao jogador ID {$uid}.";
            $msg_tipo = 'success';
        } catch (PDOException $e) {
            $msg = 'Erro: ' . $e->getMessage(); $msg_tipo = 'error';
        }
    } else { $msg = 'Preencha o ID e ao menos yens ou exp.'; $msg_tipo = 'warning'; }
}

// Carregar config atual
$recompensas_ciclo = [];
try {
    $rows = $conexao->query("SELECT dia, yens, exp, icone FROM login_streak_config ORDER BY dia")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) $recompensas_ciclo[(int)$r['dia']] = $r;
} catch (PDOException $e) {}

// Listar ícones disponíveis
$icones_disponiveis = [];
$login_img_dir = '../_img/Login/';
if (is_dir($login_img_dir)) {
    foreach (scandir($login_img_dir) as $f) {
        if (preg_match('/\.(png|jpg|gif|webp)$/i', $f)) $icones_disponiveis[] = $f;
    }
    natsort($icones_disponiveis);
    $icones_disponiveis = array_values($icones_disponiveis);
}

// Estatísticas
$stats = ['jogadores'=>0,'logaram_hoje'=>0,'ativos_semana'=>0,'maior_streak'=>0];
$top_streaks = [];
try {
    $stats['jogadores']     = (int)$conexao->query("SELECT COUNT(*) FROM login_streak")->fetchColumn();
    $stats['logaram_hoje']  = (int)$conexao->query("SELECT COUNT(*) FROM login_streak WHERE ultimo_login=CURDATE()")->fetchColumn();
    $stats['ativos_semana'] = (int)$conexao->query("SELECT COUNT(*) FROM login_streak WHERE ultimo_login>=DATE_SUB(CURDATE(),INTERVAL 6 DAY)")->fetchColumn();
    $stats['maior_streak']  = (int)$conexao->query("SELECT MAX(streak) FROM login_streak")->fetchColumn();
    $top_streaks = $conexao->query(
        "SELECT ls.streak, ls.total_logins, ls.ultimo_login, u.usuario, u.nivel, u.id as uid
         FROM login_streak ls JOIN usuarios u ON u.id=ls.usuarioid
         ORDER BY ls.streak DESC LIMIT 10"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

require_once('adm_header.php');
?>

<?php if ($msg): ?>
<div class="alert-<?php echo $msg_tipo; ?>"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<div class="adm-page-title">Login Diário — Configuração</div>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-box"><div class="stat-number"><?php echo $stats['logaram_hoje']; ?></div><div>Logaram hoje</div></div>
    <div class="stat-box"><div class="stat-number"><?php echo $stats['ativos_semana']; ?></div><div>Ativos esta semana</div></div>
    <div class="stat-box"><div class="stat-number"><?php echo $stats['jogadores']; ?></div><div>Total no sistema</div></div>
    <div class="stat-box"><div class="stat-number"><?php echo $stats['maior_streak']; ?></div><div>Maior streak ativo</div></div>
</div>

<style>
.ico-picker-wrap { position:relative; display:inline-block; }
.ico-picker-btn {
    display:flex;align-items:center;gap:6px;cursor:pointer;
    background:#2a2200;border:1px solid #555;padding:4px 8px;
    color:#FFD700;font-size:11px;
}
.ico-picker-btn:hover { border-color:#ff6600; }
.ico-picker-btn img { width:32px;height:32px;object-fit:contain; }
.ico-picker-btn .ico-none { width:32px;height:32px;background:#1a1200;border:1px dashed #444;display:inline-block; }
.ico-grid-panel {
    display:none;position:absolute;left:0;top:100%;z-index:999;
    background:#1a1200;border:1px solid #ff6600;
    padding:6px;width:320px;max-height:260px;overflow-y:auto;
    display:none;flex-wrap:wrap;gap:4px;
}
.ico-grid-panel.open { display:flex; }
.ico-grid-panel .ico-item {
    width:36px;height:36px;cursor:pointer;border:2px solid transparent;
    background:#111;padding:2px;box-sizing:border-box;
}
.ico-grid-panel .ico-item:hover { border-color:#ff6600; }
.ico-grid-panel .ico-item.selected { border-color:#FFD700; }
.ico-grid-panel .ico-item img { width:100%;height:100%;object-fit:contain; }
.ico-clear { font-size:10px;color:#ff4444;cursor:pointer;margin-left:6px;vertical-align:middle; }
.ico-clear:hover { text-decoration:underline; }
</style>

<!-- Editar recompensas -->
<fieldset>
    <legend>Editar Recompensas do Ciclo (7 dias)</legend>
    <form method="post" id="form-recomp">
        <table class="adm-table">
            <thead>
                <tr>
                    <th width="30">Dia</th>
                    <th width="130">Ícone</th>
                    <th>Yens</th>
                    <th>EXP</th>
                    <th>Prévia</th>
                </tr>
            </thead>
            <tbody>
            <?php for ($d = 1; $d <= 7; $d++):
                $rc   = $recompensas_ciclo[$d] ?? ['yens'=>0,'exp'=>0,'icone'=>''];
                $y    = (int)$rc['yens']; $e = (int)$rc['exp'];
                $ico  = $rc['icone'] ?? '';
                $prev = $y > 0 ? number_format($y,0,'.',',').' yens' : '';
                if ($e > 0) $prev .= ($prev ? ' + ' : '') . $e . ' exp';
                $ico_path = ($ico && file_exists("../_img/Login/$ico")) ? "../_img/Login/$ico" : '';
            ?>
            <tr>
                <td>
                    <b style="color:<?php echo $d===7?'#FFD700':'#ccc'; ?>"><?php echo $d; ?></b>
                </td>
                <td>
                    <input type="hidden" name="icone_<?php echo $d; ?>" id="ico_val_<?php echo $d; ?>" value="<?php echo htmlspecialchars($ico); ?>">
                    <div class="ico-picker-wrap">
                        <div class="ico-picker-btn" onclick="togglePicker(<?php echo $d; ?>)">
                            <?php if ($ico_path): ?>
                                <img id="ico_preview_<?php echo $d; ?>" src="<?php echo htmlspecialchars($ico_path); ?>" alt="">
                            <?php else: ?>
                                <span id="ico_preview_<?php echo $d; ?>" class="ico-none"></span>
                            <?php endif; ?>
                            <span id="ico_name_<?php echo $d; ?>" style="font-size:10px;color:#888;max-width:70px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo $ico ?: 'Nenhum'; ?></span>
                            <span style="color:#aaa;font-size:10px;">&#9660;</span>
                        </div>
                        <div class="ico-grid-panel" id="ico_panel_<?php echo $d; ?>">
                            <div class="ico-item <?php echo $ico===''?'selected':''; ?>" onclick="selectIcon(<?php echo $d; ?>,'')">
                                <div style="width:100%;height:100%;background:#1a1200;border:1px dashed #444;display:flex;align-items:center;justify-content:center;font-size:9px;color:#555;">X</div>
                            </div>
                            <?php foreach ($icones_disponiveis as $img_file): ?>
                            <div class="ico-item <?php echo $ico===$img_file?'selected':''; ?>" onclick="selectIcon(<?php echo $d; ?>,'<?php echo addslashes($img_file); ?>')">
                                <img src="<?php echo htmlspecialchars("../_img/Login/$img_file"); ?>" alt="<?php echo htmlspecialchars($img_file); ?>" title="<?php echo htmlspecialchars($img_file); ?>">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php if ($ico): ?>
                    <span class="ico-clear" onclick="selectIcon(<?php echo $d; ?>,'')">remover</span>
                    <?php endif; ?>
                </td>
                <td>
                    <input type="number" name="yens_<?php echo $d; ?>" value="<?php echo $y; ?>" min="0" max="999999" style="width:80px;">
                    <img src="../_img/yens.png" style="width:12px;height:12px;vertical-align:middle;">
                </td>
                <td>
                    <input type="number" name="exp_<?php echo $d; ?>" value="<?php echo $e; ?>" min="0" max="999999" style="width:80px;">
                    <img src="../_img/Icones/experiencia.png" style="width:12px;height:12px;vertical-align:middle;">
                </td>
                <td style="color:#FFD700;font-size:11px;"><?php echo htmlspecialchars($prev ?: '—'); ?></td>
            </tr>
            <?php endfor; ?>
            </tbody>
        </table>
        <div style="margin-top:10px;">
            <button type="submit" name="salvar_recompensas" class="btn-success">Salvar Recompensas</button>
        </div>
    </form>
</fieldset>

<script>
function togglePicker(d) {
    document.querySelectorAll('.ico-grid-panel').forEach(function(p){ if(p.id!=='ico_panel_'+d) p.classList.remove('open'); });
    var panel = document.getElementById('ico_panel_'+d);
    panel.classList.toggle('open');
}
function selectIcon(d, fname) {
    document.getElementById('ico_val_'+d).value = fname;
    var preview = document.getElementById('ico_preview_'+d);
    var nameEl  = document.getElementById('ico_name_'+d);
    if (fname) {
        if (preview.tagName === 'IMG') {
            preview.src = '../_img/Login/' + fname;
        } else {
            var img = document.createElement('img');
            img.id  = 'ico_preview_'+d;
            img.src = '../_img/Login/' + fname;
            img.style.cssText = 'width:32px;height:32px;object-fit:contain;';
            preview.parentNode.replaceChild(img, preview);
        }
        nameEl.textContent = fname;
    } else {
        if (preview.tagName === 'IMG') {
            var span = document.createElement('span');
            span.id = 'ico_preview_'+d;
            span.className = 'ico-none';
            preview.parentNode.replaceChild(span, preview);
        }
        nameEl.textContent = 'Nenhum';
    }
    // Marcar selecionado na grid
    var panel = document.getElementById('ico_panel_'+d);
    panel.querySelectorAll('.ico-item').forEach(function(el){ el.classList.remove('selected'); });
    // Fechar painel
    panel.classList.remove('open');
}
// Fechar ao clicar fora
document.addEventListener('click', function(e){
    if (!e.target.closest('.ico-picker-wrap')) {
        document.querySelectorAll('.ico-grid-panel').forEach(function(p){ p.classList.remove('open'); });
    }
});
</script>

<!-- Top Streaks -->
<fieldset>
    <legend>Top Streaks</legend>
    <?php if (empty($top_streaks)): ?>
        <div class="alert-warning">Nenhum jogador no sistema ainda.</div>
    <?php else: ?>
    <table class="adm-table">
        <thead>
            <tr><th>#</th><th>Jogador</th><th>Nível</th><th>Streak</th><th>Total Logins</th><th>Último Login</th><th>Ação</th></tr>
        </thead>
        <tbody>
        <?php foreach ($top_streaks as $i => $ts): ?>
            <tr>
                <td><?php echo $i+1; ?></td>
                <td><b><?php echo htmlspecialchars($ts['usuario']); ?></b></td>
                <td><?php echo (int)$ts['nivel']; ?></td>
                <td style="color:#FFD700;font-weight:bold;"><?php echo (int)$ts['streak']; ?> dias</td>
                <td><?php echo (int)$ts['total_logins']; ?></td>
                <td style="color:#888;"><?php echo htmlspecialchars($ts['ultimo_login']); ?></td>
                <td>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Resetar streak de <?php echo htmlspecialchars($ts['usuario']); ?>?');">
                        <input type="hidden" name="reset_streak" value="<?php echo (int)$ts['uid']; ?>">
                        <button type="submit" class="btn-danger" style="font-size:10px;padding:2px 7px;">Reset</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</fieldset>

<!-- Recompensa Manual -->
<fieldset>
    <legend>Recompensa Manual</legend>
    <form method="post">
        <table cellpadding="4" cellspacing="0">
            <tr>
                <td><label>ID do Jogador:</label></td>
                <td><input type="number" name="uid_recomp" min="1" style="width:80px;" required></td>
            </tr>
            <tr>
                <td><label>Yens:</label></td>
                <td>
                    <input type="number" name="yens_manual" min="0" value="0" style="width:80px;">
                    <img src="../_img/yens.png" style="width:12px;height:12px;vertical-align:middle;">
                </td>
            </tr>
            <tr>
                <td><label>EXP:</label></td>
                <td>
                    <input type="number" name="exp_manual" min="0" value="0" style="width:80px;">
                    <img src="../_img/Icones/experiencia.png" style="width:12px;height:12px;vertical-align:middle;">
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <button type="submit" name="dar_recompensa" class="btn-success">Entregar Recompensa</button>
                </td>
            </tr>
        </table>
    </form>
</fieldset>

<?php require_once('adm_footer.php'); ?>
