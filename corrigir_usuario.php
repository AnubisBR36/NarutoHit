
<?php
require_once('_inc/conexao.php');

echo "<h2>Corrigir Nome de Usuário</h2>";

// Procurar usuários com nomes alterados
$stmt = $conexao->prepare("SELECT id, usuario FROM usuarios WHERE usuario LIKE '%t3st3%'");
$stmt->execute();

echo "<h3>Usuários encontrados:</h3>";
while ($user = $stmt->fetch()) {
    echo "<p>ID: {$user['id']} - Nome atual: '{$user['usuario']}'</p>";
    
    // Se o nome estiver errado, corrigir
    if ($user['usuario'] !== 't3st3') {
        $stmt_update = $conexao->prepare("UPDATE usuarios SET usuario = ? WHERE id = ?");
        $stmt_update->execute(['t3st3', $user['id']]);
        echo "<p style='color: green;'>✓ Nome corrigido para 't3st3'</p>";
    }
}

// Verificar se a correção funcionou
$stmt_verify = $conexao->prepare("SELECT id, usuario FROM usuarios WHERE usuario = ?");
$stmt_verify->execute(['t3st3']);
$user_corrected = $stmt_verify->fetch();

if ($user_corrected) {
    echo "<h3>Usuário corrigido com sucesso:</h3>";
    echo "<p>ID: {$user_corrected['id']} - Nome: '{$user_corrected['usuario']}'</p>";
} else {
    echo "<h3>Nenhum usuário encontrado com nome 't3st3'</h3>";
}
?>
