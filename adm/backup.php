<?php
require_once('../_inc/conexao.php');
require_once('../_inc/backup_engine.php');
if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['logado']) && !isset($_SESSION['userid']) && !isset($_SESSION['uid'])) {
    header('Location: ../index.php'); exit;
}
$user_id = $_SESSION['logado'] ?? $_SESSION['userid'] ?? $_SESSION['uid'];
$stmt = $conexao->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$usuario_logado = $stmt->fetch(PDO::FETCH_ASSOC);
$modulo_necessario = 'backup';
require_once('_gm_auth.php');

backup_install_tables($conexao);

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'salvar_config':
                $modo = in_array($_POST['modo'] ?? '', ['minutos','horas','semanal'], true) ? $_POST['modo'] : 'horas';
                $intervalo = (int)($_POST['intervalo'] ?? 24);
                if ($modo === 'minutos') $intervalo = max(1, min(60, $intervalo));
                if ($modo === 'horas')   $intervalo = max(1, min(24, $intervalo));
                $dia_semana = max(0, min(6, (int)($_POST['dia_semana'] ?? 0)));
                $hora       = max(0, min(23, (int)($_POST['hora'] ?? 3)));
                $minuto     = max(0, min(59, (int)($_POST['minuto'] ?? 0)));
                $pasta      = trim((string)($_POST['pasta_destino'] ?? 'backups'));
                if ($pasta === '') $pasta = 'backups';
                $max_backups = max(1, min(500, (int)($_POST['max_backups'] ?? 30)));
                $incluir_forum = isset($_POST['incluir_forum']) ? 1 : 0;
                $mysqldump_path = trim((string)($_POST['mysqldump_path'] ?? ''));
                if (strlen($mysqldump_path) > 500) $mysqldump_path = substr($mysqldump_path, 0, 500);
                $ativo = isset($_POST['ativo']) ? 1 : 0;

                // Cria pasta para validar caminho
                backup_resolve_destino($pasta);

                $cfg = $conexao->query("SELECT * FROM backup_config ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                $proximo = backup_calc_proximo([
                    'modo' => $modo, 'intervalo' => $intervalo,
                    'dia_semana' => $dia_semana, 'hora' => $hora, 'minuto' => $minuto,
                ]);
                $stmt = $conexao->prepare("UPDATE backup_config
                    SET modo=?, intervalo=?, dia_semana=?, hora=?, minuto=?,
                        pasta_destino=?, max_backups=?, incluir_forum=?, mysqldump_path=?, ativo=?,
                        proximo_backup=?
                    WHERE id=?");
                $stmt->execute([$modo, $intervalo, $dia_semana, $hora, $minuto,
                    $pasta, $max_backups, $incluir_forum, $mysqldump_path, $ativo,
                    $ativo ? $proximo->format('Y-m-d H:i:s') : null,
                    $cfg['id']]);
                $msg = ['type' => 'success', 'text' => '✅ Configuração salva. Próximo backup: ' . $proximo->format('d/m/Y H:i')];
                break;

            case 'rodar_agora':
                $res = backup_run_now($conexao, 'manual');
                if ($res['ok']) {
                    $arq = implode(', ', $res['arquivos']);
                    $msg = ['type' => 'success', 'text' => '✅ Backup feito! Arquivos: ' . htmlspecialchars($arq) . ' (' . round($res['tamanho']/1024, 1) . ' KB)'];
                } else {
                    $msg = ['type' => 'error', 'text' => '❌ Falha no backup: ' . htmlspecialchars($res['erro'] ?: 'erro desconhecido')];
                }
                break;

            case 'apagar':
                $id = (int)($_POST['id'] ?? 0);
                $row = $conexao->prepare("SELECT * FROM backup_historico WHERE id=?");
                $row->execute([$id]);
                $h = $row->fetch(PDO::FETCH_ASSOC);
                if ($h) {
                    $cfg = $conexao->query("SELECT pasta_destino FROM backup_config ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                    $destino = backup_resolve_destino($cfg['pasta_destino']);
                    foreach (['arquivo_naruto', 'arquivo_forum'] as $c) {
                        if (!empty($h[$c])) {
                            $abs = $destino . '/' . $h[$c];
                            if (is_file($abs)) @unlink($abs);
                        }
                    }
                    $conexao->prepare("DELETE FROM backup_historico WHERE id=?")->execute([$id]);
                    $msg = ['type' => 'success', 'text' => '✅ Backup removido.'];
                }
                break;
        }
    } catch (Exception $e) {
        $msg = ['type' => 'error', 'text' => '❌ Erro: ' . htmlspecialchars($e->getMessage())];
    }
}

// Download de arquivo
if (isset($_GET['download'])) {
    $id = (int)$_GET['download'];
    $tipo = $_GET['tipo'] ?? 'naruto';
    $col = $tipo === 'forum' ? 'arquivo_forum' : 'arquivo_naruto';
    $row = $conexao->prepare("SELECT $col AS arq FROM backup_historico WHERE id=?");
    $row->execute([$id]);
    $h = $row->fetch(PDO::FETCH_ASSOC);
    $cfg = $conexao->query("SELECT pasta_destino FROM backup_config ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $destino = backup_resolve_destino($cfg['pasta_destino']);
    if ($h && !empty($h['arq']) && is_file($destino . '/' . $h['arq'])) {
        $abs = $destino . '/' . $h['arq'];
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . basename($abs) . '"');
        header('Content-Length: ' . filesize($abs));
        readfile($abs);
        exit;
    }
    http_response_code(404);
    exit('Arquivo não encontrado.');
}

$cfg = $conexao->query("SELECT * FROM backup_config ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$historico = $conexao->query("SELECT * FROM backup_historico ORDER BY criado_em DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);

$dump_disp = backup_dump_command();
$pasta_abs = backup_resolve_destino($cfg['pasta_destino']);

// Nomes reais dos bancos (lidos do config gerado pelo install/)
$dbCfg = require __DIR__ . '/../config/database.php';
$nome_db_main  = $dbCfg['mysql']['dbname']         ?? 'banco';
$nome_db_forum = $dbCfg['mysql_forum']['dbname']   ?? '';

$page_title = 'Backup Automático';
include 'adm_header.php';
?>
<style>
.bk-section { background:#1a1200; border:1px solid #444; padding:14px; margin-bottom:14px; }
.bk-section h3 { color:#ff6600; margin:0 0 8px 0; font-size:14px; border-bottom:1px solid #333; padding-bottom:5px; }
.bk-row { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:10px; }
.bk-row label { color:#BBB; font-size:11px; min-width:130px; }
.bk-row input[type=number], .bk-row input[type=text], .bk-row select {
  background:#0a0800; color:#fff; border:1px solid #555; padding:6px 8px; font-size:12px; min-width:80px;
}
.bk-row input[type=text] { min-width:200px; }
.bk-status { padding:8px 12px; margin-bottom:10px; font-size:12px; border-left:4px solid #888; background:#0a0800; }
.bk-status.ok  { border-left-color:#4CAF50; color:#9eff9e; }
.bk-status.err { border-left-color:#ff4400; color:#ff9966; }
.bk-table { width:100%; border-collapse:collapse; font-size:11px; }
.bk-table th, .bk-table td { padding:6px 8px; text-align:left; border-bottom:1px solid #333; }
.bk-table th { background:#0a0800; color:#ff6600; font-weight:bold; }
.bk-table tr:hover td { background:#221800; }
.bk-tag { display:inline-block; padding:2px 7px; font-size:10px; font-weight:bold; }
.bk-tag.ok  { background:#1a3a1a; color:#9eff9e; border:1px solid #4CAF50; }
.bk-tag.err { background:#3a1a1a; color:#ff9966; border:1px solid #ff4400; }
.bk-tag.auto   { background:#1a2a3a; color:#9ec6ff; border:1px solid #4488cc; }
.bk-tag.manual { background:#3a2a1a; color:#ffcc88; border:1px solid #cc8844; }
.bk-modo-box { display:none; }
.bk-modo-box.active { display:block; }
.bk-warn { background:#3a2a1a; border:1px solid #cc8844; color:#ffcc88; padding:8px 12px; font-size:11px; margin-bottom:10px; }
.bk-info { color:#BBB; font-size:11px; margin-top:4px; }
.botao-primario { background:#ff6600; color:#000; border:1px solid #000; padding:7px 16px; font-weight:bold; cursor:pointer; font-size:12px; }
.botao-primario:hover { background:#ff8c33; }
.botao-secundario { background:#444; color:#fff; border:1px solid #000; padding:7px 16px; cursor:pointer; font-size:12px; }
</style>

<?php if ($msg): ?>
<div class="bk-status <?php echo $msg['type'] === 'success' ? 'ok' : 'err'; ?>"><?php echo $msg['text']; ?></div>
<?php endif; ?>

<?php if ($dump_disp === null): ?>
<div class="bk-warn">⚠️ <b>mariadb-dump / mysqldump</b> não foi encontrado no PATH. O backup só funciona se essa ferramenta estiver instalada.</div>
<?php endif; ?>

<div class="bk-section">
    <h3>📊 Status</h3>
    <div class="bk-row">
        <span><b>Estado:</b> <?php echo (int)$cfg['ativo'] === 1 ? '<span class="bk-tag ok">ATIVO</span>' : '<span class="bk-tag err">DESATIVADO</span>'; ?></span>
        <span><b>Último backup:</b> <?php echo $cfg['ultimo_backup'] ? date('d/m/Y H:i', strtotime($cfg['ultimo_backup'])) : '—'; ?></span>
        <span><b>Próximo backup:</b> <?php echo $cfg['proximo_backup'] ? date('d/m/Y H:i', strtotime($cfg['proximo_backup'])) : '—'; ?></span>
    </div>
    <div class="bk-info">📁 Pasta atual: <code><?php echo htmlspecialchars($pasta_abs); ?></code></div>
    <div class="bk-info">🗄️ Banco principal: <code><?php echo htmlspecialchars($nome_db_main); ?></code><?php if($nome_db_forum): ?> &nbsp; • &nbsp; Banco do fórum: <code><?php echo htmlspecialchars($nome_db_forum); ?></code><?php endif; ?></div>
</div>

<div class="bk-section">
    <h3>⚙️ Configuração</h3>
    <form method="POST" action="?modulo=backup">
        <input type="hidden" name="action" value="salvar_config">

        <div class="bk-row">
            <label>Modo de agendamento</label>
            <select name="modo" id="modoSel" onchange="bkUpdateModo(this.value)">
                <option value="minutos" <?php echo $cfg['modo']==='minutos'?'selected':''; ?>>A cada N minutos (1–60)</option>
                <option value="horas"   <?php echo $cfg['modo']==='horas'  ?'selected':''; ?>>A cada N horas (1–24)</option>
                <option value="semanal" <?php echo $cfg['modo']==='semanal'?'selected':''; ?>>Dia da semana + hora</option>
            </select>
        </div>

        <div class="bk-modo-box" id="box_minutos">
            <div class="bk-row">
                <label>Intervalo em minutos</label>
                <input type="number" name="intervalo" min="1" max="60" value="<?php echo $cfg['modo']==='minutos' ? (int)$cfg['intervalo'] : 30; ?>">
            </div>
        </div>

        <div class="bk-modo-box" id="box_horas">
            <div class="bk-row">
                <label>Intervalo em horas</label>
                <input type="number" name="intervalo" min="1" max="24" value="<?php echo $cfg['modo']==='horas' ? (int)$cfg['intervalo'] : 24; ?>">
            </div>
        </div>

        <div class="bk-modo-box" id="box_semanal">
            <div class="bk-row">
                <label>Dia da semana</label>
                <select name="dia_semana">
                    <?php
                    $dias = [0=>'Domingo',1=>'Segunda',2=>'Terça',3=>'Quarta',4=>'Quinta',5=>'Sexta',6=>'Sábado'];
                    foreach($dias as $i=>$d) {
                        $sel = ((int)$cfg['dia_semana']===$i)?'selected':'';
                        echo "<option value='$i' $sel>$d</option>";
                    } ?>
                </select>
                <label>Hora</label>
                <input type="number" name="hora" min="0" max="23" value="<?php echo (int)$cfg['hora']; ?>">
                <label>Minuto</label>
                <input type="number" name="minuto" min="0" max="59" value="<?php echo (int)$cfg['minuto']; ?>">
            </div>
        </div>

        <div class="bk-row">
            <label>Pasta de destino</label>
            <input type="text" name="pasta_destino" value="<?php echo htmlspecialchars($cfg['pasta_destino']); ?>" placeholder="backups">
            <span class="bk-info">Relativa à raiz do projeto. Será criada se não existir.</span>
        </div>

        <div class="bk-row">
            <label>Máximo de backups guardados</label>
            <input type="number" name="max_backups" min="1" max="500" value="<?php echo (int)$cfg['max_backups']; ?>">
            <span class="bk-info">Os mais antigos serão apagados automaticamente.</span>
        </div>

        <div class="bk-row">
            <label>Caminho do mysqldump</label>
            <input type="text" name="mysqldump_path" value="<?php echo htmlspecialchars($cfg['mysqldump_path'] ?? ''); ?>" placeholder="(deixe vazio para detectar automaticamente)" style="min-width:380px;">
            <span class="bk-info">
                Opcional. Use somente se o backup falhar dizendo "mysqldump não encontrado".<br>
                Pode ser o arquivo executável OU a pasta que o contém.<br>
                <b>XAMPP (Windows):</b> <code>C:\xampp\mysql\bin</code><br>
                <b>WAMP (Windows):</b> <code>C:\wamp64\bin\mariadb\mariadb10.x.x\bin</code><br>
                <b>Linux/cPanel:</b> normalmente já está no PATH (deixe vazio).
            </span>
        </div>

        <div class="bk-row">
            <label>&nbsp;</label>
            <label style="min-width:auto;color:#fff;">
                <input type="checkbox" name="incluir_forum" value="1" <?php echo (int)$cfg['incluir_forum']===1?'checked':''; ?> <?php echo $nome_db_forum ? '' : 'disabled'; ?>>
                Incluir banco do fórum<?php echo $nome_db_forum ? ' (<code>'.htmlspecialchars($nome_db_forum).'</code>)' : ' — não configurado'; ?>
            </label>
            <label style="min-width:auto;color:#fff;margin-left:18px;">
                <input type="checkbox" name="ativo" value="1" <?php echo (int)$cfg['ativo']===1?'checked':''; ?>>
                Backup automático ATIVO
            </label>
        </div>

        <div class="bk-row" style="margin-top:14px;">
            <button type="submit" class="botao-primario">💾 Salvar Configuração</button>
        </div>
    </form>
</div>

<div class="bk-section">
    <h3>▶️ Backup Manual</h3>
    <form method="POST" action="?modulo=backup" style="display:inline;">
        <input type="hidden" name="action" value="rodar_agora">
        <button type="submit" class="botao-primario" onclick="return confirm('Rodar backup agora?');">▶️ Fazer Backup Agora</button>
    </form>
    <span class="bk-info" style="margin-left:12px;">Executa imediatamente, sem alterar o agendamento.</span>
</div>

<div class="bk-section">
    <h3>📦 Histórico (últimos 50)</h3>
    <?php if (empty($historico)): ?>
        <div class="bk-info">Nenhum backup ainda.</div>
    <?php else: ?>
    <table class="bk-table">
        <thead>
            <tr>
                <th>Quando</th>
                <th>Origem</th>
                <th>Status</th>
                <th>Banco principal</th>
                <th>Banco do fórum</th>
                <th>Tamanho</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($historico as $h): ?>
            <tr>
                <td><?php echo date('d/m/Y H:i', strtotime($h['criado_em'])); ?></td>
                <td><span class="bk-tag <?php echo $h['origem']==='auto'?'auto':'manual'; ?>"><?php echo strtoupper($h['origem']); ?></span></td>
                <td><span class="bk-tag <?php echo $h['status']==='sucesso'?'ok':'err'; ?>"><?php echo strtoupper($h['status']); ?></span>
                    <?php if (!empty($h['erro_mensagem'])): ?>
                        <div style="color:#ff9966;font-size:10px;margin-top:3px;"><?php echo htmlspecialchars(mb_substr($h['erro_mensagem'], 0, 200)); ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($h['arquivo_naruto'])): ?>
                        <a href="?modulo=backup&download=<?php echo $h['id']; ?>&tipo=naruto" style="color:#9eff9e;">⬇️ <?php echo htmlspecialchars($h['arquivo_naruto']); ?></a>
                    <?php else: ?>
                        <span style="color:#666;">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($h['arquivo_forum'])): ?>
                        <a href="?modulo=backup&download=<?php echo $h['id']; ?>&tipo=forum" style="color:#9ec6ff;">⬇️ <?php echo htmlspecialchars($h['arquivo_forum']); ?></a>
                    <?php else: ?>
                        <span style="color:#666;">—</span>
                    <?php endif; ?>
                </td>
                <td><?php echo $h['tamanho_bytes'] > 0 ? round($h['tamanho_bytes']/1024, 1).' KB' : '—'; ?></td>
                <td>
                    <form method="POST" action="?modulo=backup" style="display:inline;" onsubmit="return confirm('Remover este backup e seus arquivos?');">
                        <input type="hidden" name="action" value="apagar">
                        <input type="hidden" name="id" value="<?php echo $h['id']; ?>">
                        <button type="submit" class="botao-secundario" style="padding:3px 8px;font-size:10px;">🗑️</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<script>
function bkUpdateModo(v) {
    var boxes = document.querySelectorAll('.bk-modo-box');
    boxes.forEach(function(b){ b.classList.remove('active'); });
    var alvo = document.getElementById('box_' + v);
    if (alvo) alvo.classList.add('active');
}
bkUpdateModo(document.getElementById('modoSel').value);
</script>

<?php include 'adm_footer.php'; ?>
