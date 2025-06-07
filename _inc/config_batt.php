<?php
require_once('Encrypt.php');
$c=new C_Encrypt();

if(isset($_POST['batt'])){
	$sqlj=mysql_query("SELECT id,jutsu,status FROM jutsus WHERE usuarioid=".$db['id']);
	$dbj=mysql_fetch_assoc($sqlj);
	do{
		if(isset($_POST['jutsu'.$dbj['jutsu']])){
			if($dbj['status']=='inativo') mysql_query("UPDATE jutsus SET status='ativo' WHERE id=".$dbj['id']);
		} else {
			if($dbj['status']=='ativo') mysql_query("UPDATE jutsus SET status='inativo' WHERE id=".$dbj['id']);
		}
	} while($dbj=mysql_fetch_assoc($sqlj));
	echo "<script>self.location='?p=config&type=batt&msg=1'</script>";
}
?>
<?php
$sqlj=mysql_query("SELECT j.jutsu,j.nivel,j.status,t.nome FROM jutsus j LEFT OUTER JOIN table_jutsus t ON j.jutsu=t.id WHERE j.usuarioid=".$db['id']." ORDER BY j.status, j.nivel");
$dbj=mysql_fetch_assoc($sqlj);
?>
<fieldset><legend>Jutsus</legend>
	Selecione os jutsus que serão usados na batalha. Os jutsus serão usados em ordem aleatória. Assim que todos os jutsus marcados forem usados, será iniciado o combate corpo-a-corpo.<div class="sep"></div>
    <?php if(mysql_num_rows($sqlj)==0) echo '<div class="aviso">Você não aprendeu nenhum jutsu para utilizar em batalha.</div>'; else { ?>
    <?php
	if(isset($_GET['msg'])){
		switch($_GET['msg']){
			case 1: $msg='Configurações alteradas com sucesso!'; break;
		}
	echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>';
	}
	?>
    <form method="post" action="?p=config&amp;type=batt" onsubmit="subm.value='Carregando...';subm.disabled=true;">
    <input type="hidden" id="batt" name="batt" value="1" />
	<?php do{ ?>
    	<div><input type="checkbox" id="jutsu<?php echo $dbj['jutsu']; ?>" name="jutsu<?php echo $dbj['jutsu']; ?>"<?php if($dbj['status']=='ativo') echo ' checked="checked"'; ?> /> <?php echo $dbj['nome']; ?> - <span class="sub2">Nível <?php echo $dbj['nivel']; ?></span></div>
    <?php } while($dbj=mysql_fetch_assoc($sqlj)); ?>
    <div class="sep"></div>
    <div align="center"><input type="submit" id="subm" name="subm" class="botao" value="Salvar Alterações" /></div>
    </form>
    <?php } ?>
</fieldset>
</form>
<?php
@mysql_free_result($sqlc);
@mysql_free_result($sqlj);
?>