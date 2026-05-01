<?php
define('INDEX', true);
session_start();

require_once('../_inc/conexao.php');
require_once('../_inc/funcoes_manutencao.php');

if (!isset($_SESSION['logado'])) {
    echo '<script>window.location.href = "../index.php?p=login";</script>';
    exit;
}

if (!usuario_e_admin($conexao, $_SESSION['logado'])) {
    $page_title = 'Acesso Negado';
    include 'adm_header.php';
    echo '<div class="aviso">Acesso negado! Apenas administradores podem acessar esta página.</div>';
    include 'adm_footer.php';
    exit;
}
// Verificar permissão de GM para este módulo
$_user_id_man = $_SESSION['logado'] ?? $_SESSION['userid'] ?? $_SESSION['uid'];
$_stmt_man = $conexao->prepare("SELECT adm FROM usuarios WHERE id = ?");
$_stmt_man->execute([$_user_id_man]);
$_u_man = $_stmt_man->fetch(PDO::FETCH_ASSOC);
if(isset($_u_man['adm']) && $_u_man['adm'] == 2) {
    $modulo_necessario = 'manutencao';
    $user_id = $_user_id_man;
    $usuario_logado = $_u_man;
    require_once('_gm_auth.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'salvar') {
        $mensagem_personalizada = $_POST['mensagem_personalizada'] ?? '';
        $manutencao_ativa = isset($_POST['manutencao_ativa']) ? true : false;
        if (salvar_config_manutencao($manutencao_ativa, $mensagem_personalizada)) {
            $sucesso_msg = 'Configurações de manutenção salvas com sucesso!';
        } else {
            $erro_msg = 'Erro ao salvar configurações!';
        }
    } elseif ($action === 'excluir') {
        if (salvar_config_manutencao(false, '')) {
            $sucesso_msg = 'Mensagem de manutenção excluída com sucesso!';
        } else {
            $erro_msg = 'Erro ao excluir mensagem!';
        }
    }
}

$manutencao_ativa = esta_em_manutencao();
$mensagem_atual = obter_mensagem_manutencao();
$recaptcha_configurado = recaptcha_configurado();

$page_title = 'Gerenciar Manutenção';
include 'adm_header.php';
?>

<div class="box_top">🔧 Gerenciar Manutenção do Sistema</div>
<div class="box_middle">

<?php if (isset($sucesso_msg)): ?>
    <div class="alert-success"><?php echo htmlspecialchars($sucesso_msg); ?></div>
    <div class="sep"></div>
<?php endif; ?>

<?php if (isset($erro_msg)): ?>
    <div class="alert-error"><?php echo htmlspecialchars($erro_msg); ?></div>
    <div class="sep"></div>
<?php endif; ?>

<h3>Status do Sistema</h3>
<div class="sep"></div>

<table class="adm-table">
    <tr>
        <th>Componente</th>
        <th>Status</th>
    </tr>
    <tr>
        <td>reCAPTCHA</td>
        <td>
            <?php if ($recaptcha_configurado): ?>
                <span style="color:#90EE90;">✓ Configurado</span>
            <?php else: ?>
                <span style="color:#ff6600;">✗ Não configurado (manutenção automática ativa)</span>
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <td>Modo Manutenção</td>
        <td>
            <?php if ($manutencao_ativa): ?>
                <span style="color:#ff6600;">⚙ ATIVO — Apenas administradores podem logar</span>
            <?php else: ?>
                <?php if (!$recaptcha_configurado): ?>
                    <span style="color:#ff6600;">⚙ ATIVO (Automático — reCAPTCHA não configurado)</span>
                <?php else: ?>
                    <span style="color:#90EE90;">✓ Inativo — Sistema funcionando normalmente</span>
                <?php endif; ?>
            <?php endif; ?>
        </td>
    </tr>
</table>

<div class="sep"></div>
<div class="sep"></div>

<h3>Configurar Manutenção</h3>
<div class="sep"></div>

<form method="post" action="admin_manutencao.php">
    <input type="hidden" name="action" value="salvar">

    <fieldset>
        <legend>Mensagem Personalizada</legend>
        <p class="sub2">Digite a mensagem que será exibida aos jogadores durante a manutenção.<br>
        Se deixar em branco, será exibida a mensagem padrão: "Estamos em Manutenção"</p>
        <textarea name="mensagem_personalizada" rows="6" style="width:100%; max-width:560px; padding:6px;"><?php echo htmlspecialchars($mensagem_atual); ?></textarea>
        <div class="sep"></div>
        <label style="display:block; margin:10px 0;">
            <input type="checkbox" name="manutencao_ativa" value="1" <?php echo $manutencao_ativa ? 'checked' : ''; ?>>
            <strong>Ativar modo de manutenção</strong><br>
            <span class="sub2">Quando marcado, apenas administradores poderão fazer login</span>
        </label>
        <div class="sep"></div>
        <button type="submit" class="botao btn-success">💾 Salvar Configurações</button>
    </fieldset>
</form>

<div class="sep"></div>

<form method="post" action="admin_manutencao.php" onsubmit="return confirm('Tem certeza que deseja excluir a mensagem personalizada?');">
    <input type="hidden" name="action" value="excluir">
    <button type="submit" class="botao btn-danger">🗑️ Excluir Mensagem Personalizada</button>
</form>

<div class="sep"></div>
<p class="sub2"><strong>Dica:</strong> Para desativar completamente a manutenção, desmarque "Ativar modo de manutenção" e salve as configurações.</p>

</div>
<div class="box_bottom"></div>

<?php include 'adm_footer.php'; ?>
