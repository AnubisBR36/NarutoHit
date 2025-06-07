<?php
require_once('Encrypt.php');
$c=new C_Encrypt();

if(isset($_POST['fir_avatar'])){
	$avatar=$c->decode($_POST['fir_avatar'],$chaveuniversal);
	vn($avatar);
	mysql_query("UPDATE usuarios SET avatar=".$avatar." WHERE id=".$db['id']);
	mysql_query("INSERT INTO personagens (usuarioid) VALUES (".$db['id'].")");
	$novo=str_replace(' ','_',$db['usuario']);
	if($db['usuario']<>$novo) mysql_query("UPDATE usuarios SET usuario='".$novo."' WHERE id=".$db['id']);
	echo "<script>self.location='?p=home'</script>";
}
?>
<div class="box_top">Primeiro Login</div>
<div class="box_middle">Seja bem-vindo ao narutoHIT! Como este é seu primeiro login no jogo, queremos que você escolha um avatar para representação. Marque uma das 9 opções abaixo, e clique no botão para confirmar. Lembramos que esta função não interfere na força da sua conta. A troca de avatares pode ser feita uma vez por dia (ilimitado para jogadores VIP).<div class="sep"></div>
	<form method="post" action="?p=first" onsubmit="subm.value='Carregando...';subm.disabled=true;">
	<fieldset><legend>Avatar</legend>
    <div align="center">
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="150" align="center" bgcolor="#444444"><img src="_img/personagens/<?php echo $db['personagem']; ?>/1.jpg" width="130" height="120" onclick="document.getElementById('fir_avatar1').checked=true" /></td>
        <td width="150" align="center" bgcolor="#444444"><img src="_img/personagens/<?php echo $db['personagem']; ?>/2.jpg" width="130" height="120"onclick="document.getElementById('fir_avatar1').checked=true" /></td>
        <td width="150" align="center" bgcolor="#444444"><img src="_img/personagens/<?php echo $db['personagem']; ?>/3.jpg" width="130" height="120"onclick="document.getElementById('fir_avatar1').checked=true" /></td>
      </tr>
      <tr>
        <td align="center"><input type="radio" id="fir_avatar1" name="fir_avatar" value="<?php echo $c->encode('1',$chaveuniversal); ?>" checked="checked" /></td>
        <td align="center"><input type="radio" id="fir_avatar2" name="fir_avatar" value="<?php echo $c->encode('2',$chaveuniversal); ?>" /></td>
        <td align="center"><input type="radio" id="fir_avatar3" name="fir_avatar" value="<?php echo $c->encode('3',$chaveuniversal); ?>" /></td>
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
        <td align="center"><input type="radio" id="fir_avatar4" name="fir_avatar" value="<?php echo $c->encode('4',$chaveuniversal); ?>" /></td>
        <td align="center"><input type="radio" id="fir_avatar5" name="fir_avatar" value="<?php echo $c->encode('5',$chaveuniversal); ?>" /></td>
        <td align="center"><input type="radio" id="fir_avatar6" name="fir_avatar" value="<?php echo $c->encode('6',$chaveuniversal); ?>" /></td>
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
        <td align="center"><input type="radio" id="fir_avatar7" name="fir_avatar" value="<?php echo $c->encode('7',$chaveuniversal); ?>" /></td>
        <td align="center"><input type="radio" id="fir_avatar8" name="fir_avatar" value="<?php echo $c->encode('8',$chaveuniversal); ?>" /></td>
        <td align="center"><input type="radio" id="fir_avatar9" name="fir_avatar" value="<?php echo $c->encode('9',$chaveuniversal); ?>" /></td>
      </tr>
    </table>
    <div class="sep"></div>
    <input type="submit" id="subm" name="subm" class="botao" value="Confirmar" />
    </div>
    </fieldset>
    </form>
</div>
<div class="box_bottom"></div>