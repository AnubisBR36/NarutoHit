<?php
if(!isset($_GET['act'])){ echo "<script>self.location='?p=home'</script>"; break; }
if(!isset($_GET['id'])){ echo "<script>self.location='?p=home'</script>"; break; }
$sqlf=mysql_query("SELECT id,amigoid FROM amigos WHERE usuarioid=".$_GET['id']." AND amigoid=".$db['id']." AND status='nao'");
$dbf=mysql_fetch_assoc($sqlf);
if(mysql_num_rows($sqlf)==0){ echo "<script>self.location='?p=home'</script>"; break; }
if($db['id']<>$dbf['amigoid']){ echo "<script>self.location='?p=home'</script>"; break; }
if($_GET['act']=='accept'){
	mysql_query("UPDATE amigos SET status='sim' WHERE id=".$dbf['id']);
	mysql_query("INSERT INTO mensagens (data,origem,destino,assunto,msg) VALUES ('".date('Y-m-d H:i:s')."',0,".$_GET['id'].",'Resposta de ".$db['usuario']."','O usuário <a href=?p=view&view=".strtolower($db['usuario'])." style=color:#444444;>".$db['usuario']."</a> aceitou sua proposta de amizade. A partir de agora, ele estará em sua lista de amigos, e você poderá visualizar as atualizações deste usuário, caso tenha permissão.')");
	echo "<script>self.location='?p=friends&msg=2'</script>";
} else {
	mysql_query("DELETE FROM amigos WHERE id=".$dbf['id']);
	mysql_query("INSERT INTO mensagens (data,origem,destino,assunto,msg) VALUES ('".date('Y-m-d H:i:s')."',0,".$_GET['id'].",'Resposta de ".$db['usuario']."','O usuário <a href=?p=view&view=".strtolower($db['usuario'])." style=color:#444444;>".$db['usuario']."</a> rejeitou sua proposta de amizade.')");
	echo "<script>self.location='?p=friends&msg=3'</script>";
}
@mysql_free_result($sqlf);
?>