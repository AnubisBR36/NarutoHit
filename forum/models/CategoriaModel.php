<?php
require_once 'forum/helpers/ForumDB.php';

class CategoriaModel {
    private $db;
    
    public function __construct() {
        $this->db = ForumDB::getInstance();
    }
    
    public function getAllCategorias() {
        $stmt = $this->db->query("SELECT * FROM categorias ORDER BY ordem ASC");
        return $stmt->fetchAll();
    }
    
    public function getCategoriaById($id) {
        $stmt = $this->db->prepare("SELECT * FROM categorias WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getCategoriaByVilaId($vila_id) {
        $stmt = $this->db->prepare("SELECT * FROM categorias WHERE vila_id = ?");
        $stmt->execute([$vila_id]);
        return $stmt->fetch();
    }
    
    public function getCategoriasComEstatisticas() {
        $sql = "SELECT c.*, 
                       COUNT(DISTINCT t.id) as total_topicos,
                       COUNT(p.id) as total_postagens,
                       MAX(p.criado_em) as ultima_atividade
                FROM categorias c
                LEFT JOIN topicos t ON c.id = t.categoria_id
                LEFT JOIN postagens p ON t.id = p.topico_id
                GROUP BY c.id
                ORDER BY c.ordem ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}
