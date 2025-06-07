
<?php
require_once('_inc/conexao.php');

try {
    // Criar tabela de páginas do mapa se não existir
    $sql_pages = "CREATE TABLE IF NOT EXISTS maps_pages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(100) NOT NULL,
        grid_data TEXT,
        north_page_id INTEGER NULL,
        south_page_id INTEGER NULL,
        east_page_id INTEGER NULL,
        west_page_id INTEGER NULL,
        FOREIGN KEY (north_page_id) REFERENCES maps_pages(id),
        FOREIGN KEY (south_page_id) REFERENCES maps_pages(id),
        FOREIGN KEY (east_page_id) REFERENCES maps_pages(id),
        FOREIGN KEY (west_page_id) REFERENCES maps_pages(id)
    )";

    // Criar tabela de posições dos jogadores se não existir
    $sql_positions = "CREATE TABLE IF NOT EXISTS players_positions (
        player_id INTEGER PRIMARY KEY,
        current_page_id INTEGER NOT NULL DEFAULT 1,
        x INTEGER NOT NULL DEFAULT 10,
        y INTEGER NOT NULL DEFAULT 10,
        last_move_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (player_id) REFERENCES usuarios(id),
        FOREIGN KEY (current_page_id) REFERENCES maps_pages(id)
    )";

    $conexao->exec($sql_pages);
    $conexao->exec($sql_positions);
    
    // Verificar se já existe a página de Konoha
    $stmt = $conexao->prepare("SELECT COUNT(*) as count FROM maps_pages WHERE id = 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($result['count'] == 0) {
        // Inserir página básica de Konoha
        $stmt = $conexao->prepare("INSERT INTO maps_pages (id, name, grid_data) VALUES (1, 'Konoha', '{\"type\":\"vila\",\"obstacles\":[]}')");
        $stmt->execute();
        
        echo "Página de Konoha criada com sucesso!<br>";
    }
    
    // Inserir outras páginas básicas se não existirem
    $pages = [
        ['name' => 'Floresta de Konoha', 'grid_data' => '{"type":"floresta","obstacles":[]}'],
        ['name' => 'Caminho para Areia', 'grid_data' => '{"type":"caminho","obstacles":[]}'],
        ['name' => 'Vila da Areia', 'grid_data' => '{"type":"vila","obstacles":[]}'],
        ['name' => 'Floresta da Névoa', 'grid_data' => '{"type":"floresta","obstacles":[]}'],
        ['name' => 'Vila da Névoa', 'grid_data' => '{"type":"vila","obstacles":[]}']
    ];
    
    foreach($pages as $page) {
        $stmt = $conexao->prepare("SELECT COUNT(*) as count FROM maps_pages WHERE name = ?");
        $stmt->execute([$page['name']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($result['count'] == 0) {
            $stmt = $conexao->prepare("INSERT INTO maps_pages (name, grid_data) VALUES (?, ?)");
            $stmt->execute([$page['name'], $page['grid_data']]);
            echo "Página '{$page['name']}' criada!<br>";
        }
    }
    
    // Configurar algumas conexões básicas entre páginas
    $conexao->exec("UPDATE maps_pages SET east_page_id = 2 WHERE id = 1 AND east_page_id IS NULL"); // Konoha -> Floresta
    $conexao->exec("UPDATE maps_pages SET west_page_id = 1, east_page_id = 3 WHERE id = 2 AND west_page_id IS NULL"); // Floresta -> Caminho
    $conexao->exec("UPDATE maps_pages SET west_page_id = 2, east_page_id = 4 WHERE id = 3 AND west_page_id IS NULL"); // Caminho -> Areia
    
    echo "Sistema de mapa configurado com sucesso!<br>";
    echo "<a href='index.php?p=mapa'>Ir para o mapa</a>";
    
} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
