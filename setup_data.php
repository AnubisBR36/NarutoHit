
<?php
require_once('_inc/conexao.php');

echo "Configurando dados básicos...<br>";

// Inserir usuário admin se não existir
$admin_check = mysql_query("SELECT id FROM usuarios WHERE usuario = 'admin'");
if (!mysql_fetch_assoc($admin_check)) {
    $sql = "INSERT INTO usuarios (usuario, senha, email, nivel, yens, energia, energiamax, taijutsu, ninjutsu, genjutsu, vila, admin) 
            VALUES ('admin', '" . md5('admin123') . "', 'admin@narutohit.com', 50, 10000, 100, 100, 50, 50, 50, 1, 1)";
    
    if (mysql_query($sql)) {
        echo "✓ Usuário admin criado com sucesso!<br>";
    } else {
        echo "✗ Erro ao criar usuário admin<br>";
    }
}

// Adicionar coluna admin se não existir
try {
    mysql_query("ALTER TABLE usuarios ADD COLUMN admin INTEGER DEFAULT 0");
    echo "✓ Coluna admin adicionada<br>";
} catch (Exception $e) {
    echo "• Coluna admin já existe<br>";
}

// Inserir alguns itens básicos na tabela table_itens se estiver vazia
$items_check = mysql_query("SELECT COUNT(*) as total FROM table_itens");
$items_result = mysql_fetch_assoc($items_check);

if ($items_result['total'] == 0) {
    $items = [
        "INSERT INTO table_itens (id, categoria, nome, descricao, taijutsu, ninjutsu, genjutsu, valor, imagem) VALUES (1, 'arma', 'Kunai Simples', 'Kunai de batalha básica', 1, 0, 0, 100, 'kunai_simples')",
        "INSERT INTO table_itens (id, categoria, nome, descricao, taijutsu, ninjutsu, genjutsu, valor, imagem) VALUES (2, 'vestimenta', 'Roupa do Naruto', 'Vestimenta simples do Naruto', 0, 0, 2, 200, 'roupa_naruto')",
        "INSERT INTO table_itens (id, categoria, nome, descricao, taijutsu, ninjutsu, genjutsu, valor, imagem) VALUES (3, 'calcado', 'Sandália Ninja', 'Sandália básica para ninjas', 1, 0, 1, 150, 'sandalia_simples')"
    ];
    
    foreach ($items as $item_sql) {
        mysql_query($item_sql);
    }
    echo "✓ Itens básicos inseridos<br>";
}

echo "Configuração concluída!<br>";
echo "<a href='index.php'>Voltar ao site</a>";
?>
