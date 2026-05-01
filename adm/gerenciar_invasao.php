<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!file_exists('../_inc/conexao.php')) die("Erro: conexao.php não encontrado");
require_once('../_inc/conexao.php');
if (!isset($conexao)) die("Erro: conexão não estabelecida");
if (session_status() == PHP_SESSION_NONE) session_start();

if(!isset($_SESSION['logado']) && !isset($_SESSION['userid']) && !isset($_SESSION['uid'])) {
    echo "<script>window.location.href='../index.php';</script>"; exit;
}
$user_id = $_SESSION['logado'] ?? $_SESSION['userid'] ?? $_SESSION['uid'];
try {
    $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $usuario_logado = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { die("Erro: " . $e->getMessage()); }

$modulo_necessario = 'invasao';
require_once('_gm_auth.php');

$invasores_disponiveis = [
    'Uma Cauda' => 'Uma Cauda.jpg', 'Duas Caudas' => 'Duas Caudas.jpg',
    'Três Caudas' => 'Três Caudas.jpg', 'Quatro Caudas' => 'Quatro Caudas.jpg',
    'Cinco Caudas' => 'Cinco Caudas.jpg', 'Seis Caudas' => 'Seis Caudas.jpg',
    'Sete Caudas' => 'Sete Caudas.jpg', 'Oito Caudas' => 'Oito Caudas.jpg',
    'Nove Caudas' => 'Nove Caudas.jpg', 'Dez Caudas' => 'Dez Caudas.jpg'
];

try {
    $servidores_inv = $conexao->query("SELECT id, nome FROM servidores WHERE ativo = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $servidores_inv = [['id' => 1, 'nome' => 'Servidor 01']];
}

$flash_msg = '';
$flash_tipo = '';

if(isset($_POST['action'])) {
    if($_POST['action'] == 'criar_invasao') {
        $nome_invasor = trim($_POST['nome_invasor']);
        $hp_total = (int)$_POST['hp_total'];
        $premio_yens = (int)$_POST['premio_yens'];
        $bonus_vila = (int)$_POST['bonus_vila'];
        $servidor_invasao = isset($_POST['servidor_invasao_id']) ? (int)$_POST['servidor_invasao_id'] : 1;
        if(!empty($nome_invasor) && $hp_total > 0 && $premio_yens > 0 && $bonus_vila >= 1 && $bonus_vila <= 100) {
            try {
                $stmt = $conexao->prepare("UPDATE invasoes SET status = 'finalizada', data_fim = CURRENT_TIMESTAMP WHERE status = 'ativa' AND servidor_id = ?");
                $stmt->execute([$servidor_invasao]);
                $stmt = $conexao->prepare("UPDATE usuarios SET bonus_invasao_tai = 0, bonus_invasao_nin = 0, bonus_invasao_gen = 0, bonus_invasao_pct = 0 WHERE servidor_id = ?");
                $stmt->execute([$servidor_invasao]);
                $stmt = $conexao->prepare("DELETE FROM banner_invasao_visualizado WHERE data_visualizacao < " . Database::dateOffsetLiteral('-', 24, 'hours') . "");
                $stmt->execute();
                $imagem_arquivo = $invasores_disponiveis[$nome_invasor] ?? 'Uma Cauda.jpg';
                $stmt = $conexao->prepare("INSERT INTO invasoes (nome_invasor, imagem_arquivo, hp_total, hp_atual, premio_yens, bonus_vila, data_inicio, status, servidor_id) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, 'ativa', ?)");
                if($stmt->execute([$nome_invasor, $imagem_arquivo, $hp_total, $hp_total, $premio_yens, $bonus_vila, $servidor_invasao])) {
                    $flash_msg = "✅ Nova invasão criada com sucesso no servidor #$servidor_invasao!"; $flash_tipo = 'success';
                } else { $flash_msg = "❌ Erro ao criar invasão."; $flash_tipo = 'error'; }
            } catch (Exception $e) { $flash_msg = "❌ Erro: " . htmlspecialchars($e->getMessage()); $flash_tipo = 'error'; }
        } else { $flash_msg = "❌ Todos os campos são obrigatórios!"; $flash_tipo = 'error'; }
    }

    if($_POST['action'] == 'editar_invasao' && isset($_POST['invasao_id'])) {
        $invasao_id = (int)$_POST['invasao_id'];
        $hp_total = (int)$_POST['hp_total'];
        $hp_atual = (int)$_POST['hp_atual'];
        $premio_yens = (int)$_POST['premio_yens'];
        $bonus_vila = (int)$_POST['bonus_vila'];
        if($hp_total > 0 && $hp_atual >= 0 && $premio_yens > 0 && $bonus_vila >= 1 && $bonus_vila <= 100) {
            try {
                $stmt = $conexao->prepare("UPDATE invasoes SET hp_total = ?, hp_atual = ?, premio_yens = ?, bonus_vila = ? WHERE id = ?");
                if($stmt->execute([$hp_total, $hp_atual, $premio_yens, $bonus_vila, $invasao_id])) {
                    $flash_msg = "✅ Invasão editada com sucesso!"; $flash_tipo = 'success';
                } else { $flash_msg = "❌ Erro ao editar invasão."; $flash_tipo = 'error'; }
            } catch (Exception $e) { $flash_msg = "❌ Erro: " . htmlspecialchars($e->getMessage()); $flash_tipo = 'error'; }
        } else { $flash_msg = "❌ Valores inválidos!"; $flash_tipo = 'error'; }
    }

    if($_POST['action'] == 'criar_invasao_todos') {
        $hp_total   = (int)$_POST['hp_total_todos'];
        $premio_yens = (int)$_POST['premio_yens_todos'];
        if($hp_total > 0 && $premio_yens > 0 && !empty($servidores_inv)) {
            try {
                $nomes_invasores = array_keys($invasores_disponiveis);
                shuffle($nomes_invasores);
                $stmt = $conexao->prepare("DELETE FROM banner_invasao_visualizado WHERE data_visualizacao < " . Database::dateOffsetLiteral('-', 24, 'hours') . "");
                $stmt->execute();
                $criados = 0; $msgs = [];
                foreach($servidores_inv as $idx => $srv) {
                    $srv_id = $srv['id'];
                    $nome_invasor = $nomes_invasores[$idx % count($nomes_invasores)];
                    $imagem_arquivo = $invasores_disponiveis[$nome_invasor];
                    $bonus_vila = rand(2, 8);
                    $stmt = $conexao->prepare("UPDATE invasoes SET status = 'finalizada', data_fim = CURRENT_TIMESTAMP WHERE status = 'ativa' AND servidor_id = ?");
                    $stmt->execute([$srv_id]);
                    $stmt = $conexao->prepare("UPDATE usuarios SET bonus_invasao_tai = 0, bonus_invasao_nin = 0, bonus_invasao_gen = 0, bonus_invasao_pct = 0 WHERE servidor_id = ?");
                    $stmt->execute([$srv_id]);
                    $stmt = $conexao->prepare("INSERT INTO invasoes (nome_invasor, imagem_arquivo, hp_total, hp_atual, premio_yens, bonus_vila, data_inicio, status, servidor_id) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, 'ativa', ?)");
                    if($stmt->execute([$nome_invasor, $imagem_arquivo, $hp_total, $hp_total, $premio_yens, $bonus_vila, $srv_id])) {
                        $criados++; $msgs[] = "✅ <strong>" . htmlspecialchars($srv['nome']) . "</strong>: " . htmlspecialchars($nome_invasor) . " — Bônus: +{$bonus_vila}%";
                    } else { $msgs[] = "❌ <strong>" . htmlspecialchars($srv['nome']) . "</strong>: Erro ao criar"; }
                }
                $flash_msg = "🌐 Invasão Global — {$criados} servidor(es) iniciado(s)!<ul style='margin:5px 0 0 0;padding-left:18px;'>" . implode('', array_map(fn($m) => "<li>$m</li>", $msgs)) . "</ul>";
                $flash_tipo = 'success';
            } catch(Exception $e) { $flash_msg = "❌ Erro: " . htmlspecialchars($e->getMessage()); $flash_tipo = 'error'; }
        } else { $flash_msg = "❌ HP e Prêmio são obrigatórios e deve haver servidores ativos!"; $flash_tipo = 'error'; }
    }

    if($_POST['action'] == 'finalizar_invasao' && isset($_POST['invasao_id'])) {
        $invasao_id = (int)$_POST['invasao_id'];
        try {
            $stmt = $conexao->prepare("SELECT servidor_id FROM invasoes WHERE id = ?");
            $stmt->execute([$invasao_id]);
            $inv_data = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt = $conexao->prepare("UPDATE invasoes SET status = 'finalizada', data_fim = CURRENT_TIMESTAMP WHERE id = ?");
            if($stmt->execute([$invasao_id])) {
                if($inv_data && isset($inv_data['servidor_id'])) {
                    $stmt = $conexao->prepare("UPDATE usuarios SET bonus_invasao_tai = 0, bonus_invasao_nin = 0, bonus_invasao_gen = 0, bonus_invasao_pct = 0 WHERE servidor_id = ?");
                    $stmt->execute([$inv_data['servidor_id']]);
                }
                $flash_msg = "✅ Invasão finalizada com sucesso!"; $flash_tipo = 'success';
            } else { $flash_msg = "❌ Erro ao finalizar invasão."; $flash_tipo = 'error'; }
        } catch (Exception $e) { $flash_msg = "❌ Erro: " . htmlspecialchars($e->getMessage()); $flash_tipo = 'error'; }
    }
}

$stmt = $conexao->prepare("SELECT i.*, s.nome AS nome_servidor FROM invasoes i LEFT JOIN servidores s ON i.servidor_id = s.id WHERE i.status = 'ativa' ORDER BY i.id DESC");
$stmt->execute();
$invasoes_ativas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conexao->prepare("SELECT i.*, s.nome AS nome_servidor FROM invasoes i LEFT JOIN servidores s ON i.servidor_id = s.id ORDER BY i.id DESC LIMIT 15");
$stmt->execute();
$invasoes_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Gerenciar Invasões';
include 'adm_header.php';
?>

<div class="box_top">⚡ Gerenciamento de Invasões</div>
<div class="box_middle">

<?php if($flash_msg): ?>
    <div class="alert-<?php echo $flash_tipo; ?>"><?php echo $flash_msg; ?></div>
    <div class="sep"></div>
<?php endif; ?>

<fieldset>
    <legend>🆕 Criar Nova Invasão em Servidor Específico</legend>
    <form method="POST">
        <input type="hidden" name="action" value="criar_invasao">
        <table width="100%" cellspacing="4">
            <tr>
                <td><label>Servidor:</label><br>
                    <select name="servidor_invasao_id" required style="width:100%;">
                        <?php foreach($servidores_inv as $srv): ?>
                            <option value="<?php echo $srv['id']; ?>"><?php echo htmlspecialchars($srv['nome']); ?></option>
                        <?php endforeach; ?>
                    </select></td>
                <td><label>Invasor:</label><br>
                    <select name="nome_invasor" required style="width:100%;">
                        <?php foreach($invasores_disponiveis as $nome => $arquivo): ?>
                            <option value="<?php echo htmlspecialchars($nome); ?>"><?php echo htmlspecialchars($nome); ?></option>
                        <?php endforeach; ?>
                    </select></td>
                <td><label>HP Total:</label><br>
                    <input type="number" name="hp_total" value="2000000" min="1" max="10000000" required style="width:100%;"></td>
                <td><label>Prêmio (Yens):</label><br>
                    <input type="number" name="premio_yens" value="1000000" min="1" max="10000000" required style="width:100%;"></td>
                <td><label>Bônus Vila (%):</label><br>
                    <input type="number" name="bonus_vila" value="10" min="1" max="100" required style="width:100%;"></td>
            </tr>
            <tr>
                <td colspan="5"><button type="submit" class="botao btn-success">🚀 Criar Invasão</button></td>
            </tr>
        </table>
    </form>
</fieldset>

<div class="sep"></div>

<fieldset>
    <legend>🌐 Iniciar Invasão em TODOS os Servidores</legend>
    <p class="sub2">Cada servidor receberá um monstro único (escolhido aleatoriamente) e o Bônus Vila será sorteado entre 2% e 8%.
    <?php if(count($servidores_inv) > count($invasores_disponiveis)): ?> <span style="color:#FFAAAA;">⚠️ Mais servidores do que monstros — alguns monstros serão repetidos.</span><?php endif; ?></p>
    <form method="POST" onsubmit="return confirm('Criar invasão em TODOS os <?php echo count($servidores_inv); ?> servidor(es)?');">
        <input type="hidden" name="action" value="criar_invasao_todos">
        <table cellspacing="4">
            <tr>
                <td><label>HP Total (todos):</label><br><input type="number" name="hp_total_todos" value="2000000" min="1" max="10000000" required></td>
                <td><label>Prêmio (Yens, todos):</label><br><input type="number" name="premio_yens_todos" value="1000000" min="1" max="10000000" required></td>
                <td valign="bottom"><button type="submit" class="botao" style="background:url('../_img/fundo_botao.jpg') left; border-color:#FF9800;">🌐 Invadir Todos (<?php echo count($servidores_inv); ?> servidores)</button></td>
            </tr>
        </table>
    </form>
    <?php if(!empty($servidores_inv)): ?>
    <div style="margin-top:6px; font-size:11px; color:#888;">
        Servidores:
        <?php foreach($servidores_inv as $srv): ?>
            <span style="background:#ff6600; color:#fff; padding:2px 6px; margin:2px; font-size:10px;">🖥️ <?php echo htmlspecialchars($srv['nome']); ?></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</fieldset>

<div class="sep"></div>

<?php if(!empty($invasoes_ativas)): ?>
<h3>⚔️ Invasões Ativas (<?php echo count($invasoes_ativas); ?>)</h3>
<div class="sep"></div>
<?php foreach($invasoes_ativas as $inv): ?>
<fieldset style="border-color:#ff6600;">
    <legend style="color:#ff6600;">🖥️ <?php echo htmlspecialchars($inv['nome_servidor'] ?? 'Servidor #'.$inv['servidor_id']); ?> — <?php echo htmlspecialchars($inv['nome_invasor']); ?></legend>
    <table width="100%">
        <tr>
            <td width="150" align="center" valign="top">
                <img src="../_img/Invasao/<?php echo htmlspecialchars($inv['imagem_arquivo']); ?>"
                     style="max-width:130px; border:2px solid #ff6600;">
            </td>
            <td valign="top" style="padding-left:10px;">
                <div class="stats-row">
                    <div class="stat-box">
                        <div class="stat-number"><?php echo number_format($inv['hp_atual']); ?></div>
                        <div>HP Atual / <?php echo number_format($inv['hp_total']); ?></div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number" style="color:#FFD700;"><?php echo number_format($inv['premio_yens']); ?></div>
                        <div>Prêmio (Yens)</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number" style="color:#90EE90;">+<?php echo $inv['bonus_vila']; ?>%</div>
                        <div>Bônus Vila</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number" style="color:#FFAAAA;"><?php echo number_format($inv['players_derrotados']); ?></div>
                        <div>Players Derrotados</div>
                    </div>
                </div>
                <div class="sep"></div>
                <div style="background:#111; border:1px solid #333; height:14px; position:relative;">
                    <?php $pct = $inv['hp_total'] > 0 ? ($inv['hp_atual'] / $inv['hp_total']) * 100 : 0; ?>
                    <div style="background:#cc0000; height:100%; width:<?php echo $pct; ?>%;"></div>
                </div>
                <div class="sub2" style="text-align:center;"><?php echo round($pct, 1); ?>% HP restante</div>
                <div class="sep"></div>
                <form method="POST">
                    <input type="hidden" name="action" value="editar_invasao">
                    <input type="hidden" name="invasao_id" value="<?php echo $inv['id']; ?>">
                    <table cellspacing="3">
                        <tr>
                            <td><label>HP Total:</label><br><input type="number" name="hp_total" value="<?php echo $inv['hp_total']; ?>" min="1"></td>
                            <td><label>HP Atual:</label><br><input type="number" name="hp_atual" value="<?php echo $inv['hp_atual']; ?>" min="0"></td>
                            <td><label>Prêmio:</label><br><input type="number" name="premio_yens" value="<?php echo $inv['premio_yens']; ?>" min="1"></td>
                            <td><label>Bônus (%):</label><br><input type="number" name="bonus_vila" value="<?php echo $inv['bonus_vila']; ?>" min="1" max="100"></td>
                            <td valign="bottom"><button type="submit" class="botao btn-success">💾 Salvar</button></td>
                        </tr>
                    </table>
                </form>
                <div class="sep"></div>
                <form method="POST" onsubmit="return confirm('Finalizar esta invasão?');">
                    <input type="hidden" name="action" value="finalizar_invasao">
                    <input type="hidden" name="invasao_id" value="<?php echo $inv['id']; ?>">
                    <button type="submit" class="botao btn-danger">🏁 Finalizar Invasão</button>
                </form>
                <div class="sub2" style="margin-top:5px;">Iniciada em: <?php echo date('d/m/Y H:i', strtotime($inv['data_inicio'])); ?></div>
            </td>
        </tr>
    </table>
</fieldset>
<div class="sep"></div>
<?php endforeach; ?>

<?php else: ?>
<div class="alert-warning" style="text-align:center;">⚠️ Nenhuma invasão ativa. Crie uma invasão usando o formulário acima.</div>
<?php endif; ?>

<div class="sep"></div>
<h3>📊 Histórico de Invasões (últimas 15)</h3>
<div class="sep"></div>

<?php if(empty($invasoes_recentes)): ?>
    <p class="sub2">Nenhuma invasão encontrada.</p>
<?php else: ?>
<table class="adm-table">
    <tr>
        <th>Servidor</th>
        <th>Invasor</th>
        <th>HP Total</th>
        <th>HP Final</th>
        <th>Prêmio</th>
        <th>Status</th>
        <th>Início</th>
        <th>Fim</th>
        <th>Vencedor</th>
    </tr>
    <?php foreach($invasoes_recentes as $inv): ?>
    <tr>
        <td><?php echo htmlspecialchars($inv['nome_servidor'] ?? '#'.$inv['servidor_id']); ?></td>
        <td><?php echo htmlspecialchars($inv['nome_invasor']); ?></td>
        <td><?php echo number_format($inv['hp_total']); ?></td>
        <td><?php echo number_format($inv['hp_atual']); ?></td>
        <td><?php echo number_format($inv['premio_yens']); ?></td>
        <td>
            <?php if($inv['status'] == 'ativa'): ?>
                <span style="color:#90EE90; font-weight:bold;">ATIVA</span>
            <?php else: ?>
                <span style="color:#888;">Finalizada</span>
            <?php endif; ?>
        </td>
        <td><?php echo date('d/m/Y H:i', strtotime($inv['data_inicio'])); ?></td>
        <td><?php echo $inv['data_fim'] ? date('d/m/Y H:i', strtotime($inv['data_fim'])) : '—'; ?></td>
        <td>
            <?php if($inv['vencedor_id']): ?>
                <?php
                $sv = $conexao->prepare("SELECT usuario FROM usuarios WHERE id = ?");
                $sv->execute([$inv['vencedor_id']]);
                $venc = $sv->fetch(PDO::FETCH_ASSOC);
                ?>
                <span style="color:#FFD700;">🏆 <?php echo htmlspecialchars($venc['usuario'] ?? 'Desconhecido'); ?></span>
            <?php else: ?>
                <span style="color:#555;">—</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

</div>
<div class="box_bottom"></div>

<?php include 'adm_footer.php'; ?>
