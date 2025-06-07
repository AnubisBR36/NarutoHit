
<?php
require_once '_inc/conexao.php';

echo "<h2>Verificando contas criadas no banco de dados</h2>";

try {
    // Buscar todas as contas de usuários
    $stmt = $conexao->prepare("SELECT id, usuario, senha, email, reg, status FROM usuarios ORDER BY id DESC LIMIT 10");
    $stmt->execute();
    
    echo "<h3>Últimas contas criadas:</h3>";
    
    if ($stmt->rowCount() > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px;'>";
        echo "<tr><th>ID</th><th>Usuário (Login)</th><th>Senha (MD5)</th><th>Email</th><th>Data Registro</th><th>Status</th></tr>";
        
        while ($user = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td><strong>" . $user['usuario'] . "</strong></td>";
            echo "<td>" . $user['senha'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td>" . $user['reg'] . "</td>";
            echo "<td>" . $user['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Tentar identificar a senha original baseada nos logs do console
        echo "<h3>Informações de Login:</h3>";
        echo "<p><strong>Login:</strong> AnubisBr (processado como: Anubisbr)</p>";
        echo "<p><strong>Nota:</strong> A senha está armazenada como hash MD5 no banco. ";
        echo "Baseado nos logs do console, parece que o usuário tentou fazer login mas houve erro 'usuário não encontrado', ";
        echo "o que sugere que pode haver um problema com o processamento do nome de usuário.</p>";
        
    } else {
        echo "<p>Nenhuma conta encontrada no banco de dados.</p>";
    }
    
} catch (Exception $e) {
    echo "<p><strong>Erro:</strong> " . $e->getMessage() . "</p>";
}
?>
