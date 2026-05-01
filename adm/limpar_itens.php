<?php
require_once(__DIR__ . '/../_inc/conexao.php');

if (session_status() == PHP_SESSION_NONE) session_start();
if(!isset($_SESSION['logado']) && !isset($_SESSION['userid']) && !isset($_SESSION['uid'])) {
    header('Location: ../index.php'); exit;
}
$user_id = $_SESSION['logado'] ?? $_SESSION['userid'] ?? $_SESSION['uid'];
$stmt = $conexao->prepare("SELECT adm FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$adm_user = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$adm_user || $adm_user['adm'] != 1) {
    header('Location: adm.php'); exit;
}

$logs = [];
$concluido = false;
$totalRegistros = 0;

if(isset($_POST['confirmar'])) {
    try {
        $tabelasItens = [
            'inventario' => 'Inventário de equipamentos',
            'usaveis' => 'Itens usáveis (comida e consumíveis)'
        ];
        Database::setForeignKeys($conexao, false);
        foreach($tabelasItens as $tabela => $descricao) {
            try {
                if(Database::tableExists($conexao, $tabela)) {
                    $c = $conexao->prepare("SELECT COUNT(*) as t FROM `$tabela`");
                    $c->execute();
                    $total = $c->fetch(PDO::FETCH_ASSOC)['t'];
                    if($total > 0) {
                        $conexao->exec("DELETE FROM `$tabela`");
                        $totalRegistros += $total;
                        $logs[] = ['type' => 'success', 'msg' => "✓ $descricao: $total itens removidos"];
                    } else {
                        $logs[] = ['type' => 'neutral', 'msg' => "⚪ $descricao: já vazia"];
                    }
                } else {
                    $logs[] = ['type' => 'neutral', 'msg' => "— $tabela: não encontrada"];
                }
            } catch(Exception $e) {
                $logs[] = ['type' => 'error', 'msg' => "❌ Erro em $tabela: " . $e->getMessage()];
            }
        }
        Database::setForeignKeys($conexao, true);
        // VACUUM é o equivalente a OPTIMIZE TABLE no MySQL usamos OPTIMIZE TABLE
        try {
            if(Database::isMysql()) {
                foreach($tabelasItens as $tabela => $_d) {
                    try { $conexao->exec("OPTIMIZE TABLE `$tabela`"); } catch(Exception $e) {}
                }
            } else {
                $conexao->exec("VACUUM");
            }
            $logs[] = ['type' => 'success', 'msg' => '✓ Banco otimizado'];
        } catch(Exception $e) {}
        $concluido = true;
    } catch(Exception $e) {
        $logs[] = ['type' => 'error', 'msg' => 'Erro geral: ' . $e->getMessage()];
        Database::setForeignKeys($conexao, true);
    }
}

$page_title = 'Limpar Itens dos Jogadores';
include 'adm_header.php';
?>

<div class="box_top">🧹 Limpar Itens dos Jogadores</div>
<div class="box_middle">

<?php if(!isset($_POST['confirmar'])): ?>

<div class="aviso">
    ⚠️ <strong>ATENÇÃO — AÇÃO IRREVERSÍVEL!</strong><br>
    Este processo irá APAGAR TODOS os equipamentos e itens usáveis de TODOS os jogadores.
</div>

<div class="sep"></div>

<fieldset>
    <legend>O que será removido</legend>
    <ul style="margin:5px 0; padding-left:18px;">
        <li><strong>Inventário:</strong> Todos os equipamentos (armas, vestimentas, máscaras, calçados)</li>
        <li><strong>Itens Usáveis:</strong> Toda a comida e itens consumíveis</li>
    </ul>
    <p class="sub2">ℹ️ Os itens na loja (table_itens) NÃO serão afetados. Apenas os itens que os jogadores já possuem serão removidos.</p>
</fieldset>

<div class="sep"></div>

<form method="POST" onsubmit="return confirm('Confirmar remoção de TODOS os itens de TODOS os jogadores? Esta ação não pode ser desfeita!');">
    <input type="submit" name="confirmar" value="🗑️ CONFIRMAR E APAGAR TODOS OS ITENS" class="botao btn-danger">
    &nbsp;
    <a href="adm.php" class="botao">← Cancelar e Voltar</a>
</form>

<?php else: ?>

<?php if($concluido): ?>
<div class="alert-success">
    ✅ <strong>LIMPEZA DE ITENS CONCLUÍDA!</strong><br>
    Total de itens removidos: <strong><?php echo $totalRegistros; ?></strong>
</div>
<?php endif; ?>

<div class="sep"></div>
<h3>Log da Operação</h3>
<div style="background:#0a0a0a; border:1px solid #333; padding:8px;">
<?php foreach($logs as $log):
    $color = '#BBBBBB';
    if($log['type'] === 'success') $color = '#90EE90';
    elseif($log['type'] === 'error') $color = '#FFAAAA';
?>
<div style="color:<?php echo $color; ?>; font-size:11px; padding:2px 0;"><?php echo htmlspecialchars($log['msg']); ?></div>
<?php endforeach; ?>
</div>

<div class="sep"></div>
<a href="adm.php?modulo=limpar_itens" class="botao">🔄 Limpar Novamente</a>
&nbsp;
<a href="adm.php" class="botao">← Painel Admin</a>

<?php endif; ?>

</div>
<div class="box_bottom"></div>

<?php include 'adm_footer.php'; ?>
