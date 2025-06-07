<?php
if($db['orgid']>0){ echo "<script>self.location='?p=home'</script>"; break; }
if(!isset($_GET['id'])){ echo "<script>self.location='?p=home'</script>"; break; }
$get=$c->decode($_GET['id'],$chaveuniversal);
$ex=explode(',',$get);
$id=$ex[0];
$minimo=$ex[1];
if($db['nivel']<$minimo){ echo "<script>self.location='?p=org&msg=4'</script>"; break; }
$sqlv=mysql_query("SELECT count(id) conta FROM membros WHERE orgid=".$id." AND usuarioid=".$db['id']." AND status='nao'");
$dbv=mysql_fetch_assoc($sqlv);
if($dbv['conta']>0){ echo "<script>self.location='?p=org&msg=3'</script>"; break; }
mysql_query("INSERT INTO membros (orgid,usuarioid,posicao,rank) VALUES (".$id.",".$db['id'].",3,'Membro')");
echo "<script>self.location='?p=org&msg=2'</script>";
@mysql_free_result($sqlv);
?>