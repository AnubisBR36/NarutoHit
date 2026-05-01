<?php
class ForumDB {
    private static $instance = null;
    private $conexao;
    
    private function __construct() {
        try {
            // O fórum compartilha o MySQL principal do jogo (mesma conexão), ou
            // usa um banco MySQL separado se 'mysql_forum' estiver configurado
            // em config/database.php. As tabelas (categorias, topicos, postagens,
            // etc.) são criadas pelo instalador a partir de forum.sql.
            require_once __DIR__ . '/../../_inc/Database.php';
            $this->conexao = Database::forumConn();
        } catch(PDOException $e) {
            die("Erro ao conectar ao banco do fórum: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->conexao;
    }
}
