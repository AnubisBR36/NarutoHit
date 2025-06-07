<?php
require_once('Encrypt.php');
$c=new C_Encrypt();

if(isset($_POST['fir_avatar'])){
	if($db['config_avatar']=='sim'){ echo "<script>self.location='?p=config&type=avat&msg=2'</script>"; break; }
	$avatar=$c->decode($_POST['fir_avatar'],$chaveuniversal);
	vn($avatar);
	if(date('Y-m-d H:i:s')<$db['vip']) $config="config_avatar='nao'"; else $config="config_avatar='sim'";
	mysql_query("UPDATE usuarios SET avatar=".$avatar.", ".$config." WHERE id=".$db['id']);
	echo "<script>self.location='?p=config&type=avat&msg=1'</script>";
}
?>
<?php if(isset($_GET['msg'])){
	switch($_GET['msg']){
		case 1: $msg='Avatar alterado com sucesso!'; break;
		case 2: $msg='O avatar só pode ser trocado uma vez por dia.'; break;
	}
echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>'; } ?>
<form method="post" action="?p=config&amp;type=avat" onsubmit="fir_submit.value='Carregando...';fir_submit.disabled=true;">
<fieldset><legend>Alterar Avatar</legend>
<div align="center">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
	<td width="150" align="center" bgcolor="#444444"><img src="_img/personagens/<?php echo $db['personagem']; ?>/1.jpg" width="130" height="120" onclick="document.getElementById('fir_avatar1').checked=true" /></td>
	<td width="150" align="center" bgcolor="#444444"><img src="_img/personagens/<?php echo $db['personagem']; ?>/2.jpg" width="130" height="120"onclick="document.getElementById('fir_avatar1').checked=true" /></td>
	<td width="150" align="center" bgcolor="#444444"><img src="_img/personagens/<?php echo $db['personagem']; ?>/3.jpg" width="130" height="120"onclick="document.getElementById('fir_avatar1').checked=true" /></td>
  </tr>
  <tr>
	<td align="center"><?php if(file_exists('_img/personagens/'.$db['personagem'].'/1.jpg')){ ?><input type="radio" id="fir_avatar1" name="fir_avatar" value="<?php echo $c->encode('1',$chaveuniversal); ?>" <?php if($db['avatar']==1) echo ' checked="checked"'; ?>/><?php } ?></td>
	<td align="center"><?php if(file_exists('_img/personagens/'.$db['personagem'].'/2.jpg')){ ?><input type="radio" id="fir_avatar2" name="fir_avatar" value="<?php echo $c->encode('2',$chaveuniversal); ?>" <?php if($db['avatar']==2) echo ' checked="checked"'; ?>/><?php } ?></td>
	<td align="center"><?php if(file_exists('_img/personagens/'.$db['personagem'].'/3.jpg')){ ?><input type="radio" id="fir_avatar3" name="fir_avatar" value="<?php echo $c->encode('3',$chaveuniversal); ?>" <?php if($db['avatar']==3) echo ' checked="checked"'; ?>/><?php } ?></td>
  </tr>
  <tr>
	<td colspan="3" align="center"><div class="sep"></div></td>
	</tr>
  <tr>
	<td align="center" bgcolor="#444444"><img src="_img/personagens/<?php echo $db['personagem']; ?>/4.jpg" width="130" height="120" onclick="document.getElementById('fir_avatar1').checked=true" /></td>
	<td align="center" bgcolor="#444444"><img src="_img/personagens/<?php echo $db['personagem']; ?>/5.jpg" width="130" height="120" onclick="document.getElementById('fir_avatar1').checked=true" /></td>
	<td align="center" bgcolor="#444444"><img src="_img/personagens/<?php echo $db['personagem']; ?>/6.jpg" width="130" height="120" onclick="document.getElementById('fir_avatar1').checked=true" /></td>
  </tr>
  <tr>
	<td align="center"><?php if(file_exists('_img/personagens/'.$db['personagem'].'/4.jpg')){ ?><input type="radio" id="fir_avatar4" name="fir_avatar" value="<?php echo $c->encode('4',$chaveuniversal); ?>" <?php if($db['avatar']==4) echo ' checked="checked"'; ?>/><?php } ?></td>
	<td align="center"><?php if(file_exists('_img/personagens/'.$db['personagem'].'/5.jpg')){ ?><input type="radio" id="fir_avatar5" name="fir_avatar" value="<?php echo $c->encode('5',$chaveuniversal); ?>" <?php if($db['avatar']==5) echo ' checked="checked"'; ?>/><?php } ?></td>
	<td align="center"><?php if(file_exists('_img/personagens/'.$db['personagem'].'/6.jpg')){ ?><input type="radio" id="fir_avatar6" name="fir_avatar" value="<?php echo $c->encode('6',$chaveuniversal); ?>" <?php if($db['avatar']==6) echo ' checked="checked"'; ?>/><?php } ?></td>
  </tr>
  <tr>
	<td colspan="3" align="center"><div class="sep"></div></td>
	</tr>
  <tr>
	<td align="center" bgcolor="#444444"><img src="_img/personagens/<?php echo $db['personagem']; ?>/7.jpg" width="130" height="120" onclick="document.getElementById('fir_avatar1').checked=true" /></td>
	<td align="center" bgcolor="#444444"><img src="_img/personagens/<?php echo $db['personagem']; ?>/8.jpg" width="130" height="120" onclick="document.getElementById('fir_avatar1').checked=true" /></td>
	<td align="center" bgcolor="#444444"><img src="_img/personagens/<?php echo $db['personagem']; ?>/9.jpg" width="130" height="120" onclick="document.getElementById('fir_avatar1').checked=true" /></td>
  </tr>
  <tr>
	<td align="center"><?php if(file_exists('_img/personagens/'.$db['personagem'].'/7.jpg')){ ?><input type="radio" id="fir_avatar7" name="fir_avatar" value="<?php echo $c->encode('7',$chaveuniversal); ?>" <?php if($db['avatar']==7) echo ' checked="checked"'; ?>/><?php } ?></td>
	<td align="center"><?php if(file_exists('_img/personagens/'.$db['personagem'].'/8.jpg')){ ?><input type="radio" id="fir_avatar8" name="fir_avatar" value="<?php echo $c->encode('8',$chaveuniversal); ?>" <?php if($db['avatar']==8) echo ' checked="checked"'; ?>/><?php } ?></td>
	<td align="center"><?php if(file_exists('_img/personagens/'.$db['personagem'].'/9.jpg')){ ?><input type="radio" id="fir_avatar9" name="fir_avatar" value="<?php echo $c->encode('9',$chaveuniversal); ?>" <?php if($db['avatar']==9) echo ' checked="checked"'; ?>/><?php } ?></td>
  </tr>
</table>
<div class="sep"></div>
<input type="submit" id="fir_submit" name="fir_submit"class="botao" value="Alterar Avatar" />
</div>
</fieldset>
</form>