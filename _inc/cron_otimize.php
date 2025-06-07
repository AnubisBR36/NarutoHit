<?php require_once('conexao.php'); ?>
<?php
mysql_query("OPTIMIZE TABLE 'amigos' , 'atualizacoes' , 'cbt' , 'configuracoes' , 'enquetes' , 'inventario' , 'jutsus' , 'membros' , 'mensagens' , 'news' , 'organizacoes' , 'personagens' , 'ramen' , 'relatorios' , 'salas' , 'seguranca' , 'table_itens' , 'table_jutsus' , 'table_missoes' , 'usuarios'");
if(date('Y-m-d H:i:s')>='2010-03-28 11:59:00')
mysql_query("UPDATE usuarios SET hunt_restantes=8, config_personagem='nao', config_avatar='nao'");
mysql_query("UPDATE usuarios SET hunt_restantes=hunt_restantes+6 WHERE vip>='".date('Y-m-d H:i:s')."'");
if(date('Y-m-d')<='2010-04-04')
	mysql_query("UPDATE usuarios SET hunt_restantes=hunt_restantes+6");
mysql_query("UPDATE book SET hoje=0");
?>