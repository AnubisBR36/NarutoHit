<?php
  require_once('conexao.php');
  $timestamp=time(); 
  $timeout=time()-900; // valor em segundos
  if($db['timestamp']<$timeout) mysql_query("UPDATE usuarios SET timestamp='$timestamp' WHERE id=".$db['id']);
  //$result=mysql_db_query($db_bdad, "DELETE FROM useronline WHERE timestamp<$timeout"); 
  //$result=mysql_db_query($db_bdad, "SELECT DISTINCT ip FROM useronline") or die(mysql_error()); 
  //mysql_close(); 
?> 
