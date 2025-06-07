<?php require_once('conexao.php'); ?>
<?php
mysql_query("OPTIMIZE TABLE 'amigos' , 'atualizacoes' , 'contato', 'enquetes' , 'inventario' , 'jutsus' , 'membros' , 'mensagens' , 'organizacoes' , 'personagens' , 'ramen' , 'relatorios' , 'salas' , 'seguranca' , 'spam', 'table_itens' , 'table_jutsus' , 'table_missoes' , 'table_personagens', 'usuarios', 'vip'");
$soma=mktime(date('H')-24, date('i'), date('s'));
$missaofim=date('Y-m-d H:i:s',$soma);
mysql_query("DELETE FROM usuarios WHERE avatar=0 AND reg<='$missaofim'");
$soma=mktime(date('H')-48, date('i'), date('s'));
$missaofim=date('Y-m-d H:i:s',$soma);
mysql_query("DELETE FROM mensagens WHERE status='lido' AND data<='$missaofim'");
mysql_query("DELETE FROM relatorios WHERE status='sim' AND data<='$missaofim'");
$soma=mktime(date('H')-96, date('i'), date('s'));
$missaofim=date('Y-m-d H:i:s',$soma);
mysql_query("DELETE FROM relatorios WHERE status='nao' AND data<='$missaofim'");
$data=date('Y-m-d H:i:s');
mysql_query("DELETE FROM usuarios WHERE taijutsu=1 AND ninjutsu=1 AND genjutsu=1 AND yens>=2000 AND vitorias=0");
//mysql_query("DELETE FROM usuarios WHERE yens>=8000 AND derrotas>vitorias+50");
?>