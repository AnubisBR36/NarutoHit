
<?php
require_once('_inc/conexao.php');

echo "<h2>Status do Banco de Dados</h2>";

try {
    // Verificar usuários
    $stmt = $conexao->prepare("SELECT COUNT(*) as total FROM usuarios");
    $stmt->execute();
    $result = $stmt->fetch();
    
    echo "<p><strong>Total de usuários:</strong> " . $result['total'] . "</p>";
    
    if ($result['total'] > 0) {
        echo "<p><strong>Usuários existentes:</strong></p>";
        $users = $conexao->prepare("SELECT id, usuario, email FROM usuarios LIMIT 10");
        $users->execute();
        while ($user = $users->fetch()) {
            echo "- ID: {$user['id']}, Usuário: {$user['usuario']}, Email: {$user['email']}<br>";
        }
    }
    
    // Verificar tabela block
    $stmt = $conexao->prepare("SELECT COUNT(*) as total FROM block");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<p><strong>IPs bloqueados:</strong> " . $result['total'] . "</p>";
    
    // Verificar estrutura das tabelas principais
    echo "<h3>Estrutura das Tabelas:</h3>";
    $tables = ['usuarios', 'block', 'membros', 'organizacoes', 'jutsus'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $conexao->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
            $stmt->execute([$table]);
            
            if ($stmt->fetch()) {
                echo "<p>✓ Tabela '$table' existe</p>";
                
                // Contar registros na tabela
                $count_stmt = $conexao->prepare("SELECT COUNT(*) as total FROM `$table`");
                $count_stmt->execute();
                $count = $count_stmt->fetch();
                echo "<p>&nbsp;&nbsp;&nbsp;→ Total de registros: " . $count['total'] . "</p>";
            } else {
                echo "<p>✗ Tabela '$table' não encontrada</p>";
            }
        } catch (Exception $e) {
            echo "<p>⚠ Erro ao verificar tabela '$table': " . $e->getMessage() . "</p>";
        }
    }
    
    // Verificar arquivos de cache
    echo "<h3>Status dos Arquivos de Cache:</h3>";
    $cache_files = [
        'news/data/ipban.db.php',
        'news/data/loginban.db.php',
        'news/data/flood.db.php'
    ];
    
    foreach ($cache_files as $file) {
        if (file_exists($file)) {
            echo "<p>✓ Arquivo '$file' existe</p>";
        } else {
            echo "<p>✗ Arquivo '$file' não encontrado</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p><strong>Erro:</strong> " . $e->getMessage() . "</p>";
}

echo "<br><br>";
echo "<a href='index.php'>Voltar ao Site</a> | ";
echo "<a href='limpar_banco.php'>Limpar Banco</a> | ";
echo "<a href='limpar_ip.php'>Limpar IPs</a>";
?>
