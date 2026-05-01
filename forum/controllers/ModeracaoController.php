<?php
require_once 'forum/models/TopicoModel.php';
require_once 'forum/models/PostagemModel.php';
require_once 'forum/helpers/SecurityHelper.php';

class ModeracaoController {
    private $topicoModel;
    private $postagemModel;
    
    public function __construct() {
        $this->topicoModel = new TopicoModel();
        $this->postagemModel = new PostagemModel();
    }
    
    private function verificarPermissao() {
        if (!ForumSecurityHelper::isLoggedIn() || !ForumSecurityHelper::isAdmin()) {
            echo "<script>alert('Você não tem permissão para acessar esta página!'); self.location='?p=forum';</script>";
            exit;
        }
    }
    
    public function deletarTopico() {
        $this->verificarPermissao();
        
        $topico_id = (int)$_GET['id'];
        $topico = $this->topicoModel->getTopicoById($topico_id);
        
        if (!$topico) {
            echo "<script>alert('Tópico não encontrado!'); history.back();</script>";
            exit;
        }
        
        $this->topicoModel->deletar($topico_id);
        echo "<script>alert('Tópico deletado com sucesso!'); self.location='?p=forum_topicos&categoria={$topico['categoria_id']}';</script>";
        exit;
    }
    
    public function fixarTopico() {
        $this->verificarPermissao();
        
        $topico_id = (int)$_GET['id'];
        $fixar = (int)$_GET['fixar'];
        
        $this->topicoModel->fixar($topico_id, $fixar);
        echo "<script>alert('Tópico atualizado!'); history.back();</script>";
        exit;
    }
    
    public function fecharTopico() {
        $this->verificarPermissao();
        
        $topico_id = (int)$_GET['id'];
        $fechar = (int)$_GET['fechar'];
        
        $this->topicoModel->fechar($topico_id, $fechar);
        echo "<script>alert('Tópico atualizado!'); history.back();</script>";
        exit;
    }
}
