<?php
if(isset($_POST['char'])){
	if($db['config_personagem']=='sim'){ echo "<script>self.location='?p=config&type=char&msg=2'</script>"; break; }
	$char=$_POST['char_personagem'];
	if(($char<>'naruto')&&($char<>'sakura')&&($char<>'sasuke')&&($char<>'kakashi')){
		$sqlv=mysql_query("SELECT ".$char." FROM personagens WHERE usuarioid=".$db['id']);
		$dbv=mysql_fetch_assoc($sqlv);
		if($dbv[$char]==0){ echo "<script>self.location='?p=home'</script>"; break; }
	}
	if(date('Y-m-d H:i:s')<$db['vip']) $config="config_personagem='nao'"; else $config="config_personagem='sim'";
	mysql_query("UPDATE usuarios SET personagem='$char', avatar=1, ".$config." WHERE id=".$db['id']);
	echo "<script>self.location='?p=config&type=avat'</script>";
}
if(isset($_GET['msg'])){
	switch($_GET['msg']){
		case 1: $msg='Personagem alterado com sucesso!'; break;
		case 2: $msg='O personagem só pode ser trocado uma vez por dia.'; break;
	}
	echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>';
}
$sqlp=mysql_query("SELECT * FROM personagens WHERE usuarioid=".$db['id']);
$dbp=mysql_fetch_assoc($sqlp);
require_once('funcoes.php');
?>
<form method="post" action="?p=config&amp;type=char" onsubmit="subm.value='Carregando...';subm.disabled=true;">
<input type="hidden" id="char" name="char" value="1" />
<fieldset><legend>Alterar Personagem</legend>
	<div align="center">
	<table width="100%" cellpadding="0" cellspacing="1">
    	<tr class="table_dados">
        	<td><img src="_img/personagens/reg_naruto.jpg" onmouseover="Tip('<div class=tooltip>Uzumaki Naruto</div>')" onmouseout="UnTip()" /><br /><input type="radio" id="char_personagem1" name="char_personagem" value="naruto" <?php if($db['personagem']=='naruto') echo 'checked="checked"'; ?> /></td>
            <td><img src="_img/personagens/reg_sakura.jpg" onmouseover="Tip('<div class=tooltip>Haruno Sakura</div>')" onmouseout="UnTip()" /><br /><input type="radio" id="char_personagem2" name="char_personagem" value="sakura" <?php if($db['personagem']=='sakura') echo 'checked="checked"'; ?> /></td>
            <td><img src="_img/personagens/reg_sasuke.jpg" onmouseover="Tip('<div class=tooltip>Uchiha Sasuke</div>')" onmouseout="UnTip()" /><br /><input type="radio" id="char_personagem3" name="char_personagem" value="sasuke" <?php if($db['personagem']=='sasuke') echo 'checked="checked"'; ?> /></td>
            <td><img src="_img/personagens/reg_kakashi.jpg" onmouseover="Tip('<div class=tooltip>Hatake Kakashi</div>')" onmouseout="UnTip()" /><br /><input type="radio" id="char_personagem4" name="char_personagem" value="kakashi" <?php if($db['personagem']=='kakashi') echo 'checked="checked"'; ?> /></td>
        </tr>
        <tr>
        	<td colspan="4"><div class="sep"></div></td>
        </tr>
        <tr class="table_dados">
        <?php $x=0; $i=2; $c=1; do{ ?>
        <?php if($dbp[mysql_field_name($sqlp,$i)]==1){ $x=1; $campo=mysql_field_name($sqlp,$i); ?>
        	<td><img src="_img/personagens/<?php echo $campo; ?>/0.jpg" onmouseover="Tip('<div class=tooltip><?php fpersonagem($campo); ?></div>')" onmouseout="UnTip()" /><br /><input type="radio" id="char_personagem<?php echo $i+3; ?>" name="char_personagem" value="<?php echo $campo; ?>" <?php if($db['personagem']==mysql_field_name($sqlp,$i)) echo 'checked="checked"'; ?> /></td>
        <?php $c++; if($c==5){ $x=0; echo '</tr><tr><td colspan="4"><div class="sep"></div></td></tr><tr class="table_dados">'; $c=1; }} $i++; } while($i<mysql_num_fields($sqlp)); ?>
        </tr>
    </table>
    <?php if($x==1) echo '<div class="sep"></div>'; ?>
    <div align="center"><input type="submit" id="subm" name="subm" class="botao" value="Alterar Personagem" /></div>
    </div>
</fieldset>
</form>
<?php
@mysql_free_result($sqlp);
?>