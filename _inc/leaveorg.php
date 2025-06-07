<?php
if($db['orgid']==0){ echo "<script>self.location='?p=home'</script>"; break; }
if($db['missao']>=100){
	mysql_query("UPDATE usuarios SET orgid=0, missao=0 WHERE id=".$db['id']);
} else {
	mysql_query("UPDATE usuarios SET orgid=0 WHERE id=".$db['id']);
}
mysql_query("DELETE FROM membros WHERE usuarioid=".$db['id']);
echo "<script>self.location='?p=org&msg=1'</script>";
?>