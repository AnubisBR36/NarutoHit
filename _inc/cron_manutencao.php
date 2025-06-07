<?php
session_start();
$mysql_banco='narutohi_nh';
$mysql_usuario='root';
$mysql_senha='100490leander';
$mysql_host='localhost';
$conexao=mysql_connect($mysql_host,$mysql_usuario,$mysql_senha);
mysql_select_db($mysql_banco);
mysql_query("SET NAMES 'utf8'");

mysql_query("FLUSH TABLES");
mysql_query("DELETE FROM block WHERE timestamp<=".time());
?>