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
	$sqlm=mysql_query("SELECT count(id) conta FROM mensagens WHERE destino=".$db['id']." AND status='naolido'");
	if($sqlm) {
		$dbm=mysql_fetch_assoc($sqlm);
	} else {
		$dbm = array('conta' => 0);
	}
	$sqla=mysql_query("SELECT count(id) conta FROM relatorios WHERE inimigoid=".$db['id']." AND status='nao'");
	if($sqla) {
		$dba=mysql_fetch_assoc($sqla);
	} else {
		$dba = array('conta' => 0);
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
	?>
</div>
<?php } ?>
<div align="center" style="background:url(_img/personagens/no_avatar.jpg) no-repeat top;height:150px;">
    <a href="<?php if($db['avatar']==0) echo '?p=avatar'; else echo '?p=home'; ?>">
        <img src="_img/personagens/<?php echo $db['personagem']; ?>/<?php echo $db['avatar']; ?>.jpg" width="162" height="150" border="0" />
    </a>
</div>
<?php if(is_top1_vila($db['id'], $db['vila']) || true): ?>
<?php
// Get the appropriate nivel text for pergaminho
if($db['renegado']=='sim'){
    $sqlx=mysql_query("SELECT id FROM usuarios WHERE renegado='sim' ORDER BY nivel DESC, yens_fat DESC, vitorias DESC, derrotas ASC LIMIT 1");
    $dbx=mysql_fetch_assoc($sqlx);
    if($dbx && $dbx['id']==$db['id']) $nivel_pergaminho='Líder da Akatsuki'; else $nivel_pergaminho='Nukenin';
} else {
    $sqlx=mysql_query("SELECT id FROM usuarios WHERE vila=".$db['vila']." AND renegado='nao' ORDER BY nivel DESC, yens_fat DESC, vitorias DESC, derrotas ASC LIMIT 1");
    $dbx=mysql_fetch_assoc($sqlx);
    if($dbx && $dbx['id']==$db['id']){
        switch($db['vila']){
            case 1: $nivel_pergaminho='Hokage'; break;
            case 2: $nivel_pergaminho='Kazekage'; break;
            case 3: $nivel_pergaminho='Otokage'; break;
            case 4: $nivel_pergaminho='Líder da Vila da Chuva'; break;
            case 5: $nivel_pergaminho='Raikage'; break;
            case 6: $nivel_pergaminho='Mizukage'; break;
            case 8: $nivel_pergaminho='Tsuchikage'; break;
            case 99: $nivel_pergaminho='Hokage'; break;
        }
    } else $nivel_pergaminho=rankNinja($db['nivel']); 
}
?>
<div align="center" style="height:auto;">
    <div class="kage_container" style="margin:2px auto;">
        <img src="_img/Chapeu/Pergaminho.jpg" width="162" height="40" class="kage_pergaminho" />
        <div style="position:absolute; margin-top:-25px; width:162px; text-align:center; color:#FFD700; font-weight:bold; font-size:10px; text-shadow: -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000; font-family:'Comic Sans MS', cursive, fantasy; letter-spacing:0.5px;">
            <?php echo strtoupper($nivel_pergaminho); ?>
        </div>
        <?php if(is_top1_vila($db['id'], $db['vila'])): ?>
            <img src="_img/Chapeu/bg-kage-konoha.jpg" class="kage_bg" />
        <?php endif; ?>
        <?php 
        if(is_top1_vila($db['id'], $db['vila'])) {
            switch($db['vila']){
                case 1: echo '<div style="position:absolute;top:8px;left:50px;color:#FFD700;font-weight:bold;font-size:16px;text-shadow:2px 2px 0px #000000, -2px -2px 0px #000000, 2px -2px 0px #000000, -2px 2px 0px #000000;"><b>Hokage</b></div>'; break;
                case 2: echo '<div style="position:absolute;top:8px;left:50px;color:#FFD700;font-weight:bold;font-size:16px;text-shadow:2px 2px 0px #000000, -2px -2px 0px #000000, 2px -2px 0px #000000, -2px 2px 0px #000000;"><b>Kazekage</b></div>'; break;
                case 3: echo '<div style="position:absolute;top:8px;left:50px;color:#FFD700;font-weight:bold;font-size:16px;text-shadow:2px 2px 0px #000000, -2px -2px 0px #000000, 2px -2px 0px #000000, -2px 2px 0px #000000;"><b>Mizukage</b></div>'; break;
                case 4: echo '<div style="position:absolute;top:8px;left:50px;color:#FFD700;font-weight:bold;font-size:16px;text-shadow:2px 2px 0px #000000, -2px -2px 0px #000000, 2px -2px 0px #000000, -2px 2px 0px #000000;"><b>Raikage</b></div>'; break;
                case 5: echo '<div style="position:absolute;top:8px;left:50px;color:#FFD700;font-weight:bold;font-size:16px;text-shadow:2px 2px 0px #000000, -2px -2px 0px #000000, 2px -2px 0px #000000, -2px 2px 0px #000000;"><b>Tsuchikage</b></div>'; break;
                case 6: echo '<div style="position:absolute;top:8px;left:50px;color:#FFD700;font-weight:bold;font-size:16px;text-shadow:2px 2px 0px #000000, -2px -2px 0px #000000, 2px -2px 0px #000000, -2px 2px 0px #000000;"><b>Otokage</b></div>'; break;
                case 8: echo '<div style="position:absolute;top:8px;left:50px;color:#FFD700;font-weight:bold;font-size:16px;text-shadow:2px 2px 0px #000000, -2px -2px 0px #000000, 2px -2px 0px #000000, -2px 2px 0px #000000;"><b>Amekage</b></div>'; break;
                case 99: echo '<div style="position:absolute;top:8px;left:50px;color:#FFD700;font-weight:bold;font-size:16px;text-shadow:2px 2px 0px #000000, -2px -2px 0px #000000, 2px -2px 0px #000000, -2px 2px 0px #000000;"><b>Hokage</b></div>'; break;
            }
        } else {
            // Para jogadores que não são top 1, mostra o rank ninja
            $rank_ninja = ($db['renegado']=='sim') ? 'NUKENIN' : strtoupper(rankNinja($db['nivel']));
            echo '<div style="position:absolute;top:8px;left:50px;color:#FFFFFF;font-weight:bold;font-size:16px;text-shadow:2px 2px 0px #000000, -2px -2px 0px #000000, 2px -2px 0px #000000, -2px 2px 0px #000000;"><b>'.$rank_ninja.'</b></div>';
        }
        ?>
    </div>
</div>
<?php endif; ?>
<div align="center"><img src="_img/NewsVilas/<?php if($db['renegado']=='sim') echo 'akatsuki'; else echo $vila; ?>.jpg" onmouseover="Tip('<div class=tooltip><?php echo $txtvila; ?></div>');" onmouseout="UnTip()" /></div>
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
<div class="box2_top">Administração</div>
<div class="box2_middle">
    <div class="sub2">
        <a href="?p=adm" style="color: #FFD700;">
            <img src="_img/Chapeu/bg-kage-konoha.jpg" style="width: 16px; height: 16px; vertical-align: middle; margin-right: 3px;">
            Painel do Administrador
        </a>
    </div>
</div>
<div class="box2_bottom"></div>
<?php endif; ?>

<?php
// Verificar se o usuário é administrador ou moderador
if(isset($_SESSION['userid']) && !empty($_SESSION['userid'])) {
    $stmt = $conexao->prepare("SELECT adm FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['userid']]);
    $user_adm = $stmt->fetch(PDO::FETCH_ASSOC);

    if($user_adm && ($user_adm['adm'] == 1 || $user_adm['adm'] == 2)): ?>
<div class="box2_top">Admin</div>
<div class="box2_middle">
    <div class="sub2">
        <a href="?p=adm" style="color: #FFD700;">
            <img src="_img/Chapeu/bg-kage-konoha.jpg" style="width: 16px; height: 16px; vertical-align: middle; margin-right: 3px;">
            Painel do Administrador
        </a>
    </div>
</div>
<div class="box2_bottom"></div>
    <?php endif;
} ?>

<?php if((!isset($_GET['p']))or($_GET['p']=='home')) require_once('friendlist.php'); ?>
<?php require_once('menu_comum.php'); ?>
</div>