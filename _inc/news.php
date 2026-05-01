<?php require_once('trava.php'); ?>
<?php
// Novo sistema de notícias
require_once(__DIR__ . '/conexao.php');
require_once(__DIR__ . '/../noticia/model/NoticiaRepository.php');
require_once(__DIR__ . '/../noticia/controllers/PublicController.php');

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$repository = new NoticiaRepository($conexao);
$controller = new PublicNoticiasController($repository);
$controller->renderList($page, 3);
?>