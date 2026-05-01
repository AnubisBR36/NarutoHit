<?php
require_once 'forum/models/TopicoModel.php';
require_once 'forum/models/CategoriaModel.php';
require_once 'forum/models/PostagemModel.php';
require_once 'forum/helpers/SecurityHelper.php';

class TopicoController {
    private $topicoModel;
    private $categoriaModel;
    private $postagemModel;
    
    public function __construct() {
        $this->topicoModel = new TopicoModel();
        $this->categoriaModel = new CategoriaModel();
        $this->postagemModel = new PostagemModel();
    }
    
    public function listar($categoria_id) {
        if (!ForumSecurityHelper::isLoggedIn()) {
            header("Location: index.php?p=login");
            exit;
        }
        
        $categoria = $this->categoriaModel->getCategoriaById($categoria_id);
        if (!$categoria) {
            echo "<script>alert('Categoria não encontrada!'); self.location='?p=forum';</script>";
            exit;
        }
        
        $usuario_vila = ForumSecurityHelper::getUserVila();
        $is_admin = ForumSecurityHelper::isAdmin();
        $user_data = ForumSecurityHelper::getUserData();
        $usuario_id = ForumSecurityHelper::getUserId();
        
        // Verificar permissão - Vila Neutra (vila_id = 0) é acessível para todos
        if (!$is_admin && $categoria['vila_id'] != 0 && $categoria['vila_id'] != $usuario_vila) {
            echo "<script>alert('Você não tem permissão para acessar esta categoria!'); self.location='?p=forum';</script>";
            exit;
        }
        
        $page = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $topicos = $this->topicoModel->getTopicosPorCategoria($categoria_id, $page);
        $total_topicos = $this->topicoModel->contarTopicosPorCategoria($categoria_id);
        $total_paginas = ceil($total_topicos / 20);
        
        // Buscar tópicos lidos pelo usuário
        $topicos_lidos = $this->topicoModel->getTopicosLidosPorUsuario($usuario_id);
        
        include 'forum/views/layouts/header.php';
        include 'forum/views/topicos/listar.php';
        include 'forum/views/layouts/footer.php';
    }
    
    public function criar() {
        if (!ForumSecurityHelper::isLoggedIn()) {
            header("Location: index.php?p=login");
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $categoria_id = (int)$_POST['categoria_id'];
            $titulo = ForumSecurityHelper::sanitize($_POST['titulo']);
            $conteudo = ForumSecurityHelper::sanitize($_POST['conteudo']);
            $usuario_id = ForumSecurityHelper::getUserId();
            
            // Validar
            if (empty($titulo) || empty($conteudo)) {
                echo "<script>alert('Preencha todos os campos!'); history.back();</script>";
                exit;
            }
            
            // Verificar se a categoria existe e verificar permissão
            $categoria = $this->categoriaModel->getCategoriaById($categoria_id);
            if (!$categoria) {
                echo "<script>alert('Categoria inválida!'); self.location='?p=forum';</script>";
                exit;
            }
            
            $usuario_vila = ForumSecurityHelper::getUserVila();
            $is_admin = ForumSecurityHelper::isAdmin();
            
            if (!$is_admin && $categoria['vila_id'] != 0 && $categoria['vila_id'] != $usuario_vila) {
                echo "<script>alert('Você não tem permissão para criar tópicos nesta categoria!'); self.location='?p=forum';</script>";
                exit;
            }
            
            try {
                $topico_id = $this->topicoModel->criar($titulo, $categoria_id, $usuario_id, $conteudo);
                echo "<script>alert('Tópico criado com sucesso!'); self.location='?p=forum_topico&id={$topico_id}';</script>";
            } catch (Exception $e) {
                echo "<script>alert('Erro ao criar tópico!'); history.back();</script>";
            }
            exit;
        }
        
        // Formulário de criação
        $categoria_id = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
        $categoria = $this->categoriaModel->getCategoriaById($categoria_id);
        
        if (!$categoria) {
            echo "<script>alert('Categoria inválida!'); self.location='?p=forum';</script>";
            exit;
        }
        
        // Verificar permissão
        $usuario_vila = ForumSecurityHelper::getUserVila();
        $is_admin = ForumSecurityHelper::isAdmin();
        
        if (!$is_admin && $categoria['vila_id'] != 0 && $categoria['vila_id'] != $usuario_vila) {
            echo "<script>alert('Você não tem permissão para criar tópicos nesta categoria!'); self.location='?p=forum';</script>";
            exit;
        }
        
        include 'forum/views/layouts/header.php';
        include 'forum/views/topicos/criar.php';
        include 'forum/views/layouts/footer.php';
    }
    
    public function visualizar($topico_id) {
        if (!ForumSecurityHelper::isLoggedIn()) {
            header("Location: index.php?p=login");
            exit;
        }
        
        $topico = $this->topicoModel->getTopicoById($topico_id);
        if (!$topico) {
            echo "<script>alert('Tópico não encontrado!'); self.location='?p=forum';</script>";
            exit;
        }
        
        // Verificar permissão
        $categoria = $this->categoriaModel->getCategoriaById($topico['categoria_id']);
        $usuario_vila = ForumSecurityHelper::getUserVila();
        $is_admin = ForumSecurityHelper::isAdmin();
        $user_data = ForumSecurityHelper::getUserData();
        $usuario_id = ForumSecurityHelper::getUserId();
        
        if (!$is_admin && $categoria['vila_id'] != 0 && $categoria['vila_id'] != $usuario_vila) {
            echo "<script>alert('Você não tem permissão para ver este tópico!'); self.location='?p=forum';</script>";
            exit;
        }
        
        // Incrementar visualizações
        $this->topicoModel->incrementarVisualizacoes($topico_id);
        
        // Marcar tópico como lido
        $this->topicoModel->marcarComoLido($topico_id, $usuario_id);
        
        // Buscar postagens
        $page = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $postagens = $this->postagemModel->getPostagensPorTopico($topico_id, $page);
        $total_postagens = $this->postagemModel->contarPostagensPorTopico($topico_id);
        $total_paginas = ceil($total_postagens / 15);
        
        // Verificar curtidas e reações do usuário
        require_once 'forum/models/CurtidaModel.php';
        require_once 'forum/models/ReacaoModel.php';
        $curtidaModel = new CurtidaModel();
        $reacaoModel = new ReacaoModel();
        
        include 'forum/views/layouts/header.php';
        include 'forum/views/topicos/visualizar.php';
        include 'forum/views/layouts/footer.php';
    }
}
