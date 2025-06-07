<?php
if(!isset($_GET['id'])){ 
    echo "<script>self.location='?p=school'</script>"; 
    exit(); 
}
$sqlv=mysql_query("SELECT * FROM salas WHERE id=".$_GET['id']);
$dbv=mysql_fetch_assoc($sqlv);
if(mysql_num_rows($sqlv)==0){ 
    echo "<script>self.location='?p=school'</script>"; 
    exit(); 
}
if($dbv['usuarioid']!=0 && $dbv['usuarioid']!=$db['id']){ 
    echo "<script>self.location='?p=school'</script>"; 
    exit(); 
}
$sqlr=mysql_query("SELECT usuarioid, fim FROM salas WHERE id=".$_GET['id']);
$dbr=mysql_fetch_assoc($sqlr);
if(($dbr['usuarioid']<>$db['id'])&&($atual<$dbr['fim'])){ echo "<script>self.location='?p=school'</script>"; return; }
@mysql_free_result($sqlr);
?>