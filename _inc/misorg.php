<?php
require_once('verificar.php');
if(!isset($_GET['id'])){ echo "<script>self.location='?p=myorg'</script>"; exit; }
if(($db['orgmissao']>0)or($db['missao']>0)){ echo "<script>self.location='?p=myorg&msg=3'</script>"; exit; }
$get=$c->decode($_GET['id'],$chaveuniversal);
$ex=explode(',',$get);
$sqlm=mysql_query("SELECT * FROM table_missoes WHERE id=".$ex[0]);
if(mysql_num_rows($sqlm)==0){ echo "<script>self.location='?p=myorg&msg=2'</script>"; exit; }
$dbm=mysql_fetch_assoc($sqlm);
mysql_query("UPDATE usuarios SET orgmissao=".$dbm['id']." WHERE id=".$db['id']);
mysql_query("UPDATE table_missoes SET membros=membros+1 WHERE id=".$dbm['id']);
$dbm['membros']=$dbm['membros']+1;
if($dbm['membros']==$dbm['maximo']){
	$soma=mktime(date('H'), date('i')+($dbm['logo']*20), date('s'));
	$missaofim=date('Y-m-d H:i:s',$soma);
	mysql_query("UPDATE table_missoes SET status='andamento' WHERE id=".$dbm['id']);
	mysql_query("UPDATE usuarios SET orgmissao=0, missao=".$dbm['id'].", missao_fim='".$missaofim."' WHERE orgmissao=".$dbm['id']);
	echo "<script>self.location='?p=busymission'</script>";
}
echo "<script>self.location='?p=myorg&msg=4'</script>";
?>