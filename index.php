<?php
if(isset($_GET['allowgm'])) setcookie('allowgm',1,time()+900);
?>
<?php // Manutenção desabilitada para testes
// if(!isset($_COOKIE['allowgm'])) if(file_exists('manutencao.php')) require_once('manutencao.php'); ?>
<?php require_once('_inc/conexao.php'); ?>
<?php
function vn($numero){
	if(!is_numeric($numero)){
			header("Location: index.php?p=home"); exit;
	}
}
$chaveuniversal='hgfdhgfd';
require_once('_inc/Encrypt.php');
$c=new C_Encrypt();

function anti_sql_injection ($str) {
    if (!is_numeric($str)) {
        // For PDO with prepared statements, we don't need to escape manually
        // Just return the string as-is, prepared statements handle the escaping
        return $str;
    }
    return $str;
}
?>
<?php
if((isset($_GET['p']))&&($_GET['p']=='logout')) require_once('_inc/logout.php');
if(isset($_POST['login_login'])&&isset($_POST['login_senha'])){
		$erro=0;
		if($_POST['login_login']=='') $erro=2;
		if($_POST['login_senha']=='') $erro=1;
		if($erro==0){
			$login_usuario = trim($_POST['login_login']);
			$login_senha = md5($_POST['login_senha']);

			// Busca no banco usando prepared statement
			try {
				$stmt = $conexao->prepare("SELECT id, usuario, senha, status FROM usuarios WHERE usuario = ?");
				$stmt->execute([$login_usuario]);
				$dados = $stmt->fetch(PDO::FETCH_ASSOC);

				if($dados) {
					// Verificar senha
					if($dados['senha'] === $login_senha) {
						if($dados['status']=='banido') {
							$erro='ban';
						} else {
							$_SESSION['logado'] = $dados['id'];
							$_SESSION['usuario'] = $dados['usuario'];
							setcookie('logado',1,time()+900);

							// Atualizar IP de login
							$update_stmt = $conexao->prepare("UPDATE usuarios SET loginip = ? WHERE id = ?");
							$update_stmt->execute([ip2long($_SERVER['REMOTE_ADDR']), $dados['id']]);

							if(isset($_POST['stay_conn'])) setcookie('naruto_u',base64_encode($dados['usuario']),time()+60*60*24*30);
							header('Location: index.php?p=home'); 
							exit;
						}
					} else {
						$erro = 3; // Senha incorreta
					}
				} else {
					$erro = 2; // Usuário não encontrado
				}
			} catch (PDOException $e) {
				error_log("Erro no login: " . $e->getMessage());
				$erro = 2;
			}
		}

		// Redirect with error if any
		if($erro > 0) {
			$data = '';
			header("Location: index.php?p=login&erro=".$erro.$data); 
			exit; 
		}
}
?>
<?php
if(isset($_COOKIE['logado'])){
	if(!isset($_SESSION['logado'])){ setcookie('logado',1,time()-3600); header("Location: index.php?p=login"); exit; }
	if((isset($_GET['p']))&&($_GET['p']=='view')) $user="u.usuario='".$_GET['view']."'"; else 
	if((isset($_GET['p']))&&($_GET['p']=='prepare')) $user='u.id='.$_SESSION['prepare']; else
	$user='u.id='.$_SESSION['logado'];
	setcookie('logado',1,time()+900);
	//setcookie('session_id',session_id(),time()+900);
	if((!isset($_GET['p']))or(isset($_GET['p']))&&($_GET['p']<>'attack')){
		try {
			if((isset($_GET['p']))&&($_GET['p']=='view')) {
				$stmt = $conexao->prepare("SELECT u.*,o.nome orgnome, o.nivel orgnivel FROM usuarios u LEFT OUTER JOIN organizacoes o ON u.orgid=o.id WHERE status<>'banido' AND u.usuario=?");
				$stmt->execute([$_GET['view']]);
			} else if((isset($_GET['p']))&&($_GET['p']=='prepare')) {
				$stmt = $conexao->prepare("SELECT u.*,o.nome orgnome, o.nivel orgnivel FROM usuarios u LEFT OUTER JOIN organizacoes o ON u.orgid=o.id WHERE status<>'banido' AND u.id=?");
				$stmt->execute([$_SESSION['prepare']]);
			} else {
				$stmt = $conexao->prepare("SELECT u.*,o.nome orgnome, o.nivel orgnivel FROM usuarios u LEFT OUTER JOIN organizacoes o ON u.orgid=o.id WHERE status<>'banido' AND u.id=?");
				$stmt->execute([$_SESSION['logado']]);
			}
			$db = $stmt->fetch(PDO::FETCH_ASSOC);
			if(!$db) die("Erro no banco de dados");
			if((isset($_GET['p']))&&($_GET['p']=='view')&&(!$db)){ header("Location: ?p=home"); exit; }
		} catch (PDOException $e) {
			die("Erro no banco de dados: " . $e->getMessage());
		}
	} else {
		try {
			$stmt = $conexao->prepare("SELECT u.id, u.status, u.usuario, u.yens, u.yens_fat, u.nivel, u.orgid, u.energia, u.energiamax, u.taijutsu, u.ninjutsu, u.genjutsu, u.personagem, u.avatar, u.renegado, u.vila, u.doujutsu, u.exp, u.expmax, u.doujutsu, u.doujutsu_nivel, u.doujutsu_exp, u.doujutsu_expmax, u.vip_inicio, u.vip, u.missao, u.hunt, u.treino, u.penalidade_fim, u.config_radio, u.loginip, o.nivel orgnivel FROM usuarios u LEFT OUTER JOIN organizacoes o ON u.orgid=o.id WHERE u.id=?");
			$stmt->execute([$_SESSION['logado']]);
			$db = $stmt->fetch(PDO::FETCH_ASSOC);
			if(!$db) die("Erro no banco de dados");
		} catch (PDOException $e) {
			die("Erro no banco de dados: " . $e->getMessage());
		}
	}
	if((isset($_GET['p']))&&($_GET['p']<>'first')&&($_GET['p']<>'view')&&($_GET['p']<>'prepare')&&($db['avatar']==0)){ header("Location: index.php?p=first"); exit; }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="google-site-verification" content="PHmvgGBdlvQZDyfThlBovUAoUu8y_vrcxusn6RWkX3Q" />
<title>:: narutoHIT - mesmo nome, nova história! ::</title>
<link href="_css/naruto.css" rel="stylesheet" type="text/css" />
<?php if((isset($_GET['p']))&&($_GET['p']=='messages')or(isset($_GET['p']))&&($_GET['p']=='config')or(isset($_GET['p']))&&($_GET['p']=='configorg')){ ?><script type="text/javascript" src="_js/tinymce/jscripts/tiny_mce/tiny_mce.js"></script>
<script type="text/javascript">
tinyMCE.init({
	mode : "textareas",
	theme: "advanced",
	plugins: "emotions",
	theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,cut,copy,paste,|,undo,redo,|,link,unlink,image,|,emotions",
	theme_advanced_buttons2 : "",
	theme_advanced_buttons3 : "",
	theme_advanced_toolbar_location : "top",
	content_css:"_css/tiny.css",
	theme_advanced_statusbar_location : "bottom",
	theme_advanced_path : false
});
</script>
<?php } ?>
<script language="javascript">
var xmlhttp;

function carregaAjax(div,geturl,carg)
{
xmlhttp=GetXmlHttpObject();
if (xmlhttp==null)
  {
  alert ("Browser does not support HTTP Request");
  return;
  }
var url=geturl;
xmlhttp.onreadystatechange=function(){
  if((xmlhttp.readyState==1)&&(carg=='s')) document.getElementById(div).innerHTML='<div class="aviso" style="margin-top:10px;margin-bottom:10px;"><img src="_js/loading.gif" /><br /><b>Carregando...</b></div>';
  if(xmlhttp.readyState==4) document.getElementById(div).innerHTML=xmlhttp.responseText;
}
xmlhttp.open("GET",url,true);
xmlhttp.send(null);
}

function GetXmlHttpObject()
{
if (window.XMLHttpRequest)
  {
  // code for IE7+, Firefox, Chrome, Opera, Safari
  return new XMLHttpRequest();
  }
if (window.ActiveXObject)
  {
  // code for IE6, IE5
  return new ActiveXObject("Microsoft.XMLHTTP");
  }
return null;
}
</script>
<?php /*<script src="http://twitterjs.googlecode.com/svn/trunk/src/twitter.min.js" type="text/javascript"></script>*/ ?>
<?php if(isset($_COOKIE['logado'])){ ?>
<script type="text/javascript" src="_js/jquery-1.2.6.pack.js"></script>
<script type="text/javascript" src="_js/jquery-modal-1.0.pack.js"></script>
<?php } ?>
<link rel="shortcut icon" href="_img/favicon.ico" />
<script type="text/javascript">
if(document.location.protocol=='http:'){
 var Tynt=Tynt||[];Tynt.push('b8ezxe6o4r36yladbi-bnq');Tynt.i={"ap":"Fonte: "};
 (function(){var s=document.createElement('script');s.async="async";s.type="text/javascript";s.src='http://tcr.tynt.com/ti.js';var h=document.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);})();
}
</script>
</head>

<body>
<script src="_js/wz/wz_tooltip.js" type="text/javascript" language="javascript"></script> 
<div align="center">
<table align="center" cellpadding="0" cellspacing="0" width="760">
	<tr>
	  <td width="20" rowspan="6" style="background:url(_img/border_left.jpg) repeat-y right;">&nbsp;</td>
	  <td height="180" colspan="2" valign="bottom" style="background:url(_img/logo2.jpg) no-repeat center;">&nbsp;</td>
	  <td width="20" rowspan="6" style="background:url(_img/border_right.jpg) repeat-y;">&nbsp;</td>
    </tr>
	<tr>
	  <td colspan="2" align="center" class="menutop" style="color:#666666;"><?php require_once('_inc/top.php'); ?></td>
    </tr>
	<tr>
	  <td colspan="2" valign="top" style="background:url(_img/border_top.jpg) repeat-x top;">&nbsp;</td>
    </tr>
	<tr>
	  <td width="170" valign="top" bgcolor="#444444"><?php if(!isset($_SESSION['logado'])) require_once('_inc/menu_off.php'); else require_once('_inc/menu_on.php'); ?></td>
	  <td width="548" valign="top" bgcolor="#444444">
      <?php if(!isset($_SESSION['logado'])) require_once('_inc/anuncio_top.php'); else {
		if((date('Y-m-d H:i:s')>=$db['vip'])&&(isset($_GET['p']))&&($_GET['p']<>'view')&&($_GET['p']<>'prepare')) if(file_exists('_inc/anuncio_top.php')) require_once('_inc/anuncio_top.php');
	} ?>
	  <?php
	  if(isset($_SESSION['logado'])) require_once('_inc/cidade.php');
	  if(isset($_SESSION['logado'])) require_once('_inc/verificar_doujutsu.php');
	  if((isset($_SESSION['logado']))&&(isset($_GET['p']))&&($_GET['p']<>'view')&&($_GET['p']<>'prepare')&&($_GET['p']<>'attack')) require_once('_inc/online.php');
	  require_once('_inc/verifica_nivel.php');
	  ?>
      <?php
	  if(!isset($_GET['p'])) require_once('_inc/home.php'); else {
		switch($_GET['p']){
			case 'login': require_once('_inc/login.php'); break;
			case 'terms': require_once('_inc/terms.php'); break;
			case 'reg': require_once('_inc/reg.php'); break;
			case 'reg2': require_once('_inc/reg2.php'); break;
			case 'recover': require_once('_inc/recover.php'); break;
			case 'home': require_once('_inc/home.php'); break;
			case 'attack': require_once('_inc/attack.php'); break;
			case 'missions': require_once('_inc/missions.php'); break;
			case 'contact': require_once('_inc/contact.php'); break;
			case 'messages':
			// Check if we need to redirect to form
			if(!isset($_GET['type']) && !isset($_GET['aba'])) {
				header("Location: index.php?p=messages&type=form");
				exit();
			}
			include('_inc/messages_main.php');
			include('_inc/messages.php');
			if(!isset($_GET['type'])) include('_inc/messages_form.php');
			else{
				switch($_GET['type']){
					case 'r': include('_inc/messages_r.php'); break;
					case 'e': include('_inc/messages_e.php'); break;
				}
			}
		break;
			case 'rewardmission': require_once('_inc/rewardmission.php'); break;
			case 'rewardtrain': require_once('_inc/rewardtrain.php'); break;
			//case 'changechar': require_once('_inc/changechar.php'); break;
			case 'first': require_once('_inc/first.php'); break;
			case 'jutsus': require_once('_inc/jutsus.php'); break;
			case 'school': require_once('_inc/school.php'); break;
			case 'room': require_once('_inc/room.php'); break;
			case 'donations': require_once('_inc/donations.php'); break;
			case 'vip': require_once('_inc/vip.php'); break;
			case 'vip1': require_once('_inc/vip1.php'); break;
			case 'vip2': require_once('_inc/vip2.php'); break;
			case 'viewdonation': require_once('_inc/viewdonation.php'); break;
			case 'vipfinish': require_once('vipfinish.php'); break;
			case 'elements': require_once('_inc/elements.php'); break;
			case 'learn': require_once('_inc/learn.php'); break;
			case 'ramen': require_once('_inc/ramen.php'); break;
			case 'hunt': require_once('_inc/hunt.php'); break;
			case 'rank': require_once('_inc/rank.php'); break;
			case 'pratice': require_once('_inc/pratice.php'); break;
			case 'doujutsu': require_once('doujutsu.php'); break;
			case 'newdoujutsu': require_once('newdoujutsu.php'); break;
			case 'schooltrain': require_once('_inc/schooltrain.php'); break;
			case 'busymission': require_once('_inc/busymission.php'); break;
			case 'busytrain': require_once('_inc/busytrain.php'); break;
			case 'updates': require_once('_inc/updates.php'); break;
			case 'friends': require_once('_inc/friends.php'); break;
			case 'inventory': require_once('_inc/inventory.php'); break;
			case 'logout': require_once('_inc/logout.php'); break;
			case 'config': require_once('_inc/config.php'); break;
			case 'block': require_once('_inc/block.php'); break;
			case 'view': require_once('_inc/view.php'); break;
			case 'org': require_once('_inc/org.php'); break;
			case 'myorg': require_once('_inc/myorg.php'); break;
			case 'leaveorg': require_once('_inc/leaveorg.php'); break;
			case 'requestorg': require_once('_inc/requestorg.php'); break;
			case 'createorg': require_once('_inc/createorg.php'); break;
			case 'vieworg': require_once('_inc/vieworg.php'); break;
			case 'misorg': require_once('_inc/misorg.php'); break;
			case 'addorg': require_once('_inc/addorg.php'); break;
			case 'destroyorg': require_once('_inc/destroyorg.php'); break;
			case 'donateorg': require_once('_inc/donateorg.php'); break;
			case 'configorg': require_once('_inc/configorg.php'); break;
			case 'addfriend': require_once('_inc/addfriend.php'); break;
			case 'acceptfriend': require_once('_inc/acceptfriend.php'); break;
			case 'train': require_once('_inc/train.php'); break;
			case 'akatsuki': require_once('_inc/akatsuki.php'); break;
			case 'busyhunt': require_once('_inc/busyhunt.php'); break;
			case 'rewardhunt': require_once('_inc/rewardhunt.php'); break;
			case 'prepare': require_once('_inc/prepare.php'); break;
			case 'batalha': require_once('_inc/batalha.php'); break;
			case 'attack': require_once('_inc/attack.php'); break;
			case 'shop': require_once('_inc/shop.php'); break;
			case 'polls': require_once('_inc/polls.php'); break;
			case 'news': require_once('_inc/news.php'); break;
			case 'reports': require_once('_inc/reports.php'); break;
			case 'report': require_once('_inc/report.php'); break;
			case 'penalty': require_once('_inc/penalty.php'); break;
			case 'events': require_once('_inc/events.php'); break;
			case 'faq': require_once('_inc/faq.php'); break;
			case 'chat': require_once('_inc/chat.php'); break;
			case 'spam': require_once('_inc/spam.php'); break;
			case 'discover': require_once('_inc/discover.php'); break;
			case 'radio': require_once('_inc/radio.php'); break;
			case 'vipform': require_once('_inc/vipform.php'); break;
			case 'myshop': require_once('_inc/myshop.php'); break;
			case 'news2': require_once('_inc/news2.php'); break;
			case 'pedra': require_once('_inc/pedra.php'); break;
			case 'ads': require_once('_inc/ads.php'); break;
			case 'changedoujutsu': require_once('_inc/changedoujutsu.php'); break;
			case 'stats': require_once('_inc/stats.php'); break;
			case 'book': require_once('_inc/book.php'); break;
			case 'addbook': require_once('_inc/addbook.php'); break;
			case 'support': require_once('_inc/support.php'); break;
			case 'addmy': require_once('_inc/addmy.php'); break;
			case 'shops': require_once('_inc/shops.php'); break;
			case 'viewshop': require_once('_inc/viewshop.php'); break;
			case 'blacksmith': require_once('_inc/blacksmith.php'); break;
			case 'parchments': require_once('_inc/parchments.php'); break;
			case 'quests': require_once('_inc/quests.php'); break;
			case 'msgvip': require_once('_inc/msgvip.php'); break;
			case 'blocklogin': require_once('_inc/blocklogin.php'); break;
			case 'mapa': require_once('_inc/mapa.php'); break;
			case 'adm': require_once('_inc/adm.php'); break;
			default: require_once('_inc/error.php'); break;
		}
	} ?>
    <?php if(!isset($_SESSION['logado'])) require_once('_inc/anuncio_bottom.php'); else {
		if((date('Y-m-d H:i:s')>=$db['vip'])&&(isset($_GET['p']))&&($_GET['p']<>'view')&&($_GET['p']<>'prepare')) if(file_exists('_inc/anuncio_bottom.php')) require_once('_inc/anuncio_bottom.php');
	} ?></td>
    </tr>
    <tr>
	  <td colspan="2" valign="top" style="background:url(_img/border_bottom.jpg) repeat-x bottom #444444;padding-top:3px;">&nbsp;</td>
    </tr>
	<tr>
	  <td colspan="2" align="center" valign="middle" class="menutop">Copyright 2009 &copy; Direitos do <b>Jogo e Sistema</b> Reservados &agrave; <b>narutoHIT.net</b><br />
      Copyright 2002 &copy; Direitos do <b>Anime e Imagens</b> Reservados à <b>Masashi Kishimoto</b></td>
    </tr>
</table>
<?php
// PDO automatically frees result sets, no need for manual cleanup
?>
</div>
</body>
</html>