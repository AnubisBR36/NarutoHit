<?php
if(isset($_POST['conn'])){
	if(isset($_POST['conn_have'])) $twitter=$_POST['conn_twitter']; else $twitter='';
	if(isset($_POST['conn_view'])) $view='sim'; else $view='nao';
	if(isset($_POST['conn_ok'])) $ok='sim'; else $ok='nao';
	if(isset($_POST['conn_atu'])) $atu='sim'; else $atu='nao';
	$radio=$_POST['conn_radio'];
	$conexao->exec("UPDATE usuarios SET config_radio='$radio' WHERE id=".$db['id']);
	echo "<script>self.location='?p=config&type=conn&msg=1'</script>";
}
if(isset($_GET['msg'])){
	switch($_GET['msg']){
		case 1: $msg='Configurações atualizadas com sucesso!'; break;
	}
	echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>';
}
?>
<script>
function showDiv(box,id)  
{ 
 var elm = document.getElementById(id);
 elm.style.display = box.checked? "block":"none" 
}
</script>
<form method="post" action="?p=config&amp;type=conn" style="background:url(_img/config_conn.jpg) no-repeat right top;" onsubmit="subm.value='Carregando...';subm.disabled=true;">
<input type="hidden" id="conn" name="conn" value="1" />
<fieldset><legend>Twitter</legend>
    <input type="checkbox" id="conn_have" name="conn_have" <?php if($db['config_twitter']<>'') echo 'checked="true"'; ?> onclick="showDiv(this,'twitter');" /> Desejo acessar meu Twitter através do narutoHIT.<br /><span class="sub2">Marque esta opção caso deseje ativar o Twitter no narutoHIT. Para usuários com posts protegidos pelo Twitter, será requisitado seu login e senha, que em nenhum momento serão gravados por nosso site.</span>
    <div id="twitter" style="padding-left:25px;display:<?php if($db['config_twitter']<>'') echo 'block'; else echo 'none'; ?>">
    	<div class="sep"></div><span class="destaque">Meu Twitter:</span><br />http://twitter.com/ <input type="text" id="conn_twitter" name="conn_twitter" value="<?php echo $db['config_twitter']; ?>" /><br /><span class="sub2">Digite seu nome de usuário no Twitter.</span><br /><div class="sep"></div>
        <input type="checkbox" id="conn_view" name="conn_view" <?php if($db['config_viewtwitter']=='sim') echo 'checked="true"'; ?>/> Desejo visualizar o Twitter de outros jogadores.<br /><span class="sub2">Marque esta opção para conseguir visualizar o Twitter de outros jogadores.</span><br /><div class="sep"></div>
        <input type="checkbox" id="conn_ok" name="conn_ok" <?php if($db['config_oktwitter']=='sim') echo 'checked="true"'; ?>/> Autorizo todos os jogadores a verem meu Twitter.<br /><span class="sub2">Marque esta opção para permitir que outros jogadores vejam seu Twitter.</span>
    </div>
</fieldset>
<fieldset><legend>Atualizações</legend>
    <input type="checkbox" id="conn_atu" name="conn_atu" <?php if($db['config_atualizacoes']=='sim') echo 'checked="true"'; ?>/> Desejo enviar minhas atualizações aos meus amigos.<br /><span class="sub2">Marque esta opção para permitir o envio de atualizações à seus amigos.</span>
</fieldset>

<fieldset><legend>Rádio</legend>
	<span class="destaque">Rádio:</span><br />
    <select id="conn_radio" name="conn_radio">
    	<option value=""<?php if($db['config_radio']=='') echo ' selected="selected"'; ?>>-- Nenhum --</option>
        <option value="animix"<?php if($db['config_radio']=='animix') echo ' selected="selected"'; ?>>Rádio Animix</option>
        <option value="radiorox"<?php if($db['config_radio']=='radiorox') echo ' selected="selected"'; ?>>Rádio Rox</option>
        <option value="vibetrance"<?php if($db['config_radio']=='vibetrance') echo ' selected="selected"'; ?>>Rádio VibeTrance</option>
    </select>
    <br /><span class="sub2">Selecione uma rádio para ouvir enquanto joga narutoHIT! Caso não queira nenhuma, selecione a primeira opção da lista.</span>
    <div class="sep"></div>
    <div align="center"><input type="submit" id="subm" name="subm" class="botao" value="Salvar Alterações" /></div>
</fieldset>
</form>