<?php
require_once 'forum/models/PostagemModel.php';
require_once 'forum/models/TopicoModel.php';
require_once 'forum/models/NotificacaoModel.php';
require_once 'forum/helpers/SecurityHelper.php';

class PostagemController {
    private $postagemModel;
    private $topicoModel;
    private $notificacaoModel;
    
    public function __construct() {
        $this->postagemModel = new PostagemModel();
        $this->topicoModel = new TopicoModel();
        $this->notificacaoModel = new NotificacaoModel();
    }
    
    public function criar() {
        if (!ForumSecurityHelper::isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Não autorizado']);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $topico_id = (int)$_POST['topico_id'];
            $conteudo = ForumSecurityHelper::sanitize($_POST['conteudo']);
            $usuario_id = ForumSecurityHelper::getUserId();
            
            if (empty($conteudo)) {
                echo "<script>alert('O conteúdo não pode estar vazio!'); history.back();</script>";
                exit;
            }
            
            // Verificar se o tópico existe
            $topico = $this->topicoModel->getTopicoById($topico_id);
            if (!$topico) {
                echo "<script>alert('Tópico não encontrado!'); self.location='?p=forum';</script>";
                exit;
            }
            
            // Verificar se o tópico está fechado
            if ($topico['fechado'] == 1 && !ForumSecurityHelper::isAdmin()) {
                echo "<script>alert('Este tópico está fechado!'); history.back();</script>";
                exit;
            }
            
            // Verificar permissão da categoria do tópico
            require_once 'forum/models/CategoriaModel.php';
            $categoriaModel = new CategoriaModel();
            $categoria = $categoriaModel->getCategoriaById($topico['categoria_id']);
            
            $usuario_vila = ForumSecurityHelper::getUserVila();
            $is_admin = ForumSecurityHelper::isAdmin();
            
            if (!$is_admin && $categoria && $categoria['vila_id'] != $usuario_vila) {
                echo "<script>alert('Você não tem permissão para postar neste tópico!'); self.location='?p=forum';</script>";
                exit;
            }
            
            try {
                $postagem_id = $this->postagemModel->criar($topico_id, $usuario_id, $conteudo);
                
                // Atualizar data do tópico
                $this->topicoModel->atualizarDataModificacao($topico_id);
                
                // Notificar seguidores
                $this->notificacaoModel->notificarRespostaTopico($topico_id, $usuario_id);
                
                echo "<script>alert('Resposta enviada com sucesso!'); self.location='?p=forum_topico&id={$topico_id}';</script>";
            } catch (Exception $e) {
                echo "<script>alert('Erro ao enviar resposta!'); history.back();</script>";
            }
            exit;
        }
    }
    
    public function editar() {
        if (!ForumSecurityHelper::isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Não autorizado']);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postagem_id = (int)$_POST['postagem_id'];
            $conteudo = ForumSecurityHelper::sanitize($_POST['conteudo']);
            $usuario_id = ForumSecurityHelper::getUserId();
            
            // Verificar se é o autor ou admin
            $postagem = $this->postagemModel->getPostagemById($postagem_id);
            if ($postagem['usuario_id'] != $usuario_id && !ForumSecurityHelper::isAdmin()) {
                echo "<script>alert('Você não tem permissão para editar esta postagem!'); history.back();</script>";
                exit;
            }
            
            $this->postagemModel->editar($postagem_id, $conteudo);
            echo "<script>alert('Postagem editada com sucesso!'); self.location='?p=forum_topico&id={$postagem['topico_id']}';</script>";
            exit;
        }
    }
    
    public function deletar() {
        if (!ForumSecurityHelper::isLoggedIn()) {
            header("Location: index.php?p=login");
            exit;
        }
        
        $postagem_id = (int)$_GET['id'];
        $usuario_id = ForumSecurityHelper::getUserId();
        
        $postagem = $this->postagemModel->getPostagemById($postagem_id);
        if (!$postagem) {
            echo "<script>alert('Postagem não encontrada!'); history.back();</script>";
            exit;
        }
        
        // Verificar se é o autor ou admin
        if ($postagem['usuario_id'] != $usuario_id && !ForumSecurityHelper::isAdmin()) {
            echo "<script>alert('Você não tem permissão para deletar esta postagem!'); history.back();</script>";
            exit;
        }
        
        // Não permitir deletar a primeira postagem (criadora do tópico)
        $todas_postagens = $this->postagemModel->getPostagensPorTopico($postagem['topico_id'], 1, 1);
        if ($todas_postagens[0]['id'] == $postagem_id) {
            echo "<script>alert('A primeira postagem do tópico não pode ser deletada! Delete o tópico inteiro.'); history.back();</script>";
            exit;
        }
        
        $this->postagemModel->deletar($postagem_id);
        echo "<script>alert('Postagem deletada com sucesso!'); self.location='?p=forum_topico&id={$postagem['topico_id']}';</script>";
        exit;
    }
}
