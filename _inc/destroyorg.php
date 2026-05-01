<?php
$sqlv=mysql_query("SELECT liderid FROM organizacoes WHERE id=".$db['orgid']);
$dbv=mysql_fetch_assoc($sqlv);
if($dbv['liderid']<>$db['id']){ echo "<script>self.location='?p=home'</script>"; break; }
if(isset($_GET['token'])){
	$token=$_GET['token'];
	if($token<>$db['senha']){ echo "<script>self.location='?p=home'</script>"; break; }
	mysql_query("DELETE FROM membros WHERE orgid=".$db['orgid']);
	mysql_query("DELETE FROM organizacoes WHERE id=".$db['orgid']);
	if($db['missao']>=1000){
		mysql_query("UPDATE usuarios SET orgid='-1', orgmissao=0, missao=0 WHERE orgid=".$db['orgid']);
	} else {
		mysql_query("UPDATE usuarios SET orgid='-1', orgmissao=0 WHERE orgid=".$db['orgid']);
	}
	echo "<script>self.location='?p=org&msg=5'</script>";
}
?>
<div class="box_top">Destruir Clã</div>
<div class="box_middle"><div class="aviso">Tem certeza que deseja destruir este clã?<br />Esta ação não pode ser desfeita, portanto pense bem antes de confirmar.</div><div class="sep"></div>
	<div align="center"><input type="button" class="botao" value="Destruir Clã" onclick="if(confirm('Confirma ação?')==true) location.href='?p=destroyorg&token=<?php echo $db['senha']; ?>'" /></div>
</div>
<div class="box_bottom"></div>