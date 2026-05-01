<?php
require_once 'forum/models/TopicoModel.php';
require_once 'forum/helpers/SecurityHelper.php';

class BuscaController {
    private $topicoModel;
    
    public function __construct() {
        $this->topicoModel = new TopicoModel();
    }
    
    public function buscar() {
        if (!ForumSecurityHelper::isLoggedIn()) {
            header("Location: index.php?p=login");
            exit;
        }
        
        $termo = isset($_GET['q']) ? trim($_GET['q']) : '';
        $topicos = [];
        $total_paginas = 0;
        
        if (!empty($termo)) {
            $page = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
            $topicos = $this->topicoModel->buscarTopicos($termo, $page);
            // Simplified - in real app, count total results
            $total_paginas = 1;
        }
        
        include 'forum/views/layouts/header.php';
        include 'forum/views/busca/resultados.php';
        include 'forum/views/layouts/footer.php';
    }
}
