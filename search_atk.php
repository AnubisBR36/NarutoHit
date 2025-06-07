<?php require_once('_inc/conexao.php'); ?>
<?php
$value=base64_decode($_GET['value']);
$id=base64_decode($_GET['id']);
$nome=base64_decode($_GET['name']);
@mysql_query("INSERT INTO atualizacoes (usuarioid, texto, hora) VALUES (".$id.",'<a href=?p=view&view=".strtolower($nome).">".$nome."</a> recebeu ".number_format($value,2,',','.')." yens em uma batalha.','".time(date('Y-m-d H:i:s'))."')");
echo '<b>Postado com sucesso!</b>';
?>