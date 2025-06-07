<?php
require_once('_inc/conexao.php');
if(!isset($_GET['id'])){ echo "<script>self.location='index.php?p=home'</script>"; break; }
$sqlp=mysql_query("SELECT url FROM parceiros WHERE id=".$_GET['id']);
if(mysql_num_rows($sqlp)==0){ echo "<script>self.location='index.php?p=home'</script>"; break; }
$dbp=mysql_fetch_assoc($sqlp);
mysql_query("UPDATE parceiros SET cliques=cliques+1 WHERE id=".$_GET['id']);
echo "<script>self.location='".$dbp['url']."'</script>";
?>