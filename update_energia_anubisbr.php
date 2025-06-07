
<?php
// Script direto sem dependência de sessão
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔋 Atualizando Energia do Usuário Anubisbr</h2>";

try {
    // Conectar diretamente ao SQLite
    $db_file = __DIR__ . '/database.sqlite';
    
    if (!file_exists($db_file)) {
        echo "<p style='color: red;'>❌ Arquivo de banco não encontrado: $db_file</p>";
        exit;
    }

    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Conexão com banco estabelecida</p>";

    // Verificar usuário atual
    $stmt = $pdo->prepare("SELECT id, usuario, energia, energiamax FROM usuarios WHERE usuario = 'Anubisbr'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "<div style='border: 2px solid #333; padding: 10px; margin: 10px 0; background: #f5f5f5;'>";
        echo "<p><strong>👤 Usuário encontrado:</strong> " . htmlspecialchars($user['usuario']) . "</p>";
        echo "<p><strong>⚡ Energia atual:</strong> " . $user['energia'] . "/" . $user['energiamax'] . "</p>";
        echo "</div>";
        
        // Executar múltiplas tentativas de atualização
        $updateQueries = [
            "UPDATE usuarios SET energia = 100 WHERE usuario = 'Anubisbr'",
            "UPDATE usuarios SET energia = 100 WHERE id = " . $user['id'],
            "UPDATE usuarios SET energia = 100, energiamax = 100 WHERE usuario = 'Anubisbr'"
        ];
        
        foreach ($updateQueries as $index => $query) {
            echo "<p><strong>🔄 Tentativa " . ($index + 1) . ":</strong> " . htmlspecialchars($query) . "</p>";
            
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute();
            $affected = $stmt->rowCount();
            
            echo "<p style='margin-left: 20px;'>Resultado: " . ($result ? "✅ Sucesso" : "❌ Falha") . " | Linhas afetadas: $affected</p>";
        }
        
        // Verificar resultado final
        echo "<br><div style='border: 3px solid #4CAF50; padding: 15px; background: #e8f5e8;'>";
        echo "<h3>🔍 VERIFICAÇÃO FINAL:</h3>";
        
        $stmt = $pdo->prepare("SELECT id, usuario, energia, energiamax FROM usuarios WHERE usuario = 'Anubisbr'");
        $stmt->execute();
        $updated = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($updated) {
            echo "<p><strong>ID:</strong> " . $updated['id'] . "</p>";
            echo "<p><strong>Usuário:</strong> " . htmlspecialchars($updated['usuario']) . "</p>";
            echo "<p><strong>Energia:</strong> " . $updated['energia'] . "/" . $updated['energiamax'] . "</p>";
            
            if ($updated['energia'] == 100) {
                echo "<p style='color: green; font-size: 20px; font-weight: bold;'>🎉 SUCESSO! Energia atualizada para 100!</p>";
            } else {
                echo "<p style='color: red; font-size: 18px;'>❌ FALHA: Energia ainda em " . $updated['energia'] . "</p>";
                
                // Tentar forçar com SQL direto
                echo "<p>🔨 Tentando comando SQL direto...</p>";
                $pdo->exec("UPDATE usuarios SET energia = 100 WHERE id = " . $updated['id']);
                
                // Verificar novamente
                $stmt = $pdo->prepare("SELECT energia FROM usuarios WHERE id = ?");
                $stmt->execute([$updated['id']]);
                $final = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "<p><strong>Energia após comando direto:</strong> " . $final['energia'] . "</p>";
            }
        }
        echo "</div>";
        
    } else {
        echo "<p style='color: red;'><strong>❌ Usuário 'Anubisbr' não encontrado!</strong></p>";
        
        // Listar todos os usuários
        echo "<h3>📋 Usuários no banco:</h3>";
        $stmt = $pdo->prepare("SELECT id, usuario, energia, energiamax FROM usuarios ORDER BY id LIMIT 20");
        $stmt->execute();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Usuário</th><th>Energia</th></tr>";
        
        while ($user_list = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $user_list['id'] . "</td>";
            echo "<td>" . htmlspecialchars($user_list['usuario']) . "</td>";
            echo "<td>" . $user_list['energia'] . "/" . $user_list['energiamax'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red; background: #ffe6e6; padding: 10px; border: 2px solid red;'>";
    echo "<strong>❌ ERRO:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>Arquivo:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Linha:</strong> " . $e->getLine();
    echo "</p>";
}

// Botões de navegação
echo "<br><div style='margin: 20px 0;'>";
echo "<a href='update_energia_anubisbr.php' style='background: #4CAF50; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🔄 Executar Novamente</a>";
echo "<a href='index.php' style='background: #2196F3; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px;'>🏠 Voltar ao Jogo</a>";
echo "</div>";

// Mostrar informações do arquivo do banco
echo "<div style='background: #f0f0f0; padding: 10px; margin: 20px 0; border: 1px solid #ccc;'>";
echo "<h4>ℹ️ Informações do Banco:</h4>";
echo "<p><strong>Arquivo:</strong> " . $db_file . "</p>";
echo "<p><strong>Existe:</strong> " . (file_exists($db_file) ? "✅ Sim" : "❌ Não") . "</p>";
if (file_exists($db_file)) {
    echo "<p><strong>Tamanho:</strong> " . number_format(filesize($db_file)) . " bytes</p>";
    echo "<p><strong>Última modificação:</strong> " . date('Y-m-d H:i:s', filemtime($db_file)) . "</p>";
}
echo "</div>";
?>
