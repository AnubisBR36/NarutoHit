
<?php
require_once('_inc/conexao.php');

try {
    echo "Iniciando limpeza de IPs bloqueados...<br><br>";
    
    // Limpar tabela de IPs bloqueados
    try {
        $conexao->exec("DELETE FROM block");
        echo "✓ IPs bloqueados removidos da tabela 'block'<br>";
    } catch (Exception $e) {
        echo "⚠ Tabela 'block' não encontrada ou erro: " . $e->getMessage() . "<br>";
    }
    
    // Resetar IPs dos usuários (permitir reutilização)
    try {
        $conexao->exec("UPDATE usuarios SET ip = 0, loginip = 0");
        echo "✓ IPs dos usuários resetados<br>";
    } catch (Exception $e) {
        echo "✗ Erro ao resetar IPs dos usuários: " . $e->getMessage() . "<br>";
    }
    
    // Limpar arquivo de IPs banidos do sistema de notícias (se existir)
    $ipban_file = 'news/data/ipban.db.php';
    if (file_exists($ipban_file)) {
        file_put_contents($ipban_file, "<?php \$ipban = array(); \$ipban_stamp = array(); ?>");
        echo "✓ Arquivo de IPs banidos do sistema de notícias limpo<br>";
    }
    
    // Limpar arquivo de login ban do sistema de notícias (se existir)
    $loginban_file = 'news/data/loginban.db.php';
    if (file_exists($loginban_file)) {
        file_put_contents($loginban_file, "<?php \$loginban = array(); \$loginban_stamp = array(); ?>");
        echo "✓ Arquivo de login ban do sistema de notícias limpo<br>";
    }
    
    // Executar VACUUM para otimizar o banco
    $conexao->exec("VACUUM");
    
    echo "<br><strong>Limpeza de IPs concluída!</strong><br>";
    echo "Agora você pode tentar criar uma nova conta.<br>";
    echo "Todas as restrições de IP foram removidas.<br><br>";
    echo "<a href='index.php?p=reg'>Criar Nova Conta</a> | ";
    echo "<a href='index.php'>Voltar ao Site</a>";
    
} catch (Exception $e) {
    echo "Erro durante a limpeza: " . $e->getMessage();
}
?>
