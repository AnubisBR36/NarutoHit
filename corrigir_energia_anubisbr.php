
<?php
require_once '_inc/conexao.php';

try {
    // Buscar a conta Anubisbr
    $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE usuario = 'Anubisbr'");
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario) {
        echo "Usuário encontrado: " . $usuario['usuario'] . "<br>";
        echo "Energia atual: " . $usuario['energia'] . "<br>";
        
        // Resetar o sistema de energia
        $energia_atual = $usuario['energia'];
        $energia_ultima_atualizacao = date('Y-m-d H:i:s');
        
        // Atualizar com timestamp atual para permitir consumo normal
        $stmt = $conexao->prepare("UPDATE usuarios SET energia_ultima_atualizacao = ? WHERE usuario = 'Anubisbr'");
        if ($stmt->execute([$energia_ultima_atualizacao])) {
            echo "✅ Sistema de energia corrigido para Anubisbr!<br>";
            echo "Energia agora voltará a ser consumida normalmente em ataques.<br>";
        }
        
        // Verificar se existe algum campo que pode estar travando a energia
        $stmt = $conexao->prepare("DESCRIBE usuarios");
        $stmt->execute();
        $campos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<br>Campos da tabela usuarios:<br>";
        foreach ($campos as $campo) {
            echo "- " . $campo['Field'] . " (" . $campo['Type'] . ")<br>";
        }
        
    } else {
        echo "❌ Usuário 'Anubisbr' não encontrado!";
    }
    
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
