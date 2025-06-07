
<?php
// Evitar qualquer saída antes dos headers
ob_start();

// Iniciar sessão
session_start();

// Definir cabeçalho JSON antes de qualquer output
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Verificar se a sessão está ativa
if(!isset($_SESSION['logado'])) {
    ob_clean();
    echo json_encode(['error' => 'Sessão expirada']);
    exit;
}

try {
    // Incluir conexão com banco
    require_once('_inc/conexao.php');
    
    // Buscar posição atual do jogador
    $stmt = $conexao->prepare("SELECT * FROM players_positions WHERE player_id = ?");
    $stmt->execute([$_SESSION['logado']]);
    $position = $stmt->fetch(PDO::FETCH_ASSOC);

    if($position) {
        // Buscar outros jogadores na mesma página
        $stmt = $conexao->prepare("
            SELECT pp.player_id, pp.x, pp.y, u.usuario, u.avatar, u.personagem 
            FROM players_positions pp 
            JOIN usuarios u ON pp.player_id = u.id 
            WHERE pp.current_page_id = ? AND pp.player_id != ?
        ");
        $stmt->execute([$position['current_page_id'], $_SESSION['logado']]);
        $other_players = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Garantir que os dados estão no formato correto
        $formatted_players = [];
        foreach($other_players as $player) {
            $formatted_players[] = [
                'player_id' => (int)$player['player_id'],
                'x' => (int)$player['x'],
                'y' => (int)$player['y'],
                'usuario' => htmlspecialchars($player['usuario'], ENT_QUOTES, 'UTF-8'),
                'avatar' => htmlspecialchars($player['avatar'] ?: '_img/personagens/no_avatar.jpg', ENT_QUOTES, 'UTF-8'),
                'personagem' => htmlspecialchars($player['personagem'] ?: 'Não definido', ENT_QUOTES, 'UTF-8')
            ];
        }

        ob_clean();
        echo json_encode($formatted_players, JSON_UNESCAPED_UNICODE);
        exit;
    }

    ob_clean();
    echo json_encode([]);
    exit;
    
} catch(Exception $e) {
    ob_clean();
    echo json_encode(['error' => 'Erro interno: ' . $e->getMessage()]);
    exit;
}
?>
<?php
session_start();
require_once('_inc/conexao.php');

// Definir header JSON
header('Content-Type: application/json');

// Verificar se o usuário está logado
if(!isset($_SESSION['logado'])) {
    echo json_encode(['error' => 'Sessão expirada']);
    exit;
}

try {
    // Buscar posição atual do jogador
    $stmt = $conexao->prepare("SELECT current_page_id FROM players_positions WHERE player_id = ?");
    $stmt->execute([$_SESSION['logado']]);
    $position = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$position) {
        echo json_encode([]);
        exit;
    }
    
    // Buscar outros jogadores na mesma página
    $stmt = $conexao->prepare("
        SELECT pp.player_id, pp.x, pp.y, u.usuario, u.avatar, u.personagem 
        FROM players_positions pp 
        JOIN usuarios u ON pp.player_id = u.id 
        WHERE pp.current_page_id = ? AND pp.player_id != ?
    ");
    $stmt->execute([$position['current_page_id'], $_SESSION['logado']]);
    $other_players = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Preparar dados para retorno
    $players_data = [];
    foreach($other_players as $player) {
        $players_data[] = [
            'player_id' => $player['player_id'],
            'x' => intval($player['x']),
            'y' => intval($player['y']),
            'usuario' => $player['usuario'],
            'avatar' => $player['avatar'] ? "_img/personagens/" . $player['personagem'] . "/" . $player['avatar'] . ".jpg" : "_img/personagens/no_avatar.jpg",
            'personagem' => $player['personagem']
        ];
    }
    
    echo json_encode($players_data);
    
} catch(Exception $e) {
    echo json_encode(['error' => 'Erro interno do servidor']);
}
?>
<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Conectar ao banco de dados
    require_once('_inc/conexao.php');
    
    // Iniciar sessão para verificar o jogador atual
    session_start();
    
    // Verificar se o usuário está logado
    if (!isset($_SESSION['logado'])) {
        echo json_encode(['error' => 'Sessão expirada']);
        exit;
    }
    
    $current_player_id = $_SESSION['logado'];
    
    // Buscar a página atual do jogador logado
    $stmt = $conexao->prepare("SELECT current_page_id FROM players_positions WHERE player_id = ?");
    $stmt->execute([$current_player_id]);
    $current_position = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current_position) {
        echo json_encode([]);
        exit;
    }
    
    $current_page_id = $current_position['current_page_id'];
    
    // Buscar outros jogadores na mesma página
    $stmt = $conexao->prepare("
        SELECT 
            pp.player_id,
            pp.x,
            pp.y,
            u.usuario,
            u.personagem,
            u.avatar
        FROM players_positions pp 
        JOIN usuarios u ON pp.player_id = u.id 
        WHERE pp.current_page_id = ? 
        AND pp.player_id != ?
        AND u.lastaction >= datetime('now', '-30 minutes')
    ");
    
    $stmt->execute([$current_page_id, $current_player_id]);
    $other_players = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Preparar dados para retorno
    $players_data = [];
    foreach ($other_players as $player) {
        $avatar_path = '_img/personagens/no_avatar.jpg';
        if ($player['avatar'] && $player['personagem']) {
            $avatar_path = "_img/personagens/" . $player['personagem'] . "/" . $player['avatar'] . ".jpg";
        }
        
        $players_data[] = [
            'player_id' => $player['player_id'],
            'x' => intval($player['x']),
            'y' => intval($player['y']),
            'usuario' => $player['usuario'],
            'personagem' => $player['personagem'] ?: 'Não definido',
            'avatar' => $avatar_path
        ];
    }
    
    echo json_encode($players_data);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Erro interno: ' . $e->getMessage()]);
}
?>
