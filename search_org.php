<?php require_once('_inc/conexao.php'); ?>
<?php
$id=$_GET['id'];
echo $id;
if(isset($_GET['rank'])) $comp="rank='".$_GET['rank']."'";
if(isset($_GET['pos'])){
	switch($_GET['pos']){
		case 'Administrador': $comp='posicao=1'; break;
		case 'Moderador': $comp='posicao=2'; break;
		case 'Membro': $comp='posicao=3'; break;
	}
}
mysql_query("UPDATE membros SET ".$comp." WHERE id=$id");
?>