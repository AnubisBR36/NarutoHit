<?php require_once('conexao.php'); ?>
<?php
$q=$_GET['id'];
$sqlm=mysql_query("SELECT count(id) conta FROM mensagens WHERE destino=".$q." AND status='naolido'");
$dbm=mysql_fetch_assoc($sqlm);
if($dbm['conta']>0){
echo '<div class="action"><span class="sub2"><a href="?p=messages">'.$dbm['conta'].' nova';
if($dbm['conta']>1) echo 's';
echo ' mensage';
if($dbm['conta']>1) echo 'ns'; else echo 'm';
echo '!</a></span></div>';
}
$sqlr=mysql_query("SELECT count(id) conta FROM relatorios WHERE inimigoid=".$q." AND status='nao'");
$dbr=mysql_fetch_assoc($sqlr);
if($dbr['conta']>0){
echo '
<div class="action"><span class="sub2"><a href="?p=reports">Você foi atacado '.$dbr['conta'].' vez';
if($dbr['conta']>1) echo 'es';
echo '!</a></span></div>';
}
mysql_free_result($sqlm);
mysql_free_result($sqlr);
?>