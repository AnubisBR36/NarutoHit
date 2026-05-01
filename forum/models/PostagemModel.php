<?php
require_once 'forum/helpers/ForumDB.php';

class PostagemModel {
    private $db;
    
    public function __construct() {
        $this->db = ForumDB::getInstance();
    }
    
    public function criar($topico_id, $usuario_id, $conteudo) {
        $stmt = $this->db->prepare("
            INSERT INTO postagens (topico_id, usuario_id, conteudo) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$topico_id, $usuario_id, $conteudo]);
        return $this->db->lastInsertId();
    }
    
    public function getPostagensPorTopico($topico_id, $page = 1, $per_page = 15) {
        $offset = ($page - 1) * $per_page;
        
        $stmt = $this->db->prepare("
            SELECT p.*,
                   COUNT(c.id) as total_curtidas
            FROM postagens p
            LEFT JOIN curtidas c ON p.id = c.postagem_id
            WHERE p.topico_id = ?
            GROUP BY p.id
            ORDER BY p.criado_em ASC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$topico_id, $per_page, $offset]);
        $postagens = $stmt->fetchAll();
        
        // Buscar dados dos usuários do banco principal em uma única query
        if (!empty($postagens)) {
            require_once '_inc/conexao.php';
            global $conexao;
            if ($conexao) {
                // Coletar IDs únicos
                $user_ids = array_unique(array_column($postagens, 'usuario_id'));
                if (!empty($user_ids)) {
                    $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
                    $stmt_users = $conexao->prepare("SELECT id, usuario, personagem, avatar, vila, renegado, reg, adm FROM usuarios WHERE id IN ($placeholders)");
                    $stmt_users->execute($user_ids);
                    $users = [];
                    while ($user = $stmt_users->fetch(PDO::FETCH_ASSOC)) {
                        $users[$user['id']] = $user;
                    }
                    
                    // Adicionar dados às postagens
                    foreach ($postagens as &$postagem) {
                        if (isset($users[$postagem['usuario_id']])) {
                            $postagem['usuario'] = $users[$postagem['usuario_id']]['usuario'];
                            $postagem['personagem'] = $users[$postagem['usuario_id']]['personagem'];
                            $postagem['avatar'] = $users[$postagem['usuario_id']]['avatar'];
                            $postagem['vila'] = $users[$postagem['usuario_id']]['vila'];
                            $postagem['renegado'] = $users[$postagem['usuario_id']]['renegado'];
                            $postagem['data_registro'] = $users[$postagem['usuario_id']]['reg'];
                            $postagem['adm'] = $users[$postagem['usuario_id']]['adm'];
                        }
                    }
                }
            }
        }
        
        return $postagens;
    }
    
    public function contarPostagensPorTopico($topico_id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM postagens WHERE topico_id = ?");
        $stmt->execute([$topico_id]);
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    public function getPostagemById($id) {
        $stmt = $this->db->prepare("SELECT * FROM postagens WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function editar($postagem_id, $conteudo) {
        $stmt = $this->db->prepare("
            UPDATE postagens 
            SET conteudo = ?, editado = 1, editado_em = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->execute([$conteudo, $postagem_id]);
    }
    
    public function deletar($postagem_id) {
        try {
            $this->db->beginTransaction();
            
            // Deletar curtidas
            $stmt = $this->db->prepare("DELETE FROM curtidas WHERE postagem_id = ?");
            $stmt->execute([$postagem_id]);
            
            // Deletar postagem
            $stmt = $this->db->prepare("DELETE FROM postagens WHERE id = ?");
            $stmt->execute([$postagem_id]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
