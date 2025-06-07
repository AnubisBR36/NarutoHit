
<?php
require_once('_inc/conexao.php');

echo "<h2>🎮 Criando Contas de Teste no Mapa</h2>";

try {
    // Lista de contas de teste para criar
    $contas_teste = [
        [
            'usuario' => 'Naruto_Test',
            'email' => 'naruto@teste.com',
            'senha' => 'teste123',
            'personagem' => 'naruto',
            'avatar' => '1',
            'x' => 5,
            'y' => 8
        ],
        [
            'usuario' => 'Sasuke_Test',
            'email' => 'sasuke@teste.com',
            'senha' => 'teste123',
            'personagem' => 'sasuke',
            'avatar' => '2',
            'x' => 12,
            'y' => 15
        ],
        [
            'usuario' => 'Sakura_Test',
            'email' => 'sakura@teste.com',
            'senha' => 'teste123',
            'personagem' => 'sakura',
            'avatar' => '3',
            'x' => 18,
            'y' => 6
        ],
        [
            'usuario' => 'Kakashi_Test',
            'email' => 'kakashi@teste.com',
            'senha' => 'teste123',
            'personagem' => 'kakashi',
            'avatar' => '4',
            'x' => 3,
            'y' => 12
        ],
        [
            'usuario' => 'Gaara_Test',
            'email' => 'gaara@teste.com',
            'senha' => 'teste123',
            'personagem' => 'gaara',
            'avatar' => '1',
            'x' => 16,
            'y' => 9
        ]
    ];

    echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc;'>";
    echo "<h3>📝 Criando contas de teste...</h3>";

    foreach ($contas_teste as $conta) {
        echo "<p><strong>🔸 Processando:</strong> " . $conta['usuario'] . "</p>";
        
        // Verificar se o usuário já existe
        $stmt = $conexao->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmt->execute([$conta['usuario']]);
        $usuario_existente = $stmt->fetch();

        if ($usuario_existente) {
            echo "<p style='margin-left: 20px; color: orange;'>⚠️ Usuário já existe - atualizando posição no mapa</p>";
            $user_id = $usuario_existente['id'];
            
            // Atualizar lastaction para que o usuário apareça como ativo
            $stmt = $conexao->prepare("UPDATE usuarios SET lastaction = datetime('now') WHERE id = ?");
            $stmt->execute([$user_id]);
        } else {
            // Criar novo usuário
            $senha_hash = md5($conta['senha']);
            $stmt = $conexao->prepare("
                INSERT INTO usuarios (
                    usuario, email, senha, personagem, avatar, energia, energiamax, 
                    vida, vidamax, chakra, chakramax, nivel, experiencia, ryous, 
                    datareg, ip, lastaction, ban, lastlogin
                ) VALUES (?, ?, ?, ?, ?, 100, 100, 100, 100, 100, 100, 1, 0, 1000, 
                    datetime('now'), '127.0.0.1', datetime('now'), 0, datetime('now'))
            ");
            
            $result = $stmt->execute([
                $conta['usuario'],
                $conta['email'],
                $senha_hash,
                $conta['personagem'],
                $conta['avatar']
            ]);

            if ($result) {
                $user_id = $conexao->lastInsertId();
                echo "<p style='margin-left: 20px; color: green;'>✅ Usuário criado com sucesso (ID: $user_id)</p>";
            } else {
                echo "<p style='margin-left: 20px; color: red;'>❌ Erro ao criar usuário</p>";
                continue;
            }
        }

        // Verificar se já tem posição no mapa
        $stmt = $conexao->prepare("SELECT player_id FROM players_positions WHERE player_id = ?");
        $stmt->execute([$user_id]);
        $posicao_existente = $stmt->fetch();

        if ($posicao_existente) {
            // Atualizar posição existente
            $stmt = $conexao->prepare("UPDATE players_positions SET x = ?, y = ?, current_page_id = 1 WHERE player_id = ?");
            $result = $stmt->execute([$conta['x'], $conta['y'], $user_id]);
            echo "<p style='margin-left: 20px; color: blue;'>🔄 Posição atualizada para X: {$conta['x']}, Y: {$conta['y']}</p>";
        } else {
            // Inserir nova posição no mapa
            $stmt = $conexao->prepare("INSERT INTO players_positions (player_id, current_page_id, x, y, last_move_time) VALUES (?, 1, ?, ?, CURRENT_TIMESTAMP)");
            $result = $stmt->execute([$user_id, $conta['x'], $conta['y']]);
            echo "<p style='margin-left: 20px; color: green;'>📍 Posição criada no mapa X: {$conta['x']}, Y: {$conta['y']}</p>";
        }

        echo "<div style='margin: 5px 0; padding: 5px; background: #e8f5e8; border-left: 3px solid #4CAF50;'>";
        echo "<strong>✨ {$conta['usuario']}</strong> - Personagem: {$conta['personagem']} | Posição: ({$conta['x']}, {$conta['y']})";
        echo "</div>";
    }

    echo "</div>";

    // Verificar quantos jogadores estão no mapa agora
    echo "<div style='background: #e8f5e8; padding: 15px; margin: 20px 0; border: 2px solid #4CAF50;'>";
    echo "<h3>📊 Resumo do Mapa</h3>";
    
    $stmt = $conexao->prepare("
        SELECT COUNT(*) as total_players 
        FROM players_positions pp 
        JOIN usuarios u ON pp.player_id = u.id 
        WHERE pp.current_page_id = 1
    ");
    $stmt->execute();
    $total = $stmt->fetch();
    
    echo "<p><strong>👥 Total de jogadores em Konoha:</strong> " . $total['total_players'] . "</p>";

    // Listar todos os jogadores no mapa
    $stmt = $conexao->prepare("
        SELECT u.usuario, u.personagem, u.avatar, pp.x, pp.y 
        FROM players_positions pp 
        JOIN usuarios u ON pp.player_id = u.id 
        WHERE pp.current_page_id = 1 
        ORDER BY u.usuario
    ");
    $stmt->execute();
    $jogadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h4>🗺️ Jogadores no mapa:</h4>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #4CAF50; color: white;'>";
    echo "<th>Usuário</th><th>Personagem</th><th>Avatar</th><th>Posição X</th><th>Posição Y</th>";
    echo "</tr>";

    foreach ($jogadores as $jogador) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($jogador['usuario']) . "</td>";
        echo "<td>" . htmlspecialchars($jogador['personagem']) . "</td>";
        echo "<td>" . $jogador['avatar'] . "</td>";
        echo "<td>" . $jogador['x'] . "</td>";
        echo "<td>" . $jogador['y'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";

    echo "<div style='background: #fff3cd; padding: 10px; margin: 10px 0; border: 1px solid #ffeaa7;'>";
    echo "<h4>🔑 Informações de Login</h4>";
    echo "<p><strong>Senha para todas as contas de teste:</strong> teste123</p>";
    echo "<p><strong>Contas criadas:</strong> Naruto_Test, Sasuke_Test, Sakura_Test, Kakashi_Test, Gaara_Test</p>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb;'>";
    echo "<h4>❌ Erro:</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}

// Botões de navegação
echo "<div style='margin: 20px 0;'>";
echo "<a href='criar_contas_teste_mapa.php' style='background: #4CAF50; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🔄 Executar Novamente</a>";
echo "<a href='index.php?p=mapa' style='background: #2196F3; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px;'>🗺️ Ver Mapa</a>";
echo "<a href='index.php' style='background: #FF9800; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>🏠 Voltar ao Jogo</a>";
echo "</div>";
?>
