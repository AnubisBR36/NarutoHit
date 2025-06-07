<?php
require_once('trava.php');
$sqln=mysql_query("SELECT * FROM news ORDER BY id DESC LIMIT 5");
$dbn=mysql_fetch_assoc($sqln);
?>
<?php

$mzn_path = "mznews"; require_once($mzn_path ."/mzn2.php"); $mzn_selfpage = $s->req['PHP_SELF'];
$mzn2 = new MZn2_Noticias;

?>
<?php

$mzn2->categoria = "principal";
$mzn2->data = $s->req['mzn_data'];
$mzn2->usuario = $s->req['mzn_usuario'];
$mzn2->busca = $s->req['mzn_busca'];
$mzn2->pagina = $s->req['mzn_pg'];

$mzn2->porpagina = 5;
$mzn2->mostrar_noticias();

?>
<?php
$number=5;
include("news/show_news.php");
?>