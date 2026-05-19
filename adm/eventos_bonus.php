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
if (!$is_admin_ext) { header('Location: adm.php'); exit; }

$page_title = 'Eventos de Bônus';
$msg = ''; $msg_tipo = '';

// ── Criar tabela se não existir ───────────────────────────────────────────────
try {
    $ai  = Database::isMysql() ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $dts = Database::isMysql() ? 'CURRENT_TIMESTAMP'              : '(CURRENT_TIMESTAMP)';
    $conexao->exec("CREATE TABLE IF NOT EXISTS eventos_bonus (
        id        $ai,
        nome      VARCHAR(120) NOT NULL,
        descricao TEXT         NOT NULL DEFAULT '',
        mult_exp  DECIMAL(5,2) NOT NULL DEFAULT 1.00,
        mult_yens DECIMAL(5,2) NOT NULL DEFAULT 1.00,
        mult_drop DECIMAL(5,2) NOT NULL DEFAULT 1.00,
        inicio    DATETIME     NOT NULL,
        fim       DATETIME     NOT NULL,
        banner_cor VARCHAR(20) NOT NULL DEFAULT '#ff6600',
        criado_em DATETIME     DEFAULT $dts
    )");
} catch (Exception $e) {}

$agora = date('Y-m-d H:i:s');

// ── AÇÕES ─────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['criar_evento'])) {
        $nome      = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $mult_exp  = max(0.1, min(10.0, (float)str_replace(',','.',($_POST['mult_exp']  ?? '1'))));
        $mult_yens = max(0.1, min(10.0, (float)str_replace(',','.',($_POST['mult_yens'] ?? '1'))));
        $mult_drop = max(0.1, min(10.0, (float)str_replace(',','.',($_POST['mult_drop'] ?? '1'))));
        $inicio    = trim($_POST['inicio'] ?? '');
        $fim       = trim($_POST['fim']    ?? '');
        $cor       = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['banner_cor'] ?? '') ? $_POST['banner_cor'] : '#ff6600';

        if ($nome === '' || $inicio === '' || $fim === '') {
            $msg = 'Preencha nome, data de início e data de fim.'; $msg_tipo = 'error';
        } elseif ($fim <= $inicio) {
            $msg = 'A data de fim deve ser posterior ao início.'; $msg_tipo = 'error';
        } else {
            try {
                $st = $conexao->prepare(
                    "INSERT INTO eventos_bonus (nome, descricao, mult_exp, mult_yens, mult_drop, inicio, fim, banner_cor)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $st->execute([$nome, $descricao, $mult_exp, $mult_yens, $mult_drop, $inicio, $fim, $cor]);
                $msg = 'Evento criado com sucesso!'; $msg_tipo = 'success';
            } catch (Exception $e) {
                $msg = 'Erro ao criar evento: ' . $e->getMessage(); $msg_tipo = 'error';
            }
        }
    }

    if (isset($_POST['deletar_evento'])) {
        $del_id = (int)($_POST['evento_id'] ?? 0);
        if ($del_id > 0) {
            try {
                $conexao->prepare("DELETE FROM eventos_bonus WHERE id = ?")->execute([$del_id]);
                $msg = 'Evento removido.'; $msg_tipo = 'success';
            } catch (Exception $e) {
                $msg = 'Erro ao remover evento.'; $msg_tipo = 'error';
            }
        }
    }

    if (isset($_POST['editar_evento'])) {
        $eid      = (int)($_POST['evento_id'] ?? 0);
        $nome     = trim($_POST['nome']     ?? '');
        $desc     = trim($_POST['descricao'] ?? '');
        $mexp     = max(0.1, min(10.0, (float)str_replace(',','.',($_POST['mult_exp']  ?? '1'))));
        $myens    = max(0.1, min(10.0, (float)str_replace(',','.',($_POST['mult_yens'] ?? '1'))));
        $mdrop    = max(0.1, min(10.0, (float)str_replace(',','.',($_POST['mult_drop'] ?? '1'))));
        $inicio   = trim($_POST['inicio'] ?? '');
        $fim      = trim($_POST['fim']    ?? '');
        $cor      = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['banner_cor'] ?? '') ? $_POST['banner_cor'] : '#ff6600';
        if ($eid > 0 && $nome !== '' && $fim > $inicio) {
            try {
                $conexao->prepare(
                    "UPDATE eventos_bonus SET nome=?, descricao=?, mult_exp=?, mult_yens=?, mult_drop=?, inicio=?, fim=?, banner_cor=? WHERE id=?"
                )->execute([$nome, $desc, $mexp, $myens, $mdrop, $inicio, $fim, $cor, $eid]);
                $msg = 'Evento atualizado.'; $msg_tipo = 'success';
            } catch (Exception $e) {
                $msg = 'Erro ao atualizar.'; $msg_tipo = 'error';
            }
        }
    }
}

// ── Carregar eventos ──────────────────────────────────────────────────────────
$eventos = [];
try {
    $eventos = $conexao->query(
        "SELECT * FROM eventos_bonus ORDER BY inicio DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Evento a editar (GET)
$edit_id = (int)($_GET['editar'] ?? 0);
$edit_ev = null;
if ($edit_id > 0) {
    foreach ($eventos as $ev) {
        if ((int)$ev['id'] === $edit_id) { $edit_ev = $ev; break; }
    }
}

require_once('adm_header.php');
?>

<?php if ($msg): ?>
<div class="alert-<?php echo $msg_tipo; ?>"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<div class="adm-page-title">Eventos Temporários de Bônus</div>

<style>
.ev-status { display:inline-block; padding:1px 8px; border-radius:3px; font-size:10px; font-weight:bold; }
.ev-ativo   { background:#143314; color:#90ee90; border:1px solid #4a4; }
.ev-futuro  { background:#132040; color:#87cefa; border:1px solid #46f; }
.ev-expirado{ background:#2a2a2a; color:#666;    border:1px solid #444; }
.mult-badge { display:inline-block; background:#2a1a00; border:1px solid #ff6600; color:#FFD700; padding:2px 6px; font-size:11px; border-radius:3px; margin:1px; }
.ev-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.ev-form-row { display:flex; align-items:center; gap:8px; margin:4px 0; flex-wrap:wrap; }
.ev-form-row label { color:#aaa; font-size:11px; min-width:160px; }
.ev-form-row input, .ev-form-row textarea { background:#1a1200; border:1px solid #555; color:#fff; padding:4px 6px; font-size:12px; }
</style>

<!-- FORMULÁRIO CRIAR / EDITAR -->
<fieldset>
    <legend><?php echo $edit_ev ? 'Editar Evento' : 'Criar Novo Evento'; ?></legend>
    <form method="post">
        <?php if ($edit_ev): ?>
            <input type="hidden" name="evento_id" value="<?php echo $edit_ev['id']; ?>">
        <?php endif; ?>

        <div class="ev-form-row">
            <label>Nome do evento:</label>
            <input type="text" name="nome" value="<?php echo htmlspecialchars($edit_ev['nome'] ?? ''); ?>" maxlength="120" style="width:280px;" required>
        </div>
        <div class="ev-form-row">
            <label>Descrição (exibida no jogo):</label>
            <textarea name="descricao" rows="2" style="width:350px;"><?php echo htmlspecialchars($edit_ev['descricao'] ?? ''); ?></textarea>
        </div>
        <div class="ev-form-row">
            <label>Início:</label>
            <input type="datetime-local" name="inicio" value="<?php echo isset($edit_ev['inicio']) ? str_replace(' ','T',substr($edit_ev['inicio'],0,16)) : ''; ?>" required>
            <label style="margin-left:10px;">Fim:</label>
            <input type="datetime-local" name="fim" value="<?php echo isset($edit_ev['fim']) ? str_replace(' ','T',substr($edit_ev['fim'],0,16)) : ''; ?>" required>
        </div>
        <div class="ev-form-row">
            <label>Multiplicador EXP:</label>
            <input type="number" name="mult_exp"  value="<?php echo number_format((float)($edit_ev['mult_exp']  ?? 1),2,'.','.'); ?>" min="0.1" max="10" step="0.1" style="width:80px;">
            <span style="color:#555;font-size:10px;">Ex: 2.0 = EXP dobrada</span>
        </div>
        <div class="ev-form-row">
            <label>Multiplicador Yens:</label>
            <input type="number" name="mult_yens" value="<?php echo number_format((float)($edit_ev['mult_yens'] ?? 1),2,'.','.'); ?>" min="0.1" max="10" step="0.1" style="width:80px;">
        </div>
        <div class="ev-form-row">
            <label>Multiplicador Drop:</label>
            <input type="number" name="mult_drop" value="<?php echo number_format((float)($edit_ev['mult_drop'] ?? 1),2,'.','.'); ?>" min="0.1" max="10" step="0.1" style="width:80px;">
            <span style="color:#555;font-size:10px;">Ex: 1.5 = 50% mais chance de item</span>
        </div>
        <div class="ev-form-row">
            <label>Cor do banner:</label>
            <input type="color" name="banner_cor" value="<?php echo htmlspecialchars($edit_ev['banner_cor'] ?? '#ff6600'); ?>" style="width:50px;height:28px;padding:2px;">
            <span style="color:#555;font-size:10px;">Cor exibida no aviso do evento no jogo</span>
        </div>
        <div style="margin-top:8px; display:flex; gap:8px;">
            <?php if ($edit_ev): ?>
                <button type="submit" name="editar_evento" class="btn-success">Salvar Alterações</button>
                <a href="eventos_bonus.php" style="color:#aaa; font-size:11px; align-self:center;">Cancelar</a>
            <?php else: ?>
                <button type="submit" name="criar_evento" class="btn-success">Criar Evento</button>
            <?php endif; ?>
        </div>
    </form>
</fieldset>

<!-- LISTA DE EVENTOS -->
<fieldset>
    <legend>Todos os Eventos (<?php echo count($eventos); ?>)</legend>
    <?php if (empty($eventos)): ?>
        <p style="color:#666;">Nenhum evento cadastrado ainda.</p>
    <?php else: ?>
    <table class="adm-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Nome / Período</th>
                <th>Multiplicadores</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($eventos as $ev): ?>
            <?php
            $ini = $ev['inicio'];
            $fim = $ev['fim'];
            if ($agora >= $ini && $agora <= $fim) {
                $status = '<span class="ev-status ev-ativo">ATIVO</span>';
            } elseif ($agora < $ini) {
                $status = '<span class="ev-status ev-futuro">AGENDADO</span>';
            } else {
                $status = '<span class="ev-status ev-expirado">EXPIRADO</span>';
            }
            ?>
            <tr>
                <td style="color:#555;"><?php echo $ev['id']; ?></td>
                <td>
                    <b style="color:#FFD700;"><?php echo htmlspecialchars($ev['nome']); ?></b><br>
                    <span style="color:#555;font-size:10px;"><?php echo substr($ini,0,16); ?> até <?php echo substr($fim,0,16); ?></span>
                    <?php if (!empty($ev['descricao'])): ?>
                        <br><span style="color:#888;font-size:10px;"><?php echo htmlspecialchars(mb_substr($ev['descricao'],0,60)); ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ((float)$ev['mult_exp']  != 1.0): ?><span class="mult-badge">EXP x<?php echo number_format($ev['mult_exp'],2); ?></span><?php endif; ?>
                    <?php if ((float)$ev['mult_yens'] != 1.0): ?><span class="mult-badge">Yens x<?php echo number_format($ev['mult_yens'],2); ?></span><?php endif; ?>
                    <?php if ((float)$ev['mult_drop'] != 1.0): ?><span class="mult-badge">Drop x<?php echo number_format($ev['mult_drop'],2); ?></span><?php endif; ?>
                    <?php if ((float)$ev['mult_exp'] == 1.0 && (float)$ev['mult_yens'] == 1.0 && (float)$ev['mult_drop'] == 1.0): ?><span style="color:#555;font-size:10px;">nenhum</span><?php endif; ?>
                </td>
                <td><?php echo $status; ?></td>
                <td>
                    <a href="eventos_bonus.php?editar=<?php echo $ev['id']; ?>" style="color:#87CEFA;font-size:11px;">Editar</a>
                    &nbsp;
                    <form method="post" style="display:inline;" onsubmit="return confirm('Remover este evento?')">
                        <input type="hidden" name="evento_id" value="<?php echo $ev['id']; ?>">
                        <button type="submit" name="deletar_evento" style="background:none;border:none;color:#ff4444;font-size:11px;cursor:pointer;padding:0;">Remover</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</fieldset>

<fieldset>
    <legend>Como funciona</legend>
    <ul style="color:#888;font-size:11px;margin:4px 0;padding-left:16px;line-height:20px;">
        <li>Multiplicadores de eventos se <b style="color:#FFD700;">somam</b> ao multiplicador global (Config. do Jogo).</li>
        <li>Se dois eventos estiverem ativos ao mesmo tempo, os multiplicadores se <b style="color:#FFD700;">empilham</b> (multiplicam entre si).</li>
        <li><b style="color:#FFD700;">EXP</b> — aplicado ao ganho de experiência em caças e treinos.</li>
        <li><b style="color:#FFD700;">Yens</b> — aplicado ao ganho de yens em caças.</li>
        <li><b style="color:#FFD700;">Drop</b> — multiplica a chance de encontrar item ao finalizar caças.</li>
        <li>Eventos expirados ficam listados para histórico mas não têm efeito no jogo.</li>
    </ul>
</fieldset>

<?php require_once('adm_footer.php'); ?>
