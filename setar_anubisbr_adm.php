
<?php
// Script para definir Anubisbr como administrador
require_once('_inc/conexao.php');

try {
    // Definir Anubisbr como administrador (adm = 1)
    $stmt = $conexao->prepare("UPDATE usuarios SET adm = 1 WHERE usuario = 'Anubisbr'");
    $result = $stmt->execute();
    
    if($result) {
        echo "✅ Usuário 'Anubisbr' definido como administrador com sucesso!";
    } else {
        echo "❌ Erro ao definir usuário como administrador.";
    }
    
    // Verificar se foi atualizado
    $stmt = $conexao->prepare("SELECT usuario, adm FROM usuarios WHERE usuario = 'Anubisbr'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($user) {
        echo "<br><br>Status atual: Usuário " . $user['usuario'] . " tem nível de administração: " . $user['adm'];
        echo "<br>0 = Usuário normal, 1 = Administrador, 2 = Moderador";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}
?>
