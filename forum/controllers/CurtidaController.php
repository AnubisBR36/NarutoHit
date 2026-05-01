<?php
require_once 'forum/models/CurtidaModel.php';
require_once 'forum/models/PostagemModel.php';
require_once 'forum/models/NotificacaoModel.php';
require_once 'forum/helpers/SecurityHelper.php';

class CurtidaController {
    private $curtidaModel;
    private $postagemModel;
    private $notificacaoModel;
    
    public function __construct() {
        $this->curtidaModel = new CurtidaModel();
        $this->postagemModel = new PostagemModel();
        $this->notificacaoModel = new NotificacaoModel();
    }
    
    public function curtir() {
        if (!ForumSecurityHelper::isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Não autorizado']);
            exit;
        }
        
        $postagem_id = (int)$_POST['postagem_id'];
        $usuario_id = ForumSecurityHelper::getUserId();
        
        $postagem = $this->postagemModel->getPostagemById($postagem_id);
        if (!$postagem) {
            echo json_encode(['success' => false, 'message' => 'Postagem não encontrada']);
            exit;
        }
        
        // Não pode curtir a própria postagem
        if ($postagem['usuario_id'] == $usuario_id) {
            echo json_encode(['success' => false, 'message' => 'Você não pode curtir sua própria postagem']);
            exit;
        }
        
        // Verificar se já curtiu
        if ($this->curtidaModel->usuarioCurtiu($postagem_id, $usuario_id)) {
            // Descurtir
            $this->curtidaModel->descurtir($postagem_id, $usuario_id);
            $total = $this->curtidaModel->contarCurtidas($postagem_id);
            echo json_encode(['success' => true, 'curtiu' => false, 'total' => $total]);
        } else {
            // Curtir
            $this->curtidaModel->curtir($postagem_id, $usuario_id);
            $total = $this->curtidaModel->contarCurtidas($postagem_id);
            
            // Notificar autor
            $this->notificacaoModel->notificarCurtida($postagem_id, $usuario_id);
            
            echo json_encode(['success' => true, 'curtiu' => true, 'total' => $total]);
        }
        exit;
    }
}
