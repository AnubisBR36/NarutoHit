<?php
require_once 'forum/models/CategoriaModel.php';
require_once 'forum/helpers/SecurityHelper.php';

class ForumController {
    private $categoriaModel;
    
    public function __construct() {
        $this->categoriaModel = new CategoriaModel();
    }
    
    public function index() {
        if (!ForumSecurityHelper::isLoggedIn()) {
            header("Location: index.php?p=login");
            exit;
        }
        
        $categorias = $this->categoriaModel->getCategoriasComEstatisticas();
        $usuario_vila = ForumSecurityHelper::getUserVila();
        $is_admin = ForumSecurityHelper::isAdmin();
        $user_data = ForumSecurityHelper::getUserData();
        
        include 'forum/views/layouts/header.php';
        include 'forum/views/categorias/index.php';
        include 'forum/views/layouts/footer.php';
    }
    
    public function categoria($categoria_id) {
        if (!ForumSecurityHelper::isLoggedIn()) {
            header("Location: index.php?p=login");
            exit;
        }
        
        $categoria = $this->categoriaModel->getCategoriaById($categoria_id);
        if (!$categoria) {
            echo "<script>alert('Categoria não encontrada!'); history.back();</script>";
            exit;
        }
        
        $usuario_vila = ForumSecurityHelper::getUserVila();
        $is_admin = ForumSecurityHelper::isAdmin();
        
        // Verificar se pode acessar esta categoria
        if (!$is_admin && $categoria['vila_id'] != $usuario_vila) {
            echo "<script>alert('Você não tem permissão para acessar esta categoria!'); self.location='?p=forum';</script>";
            exit;
        }
        
        header("Location: index.php?p=forum_topicos&categoria=" . $categoria_id);
        exit;
    }
}
