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

$modulo_necessario = 'clas';
require_once('_gm_auth.php');

$mensagem = "";
$tipo_mensagem = "";

if(isset($_POST['action'])) {
    if($_POST['action'] == 'excluir_todos' && isset($_POST['confirmar']) && $_POST['confirmar'] == 'CONFIRMO') {
        try {
            $conexao->beginTransaction();
            $stmt = $conexao->query("SELECT COUNT(*) as total FROM organizacoes");
            $total_clas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            $conexao->exec("UPDATE usuarios SET orgid = 0, orgmissao = 0 WHERE orgid > 0");
            $conexao->exec("DELETE FROM membros");
            $conexao->exec("DELETE FROM organizacoes");
            try { $conexao->exec("ALTER TABLE organizacoes AUTO_INCREMENT = 1"); } catch (Exception $e) {}
            try { $conexao->exec("ALTER TABLE membros AUTO_INCREMENT = 1"); } catch (Exception $e) {}
            $conexao->commit();
            $mensagem = "✅ Todos os clãs foram excluídos com sucesso! Total: $total_clas clãs removidos.";
            $tipo_mensagem = 'success';
        } catch (Exception $e) {
            $conexao->rollBack();
            $mensagem = "❌ Erro ao excluir clãs: " . htmlspecialchars($e->getMessage());
            $tipo_mensagem = 'error';
        }
    }

    if($_POST['action'] == 'excluir_inativos' && isset($_POST['confirmar_inativos']) && $_POST['confirmar_inativos'] == 'CONFIRMO') {
        try {
            $conexao->beginTransaction();
            $limite_inatividade = time() - (30 * 24 * 60 * 60);
            $stmt = $conexao->prepare("
                SELECT o.id FROM organizacoes o
                LEFT JOIN usuarios u ON o.liderid = u.id
                WHERE u.timestamp < ? OR u.id IS NULL
            ");
            $stmt->execute([$limite_inatividade]);
            $clas_para_excluir = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $total_removidos = 0;
            foreach($clas_para_excluir as $cla) {
                $s = $conexao->prepare("UPDATE usuarios SET orgid = 0, orgmissao = 0 WHERE orgid = ?");
                $s->execute([$cla['id']]);
                $s = $conexao->prepare("DELETE FROM membros WHERE orgid = ?");
                $s->execute([$cla['id']]);
                $s = $conexao->prepare("DELETE FROM organizacoes WHERE id = ?");
                $s->execute([$cla['id']]);
                $total_removidos++;
            }
            $conexao->commit();
            $mensagem = "✅ Clãs inativos excluídos! Total: $total_removidos clãs removidos.";
            $tipo_mensagem = 'success';
        } catch (Exception $e) {
            $conexao->rollBack();
            $mensagem = "❌ Erro: " . htmlspecialchars($e->getMessage());
            $tipo_mensagem = 'error';
        }
    }
}

try {
    $total_clas = $conexao->query("SELECT COUNT(*) as t FROM organizacoes")->fetch()['t'] ?? 0;
    $total_membros = $conexao->query("SELECT COUNT(*) as t FROM membros")->fetch()['t'] ?? 0;
    $limite_inatividade = time() - (30 * 24 * 60 * 60);
    $s = $conexao->prepare("SELECT COUNT(*) as t FROM organizacoes o LEFT JOIN usuarios u ON o.liderid = u.id WHERE u.timestamp < ? OR u.id IS NULL");
    $s->execute([$limite_inatividade]);
    $clas_inativos = $s->fetch()['t'] ?? 0;
} catch (Exception $e) {
    $total_clas = $clas_inativos = $total_membros = 0;
}

$page_title = 'Gerenciar Clãs';
include 'adm_header.php';
?>

<div class="box_top">🏯 Gerenciar Clãs</div>
<div class="box_middle">

<?php if($mensagem): ?>
    <div class="alert-<?php echo $tipo_mensagem; ?>"><?php echo $mensagem; ?></div>
    <div class="sep"></div>
<?php endif; ?>

<div class="stats-row">
    <div class="stat-box">
        <div class="stat-number"><?php echo $total_clas; ?></div>
        <div>Total de Clãs</div>
    </div>
    <div class="stat-box">
        <div class="stat-number"><?php echo $total_membros; ?></div>
        <div>Total de Membros</div>
    </div>
    <div class="stat-box">
        <div class="stat-number" style="color:<?php echo $clas_inativos > 0 ? '#FFD700' : '#90EE90'; ?>"><?php echo $clas_inativos; ?></div>
        <div>Clãs com Líder Inativo (30+ dias)</div>
    </div>
</div>

<div class="sep"></div>

<?php if($clas_inativos > 0): ?>
<fieldset>
    <legend>⚠️ Excluir Clãs com Líderes Inativos</legend>
    <p class="sub2">Exclui todos os clãs cujos líderes estão inativos há mais de 30 dias. <strong><?php echo $clas_inativos; ?> clãs</strong> serão afetados.</p>
    <form method="POST" onsubmit="return confirm('Confirma a exclusão de clãs com líderes inativos?')">
        <input type="hidden" name="action" value="excluir_inativos">
        <label>Digite <strong>CONFIRMO</strong> para confirmar:</label><br>
        <input type="text" name="confirmar_inativos" placeholder="CONFIRMO" required style="width:200px; margin-top:5px;">
        <button type="submit" class="botao" style="margin-left:8px;">🗑️ Excluir Clãs Inativos</button>
    </form>
</fieldset>
<div class="sep"></div>
<?php endif; ?>

<fieldset style="border-color:#cc0000;">
    <legend style="color:#FFAAAA;">🚨 ZONA DE PERIGO — Excluir TODOS os Clãs</legend>
    <div class="aviso">Esta ação irá excluir TODOS os <?php echo $total_clas; ?> clãs do sistema, incluindo membros e dados relacionados. IRREVERSÍVEL!</div>
    <div class="sep"></div>
    <form method="POST" onsubmit="return confirm('ÚLTIMA CONFIRMAÇÃO: Excluir TODOS os clãs? Esta ação é IRREVERSÍVEL!')">
        <input type="hidden" name="action" value="excluir_todos">
        <label>Digite <strong>CONFIRMO</strong> para confirmar:</label><br>
        <input type="text" name="confirmar" placeholder="CONFIRMO" required style="width:200px; margin-top:5px;">
        <button type="submit" class="botao btn-danger" style="margin-left:8px;">💀 EXCLUIR TODOS OS CLÃS</button>
    </form>
</fieldset>

<?php if($clas_inativos > 0): ?>
<div class="sep"></div>
<h3>📋 Clãs com Líderes Inativos (30+ dias)</h3>
<?php try {
    $s = $conexao->prepare("
        SELECT o.id, o.nome, o.sigla, u.usuario, u.timestamp,
               (SELECT COUNT(*) FROM membros WHERE orgid = o.id) as total_membros
        FROM organizacoes o
        LEFT JOIN usuarios u ON o.liderid = u.id
        WHERE u.timestamp < ? OR u.id IS NULL
        ORDER BY u.timestamp ASC LIMIT 50
    ");
    $s->execute([$limite_inatividade]);
    $lista_inativos = $s->fetchAll(PDO::FETCH_ASSOC);
    ?>
<table class="adm-table">
    <tr>
        <th>ID</th>
        <th>Nome do Clã</th>
        <th>Sigla</th>
        <th>Líder</th>
        <th>Última Atividade</th>
        <th>Membros</th>
    </tr>
    <?php foreach($lista_inativos as $cla): ?>
    <tr>
        <td><?php echo $cla['id']; ?></td>
        <td><?php echo htmlspecialchars($cla['nome']); ?></td>
        <td><?php echo htmlspecialchars($cla['sigla']); ?></td>
        <td><?php echo $cla['usuario'] ? htmlspecialchars($cla['usuario']) : '<span style="color:#FFAAAA;">Não encontrado</span>'; ?></td>
        <td>
            <?php if($cla['timestamp']): ?>
                <?php echo date('d/m/Y H:i', $cla['timestamp']); ?>
                <br><span style="color:#ff6600; font-size:10px;">(<?php echo floor((time() - $cla['timestamp']) / 86400); ?> dias inativo)</span>
            <?php else: ?>
                <span style="color:#FFAAAA;">Nunca ativo</span>
            <?php endif; ?>
        </td>
        <td><?php echo (int)$cla['total_membros']; ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php } catch(Exception $e) { ?>
    <div class="alert-error">Erro ao carregar lista: <?php echo htmlspecialchars($e->getMessage()); ?></div>
<?php } ?>
<?php endif; ?>

</div>
<div class="box_bottom"></div>

<?php include 'adm_footer.php'; ?>
