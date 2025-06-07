<?php
if((!isset($_GET['id']))or(!isset($_GET['name']))){ echo "<script>self.location='?p=home'</script>"; break; }
$id=$c->decode($_GET['id'],$chaveuniversal);
if($id==''){ echo "<script>self.location='?p=home'</script>"; break; }
$name=$c->decode($_GET['name'],$chaveuniversal);
if($name==''){ echo "<script>self.location='?p=home'</script>"; break; }
$dbs=mysql_fetch_assoc(mysql_query("SELECT config_apresentacao FROM usuarios WHERE id=".$id));
$msg=$dbs['config_apresentacao'];
if($msg==''){ echo "<script>self.location='?p=view&view=".strtolower($name)."'</script>"; break;
mysql_query("INSERT INTO spam (usuarioid, usuario, informanteid, informante, mensagem) VALUES ($id, '$name', ".$db['id'].", '".$db['usuario']."', '$msg')");
echo "<script>self.location='?p=view&view=".strtolower($name)."&report=true'</script>";
?>