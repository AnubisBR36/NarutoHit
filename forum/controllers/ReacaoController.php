<?php
require_once 'forum/models/ReacaoModel.php';
require_once 'forum/models/PostagemModel.php';
require_once 'forum/helpers/SecurityHelper.php';

class ReacaoController {
    private $reacaoModel;
    private $postagemModel;
    
    public function __construct() {
        $this->reacaoModel = new ReacaoModel();
        $this->postagemModel = new PostagemModel();
    }
    
    public function reagir() {
        ob_clean();
        header('Content-Type: application/json');
        
        if (!ForumSecurityHelper::isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Não autorizado']);
            exit;
        }
        
        $postagem_id = (int)($_POST['postagem_id'] ?? 0);
        $tipo = $_POST['tipo'] ?? '';
        $usuario_id = ForumSecurityHelper::getUserId();
        
        if (!$postagem_id || !$tipo) {
            echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
            exit;
        }
        
        $tipos_validos = ['coracao', 'rindo', 'triste', 'bravo', 'surpreso'];
        if (!in_array($tipo, $tipos_validos)) {
            echo json_encode(['success' => false, 'message' => 'Tipo de reação inválido']);
            exit;
        }
        
        $postagem = $this->postagemModel->getPostagemById($postagem_id);
        if (!$postagem) {
            echo json_encode(['success' => false, 'message' => 'Postagem não encontrada']);
            exit;
        }
        
        $reacao_atual = $this->reacaoModel->getReacaoUsuario($postagem_id, $usuario_id);
        
        if ($reacao_atual === $tipo) {
            $this->reacaoModel->removerReacao($postagem_id, $usuario_id);
            $acao = 'removida';
        } else {
            $this->reacaoModel->reagir($postagem_id, $usuario_id, $tipo);
            $acao = 'adicionada';
        }
        
        $reacoes = $this->reacaoModel->contarReacoes($postagem_id);
        
        echo json_encode([
            'success' => true, 
            'acao' => $acao,
            'reacoes' => $reacoes
        ]);
        exit;
    }
}
