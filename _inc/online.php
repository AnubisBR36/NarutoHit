<?php
  require_once('conexao.php');
  $timestamp=time(); 
  $timeout=time()-900; // valor em segundos
  if($db['timestamp']<$timeout) {
    try {
      $stmt = $conexao->prepare("UPDATE usuarios SET timestamp=? WHERE id=?");
      $stmt->execute([$timestamp, $db['id']]);
    } catch (PDOException $e) {
      // Handle error silently for now
    }
  }
  
  // Sempre atualizar o timestamp para manter o usuário como online
  try {
    $stmt = $conexao->prepare("UPDATE usuarios SET timestamp=? WHERE id=?");
    $stmt->execute([$timestamp, $db['id']]);
  } catch (PDOException $e) {
    // Handle error silently for now
  }
  //$result=mysql_db_query($db_bdad, "DELETE FROM useronline WHERE timestamp<$timeout"); 
  //$result=mysql_db_query($db_bdad, "SELECT DISTINCT ip FROM useronline") or die(mysql_error()); 
  //mysql_close(); 
?> 
