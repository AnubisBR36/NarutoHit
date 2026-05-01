<?php
require_once 'forum/helpers/ForumDB.php';

class CurtidaModel {
    private $db;
    
    public function __construct() {
        $this->db = ForumDB::getInstance();
    }
    
    public function curtir($postagem_id, $usuario_id) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO curtidas (postagem_id, usuario_id) 
                VALUES (?, ?)
            ");
            $stmt->execute([$postagem_id, $usuario_id]);
            return true;
        } catch (PDOException $e) {
            // Já curtiu
            return false;
        }
    }
    
    public function descurtir($postagem_id, $usuario_id) {
        $stmt = $this->db->prepare("
            DELETE FROM curtidas 
            WHERE postagem_id = ? AND usuario_id = ?
        ");
        $stmt->execute([$postagem_id, $usuario_id]);
        return $stmt->rowCount() > 0;
    }
    
    public function usuarioCurtiu($postagem_id, $usuario_id) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as curtiu 
            FROM curtidas 
            WHERE postagem_id = ? AND usuario_id = ?
        ");
        $stmt->execute([$postagem_id, $usuario_id]);
        $result = $stmt->fetch();
        return $result['curtiu'] > 0;
    }
    
    public function contarCurtidas($postagem_id) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM curtidas 
            WHERE postagem_id = ?
        ");
        $stmt->execute([$postagem_id]);
        $result = $stmt->fetch();
        return $result['total'];
    }
}
