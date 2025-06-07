
<?php
require_once('_inc/conexao.php');

echo "<h2>Debug - Verificando usuário 't3st3'</h2>";

// Verificar se o usuário existe no banco
$stmt = $conexao->prepare("SELECT id, usuario, senha, email FROM usuarios WHERE usuario LIKE ?");
$stmt->execute(['%t3st3%']);

echo "<h3>Usuários encontrados com 't3st3':</h3>";
while ($user = $stmt->fetch()) {
    echo "<p>";
    echo "<strong>ID:</strong> " . $user['id'] . "<br>";
    echo "<strong>Usuário (como está no banco):</strong> '" . $user['usuario'] . "'<br>";
    echo "<strong>Senha (MD5):</strong> " . $user['senha'] . "<br>";
    echo "<strong>Email:</strong> " . $user['email'] . "<br>";
    echo "</p>";
}

// Testar diferentes variações do nome
$variações = ['t3st3', 'T3st3', 'T3ST3', 't3ST3'];

echo "<h3>Testando variações do nome:</h3>";
foreach ($variações as $nome) {
    $stmt = $conexao->prepare("SELECT COUNT(*) as total FROM usuarios WHERE usuario = ?");
    $stmt->execute([$nome]);
    $result = $stmt->fetch();
    echo "<p><strong>'{$nome}':</strong> " . $result['total'] . " encontrado(s)</p>";
}

// Mostrar todos os usuários para comparação
echo "<h3>Todos os usuários no banco:</h3>";
$stmt = $conexao->prepare("SELECT id, usuario FROM usuarios ORDER BY id DESC LIMIT 10");
$stmt->execute();

while ($user = $stmt->fetch()) {
    echo "<p>ID: {$user['id']} - Usuário: '{$user['usuario']}'</p>";
}

// Teste de login manual
echo "<h3>Teste de Login Manual:</h3>";
$login_test = 't3st3';
$senha_test = md5('123456'); // Assumindo que a senha é 123456

$stmt = $conexao->prepare("SELECT * FROM usuarios WHERE usuario = ? AND senha = ?");
$stmt->execute([$login_test, $senha_test]);
$result = $stmt->fetch();

if ($result) {
    echo "<p style='color: green;'>✓ Login funcionaria com '$login_test' e senha '123456'</p>";
} else {
    echo "<p style='color: red;'>✗ Login não funciona com '$login_test' e senha '123456'</p>";
    
    // Tentar encontrar o usuário sem verificar senha
    $stmt2 = $conexao->prepare("SELECT usuario, senha FROM usuarios WHERE usuario = ?");
    $stmt2->execute([$login_test]);
    $user_only = $stmt2->fetch();
    
    if ($user_only) {
        echo "<p>Usuário encontrado: '{$user_only['usuario']}' com senha MD5: {$user_only['senha']}</p>";
        echo "<p>MD5 de '123456': " . md5('123456') . "</p>";
    } else {
        echo "<p>Usuário '$login_test' não encontrado no banco.</p>";
    }
}
?>
