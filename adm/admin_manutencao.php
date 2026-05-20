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

if (!function_exists('adm_log')) {
    function adm_log($pdo, $autor_id, $autor_nome, $acao, $alvo_id = null, $alvo_nome = null, $detalhes = null) {
        try { $pdo->prepare("INSERT INTO admin_logs (autor_id,autor_nome,acao,alvo_id,alvo_nome,detalhes) VALUES (?,?,?,?,?,?)")->execute([$autor_id,$autor_nome,$acao,$alvo_id,$alvo_nome,$detalhes]); } catch(Exception $e) {}
    }
}
$_man_uid  = $_SESSION['logado'] ?? 0;
$_man_nome = '?';
try { $_s = $conexao->prepare("SELECT usuario FROM usuarios WHERE id=?"); $_s->execute([$_man_uid]); $_man_nome = $_s->fetchColumn() ?: '?'; } catch(Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'salvar') {
        $mensagem_personalizada = $_POST['mensagem_personalizada'] ?? '';
        $manutencao_ativa = isset($_POST['manutencao_ativa']) ? true : false;
        if (salvar_config_manutencao($manutencao_ativa, $mensagem_personalizada)) {
            adm_log($conexao, $_man_uid, $_man_nome, 'Config Manutenção', null, null, 'Ativo=' . ($manutencao_ativa ? 'sim' : 'não'));
            $sucesso_msg = 'Configurações de manutenção salvas com sucesso!';
        } else {
            $erro_msg = 'Erro ao salvar configurações!';
        }
    } elseif ($action === 'excluir') {
        if (salvar_config_manutencao(false, '')) {
            adm_log($conexao, $_man_uid, $_man_nome, 'Config Manutenção', null, null, 'Mensagem excluída');
            $sucesso_msg = 'Mensagem de manutenção excluída com sucesso!';
        } else {
            $erro_msg = 'Erro ao excluir mensagem!';
        }
    } elseif ($action === 'salvar_recaptcha') {
        $rc_version    = $_POST['rc_version']    ?? 'v2';
        $rc_site_key   = $_POST['rc_site_key']   ?? '';
        $rc_secret_key = $_POST['rc_secret_key'] ?? '';
        $rc_min_score  = $_POST['rc_min_score']  ?? '0.5';
        if (empty($rc_site_key) || empty($rc_secret_key)) {
            $erro_msg = 'Site Key e Secret Key são obrigatórios!';
        } elseif (salvar_config_recaptcha($rc_version, $rc_site_key, $rc_secret_key, $rc_min_score)) {
            adm_log($conexao, $_man_uid, $_man_nome, 'Config reCAPTCHA', null, null, "Versão=$rc_version | Score=$rc_min_score");
            $sucesso_msg = 'Configurações do reCAPTCHA salvas com sucesso!';
        } else {
            $erro_msg = 'Erro ao salvar configurações do reCAPTCHA!';
        }
    } elseif ($action === 'limpar_recaptcha') {
        if (salvar_config_recaptcha('v2', '', '', 0.5)) {
            adm_log($conexao, $_man_uid, $_man_nome, 'Config reCAPTCHA', null, null, 'Chaves removidas');
            $sucesso_msg = 'Configurações do reCAPTCHA limpas com sucesso!';
        } else {
            $erro_msg = 'Erro ao limpar configurações do reCAPTCHA!';
        }
    } elseif ($action === 'desbloquear_ip') {
        $ip_num = (int)($_POST['ip_num'] ?? 0);
        if ($ip_num > 0) {
            require_once('../_inc/rate_limit.php');
            rl_desbloquear_ip($conexao, $ip_num);
            adm_log($conexao, $_man_uid, $_man_nome, 'Desbloquear IP', null, null, 'IP=' . long2ip($ip_num));
            $sucesso_msg = 'IP desbloqueado com sucesso!';
        }
    } elseif ($action === 'limpar_bloqueios') {
        require_once('../_inc/rate_limit.php');
        rl_limpar_todos($conexao);
        adm_log($conexao, $_man_uid, $_man_nome, 'Limpar Bloqueios IP', null, null, 'Todos os bloqueios removidos');
        $sucesso_msg = 'Todos os bloqueios foram removidos!';
    } elseif ($action === 'fix_energiamax') {
        try {
            // Busca todas as contas com energiamax diferente de nivel*100
            $rows = $conexao->query("SELECT id, usuario, nivel, energiamax FROM usuarios WHERE status <> 'banido'")->fetchAll(PDO::FETCH_ASSOC);
            $corrigidos = 0;
            $stmt_fix = $conexao->prepare("UPDATE usuarios SET energiamax = ?, energia = LEAST(energia, ?) WHERE id = ?");
            foreach ($rows as $row) {
                $correto = max(100, (int)$row['nivel'] * 100);
                if ((int)$row['energiamax'] !== $correto) {
                    $stmt_fix->execute([$correto, $correto, $row['id']]);
                    $corrigidos++;
                }
            }
            adm_log($conexao, $_man_uid, $_man_nome, 'Fix EnergiaMáx', null, null, "Contas corrigidas: $corrigidos");
            $sucesso_msg = "Correção concluída: <b>$corrigidos</b> conta(s) tiveram o energiamax atualizado para nivel×100.";
        } catch (PDOException $e) {
            $erro_msg = 'Erro ao corrigir: ' . $e->getMessage();
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

<h3>Configurar reCAPTCHA</h3>
<div class="sep"></div>

<?php
$rc_cfg = obter_config_recaptcha();
$rc_version    = $rc_cfg['version']    ?? 'v2';
$rc_site_key   = $rc_cfg['site_key']   ?? '';
$rc_secret_key = $rc_cfg['secret_key'] ?? '';
$rc_min_score  = $rc_cfg['min_score']  ?? 0.5;
?>

<p class="sub2">
    O reCAPTCHA protege o formulário de cadastro contra bots.<br>
    Obtenha suas chaves em <strong>google.com/recaptcha/admin</strong>.<br>
    <strong>v2 Checkbox</strong>: exibe o widget visual. <strong>v3 Invisível</strong>: avalia por pontuação sem interação do usuário.
</p>
<div class="sep"></div>

<form method="post" action="admin_manutencao.php">
    <input type="hidden" name="action" value="salvar_recaptcha">
    <fieldset>
        <legend>Chaves do reCAPTCHA</legend>

        <table class="adm-table" style="max-width:600px;">
            <tr>
                <th style="width:160px;">Versão</th>
                <td>
                    <label style="margin-right:20px;">
                        <input type="radio" name="rc_version" value="v2" <?php echo $rc_version === 'v2' ? 'checked' : ''; ?> onchange="toggleScore(this)">
                        v2 Checkbox
                    </label>
                    <label>
                        <input type="radio" name="rc_version" value="v3" <?php echo $rc_version === 'v3' ? 'checked' : ''; ?> onchange="toggleScore(this)">
                        v3 Invisível
                    </label>
                </td>
            </tr>
            <tr>
                <th>Site Key</th>
                <td><input type="text" name="rc_site_key" value="<?php echo htmlspecialchars($rc_site_key); ?>" style="width:100%;max-width:420px;padding:4px;" placeholder="Chave do site (pública)"></td>
            </tr>
            <tr>
                <th>Secret Key</th>
                <td><input type="text" name="rc_secret_key" value="<?php echo htmlspecialchars($rc_secret_key); ?>" style="width:100%;max-width:420px;padding:4px;" placeholder="Chave secreta (privada)"></td>
            </tr>
            <tr id="row_score" style="display:<?php echo $rc_version === 'v3' ? 'table-row' : 'none'; ?>;">
                <th>Score mínimo (v3)</th>
                <td>
                    <input type="number" name="rc_min_score" value="<?php echo $rc_min_score; ?>" min="0.1" max="1.0" step="0.05" style="width:80px;padding:4px;">
                    <span class="sub2"> (0.1 = permissivo, 1.0 = rigoroso; recomendado: 0.5)</span>
                </td>
            </tr>
        </table>

        <div class="sep"></div>
        <button type="submit" class="botao btn-success">Salvar reCAPTCHA</button>
    </fieldset>
</form>

<div class="sep"></div>

<?php if ($recaptcha_configurado): ?>
<form method="post" action="admin_manutencao.php" onsubmit="return confirm('Remover configurações do reCAPTCHA?');">
    <input type="hidden" name="action" value="limpar_recaptcha">
    <button type="submit" class="botao btn-danger">Remover reCAPTCHA</button>
</form>
<?php endif; ?>

<div class="sep"></div>

<h3>IPs Bloqueados (Rate Limit de Login)</h3>
<div class="sep"></div>

<?php
require_once('../_inc/rate_limit.php');
$ips_bloqueados = rl_listar_bloqueados($conexao);
?>

<p class="sub2">
    IPs bloqueados automaticamente após <?php echo RATE_LIMIT_MAX_FALHAS; ?> tentativas falhas.
    Bloqueio dura <?php echo rl_formatar_tempo(RATE_LIMIT_BLOQUEIO_SEG); ?>.
</p>
<div class="sep"></div>

<?php if (empty($ips_bloqueados)): ?>
    <p style="color:#90EE90;">Nenhum IP bloqueado no momento.</p>
<?php else: ?>
    <form method="post" action="admin_manutencao.php" onsubmit="return confirm('Remover todos os bloqueios?');" style="display:inline;">
        <input type="hidden" name="action" value="limpar_bloqueios">
        <button type="submit" class="botao btn-danger" style="margin-bottom:10px;">Desbloquear Todos (<?php echo count($ips_bloqueados); ?>)</button>
    </form>
    <div class="sep"></div>
    <table class="adm-table" style="max-width:700px;">
        <tr>
            <th>IP</th>
            <th>Tentativas</th>
            <th>Primeiro Erro</th>
            <th>Bloqueado Até</th>
            <th>Ação</th>
        </tr>
        <?php foreach ($ips_bloqueados as $bl): ?>
        <?php $ip_str = rl_ip_num_to_str($bl['ip']); $seg_rest = max(0, strtotime($bl['bloqueado_ate']) - time()); ?>
        <tr>
            <td><code><?php echo htmlspecialchars($ip_str); ?></code></td>
            <td style="text-align:center;color:#ff6666;"><?php echo (int)$bl['tentativas']; ?></td>
            <td style="font-size:12px;"><?php echo date('d/m H:i:s', strtotime($bl['primeiro_erro'])); ?></td>
            <td style="font-size:12px;color:#f9a825;">
                <?php echo date('d/m H:i:s', strtotime($bl['bloqueado_ate'])); ?>
                <br><small>(<?php echo rl_formatar_tempo($seg_rest); ?> restantes)</small>
            </td>
            <td>
                <form method="post" action="admin_manutencao.php" style="display:inline;">
                    <input type="hidden" name="action" value="desbloquear_ip">
                    <input type="hidden" name="ip_num" value="<?php echo (int)$bl['ip']; ?>">
                    <button type="submit" class="botao btn-success" style="padding:3px 10px;font-size:12px;">Desbloquear</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<div class="sep"></div>

<h3>Correção de Energia Máxima</h3>
<div class="sep"></div>
<p class="sub2">
    A energia máxima de cada jogador deve ser <b>nível × 100</b> (ex: nível 5 → 500, nível 99 → 9.900).<br>
    Contas criadas antes desta regra ou editadas manualmente podem ter valor incorreto.<br>
    Esta ação corrige todas as contas de uma vez e também ajusta a energia atual se estiver acima do novo máximo.
</p>
<?php
// Contar quantas contas estão com energiamax errado
$count_errado = 0;
try {
    $rows_chk = $conexao->query("SELECT nivel, energiamax FROM usuarios WHERE status <> 'banido'")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows_chk as $rc) {
        if ((int)$rc['energiamax'] !== max(100, (int)$rc['nivel'] * 100)) $count_errado++;
    }
} catch (PDOException $e) {}
?>
<p>
    Contas com energiamax incorreto: <b style="color:<?php echo $count_errado > 0 ? '#ff8888' : '#88ff88'; ?>;"><?php echo $count_errado; ?></b>
</p>
<form method="post" action="admin_manutencao.php" onsubmit="return confirm('Corrigir energiamax de <?php echo $count_errado; ?> conta(s)? A energia atual será limitada ao novo máximo.');">
    <input type="hidden" name="action" value="fix_energiamax">
    <button type="submit" class="botao<?php echo $count_errado == 0 ? '' : ' btn-danger'; ?>" <?php echo $count_errado == 0 ? 'disabled' : ''; ?>>
        Corrigir EnergiaMáx (<?php echo $count_errado; ?> conta<?php echo $count_errado != 1 ? 's' : ''; ?>)
    </button>
</form>

<div class="sep"></div>
<p class="sub2"><strong>Dica:</strong> Para desativar completamente a manutenção, desmarque "Ativar modo de manutenção" e salve as configurações.</p>

</div>
<div class="box_bottom"></div>

<script>
function toggleScore(radio) {
    document.getElementById('row_score').style.display = radio.value === 'v3' ? 'table-row' : 'none';
}
document.querySelectorAll('input[name="rc_version"]').forEach(function(r){
    r.addEventListener('change', function(){ toggleScore(this); });
});
</script>

<?php include 'adm_footer.php'; ?>
