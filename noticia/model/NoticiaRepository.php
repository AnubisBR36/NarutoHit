<?php
// Repositório para gerenciar notícias no banco de dados
class NoticiaRepository {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Criar nova notícia
    public function create($titulo, $conteudo, $autor, $data_expiracao = null, $usar_cores = 1) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO noticias (titulo, conteudo, autor, data_criacao, data_expiracao, usar_cores) 
                VALUES (?, ?, ?, CURRENT_TIMESTAMP, ?, ?)
            ");
            return $stmt->execute([$titulo, $conteudo, $autor, $data_expiracao, $usar_cores]);
        } catch (PDOException $e) {
            error_log("Erro ao criar notícia: " . $e->getMessage());
            return false;
        }
    }
    
    // Buscar notícia por ID
    public function findById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM noticias WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar notícia: " . $e->getMessage());
            return null;
        }
    }
    
    // Atualizar notícia
    public function update($id, $titulo, $conteudo, $data_expiracao = null, $usar_cores = 1) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE noticias 
                SET titulo = ?, conteudo = ?, data_atualizacao = CURRENT_TIMESTAMP, data_expiracao = ?, usar_cores = ?
                WHERE id = ?
            ");
            return $stmt->execute([$titulo, $conteudo, $data_expiracao, $usar_cores, $id]);
        } catch (PDOException $e) {
            error_log("Erro ao atualizar notícia: " . $e->getMessage());
            return false;
        }
    }
    
    // Deletar notícia
    public function delete($id) {
        try {
            $this->pdo->beginTransaction();
            
            $stmt = $this->pdo->prepare("DELETE FROM noticia_lida WHERE noticia_id = ?");
            $stmt->execute([$id]);
            
            $stmt = $this->pdo->prepare("DELETE FROM noticias WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Erro ao deletar notícia: " . $e->getMessage());
            return false;
        }
    }
    
    // Buscar todas as notícias com paginação (admin - inclui expiradas)
    public function fetchPage($limit = 10, $offset = 0) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM noticias 
                ORDER BY data_criacao DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar notícias: " . $e->getMessage());
            return [];
        }
    }
    
    // Buscar notícias ativas (não expiradas) para usuários
    public function fetchActiveNews($limit = 10, $offset = 0) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM noticias 
                WHERE data_expiracao IS NULL OR data_expiracao > CURRENT_TIMESTAMP
                ORDER BY data_criacao DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar notícias ativas: " . $e->getMessage());
            return [];
        }
    }
    
    // Contar total de notícias
    public function count() {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM noticias");
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erro ao contar notícias: " . $e->getMessage());
            return 0;
        }
    }
}
