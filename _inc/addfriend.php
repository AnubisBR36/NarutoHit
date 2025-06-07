<?php
if(!isset($_GET['id'])){ echo "<script>self.location='?p=home'</script>"; break; }
$id=$_GET['id'];
$sqlf=mysql_query("SELECT id FROM usuarios WHERE usuario='".$id."'");
$dbf=mysql_fetch_assoc($sqlf);
mysql_query("INSERT INTO amigos (usuarioid,amigoid) VALUES (".$db['id'].",".$dbf['id'].")");
mysql_query("INSERT INTO mensagens (data,origem,destino,assunto,msg) VALUES ('".date('Y-m-d H:i:s')."',0,".$dbf['id'].",'".$db['usuario']." quer ser seu amigo!','O usuário <a href=?p=view&view=".strtolower($db['usuario'])." style=color:#444444;>".$db['usuario']."</a> deseja ser seu amigo.<br /><br />Para aceitar, clique <a href=?p=acceptfriend&act=accept&id=".$db['id']." style=color:#444444>aqui</a>. Para rejeitar, clique <a href=?p=acceptfriend&act=reject&id=".$db['id']." style=color:#444444;>aqui</a>.<br /><br />Lembre-se que, ao aceitar, este usuário estará apto a visualizar suas atualizações e seu Twitter (caso tenha permitido em suas configurações).')");
echo "<script>self.location='?p=friends&msg=1'</script>";
@mysql_free_result($sqlf);
?>