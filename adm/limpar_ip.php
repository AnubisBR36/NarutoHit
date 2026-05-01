<?php
require_once('../_inc/conexao.php');
if (session_status() == PHP_SESSION_NONE) session_start();
if(!isset($_SESSION['logado']) && !isset($_SESSION['userid']) && !isset($_SESSION['uid'])) {
    header('Location: ../index.php'); exit;
}
$user_id = $_SESSION['logado'] ?? $_SESSION['userid'] ?? $_SESSION['uid'];
$stmt = $conexao->prepare("SELECT adm FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$adm_user = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$adm_user || ($adm_user['adm'] != 1 && $adm_user['adm'] != 2)) {
    header('Location: ../index.php'); exit;
}

$resultado = '';
$tipo = '';

if(isset($_POST['confirmar'])) {
    try {
        if(!Database::tableExists($conexao, 'block')) {
            $resultado = "Tabela 'block' não encontrada no banco de dados.";
            $tipo = 'warning';
        } else {
            $count_stmt = $conexao->prepare("SELECT COUNT(*) as total FROM block");
            $count_stmt->execute();
            $total_ips = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            if($total_ips == 0) {
                $resultado = "Não há IPs bloqueados para remover.";
                $tipo = 'warning';
            } else {
                $conexao->exec("DELETE FROM block");
                // Resetar contador de auto-incremento
                try {
                    $conexao->exec("ALTER TABLE block AUTO_INCREMENT = 1");
                } catch(Exception $e) {}
                $resultado = "✅ {$total_ips} IPs bloqueados foram removidos com sucesso! Todos os usuários podem tentar fazer login novamente.";
                $tipo = 'success';
            }
        }
    } catch(Exception $e) {
        $resultado = "Erro durante a limpeza: " . htmlspecialchars($e->getMessage());
        $tipo = 'error';
    }
}

$total_ips_atual = 0;
try {
    $s = $conexao->prepare("SELECT COUNT(*) as total FROM block");
    $s->execute();
    $total_ips_atual = $s->fetch(PDO::FETCH_ASSOC)['total'];
} catch(Exception $e) {}

$page_title = 'Limpar IPs Bloqueados';
include 'adm_header.php';
?>

<div class="box_top">🔓 Limpar IPs Bloqueados</div>
<div class="box_middle">

<?php if($resultado): ?>
    <div class="alert-<?php echo $tipo; ?>"><?php echo $resultado; ?></div>
    <div class="sep"></div>
<?php endif; ?>

<div class="stats-row">
    <div class="stat-box">
        <div class="stat-number"><?php echo $total_ips_atual; ?></div>
        <div>IPs Bloqueados Atualmente</div>
    </div>
</div>

<div class="sep"></div>

<?php if(!isset($_POST['confirmar'])): ?>

<div class="aviso">
    ⚠️ Esta ação irá remover TODOS os IPs bloqueados do sistema.<br>
    Todos os usuários previamente bloqueados por tentativas de login poderão tentar novamente.
</div>

<div class="sep"></div>

<?php if($total_ips_atual > 0):
    try {
        $stmt = $conexao->prepare("SELECT ip, tentativas, timestamp FROM block LIMIT 5");
        $stmt->execute();
        $ips_exemplo = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if($ips_exemplo):
?>
<h3>Exemplos de IPs que serão removidos:</h3>
<table class="adm-table">
    <tr>
        <th>IP</th>
        <th>Tentativas</th>
        <th>Bloqueado em</th>
    </tr>
    <?php foreach($ips_exemplo as $ip_info): ?>
    <tr>
        <td><?php echo htmlspecialchars($ip_info['ip']); ?></td>
        <td><?php echo (int)$ip_info['tentativas']; ?></td>
        <td><?php echo date('d/m/Y H:i:s', $ip_info['timestamp']); ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if($total_ips_atual > 5): ?>
    <tr>
        <td colspan="3" style="color:#888;font-style:italic;">... e mais <?php echo $total_ips_atual - 5; ?> IPs</td>
    </tr>
    <?php endif; ?>
</table>
<div class="sep"></div>
<?php endif; } catch(Exception $e) {} endif; ?>

<form method="POST" onsubmit="return confirm('Confirmar remoção de todos os IPs bloqueados?');">
    <input type="submit" name="confirmar" value="🔓 Remover Todos os IPs Bloqueados" class="botao btn-danger">
    &nbsp;
    <a href="adm.php" class="botao">← Voltar ao Painel</a>
</form>

<?php else: ?>
<a href="adm.php?modulo=desbloquear_ips" class="botao">🔄 Limpar Novamente</a>
&nbsp;
<a href="adm.php" class="botao">← Voltar ao Painel</a>
<?php endif; ?>

</div>
<div class="box_bottom"></div>

<?php include 'adm_footer.php'; ?>
