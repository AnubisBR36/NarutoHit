<div style="width:170px;">
<?php 
require_once('trava.php'); 
require_once('funcoes.php');
?>
<?php
switch($db['vila']){
        case 1: $vila='folha'; if($db['renegado']=='sim') $txtvila='Akatsuki (Vila da Folha)'; else $txtvila='Vila da Folha'; break;
        case 2: $vila='areia'; if($db['renegado']=='sim') $txtvila='Akatsuki (Vila da Areia)'; else $txtvila='Vila da Areia'; break;
        case 3: $vila='som'; if($db['renegado']=='sim') $txtvila='Akatsuki (Vila do Som)'; else $txtvila='Vila do Som'; break;
        case 4: $vila='chuva'; if($db['renegado']=='sim') $txtvila='Akatsuki (Vila da Chuva)'; else $txtvila='Vila da Chuva'; break;
        case 5: $vila='nuvem'; if($db['renegado']=='sim') $txtvila='Akatsuki (Vila da Nuvem)'; else $txtvila='Vila da Nuvem'; break;
        case 6: $vila='nevoa'; if($db['renegado']=='sim') $txtvila='Akatsuki (Vila da Névoa)'; else $txtvila='Vila da Névoa'; break;
        case 8: $vila='pedra'; if($db['renegado']=='sim') $txtvila='Akatsuki (Vila da Pedra)'; else $txtvila='Vila da Pedra'; break;
        case 99: $vila='folha'; $txtvila='Vila da Folha'; break;
} ?>
<?php if((!isset($_GET['p']))or(isset($_GET['p']))&&($_GET['p']<>'attack')){ ?>
<?php if((!isset($_GET['p']))or(isset($_GET['p']))&&($_GET['p']<>'view')&&($_GET['p']<>'prepare')){ ?>
<div id="msg" style="margin-bottom:4px;">
        <?php
        try {
                $stmt = $conexao->prepare("SELECT count(id) as conta FROM mensagens WHERE destino = ? AND status = 'naolido'");
                $stmt->execute([$db['id']]);
                $dbm = $stmt->fetch(PDO::FETCH_ASSOC);
                if(!$dbm) $dbm = array('conta' => 0);
        } catch (PDOException $e) {
                $dbm = array('conta' => 0);
        }

        try {
                $stmt = $conexao->prepare("SELECT count(id) as conta FROM relatorios WHERE inimigoid = ? AND status = 'nao'");
                $stmt->execute([$db['id']]);
                $dba = $stmt->fetch(PDO::FETCH_ASSOC);
                if(!$dba) $dba = array('conta' => 0);
        } catch (PDOException $e) {
                $dba = array('conta' => 0);
        }
        // Notificação de tickets de suporte com novidade
        try {
                $stmt = $conexao->prepare("SELECT count(id) as conta FROM tickets WHERE usuario_id = ? AND nao_lido_player = 1");
                $stmt->execute([$db['id']]);
                $dbt = $stmt->fetch(PDO::FETCH_ASSOC);
                if(!$dbt) $dbt = array('conta' => 0);
        } catch (PDOException $e) {
                $dbt = array('conta' => 0);
        }
        if($dbm['conta']>0){
                echo '<div class="action"><a href="?p=messages">'.$dbm['conta'].' nova';
                if($dbm['conta']>1) echo 's';
                echo ' mensage';
                if($dbm['conta']>1) echo 'ns'; else echo 'm';
                echo '!</a></div>';
        }
        if($dba['conta']>0){
                echo '<div class="action"><a href="?p=reports">Você foi atacado '.$dba['conta'].' vez';
                if($dba['conta']>1) echo 'es';
                echo '!</a></div>';
        }
        if($dbt['conta']>0){
                echo '<div class="action"><a href="?p=support" style="display:flex;align-items:center;gap:6px;justify-content:center;">';
                echo '<img src="_img/important.png" alt="!" style="width:18px;height:18px;flex-shrink:0;animation:tkPulse 1.2s ease-in-out infinite;" />';
                echo '<span>'.$dbt['conta'].' ticket';
                if($dbt['conta']>1) echo 's';
                echo ' atualizado';
                if($dbt['conta']>1) echo 's';
                echo '!</span></a></div>';
                echo '<style>@keyframes tkPulse{0%,100%{transform:scale(1);filter:drop-shadow(0 0 0 transparent);}50%{transform:scale(1.15);filter:drop-shadow(0 0 4px #ff6666);}}</style>';
        }
        ?>
</div>
<?php } ?>
<?php $isVipUser = isset($db['vip']) && date('Y-m-d H:i:s') < $db['vip']; ?>
<?php
// Buscar nome do servidor — usa o servidor da sessão (correto para ADM que logou em outro servidor)
try {
    $srv_id_player = null;
    if (isset($_SESSION['servidor_id']) && $_SESSION['servidor_id'] !== null) {
        $srv_id_player = (int)$_SESSION['servidor_id'];
    } elseif (isset($db['servidor_id']) && $db['servidor_id'] !== null) {
        $srv_id_player = (int)$db['servidor_id'];
    }
    if ($srv_id_player !== null) {
        $stmt_srv_nome = $conexao->prepare("SELECT nome FROM servidores WHERE id = ? LIMIT 1");
        $stmt_srv_nome->execute([$srv_id_player]);
        $row_srv_nome = $stmt_srv_nome->fetch(PDO::FETCH_ASSOC);
        $srv_nome_display = $row_srv_nome ? $row_srv_nome['nome'] : 'Servidor '.$srv_id_player;
    } else {
        $srv_nome_display = null;
    }
} catch (Exception $e) {
    $srv_nome_display = null;
}
?>
<div align="center" style="position:relative; width:162px; margin:0 auto;">
    <div style="position:relative; background:url(_img/personagens/no_avatar.jpg) no-repeat top; height:150px; width:162px; overflow:hidden; border-radius:4px;">
        <a href="<?php if($db['avatar']==0) echo '?p=avatar'; else echo '?p=home'; ?>">
            <img src="_img/personagens/<?php echo $db['personagem']; ?>/<?php echo $db['avatar']; ?>.jpg" width="162" height="150" border="0" <?php if($isVipUser) echo 'title="VIP"'; ?> />
        </a>
        <?php if($isVipUser): ?>
        <img src="_img/vip.png" alt="VIP" style="position:absolute;top:4px;left:4px;width:26px;height:26px;z-index:4;filter:drop-shadow(0 1px 2px rgba(0,0,0,0.8));pointer-events:none;" />
        <?php endif; ?>
        <?php
        // Notificação de mensagens não lidas
        try {
            $stmt_unread_avatar = $conexao->prepare("SELECT COUNT(*) as total FROM mensagens WHERE destino = ? AND status = 'naolido'");
            $stmt_unread_avatar->execute([$db['id']]);
            $unread_avatar = $stmt_unread_avatar->fetch(PDO::FETCH_ASSOC);
            if($unread_avatar && $unread_avatar['total'] > 0) {
                echo '<div style="position:absolute;top:5px;right:5px;background:#ff0000;color:#fff;border-radius:10px;padding:2px 6px;font-size:10px;font-weight:bold;min-width:15px;text-align:center;z-index:3;">';
                echo $unread_avatar['total'];
                echo '</div>';
            }
        } catch (PDOException $e) {}
        ?>
        <?php if($srv_nome_display): ?>
        <style>
            @keyframes srvScreenBlink {
                0%, 45%   { fill:#0d0d0d; filter:none; }
                50%, 95%  { fill:#39ff14; filter:drop-shadow(0 0 2px #39ff14); }
                100%      { fill:#0d0d0d; filter:none; }
            }
            .srv-screen { animation: srvScreenBlink 1.4s steps(1, end) infinite; transform-origin:center; }
        </style>
        <div style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,0.72);padding:4px 6px;display:flex;align-items:center;justify-content:center;gap:5px;">
            <svg width="13" height="13" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="flex-shrink:0;">
                <rect x="2" y="3" width="20" height="14" rx="1.5" ry="1.5" fill="#cfcfcf" stroke="#1a1a1a" stroke-width="1.2"/>
                <rect class="srv-screen" x="3.5" y="4.5" width="17" height="11" rx="0.5" ry="0.5"/>
                <rect x="9" y="18" width="6" height="2" fill="#cfcfcf" stroke="#1a1a1a" stroke-width="0.8"/>
                <rect x="6" y="20" width="12" height="1.6" rx="0.6" ry="0.6" fill="#1a1a1a"/>
            </svg>
            <span style="color:#FFD700;font-size:11px;font-weight:bold;letter-spacing:0.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:130px;"><?php echo htmlspecialchars($srv_nome_display); ?></span>
        </div>
        <?php endif; ?>
    </div>
</div>
<div align="center"><img src="<?php echo getBandanaPath($db['bandana_estilo'], $db['renegado'], $db['vila']); ?>" style="width: 117px; height: 55px;" /></div>
<?php } ?>

<?php
// Verificar se o usuário é administrador para mostrar seção de Administração
$show_admin = false;

// Primeiro verificar se há sessão ativa
if(isset($_SESSION['logado']) && !empty($_SESSION['logado'])) {
    // Verificar se já temos a informação na sessão
    if(isset($_SESSION['adm']) && ($_SESSION['adm'] == 1 || $_SESSION['adm'] == 2)) {
        $show_admin = true;
    } else {
        // Se não, buscar no banco de dados
        $stmt = $conexao->prepare("SELECT adm FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['logado']]);
        $user_adm_check = $stmt->fetch(PDO::FETCH_ASSOC);

        if($user_adm_check && ($user_adm_check['adm'] == 1 || $user_adm_check['adm'] == 2)) {
            $show_admin = true;
            $_SESSION['adm'] = $user_adm_check['adm']; // Atualizar sessão
        }
    }
}

if($show_admin): ?>
<div style="background:#2a1a00;border:1px solid #FFD700;border-radius:4px;margin:4px 0;padding:6px 8px;">
    <a href="adm/adm.php" style="color:#FFD700;font-weight:bold;text-decoration:none;font-size:11px;">
        <img src="_img/Chapeu/bg-kage-konoha.jpg" style="width:14px;height:14px;vertical-align:middle;margin-right:3px;">
        Painel do Administrador
    </a>
</div>
<?php endif; ?>

<?php if((!isset($_GET['p']))or($_GET['p']=='home')) require_once('friendlist.php'); ?>
<?php 
// Replacing the menu, specifically "Chat" to "Invasão"
require_once('menu_comum.php'); 
?>
</div>