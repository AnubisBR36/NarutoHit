<?php
require_once 'forum/helpers/ForumDB.php';

class ReacaoModel {
    private $db;
    
    public function __construct() {
        $this->db = ForumDB::getInstance();
    }
    
    public function reagir($postagem_id, $usuario_id, $tipo) {
        try {
            $this->db->prepare("DELETE FROM reacoes WHERE postagem_id = ? AND usuario_id = ?")->execute([$postagem_id, $usuario_id]);
            $stmt = $this->db->prepare("INSERT INTO reacoes (postagem_id, usuario_id, tipo) VALUES (?, ?, ?)");
            $stmt->execute([$postagem_id, $usuario_id, $tipo]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function removerReacao($postagem_id, $usuario_id) {
        $stmt = $this->db->prepare("DELETE FROM reacoes WHERE postagem_id = ? AND usuario_id = ?");
        $stmt->execute([$postagem_id, $usuario_id]);
        return $stmt->rowCount() > 0;
    }
    
    public function getReacaoUsuario($postagem_id, $usuario_id) {
        $stmt = $this->db->prepare("SELECT tipo FROM reacoes WHERE postagem_id = ? AND usuario_id = ?");
        $stmt->execute([$postagem_id, $usuario_id]);
        $result = $stmt->fetch();
        return $result ? $result['tipo'] : null;
    }
    
    public function contarReacoes($postagem_id) {
        $stmt = $this->db->prepare("SELECT tipo, COUNT(*) as total FROM reacoes WHERE postagem_id = ? GROUP BY tipo");
        $stmt->execute([$postagem_id]);
        $reacoes = [];
        while ($row = $stmt->fetch()) {
            $reacoes[$row['tipo']] = $row['total'];
        }
        return $reacoes;
    }
}
