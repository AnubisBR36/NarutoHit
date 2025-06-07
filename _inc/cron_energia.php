<?php require_once('conexao.php'); ?>
<?php
mysql_query("UPDATE usuarios SET energia=energia+1 WHERE energia<>energiamax AND tipo='player'");
?>