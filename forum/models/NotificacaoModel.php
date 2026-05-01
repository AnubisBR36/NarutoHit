<?php
require_once 'forum/helpers/ForumDB.php';

class NotificacaoModel {
    private $db;
    
    public function __construct() {
        $this->db = ForumDB::getInstance();
    }
    
    public function criar($usuario_id, $tipo, $referencia_id, $mensagem) {
        $stmt = $this->db->prepare("
            INSERT INTO notificacoes (usuario_id, tipo, referencia_id, mensagem) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$usuario_id, $tipo, $referencia_id, $mensagem]);
        return $this->db->lastInsertId();
    }
    
    public function getNotificacoesUsuario($usuario_id, $apenas_nao_lidas = false, $limit = 20) {
        $sql = "SELECT * FROM notificacoes WHERE usuario_id = ?";
        if ($apenas_nao_lidas) {
            $sql .= " AND lida = 0";
        }
        $sql .= " ORDER BY criado_em DESC LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$usuario_id, $limit]);
        return $stmt->fetchAll();
    }
    
    public function contarNaoLidas($usuario_id) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM notificacoes 
            WHERE usuario_id = ? AND lida = 0
        ");
        $stmt->execute([$usuario_id]);
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    public function marcarComoLida($notificacao_id) {
        $stmt = $this->db->prepare("UPDATE notificacoes SET lida = 1 WHERE id = ?");
        $stmt->execute([$notificacao_id]);
    }
    
    public function marcarTodasComoLidas($usuario_id) {
        $stmt = $this->db->prepare("UPDATE notificacoes SET lida = 1 WHERE usuario_id = ?");
        $stmt->execute([$usuario_id]);
    }
    
    public function notificarRespostaTopico($topico_id, $autor_resposta_id) {
        // Buscar o criador do tópico e seguidores
        $stmt = $this->db->prepare("
            SELECT DISTINCT s.usuario_id, t.usuario_id as criador_id
            FROM topicos t
            LEFT JOIN seguir_topicos s ON t.id = s.topico_id
            WHERE t.id = ?
        ");
        $stmt->execute([$topico_id]);
        $usuarios = $stmt->fetchAll();
        
        foreach ($usuarios as $user) {
            // Não notificar quem fez a resposta
            if ($user['usuario_id'] == $autor_resposta_id) continue;
            if ($user['criador_id'] == $autor_resposta_id) continue;
            
            $usuario_notificar = $user['usuario_id'] ?: $user['criador_id'];
            $this->criar(
                $usuario_notificar,
                'resposta_topico',
                $topico_id,
                'Nova resposta em um tópico que você segue'
            );
        }
    }
    
    public function notificarCurtida($postagem_id, $usuario_que_curtiu_id) {
        // Buscar autor da postagem
        $stmt = $this->db->prepare("SELECT usuario_id FROM postagens WHERE id = ?");
        $stmt->execute([$postagem_id]);
        $postagem = $stmt->fetch();
        
        if ($postagem && $postagem['usuario_id'] != $usuario_que_curtiu_id) {
            $this->criar(
                $postagem['usuario_id'],
                'curtida',
                $postagem_id,
                'Alguém curtiu sua postagem'
            );
        }
    }
}
