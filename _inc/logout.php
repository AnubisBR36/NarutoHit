
<?php
mysql_query("UPDATE usuarios SET timestamp=0 WHERE id=".$_SESSION['logado']);
unset($_SESSION['logado']);
unset($_SESSION['errobot']);
setcookie('logado',1,time()-3600);
setcookie('session_id',1,time()-3600);
if(isset($_GET['reason'])) $reason='&reason='.$_GET['reason']; else $reason='';
echo "<script>self.location='?p=login".$reason."'</script>";
?>
