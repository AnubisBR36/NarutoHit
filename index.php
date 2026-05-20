<?php
define('INDEX', true);

// Se o instalador ainda não foi executado (pasta install/ existe E
// config/database.php ainda não foi gerado), redireciona automaticamente
// para o wizard. Quando config/database.php já existe, o site abre
// normalmente — o instalador segue disponível em install/install.php
// caso o admin queira reinstalar manualmente.
if (
    is_dir(__DIR__ . '/install') &&
    is_file(__DIR__ . '/install/install.php') &&
    !is_file(__DIR__ . '/config/database.php')
) {
    header('Location: install/install.php');
    exit;
}

// session_start() removido daqui — security.php (via conexao.php) configura
// os parâmetros de cookie seguros (HttpOnly, SameSite, Secure) ANTES de iniciar
// a sessão. Chamar session_start() aqui impedia essa configuração de ser aplicada.
if(isset($_GET['allowgm'])) setcookie('allowgm',1,time()+900);

// Verificar se é página do fórum - usar layout próprio (ANTES de qualquer outra verificação)
$forum_pages = ['forum', 'forum_topicos', 'forum_topico', 'forum_criar_topico', 'forum_criar_postagem', 
                'forum_deletar_postagem', 'forum_curtir', 'forum_deletar_topico', 'forum_fixar', 
                'forum_fechar', 'forum_busca', 'forum_reacao'];
$current_page = $_GET['p'] ?? '';
if (in_array($current_page, $forum_pages)) {
    require_once('_inc/conexao.php');
    require_once('_inc/forum.php');
    exit;
}

require_once('_inc/conexao.php');
require_once('_inc/funcoes_manutencao.php');

function vn($numero){
        if(!is_numeric($numero)){
                        echo "<script>self.location='?p=home'</script>";
                        exit;
        }
}
$PHP_SELF = $_SERVER['PHP_SELF'];
$chaveuniversal='hgfdhgfd';
$PHP_SELF = '';
require_once('_inc/Encrypt.php');
$c=new C_Encrypt();

// Função anti_sql_injection removida - usando apenas prepared statements
?>
<?php
if(isset($_GET['p'])) if($_GET['p']=='logout') require_once('_inc/logout.php');

// Handler: Aceite ou Negação dos termos de desbanimento
if (isset($_POST['ban_terms_action']) && isset($_SESSION['unban_user_id'])) {
        $unban_user_id = (int)$_SESSION['unban_user_id'];
        $ban_penalty_config = file_exists('config/ban_penalty.php') ? require('config/ban_penalty.php') : ['penalty_minutes' => 5];
        $penalty_minutes = max(1, (int)($ban_penalty_config['penalty_minutes'] ?? 5));

        if ($_POST['ban_terms_action'] === 'accept') {
                $conexao->prepare("UPDATE usuarios SET ban_aceite_pendente = 0, ban_penalty_ate = NULL WHERE id = ?")->execute([$unban_user_id]);
                $stmt2 = $conexao->prepare("SELECT * FROM usuarios WHERE id = ?");
                $stmt2->execute([$unban_user_id]);
                $unban_user = $stmt2->fetch(PDO::FETCH_ASSOC);
                if ($unban_user) {
                        session_regenerate_id(true);
                        $_SESSION['logado'] = $unban_user['id'];
                        unset($_SESSION['unban_user_id'], $_SESSION['unban_info']);
                        setcookie('logado', 1, time()+900);
                        $conexao->prepare("UPDATE usuarios SET loginip=?, ultimo_acesso=CURRENT_TIMESTAMP WHERE id=?")->execute([ip2long($_SERVER['REMOTE_ADDR']), $unban_user['id']]);
                        require_once('_inc/cutenews_sso.php');
                        cutenews_set_sso_token($unban_user['id'], $unban_user['usuario'], in_array($unban_user['adm'], [1, 2]) ? 1 : 0);
                        if ($unban_user['avatar'] == 0) { header("Location: index.php?p=first"); exit; }
                        header("Location: index.php?p=home");
                        exit;
                }
        } elseif ($_POST['ban_terms_action'] === 'deny') {
                $penalty_ate = date('Y-m-d H:i:s', time() + $penalty_minutes * 60);
                $conexao->prepare("UPDATE usuarios SET ban_penalty_ate = ? WHERE id = ?")->execute([$penalty_ate, $unban_user_id]);
                $_SESSION['ban_penalty_info'] = ['ate' => $penalty_ate];
                unset($_SESSION['unban_user_id'], $_SESSION['unban_info']);
                header("Location: index.php?p=login&penalidade=1");
                exit;
        }
}

if(isset($_POST['login_login'])){
        $erro=0;
        if($_POST['login_login']=='') $erro=1;
        if($_POST['login_senha']=='') $erro=1;
        // Rate limit: máx 8 tentativas em 5 min (proteção contra brute force)
        if($erro==0 && !rate_limit_check('login', 8, 300)){
                error_log('[login] rate limit excedido para IP ' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
                $erro=3;
        }
        
        if($erro==0){
                $stmt = $conexao->prepare("SELECT id,senha,avatar,missao,missao_fim,status,adm,ban_data,ban_duracao,ban_motivo,servidor_id FROM usuarios WHERE usuario=?");
                $stmt->execute([$_POST['login_login']]);
                $db = $stmt->fetch(PDO::FETCH_ASSOC);
                if(!$db) $erro=2;
                
                if($erro==0){
                        $is_admin = isset($db['adm']) && in_array($db['adm'], [1, 2]);
                        
                        if (recaptcha_configurado()) {
                                $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
                                if (!verificar_recaptcha($recaptcha_response)) {
                                        $erro=6;
                                }
                        }
                        
                        if(esta_em_manutencao() && !$is_admin) {
                                header("Location: index.php?p=manutencao");
                                exit;
                        }
                }
                if($erro==0){
                        //if($db['status']=='inativo') $erro=5;
                        if($db['missao']==999){
                                $atual=date('Y-m-d H:i:s');
                                if($atual<$db['missao_fim']) $erro=4;
                        }
                        if($db['status']=='banido'){
                                $ban_expirado = false;
                                if (!empty($db['ban_data']) && !empty($db['ban_duracao']) && (int)$db['ban_duracao'] < 3650) {
                                        try {
                                                $data_fim = new DateTime($db['ban_data']);
                                                $data_fim->add(new DateInterval('P' . (int)$db['ban_duracao'] . 'D'));
                                                if (new DateTime() >= $data_fim) {
                                                        $ban_expirado = true;
                                                }
                                        } catch (Exception $e) {}
                                }
                                if ($ban_expirado) {
                                        // Registrar no histórico antes de limpar
                                        try {
                                            $stmt_hist = $conexao->prepare("INSERT INTO ban_historico (usuario_id, motivo, duracao, ban_data, ban_fim) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
                                            $stmt_hist->execute([$db['id'], $db['ban_motivo'], $db['ban_duracao'], $db['ban_data']]);
                                        } catch (Exception $e) {}
                                        $stmt_unban = $conexao->prepare("UPDATE usuarios SET status = 'ativo', ban_data = NULL, ban_duracao = NULL, ban_motivo = NULL, ban_aceite_pendente = 1, ban_penalty_ate = NULL WHERE id = ?");
                                        $stmt_unban->execute([$db['id']]);
                                } else {
                                        $_SESSION['ban_info'] = [
                                                'motivo' => $db['ban_motivo'] ?? 'Não especificado',
                                                'data'   => $db['ban_data'] ?? '',
                                                'duracao' => (int)($db['ban_duracao'] ?? 0)
                                        ];
                                        echo "<script>self.location='index.php?p=login&erro=ban'</script>";
                                        exit;
                                }
                        }
                        // Verificação de senha (suporta hash moderno + legado md5)
                        if(!senha_verificar((string)$_POST['login_senha'], $db['senha'] ?? '')){
                                $erro=3;
                        } else if (senha_precisa_rehash($db['senha'])) {
                                // Auto-upgrade silencioso de md5 para password_hash
                                try {
                                        $novo_hash = senha_hash((string)$_POST['login_senha']);
                                        $conexao->prepare("UPDATE usuarios SET senha=? WHERE id=?")
                                                ->execute([$novo_hash, $db['id']]);
                                } catch (Exception $e) { /* ignora */ }
                        }
                        if($erro==0){
                                // Verificar pendências de ban (penalidade ou aceite de termos)
                                try {
                                        $stmt_bchk = $conexao->prepare("SELECT ban_aceite_pendente, ban_penalty_ate FROM usuarios WHERE id = ?");
                                        $stmt_bchk->execute([$db['id']]);
                                        $ban_chk = $stmt_bchk->fetch(PDO::FETCH_ASSOC);
                                } catch (Exception $e) { $ban_chk = null; }

                                if ($ban_chk) {
                                        // Verificar penalidade ativa por negar termos
                                        if (!empty($ban_chk['ban_penalty_ate'])) {
                                                try {
                                                        $penalty_dt = new DateTime($ban_chk['ban_penalty_ate']);
                                                        if (new DateTime() < $penalty_dt) {
                                                                $_SESSION['ban_penalty_info'] = ['ate' => $ban_chk['ban_penalty_ate']];
                                                                header("Location: index.php?p=login&penalidade=1");
                                                                exit;
                                                        } else {
                                                                $conexao->prepare("UPDATE usuarios SET ban_penalty_ate = NULL WHERE id = ?")->execute([$db['id']]);
                                                        }
                                                } catch (Exception $e) {}
                                        }
                                        // Verificar se precisa aceitar termos de desbanimento
                                        if (!empty($ban_chk['ban_aceite_pendente']) && $ban_chk['ban_aceite_pendente'] == 1) {
                                                $_SESSION['unban_user_id'] = $db['id'];
                                                $_SESSION['unban_info'] = ['usuario' => $_POST['login_login']];
                                                header("Location: index.php?p=login&unban=1");
                                                exit;
                                        }
                                }

                                // VERIFICAÇÃO DE SERVIDOR
                                $servidor_selecionado_idx = isset($_POST['servidor_id']) ? (int)$_POST['servidor_id'] : -1;
                                $usuario_servidor_idx = isset($db['servidor_id']) && $db['servidor_id'] !== null ? (int)$db['servidor_id'] : null;
                                $is_adm_idx = isset($db['adm']) && in_array($db['adm'], [1, 2]);
                                if (!$is_adm_idx && $usuario_servidor_idx !== null && $servidor_selecionado_idx !== -1 && $usuario_servidor_idx !== $servidor_selecionado_idx) {
                                        header("Location: index.php?p=login&erro=7");
                                        exit;
                                }

                                // SEGURANÇA: Regenera o session ID para prevenir session fixation
                                session_regenerate_id(true);
                                rate_limit_reset('login');
                                $_SESSION['logado']=$db['id'];
                                $_SESSION['servidor_id'] = $is_adm_idx ? $servidor_selecionado_idx : $usuario_servidor_idx;
                                setcookie('logado',1,time()+900);
                                $stmt_update = $conexao->prepare("UPDATE usuarios SET loginip=?, ultimo_acesso=CURRENT_TIMESTAMP WHERE id=?");
                                $stmt_update->execute([ip2long($_SERVER['REMOTE_ADDR']), $db['id']]);
                                
                                // Gera token SSO para integração com CuteNews
                                require_once('_inc/cutenews_sso.php');
                                $is_admin_for_sso = in_array($db['adm'], [1, 2]) ? 1 : 0;
                                cutenews_set_sso_token($db['id'], $_POST['login_login'], $is_admin_for_sso);
                                if($db['avatar']==0){ 
                                        header("Location: index.php?p=first"); 
                                        exit; 
                                }
                                header("Location: index.php?p=home");
                                exit;
                        }
                }
        }
        if($erro>0){ 
                if($erro==4) $data='&date='.$c->encode(str_replace(' ',' ','_',$db['missao_fim']),$chaveuniversal); 
                else $data=''; 
                header("Location: index.php?p=login&erro=".$erro.$data);
                exit; 
        }
}

if(!isset($_COOKIE['allowgm']) && esta_em_manutencao() && !isset($_SESSION['logado'])) {
        $p = $_GET['p'] ?? 'home';
        // Durante manutenção: apenas login e página de manutenção liberados
        $paginas_livres = ['manutencao', 'login'];
        if (!in_array($p, $paginas_livres)) {
                header("Location: index.php?p=manutencao");
                exit;
        }
}
?>
<?php
if(isset($_COOKIE['logado'])){
        if(!isset($_SESSION['logado'])){ 
                setcookie('logado', '', time()-3600); 
                header("Location: index.php?p=login"); 
                exit; 
        }

        if((isset($_GET['p']))&&($_GET['p']=='view')) {
                $user = "u.usuario='".$_GET['view']."'";
        } else if((isset($_GET['p']))&&($_GET['p']=='prepare')) {
                $user = 'u.id='.$_SESSION['prepare'];
        } else {
                $user = 'u.id='.$_SESSION['logado'];
        }

        setcookie('logado', 1, time()+900);

        // Atualizar último acesso
        try {
                $stmt_acesso = $conexao->prepare("UPDATE usuarios SET ultimo_acesso=CURRENT_TIMESTAMP WHERE id=?");
                $stmt_acesso->execute([$_SESSION['logado']]);
        } catch (PDOException $e) {
                // Silently handle error
        }

        if((!isset($_GET['p'])) || (isset($_GET['p']) && $_GET['p'] != 'attack')){
                if((isset($_GET['p']))&&($_GET['p']=='view')) {
                        $stmt = $conexao->prepare("SELECT u.*, o.nome as orgnome, o.nivel as orgnivel FROM usuarios u LEFT OUTER JOIN organizacoes o ON u.orgid=o.id WHERE u.status<>'banido' AND LOWER(u.usuario)=LOWER(?)");
                        $stmt->execute([$_GET['view']]);
                } else if((isset($_GET['p']))&&($_GET['p']=='prepare')) {
                        $stmt = $conexao->prepare("SELECT u.*, o.nome as orgnome, o.nivel as orgnivel FROM usuarios u LEFT OUTER JOIN organizacoes o ON u.orgid=o.id WHERE u.status<>'banido' AND u.id=?");
                        $stmt->execute([$_SESSION['prepare']]);
                } else {
                        $stmt = $conexao->prepare("SELECT u.*, o.nome as orgnome, o.nivel as orgnivel FROM usuarios u LEFT OUTER JOIN organizacoes o ON u.orgid=o.id WHERE u.status<>'banido' AND u.id=?");
                        $stmt->execute([$_SESSION['logado']]);
                }
                $db = $stmt->fetch(PDO::FETCH_ASSOC);
                if(!$db && isset($_GET['p']) && $_GET['p']=='view'){ 
                        header("Location: index.php?p=home"); 
                        exit; 
                }
        } else {
                $stmt = $conexao->prepare("SELECT u.id, u.status, u.usuario, u.yens, u.yens_fat, u.nivel, u.orgid, u.energia, u.energiamax, u.chakra_regen_ultima, u.taijutsu, u.ninjutsu, u.genjutsu, u.personagem, u.avatar, u.renegado, u.vila, u.doujutsu, u.exp, u.expmax, u.doujutsu_nivel, u.doujutsu_exp, u.doujutsu_expmax, u.vip_inicio, u.vip, u.missao, u.hunt, u.treino, u.penalidade_fim, u.loginip, u.bandana_estilo, u.bonus_invasao_tai, u.bonus_invasao_nin, u.bonus_invasao_gen, u.bonus_invasao_pct, u.adm, u.timestamp, COALESCE(o.nivel, 0) as orgnivel FROM usuarios u LEFT OUTER JOIN organizacoes o ON u.orgid=o.id WHERE u.id=?");
                $stmt->execute([$_SESSION['logado']]);
                $db = $stmt->fetch(PDO::FETCH_ASSOC);
                if($db && $db['status']=='banido'){ 
                        header("Location: index.php?p=logout&ban=true"); 
                        exit; 
                }
                // Regeneração passiva de chakra (5% por hora)
                if($db) require_once('_inc/chakra_regen.php');
        }

        if($db && isset($_GET['p']) && $_GET['p'] != 'first' && $_GET['p'] != 'view' && $_GET['p'] != 'prepare' && $db['avatar'] == 0){ 
                header("Location: index.php?p=first"); 
                exit; 
        }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="google-site-verification" content="PHmvgGBdlvQZDyfThlBovUAoUu8y_vrcxusn6RWkX3Q" />
<title>:: <?php echo nome_servidor(); ?> - mesmo nome, nova história! ::</title>
        <link rel="stylesheet" type="text/css" href="_css/naruto.css" />
        <link rel="stylesheet" type="text/css" href="_css/banner_invasao.css" />
        <link rel="stylesheet" type="text/css" href="_css/popup_noticia.css" />
        <script src="_js/jquery-1.2.6.pack.js" type="text/javascript"></script>
        <script src="_js/jquery-modal-1.0.pack.js" type="text/javascript"></script>

        <script src="_js/banner_invasao.js" type="text/javascript"></script>
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
<script type="text/javascript" src="_js/css-supports-polyfill.js"></script>
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
<script>
function nhToggle(id) {
    var el = document.getElementById(id);
    var btn = document.getElementById('nhbtn-' + id);
    if (!el) return;
    var hidden = el.style.display === 'none';
    el.style.display = hidden ? '' : 'none';
    if (btn) btn.innerHTML = hidden ? '▲' : '▼';
    try { localStorage.setItem('nh_toggle_' + id, hidden ? '1' : '0'); } catch(e) {}
}
document.addEventListener('DOMContentLoaded', function() {
    ['bloco-inventario','bloco-equipamentos'].forEach(function(id) {
        try {
            var saved = localStorage.getItem('nh_toggle_' + id);
            if (saved === '0') {
                var el = document.getElementById(id);
                var btn = document.getElementById('nhbtn-' + id);
                if (el) el.style.display = 'none';
                if (btn) btn.innerHTML = '▼';
            }
        } catch(e) {}
    });
});
</script>
</head>

<body>
        <script type="text/javascript">
                // Tooltip initialization - must be in body
                window.addEventListener('DOMContentLoaded', function() {
                        var script = document.createElement('script');
                        script.src = '_js/wz/wz_tooltip.js';
                        script.type = 'text/javascript';
                        document.head.appendChild(script);
                });
        </script>
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
                        case 'manutencao': require_once('_inc/manutencao.php'); break;
                        case 'admin_manutencao': require_once('_inc/admin_manutencao.php'); break;
                        case 'terms': require_once('_inc/terms.php'); break;
                        case 'reg': require_once('_inc/reg.php'); break;
                        case 'reg2': require_once('_inc/reg2.php'); break;
                        case 'recover': require_once('_inc/recover.php'); break;
                        case 'home': require_once('_inc/home.php'); break;
                        case 'attack': require_once('_inc/attack.php'); break;
                        case 'missions': require_once('_inc/missions.php'); break;
                        case 'orgmissions': require_once('_inc/orgmissions.php'); break;
                        case 'rewardorgmission': require_once('_inc/rewardorgmission.php'); break;
                        case 'contact': require_once('_inc/contact.php'); break;
                        case 'messages': require_once('_inc/messages.php'); break;
                        case 'rewardmission': require_once('_inc/rewardmission.php'); break;
                        case 'rewardtrain': require_once('_inc/rewardtrain.php'); break;
                        //case 'changechar': require_once('_inc/changechar.php'); break;
                        case 'first': require_once('_inc/first.php'); break;
                        case 'jutsus': require_once('_inc/jutsus.php'); break;
                        case 'selos': require_once('_inc/selos.php'); break;
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
                        case 'rank_doujutsu': require_once('_inc/rank_doujutsu.php'); break;
                        case 'pratice': require_once('_inc/pratice.php'); break;
                        case 'doujutsu': require_once('doujutsu.php'); break;
                        case 'newdoujutsu': require_once('newdoujutsu.php'); break;
                        case 'despertar': require_once('_inc/despertar.php'); break;
                        case 'schooltrain': require_once('_inc/schooltrain.php'); break;
                        case 'treinar_doujutsu': require_once('_inc/treinar_doujutsu.php'); break;
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
                        case 'cancelhunt': require_once('_inc/cancelhunt.php'); break;
                        case 'rewardhunt': require_once('_inc/rewardhunt.php'); break;
                        case 'prepare': require_once('_inc/prepare.php'); break;
                case 'prepareIn': require_once('_inc/prepareIn.php'); break;
                        case 'shop': require_once('_inc/shop.php'); break;
                        case 'polls': require_once('_inc/polls.php'); break;
                        case 'news': require_once('_inc/news.php'); break;
                        case 'forum':
                        case 'forum_topicos':
                        case 'forum_topico':
                        case 'forum_criar_topico':
                        case 'forum_criar_postagem':
                        case 'forum_deletar_postagem':
                        case 'forum_curtir':
                        case 'forum_deletar_topico':
                        case 'forum_fixar':
                        case 'forum_fechar':
                        case 'forum_busca':
                            require_once('_inc/forum.php');
                            break;
                        case 'admin_noticias': 
                            require_once('noticia/model/NoticiaRepository.php');
                            require_once('noticia/controllers/AdminController.php');
                            
                            $repository = new NoticiaRepository($conexao);
                            $controller = new AdminNoticiasController($repository, $usuario);
                            
                            $acao = $_GET['acao'] ?? 'listar';
                            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                            $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
                            
                            switch($acao) {
                                case 'nova':
                                    $controller->exibirFormulario();
                                    break;
                                case 'editar':
                                    $controller->exibirFormulario($id);
                                    break;
                                case 'salvar':
                                    $controller->salvar();
                                    break;
                                case 'deletar':
                                    $controller->deletar();
                                    break;
                                default:
                                    $controller->listar($page);
                            }
                            break;
                        case 'reports': require_once('_inc/reports.php'); break;
                        case 'report': require_once('_inc/report.php'); break;
                        case 'delete_report': require_once('_inc/delete_report.php'); break;
                        case 'penalty': require_once('_inc/penalty.php'); break;
                        case 'events': require_once('_inc/events.php'); break;
                        case 'faq': require_once('_inc/faq.php'); break;
                        case 'manual': require_once('_inc/manual.php'); break;
                        case 'orkut': // legado — redireciona para o novo mercado
                        case 'mercado': require_once('_inc/mercado.php'); break;
                        case 'banidos': require_once('_inc/banidos.php'); break;
                        case 'invasao': require_once('_inc/invasao.php'); break;
                case 'chat': require_once('_inc/chat.php'); break;
                        case 'spam': require_once('_inc/spam.php'); break;
                        case 'discover': require_once('_inc/discover.php'); break;
                        case 'vipform': require_once('_inc/vipform.php'); break;
                        case 'myshop': require_once('_inc/myshop.php'); break;
                        case 'news2': require_once('_inc/news2.php'); break;
                        case 'pedra': require_once('_inc/pedra.php'); break;
                        case 'ads': require_once('_inc/ads.php'); break;
                        case 'changedoujutsu': require_once('_inc/changedoujutsu.php'); break;
                        case 'stats': require_once('_inc/stats.php'); break;
                        case 'book': require_once('_inc/book.php'); break;
                        case 'addbook': require_once('_inc/addbook.php'); break;
                        case 'usar_cristal_buff': require_once('_inc/usar_cristal_buff.php'); break;
                        case 'cristais_buff': require_once('_inc/cristais_buff.php'); break;
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
                        case 'floresta': require_once('_inc/floresta.php'); break;
                        case 'attackinIn': require_once('_inc/attackinIn.php'); break;
                        case 'mapa_areia':
            // Verificar se o usuário pertence à Vila da Areia ou é admin
            $stmt_check = $conexao->prepare("SELECT vila, adm FROM usuarios WHERE id = ?");
            $stmt_check->execute([$_SESSION['logado']]);
            $user_check = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if($user_check['vila'] == 2 || $user_check['adm'] == 'sim' || $user_check['adm'] == 1 || $user_check['adm'] == 2) {
                include("_inc/mapa_areia.php");
            } else {
                include("_inc/mapa.php");
            }
            break;

        case 'mapa_akatsuki':
            // Verificar se o usuário é renegado ou admin
            $stmt_check = $conexao->prepare("SELECT renegado, adm FROM usuarios WHERE id = ?");
            $stmt_check->execute([$_SESSION['logado']]);
            $user_check = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if($user_check['renegado'] == 'sim' || $user_check['adm'] == 'sim' || $user_check['adm'] == 1 || $user_check['adm'] == 2) {
                include("_inc/mapa_akatsuki.php");
            } else {
                include("_inc/mapa.php");
            }
            break;

        case 'mapa_chuva':
            // Verificar se o usuário pertence à Vila da Chuva ou é admin
            $stmt_check = $conexao->prepare("SELECT vila, adm FROM usuarios WHERE id = ?");
            $stmt_check->execute([$_SESSION['logado']]);
            $user_check = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if($user_check['vila'] == 4 || $user_check['adm'] == 'sim' || $user_check['adm'] == 1 || $user_check['adm'] == 2) {
                include("_inc/mapa_chuva.php");
            } else {
                include("_inc/mapa.php");
            }
            break;

        case 'mapa_nevoa':
            // Verificar se o usuário pertence à Vila da Névoa ou é admin
            $stmt_check = $conexao->prepare("SELECT vila, adm FROM usuarios WHERE id = ?");
            $stmt_check->execute([$_SESSION['logado']]);
            $user_check = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if($user_check['vila'] == 6 || $user_check['adm'] == 'sim' || $user_check['adm'] == 1 || $user_check['adm'] == 2) {
                include("_inc/mapa_nevoa.php");
            } else {
                include("_inc/mapa.php");
            }
            break;

        case 'mapa_nuvem':
            // Verificar se o usuário pertence à Vila da Nuvem ou é admin
            $stmt_check = $conexao->prepare("SELECT vila, adm FROM usuarios WHERE id = ?");
            $stmt_check->execute([$_SESSION['logado']]);
            $user_check = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if($user_check['vila'] == 5 || $user_check['adm'] == 'sim' || $user_check['adm'] == 1 || $user_check['adm'] == 2) {
                include("_inc/mapa_nuvem.php");
            } else {
                include("_inc/mapa.php");
            }
            break;

        case 'mapa_pedra':
            // Verificar se o usuário pertence à Vila da Pedra ou é admin
            $stmt_check = $conexao->prepare("SELECT vila, adm FROM usuarios WHERE id = ?");
            $stmt_check->execute([$_SESSION['logado']]);
            $user_check = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if($user_check['vila'] == 8 || $user_check['adm'] == 'sim' || $user_check['adm'] == 1 || $user_check['adm'] == 2) {
                include("_inc/mapa_pedra.php");
            } else {
                include("_inc/mapa.php");
            }
            break;
        case 'mapa': 
            // Redirecionar para o mapa mundial
            header("Location: index.php?p=mapa_mundial");
            exit;
            break;
        case 'mapa_mundial': require_once('_inc/mapa_mundial.php'); break;
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
          <td colspan="2" align="center" valign="middle" class="menutop">Copyright 2009 &copy; Direitos do <b>Jogo e Sistema</b> Reservados &agrave; <b><?php echo nome_servidor(); ?>.net</b><br />
      Copyright 2002 &copy; Direitos do <b>Anime e Imagens</b> Reservados à <b>Masashi Kishimoto</b></td>
    </tr>
</table>
<?php
// PDO automatically handles resource cleanup
?>

<!-- Popup de Notícias Novas -->
<?php if(isset($_SESSION['logado']) && (!isset($_GET['p']) || $_GET['p'] == 'home')): ?>
<div id="popup-noticia-overlay"></div>
<div id="popup-noticia">
    <div id="popup-noticia-content">
        <div id="popup-noticia-pergaminho">
            <img src="_img/Notícia/Pergaminho.png" alt="Pergaminho" />
        </div>
        <div id="popup-noticia-titulo">
            News
        </div>
        <button id="popup-noticia-botao">
            Ler Notícia
        </button>
    </div>
</div>
<script type="text/javascript" src="_js/popup_noticia.js"></script>
<?php endif; ?>

</div>
</body>
</html>