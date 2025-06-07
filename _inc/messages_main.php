
<?php
require_once('verificar.php');

// Verificar se há mensagens não lidas
$sqlcount = mysql_query("SELECT COUNT(id) as total FROM mensagens WHERE destino=".$db['id']." AND status='naolido'");
$countrow = mysql_fetch_assoc($sqlcount);
$mensagens_nao_lidas = $countrow ? $countrow['total'] : 0;

// Verificar total de mensagens
$sqltotal = mysql_query("SELECT COUNT(id) as total FROM mensagens WHERE destino=".$db['id']);
$totalrow = mysql_fetch_assoc($sqltotal);
$total_mensagens = $totalrow ? $totalrow['total'] : 0;
?>
