<?php
require_once 'forum/helpers/ForumDB.php';

class TopicoModel {
    private $db;
    
    public function __construct() {
        $this->db = ForumDB::getInstance();
    }
    
    public function criar($titulo, $categoria_id, $usuario_id, $conteudo_inicial) {
        try {
            $this->db->beginTransaction();
            
            // Criar tópico
            $stmt = $this->db->prepare("
                INSERT INTO topicos (titulo, categoria_id, usuario_id) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$titulo, $categoria_id, $usuario_id]);
            $topico_id = $this->db->lastInsertId();
            
            // Criar primeira postagem
            $stmt = $this->db->prepare("
                INSERT INTO postagens (topico_id, usuario_id, conteudo) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$topico_id, $usuario_id, $conteudo_inicial]);
            
            // Auto-seguir o tópico
            $stmt = $this->db->prepare("
                INSERT INTO seguir_topicos (topico_id, usuario_id) 
                VALUES (?, ?)
            ");
            $stmt->execute([$topico_id, $usuario_id]);
            
            $this->db->commit();
            return $topico_id;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function getTopicoById($id) {
        $stmt = $this->db->prepare("SELECT * FROM topicos WHERE id = ?");
        $stmt->execute([$id]);
        $topico = $stmt->fetch();
        
        if ($topico) {
            // Buscar dados do usuário do banco principal
            require_once '_inc/conexao.php';
            global $conexao;
            if ($conexao) {
                $stmt_user = $conexao->prepare("SELECT usuario, personagem, vila, renegado FROM usuarios WHERE id = ?");
                $stmt_user->execute([$topico['usuario_id']]);
                $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    $topico['autor_nome'] = $user['usuario'];
                    $topico['personagem'] = $user['personagem'];
                    $topico['vila'] = $user['vila'];
                    $topico['renegado'] = $user['renegado'];
                }
            }
        }
        
        return $topico;
    }
    
    public function getTopicosPorCategoria($categoria_id, $page = 1, $per_page = 20) {
        $offset = ($page - 1) * $per_page;
        
        $stmt = $this->db->prepare("
            SELECT t.*,
                   COUNT(p.id) as total_respostas,
                   MAX(p.criado_em) as ultima_resposta
            FROM topicos t
            LEFT JOIN postagens p ON t.id = p.topico_id
            WHERE t.categoria_id = ?
            GROUP BY t.id
            ORDER BY t.fixado DESC, t.atualizado_em DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$categoria_id, $per_page, $offset]);
        $topicos = $stmt->fetchAll();
        
        // Buscar dados dos usuários do banco principal em uma única query
        if (!empty($topicos)) {
            require_once '_inc/conexao.php';
            global $conexao;
            if ($conexao) {
                // Coletar IDs únicos
                $user_ids = array_unique(array_column($topicos, 'usuario_id'));
                if (!empty($user_ids)) {
                    $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
                    $stmt_users = $conexao->prepare("SELECT id, usuario, personagem FROM usuarios WHERE id IN ($placeholders)");
                    $stmt_users->execute($user_ids);
                    $users = [];
                    while ($user = $stmt_users->fetch(PDO::FETCH_ASSOC)) {
                        $users[$user['id']] = $user;
                    }
                    
                    // Adicionar dados aos tópicos
                    foreach ($topicos as &$topico) {
                        if (isset($users[$topico['usuario_id']])) {
                            $topico['autor_nome'] = $users[$topico['usuario_id']]['usuario'];
                            $topico['personagem'] = $users[$topico['usuario_id']]['personagem'];
                        }
                    }
                }
            }
        }
        
        return $topicos;
    }
    
    public function contarTopicosPorCategoria($categoria_id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM topicos WHERE categoria_id = ?");
        $stmt->execute([$categoria_id]);
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    public function incrementarVisualizacoes($topico_id) {
        $stmt = $this->db->prepare("UPDATE topicos SET visualizacoes = visualizacoes + 1 WHERE id = ?");
        $stmt->execute([$topico_id]);
    }
    
    public function atualizarDataModificacao($topico_id) {
        $stmt = $this->db->prepare("UPDATE topicos SET atualizado_em = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$topico_id]);
    }
    
    public function deletar($topico_id) {
        try {
            $this->db->beginTransaction();
            
            // Deletar notificações relacionadas
            $stmt = $this->db->prepare("DELETE FROM notificacoes WHERE referencia_id = ? AND tipo IN ('resposta_topico', 'curtida')");
            $stmt->execute([$topico_id]);
            
            // Deletar curtidas das postagens
            $stmt = $this->db->prepare("
                DELETE FROM curtidas WHERE postagem_id IN 
                (SELECT id FROM postagens WHERE topico_id = ?)
            ");
            $stmt->execute([$topico_id]);
            
            // Deletar postagens
            $stmt = $this->db->prepare("DELETE FROM postagens WHERE topico_id = ?");
            $stmt->execute([$topico_id]);
            
            // Deletar seguimentos
            $stmt = $this->db->prepare("DELETE FROM seguir_topicos WHERE topico_id = ?");
            $stmt->execute([$topico_id]);
            
            // Deletar tópico
            $stmt = $this->db->prepare("DELETE FROM topicos WHERE id = ?");
            $stmt->execute([$topico_id]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function fixar($topico_id, $fixar = 1) {
        $stmt = $this->db->prepare("UPDATE topicos SET fixado = ? WHERE id = ?");
        $stmt->execute([$fixar, $topico_id]);
    }
    
    public function fechar($topico_id, $fechar = 1) {
        $stmt = $this->db->prepare("UPDATE topicos SET fechado = ? WHERE id = ?");
        $stmt->execute([$fechar, $topico_id]);
    }
    
    public function buscarTopicos($termo, $page = 1, $per_page = 20) {
        $offset = ($page - 1) * $per_page;
        $termo_busca = "%{$termo}%";
        
        $stmt = $this->db->prepare("
            SELECT t.*, c.nome as categoria_nome,
                   COUNT(p.id) as total_respostas
            FROM topicos t
            JOIN categorias c ON t.categoria_id = c.id
            LEFT JOIN postagens p ON t.id = p.topico_id
            WHERE t.titulo LIKE ?
            GROUP BY t.id
            ORDER BY t.atualizado_em DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$termo_busca, $per_page, $offset]);
        $topicos = $stmt->fetchAll();
        
        // Buscar dados dos usuários do banco principal em uma única query
        if (!empty($topicos)) {
            require_once '_inc/conexao.php';
            global $conexao;
            if ($conexao) {
                // Coletar IDs únicos
                $user_ids = array_unique(array_column($topicos, 'usuario_id'));
                if (!empty($user_ids)) {
                    $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
                    $stmt_users = $conexao->prepare("SELECT id, usuario, personagem FROM usuarios WHERE id IN ($placeholders)");
                    $stmt_users->execute($user_ids);
                    $users = [];
                    while ($user = $stmt_users->fetch(PDO::FETCH_ASSOC)) {
                        $users[$user['id']] = $user;
                    }
                    
                    // Adicionar dados aos tópicos
                    foreach ($topicos as &$topico) {
                        if (isset($users[$topico['usuario_id']])) {
                            $topico['autor_nome'] = $users[$topico['usuario_id']]['usuario'];
                            $topico['personagem'] = $users[$topico['usuario_id']]['personagem'];
                        }
                    }
                }
            }
        }
        
        return $topicos;
    }
    
    public function getTopicosLidosPorUsuario($usuario_id) {
        // Cria tabela se não existir
        $this->db->exec("CREATE TABLE IF NOT EXISTS topicos_lidos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            topico_id INT NOT NULL,
            usuario_id INT NOT NULL,
            lido_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_topico_usuario (topico_id, usuario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $stmt = $this->db->prepare("SELECT topico_id FROM topicos_lidos WHERE usuario_id = ?");
        $stmt->execute([$usuario_id]);
        $lidos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $lidos;
    }
    
    public function marcarComoLido($topico_id, $usuario_id) {
        // Cria tabela se não existir
        $this->db->exec("CREATE TABLE IF NOT EXISTS topicos_lidos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            topico_id INT NOT NULL,
            usuario_id INT NOT NULL,
            lido_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_topico_usuario (topico_id, usuario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $stmt = $this->db->prepare("INSERT IGNORE INTO topicos_lidos (topico_id, usuario_id) VALUES (?, ?)");
        $stmt->execute([$topico_id, $usuario_id]);
    }
}
