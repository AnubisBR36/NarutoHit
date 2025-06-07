<?php require_once('../_inc/conexao.php'); ?>
<?php
function anti_sql_injection ($str) {
    if (!is_numeric($str)) {
        $str= get_magic_quotes_gpc() ? stripslashes($str) : $str;
        $str= function_exists("mysql_real_escape_string") ? mysql_real_escape_string($str) : mysql_escape_string($str);
    }
    return $str;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>:: narutoHIT ::</title>
<link href="_css/naruto.css" rel="stylesheet" type="text/css" />
</head>

<body>
<script src="../admin/_js/wz/wz_tooltip.js" type="text/javascript" language="javascript"></script> 
<div align="center">
<table align="center" cellpadding="0" cellspacing="0" width="760">
	<tr>
	  <td width="20" rowspan="6" style="background:url(_img/border_left.jpg) repeat-y right;">&nbsp;</td>
	  <td height="180" valign="top" style="background:url(_img/logo1.jpg) no-repeat center;">&nbsp;</td>
	  <td width="20" rowspan="6" style="background:url(_img/border_right.jpg) repeat-y;">&nbsp;</td>
    </tr>
	<tr>
	  <td align="center" class="menutop" style="color:#666666;"><?php require_once('top.php'); ?></td>
    </tr>
	<tr>
	  <td valign="top" style="background:url(_img/border_top.jpg) repeat-x top;">&nbsp;</td>
    </tr>
	<tr>
	  <td valign="top" bgcolor="#444444">
        <?php
	  if(!isset($_GET['p'])) require_once('home.php'); else {
		switch($_GET['p']){
			case 'spam': require_once('nh_spam.php'); break;
			case 'view': require_once('nh_viewspam.php'); break;
			case 'vip': require_once('vip.php'); break;
		}
	} ?></td>
    </tr>
    <tr>
	  <td valign="top" style="background:url(_img/border_bottom.jpg) repeat-x bottom #444444;padding-top:3px;">&nbsp;</td>
    </tr>
	<tr>
	  <td align="center" valign="middle" class="menutop">Copyright 2009 &copy; Direitos do <b>Jogo e Sistema</b> Reservados &agrave; <b>narutoHIT.net</b><br />
      Copyright 2002 &copy; Direitos do <b>Anime e Imagens</b> Reservados à <b>Masashi Kishimoto</b></td>
    </tr>
</table>
</div>
</body>
</html>