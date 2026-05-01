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
if(!$adm_user || $adm_user['adm'] != 1) {
    header('Location: adm.php'); exit;
}

$logs = [];
$concluido = false;
$totalRegistros = 0;

if(isset($_POST['confirmar'])) {
    try {
        $tabelas = [
            'membros' => 'Membros de organizações',
            'amigos' => 'Lista de amigos',
            'mensagens' => 'Mensagens entre usuários',
            'inventario' => 'Inventário dos usuários',
            'jutsus' => 'Jutsus dos usuários',
            'natureza' => 'Natureza de chakra',
            'personagens' => 'Personagens desbloqueados',
            'quests' => 'Progresso de quests',
            'ramen' => 'Histórico de ramen',
            'usaveis' => 'Itens usáveis',
            'book' => 'Book de ataques',
            'relatorios' => 'Relatórios de usuários',
            'seguranca' => 'Log de segurança',
            'spam' => 'Controle de spam',
            'vendas' => 'Histórico de vendas',
            'verificador' => 'Verificações de missões',
            'vip' => 'Histórico VIP',
            'atualizacoes' => 'Atualizações',
            'configuracoes' => 'Configurações',
            'contato' => 'Mensagens de contato',
            'salas' => 'Salas ocupadas',
            'usuarios' => 'Contas de usuários'
        ];
        $tabelasSistema = ['block', 'cbt', 'enquetes'];

        Database::setForeignKeys($conexao, false);
        $logs[] = ['type' => 'info', 'msg' => 'Foreign keys desabilitadas temporariamente'];

        foreach($tabelas as $tabela => $descricao) {
            try {
                if(Database::tableExists($conexao, $tabela)) {
                    $c = $conexao->prepare("SELECT COUNT(*) as t FROM `$tabela`");
                    $c->execute();
                    $total = $c->fetch(PDO::FETCH_ASSOC)['t'];
                    if($total > 0) {
                        $conexao->exec("DELETE FROM `$tabela`");
                        $totalRegistros += $total;
                        $logs[] = ['type' => 'success', 'msg' => "✓ $descricao ($tabela): $total registros removidos"];
                    } else {
                        $logs[] = ['type' => 'neutral', 'msg' => "⚪ $descricao ($tabela): já vazia"];
                    }
                } else {
                    $logs[] = ['type' => 'neutral', 'msg' => "— $tabela: não encontrada"];
                }
            } catch(Exception $e) {
                $logs[] = ['type' => 'error', 'msg' => "❌ Erro em $tabela: " . $e->getMessage()];
            }
        }

        foreach($tabelasSistema as $tabela) {
            try {
                if(Database::tableExists($conexao, $tabela)) {
                    $c = $conexao->prepare("SELECT COUNT(*) as t FROM `$tabela`");
                    $c->execute();
                    $total = $c->fetch(PDO::FETCH_ASSOC)['t'];
                    if($total > 0) {
                        $conexao->exec("DELETE FROM `$tabela`");
                        $totalRegistros += $total;
                        $logs[] = ['type' => 'success', 'msg' => "✓ Sistema ($tabela): $total registros removidos"];
                    }
                }
            } catch(Exception $e) {
                $logs[] = ['type' => 'neutral', 'msg' => "⚠ $tabela: " . $e->getMessage()];
            }
        }

        Database::setForeignKeys($conexao, true);
        $logs[] = ['type' => 'info', 'msg' => 'Foreign keys reabilitadas'];

        try {
            if(Database::isMysql()) {
                $allTabs = array_merge(array_keys($tabelas), $tabelasSistema);
                foreach($allTabs as $t) {
                    try { $conexao->exec("OPTIMIZE TABLE `$t`"); } catch(Exception $e) {}
                }
                $logs[] = ['type' => 'success', 'msg' => '✓ Banco otimizado com OPTIMIZE TABLE'];
            } else {
                $conexao->exec("VACUUM");
                $logs[] = ['type' => 'success', 'msg' => '✓ Banco otimizado com VACUUM'];
            }
        } catch(Exception $e) {}

        $concluido = true;
    } catch(Exception $e) {
        $logs[] = ['type' => 'error', 'msg' => 'Erro geral: ' . $e->getMessage()];
        Database::setForeignKeys($conexao, true);
    }
}

$page_title = 'Limpar Banco de Dados';
include 'adm_header.php';
?>

<div class="box_top">🗑️ Limpeza Completa do Banco de Dados</div>
<div class="box_middle">

<?php if(!isset($_POST['confirmar'])): ?>

<div class="aviso">
    ⚠️ <strong>ATENÇÃO — AÇÃO IRREVERSÍVEL!</strong><br>
    Este processo irá APAGAR TODAS as contas de usuários e todos os dados relacionados do banco de dados.<br>
    Use apenas em ambiente de teste ou para resetar completamente o servidor.
</div>

<div class="sep"></div>

<fieldset>
    <legend>O que será apagado</legend>
    <ul style="margin:5px 0; padding-left:18px;">
        <li>Contas de usuários</li>
        <li>Inventário, jutsus, quests, missões</li>
        <li>Mensagens, amigos, membros de clã</li>
        <li>Histórico de VIP, vendas, relatórios</li>
        <li>IPs bloqueados e logs de segurança</li>
    </ul>
    <p class="sub2">⚠️ Os itens da loja (table_itens) e configurações do sistema NÃO serão afetados.</p>
</fieldset>

<div class="sep"></div>

<form method="POST" onsubmit="return confirm('ÚLTIMA CONFIRMAÇÃO: Você tem ABSOLUTA certeza? Isso irá apagar TODOS os usuários e seus dados. Esta ação NÃO pode ser desfeita!');">
    <input type="submit" name="confirmar" value="💀 CONFIRMAR E APAGAR TUDO" class="botao btn-danger">
    &nbsp;
    <a href="adm.php" class="botao">← Cancelar e Voltar</a>
</form>

<?php else: ?>

<?php if($concluido): ?>
    <div class="alert-success">
        ✅ <strong>LIMPEZA CONCLUÍDA!</strong><br>
        Total de registros removidos: <strong><?php echo $totalRegistros; ?></strong>
    </div>
<?php endif; ?>

<div class="sep"></div>
<h3>Log da Operação</h3>
<div style="background:#0a0a0a; border:1px solid #333; padding:8px; max-height:300px; overflow-y:auto;">
<?php foreach($logs as $log):
    $color = '#BBBBBB';
    if($log['type'] === 'success') $color = '#90EE90';
    elseif($log['type'] === 'error') $color = '#FFAAAA';
    elseif($log['type'] === 'info') $color = '#87CEFA';
?>
<div style="color:<?php echo $color; ?>; font-size:11px; padding:2px 0;"><?php echo htmlspecialchars($log['msg']); ?></div>
<?php endforeach; ?>
</div>

<div class="sep"></div>
<a href="../index.php?p=reg" class="botao btn-success">🚀 Criar Nova Conta</a>
&nbsp;
<a href="adm.php" class="botao">← Painel Admin</a>

<?php endif; ?>

</div>
<div class="box_bottom"></div>

<?php include 'adm_footer.php'; ?>
