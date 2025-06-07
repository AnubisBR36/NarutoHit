
<?php
require_once('_inc/conexao.php');

try {
    echo "Iniciando limpeza COMPLETA das contas de usuários...<br><br>";
    
    // Lista das tabelas relacionadas aos usuários para limpar
    $tabelas_usuarios = [
        'usuarios',
        'membros', 
        'amigos',
        'messages',
        'inventario',
        'book',
        'contato',
        'relatorios',
        'spam',
        'vendas',
        'verificador',
        'vip',
        'ramen',
        'usaveis',
        'block'
    ];
    
    // Verificar se estamos usando MySQL ou SQLite
    $is_mysql = ($conexao instanceof mysqli);
    
    if ($is_mysql) {
        // Para MySQL
        $conexao->query("SET FOREIGN_KEY_CHECKS = 0");
        
        foreach ($tabelas_usuarios as $tabela) {
            try {
                // Verificar se a tabela existe
                $result = $conexao->query("SHOW TABLES LIKE '$tabela'");
                
                if ($result && $result->num_rows > 0) {
                    // Limpar dados da tabela
                    $conexao->query("DELETE FROM `$tabela`");
                    
                    // Resetar auto increment
                    $conexao->query("ALTER TABLE `$tabela` AUTO_INCREMENT = 1");
                    
                    echo "✓ Dados da tabela '$tabela' limpos com sucesso<br>";
                } else {
                    echo "⚠ Tabela '$tabela' não encontrada<br>";
                }
            } catch (Exception $e) {
                echo "✗ Erro ao limpar dados da tabela '$tabela': " . $e->getMessage() . "<br>";
            }
        }
        
        $conexao->query("SET FOREIGN_KEY_CHECKS = 1");
    } else {
        // Para SQLite
        $conexao->exec("PRAGMA foreign_keys = OFF");
        
        foreach ($tabelas_usuarios as $tabela) {
            try {
                // Verificar se a tabela existe
                $stmt = $conexao->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
                $stmt->execute([$tabela]);
                
                if ($stmt->fetch()) {
                    // Limpar apenas os dados da tabela (não a estrutura)
                    $conexao->exec("DELETE FROM `$tabela`");
                    
                    // Resetar o auto increment se a tabela tiver ID
                    $conexao->exec("DELETE FROM sqlite_sequence WHERE name='$tabela'");
                    
                    echo "✓ Dados da tabela '$tabela' limpos com sucesso<br>";
                } else {
                    echo "⚠ Tabela '$tabela' não encontrada<br>";
                }
            } catch (Exception $e) {
                echo "✗ Erro ao limpar dados da tabela '$tabela': " . $e->getMessage() . "<br>";
            }
        }
        
        $conexao->exec("PRAGMA foreign_keys = ON");
    }
    
    // Limpar arquivos de cache/bloqueio se existirem
    $files_to_clean = [
        'news/data/ipban.db.php',
        'news/data/loginban.db.php',
        'news/data/flood.db.php'
    ];
    
    foreach ($files_to_clean as $file) {
        if (file_exists($file)) {
            if (strpos($file, 'ipban') !== false) {
                file_put_contents($file, "<?php \$ipban = array(); \$ipban_stamp = array(); ?>");
            } elseif (strpos($file, 'loginban') !== false) {
                file_put_contents($file, "<?php \$loginban = array(); \$loginban_stamp = array(); ?>");
            } elseif (strpos($file, 'flood') !== false) {
                file_put_contents($file, "<?php \$flood = array(); ?>");
            }
            echo "✓ Arquivo '$file' limpo<br>";
        }
    }
    
    // Executar otimização do banco
    if ($is_mysql) {
        foreach ($tabelas_usuarios as $tabela) {
            $conexao->query("OPTIMIZE TABLE `$tabela`");
        }
    } else {
        $conexao->exec("VACUUM");
    }
    
    echo "<br><strong>✅ LIMPEZA COMPLETA CONCLUÍDA!</strong><br>";
    echo "• Todas as contas de usuários foram TOTALMENTE removidas<br>";
    echo "• Todos os IPs bloqueados foram removidos<br>";
    echo "• Cache de sistema foi limpo<br>";
    echo "• Banco de dados foi otimizado<br><br>";
    echo "✨ <strong>Agora você pode criar uma nova conta normalmente!</strong><br><br>";
    echo "<a href='index.php?p=reg' style='color: green; font-weight: bold;'>🚀 Criar Nova Conta</a> | ";
    echo "<a href='verificar_banco.php'>🔍 Verificar Status do Banco</a> | ";
    echo "<a href='index.php'>🏠 Voltar ao Site</a>";
    
} catch (Exception $e) {
    echo "❌ <strong>Erro durante a limpeza:</strong> " . $e->getMessage();
}
?>
