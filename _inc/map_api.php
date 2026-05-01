<?php
require_once('conexao.php');
require_once('verificar.php');
header('Content-Type: application/json');
header('Cache-Control: no-cache');

$CONFIG_FILE = __DIR__ . '/../map_config.json';
$PLAYERS_FILE = __DIR__ . '/../map_players.json';

$isAdmin = (isset($db) && is_array($db) && isset($db['adm'])) && ($db['adm'] == 1 || $db['adm'] == 2);
$isVIP = (isset($db) && is_array($db) && isset($db['vip'])) && ($db['vip'] > 0 || (is_string($db['vip']) && strtotime($db['vip']) > time()));

function loadMapConfig() {
    global $CONFIG_FILE;
    if (file_exists($CONFIG_FILE)) {
        $content = file_get_contents($CONFIG_FILE);
        return json_decode($content, true) ?: getDefaultConfig();
    }
    return getDefaultConfig();
}

function saveMapConfig($config) {
    global $CONFIG_FILE;
    return file_put_contents($CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function getDefaultConfig() {
    return [
        "MapaBase" => ["tileSize" => 32, "entradas" => [], "saidas" => [], "bloqueados" => []],
        "Vila Akatsuki" => ["tileSize" => 40, "entradas" => [], "saidas" => [], "bloqueados" => []],
        "Vila Oculta da Areia" => ["tileSize" => 40, "entradas" => [], "saidas" => [], "bloqueados" => []],
        "Vila Oculta da Chuva" => ["tileSize" => 40, "entradas" => [], "saidas" => [], "bloqueados" => []],
        "Vila Oculta da Folha" => ["tileSize" => 40, "entradas" => [], "saidas" => [], "bloqueados" => []],
        "Vila Oculta da Névoa" => ["tileSize" => 40, "entradas" => [], "saidas" => [], "bloqueados" => []],
        "Vila Oculta da Nuvem" => ["tileSize" => 40, "entradas" => [], "saidas" => [], "bloqueados" => []],
        "Vila Oculta da Pedra" => ["tileSize" => 40, "entradas" => [], "saidas" => [], "bloqueados" => []]
    ];
}

function loadPlayers() {
    global $PLAYERS_FILE;
    if (file_exists($PLAYERS_FILE)) {
        $content = file_get_contents($PLAYERS_FILE);
        return json_decode($content, true) ?: [];
    }
    return [];
}

function savePlayers($players) {
    global $PLAYERS_FILE;
    return file_put_contents($PLAYERS_FILE, json_encode($players, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function getPlayerPosition($playerId, $playerVila = 1) {
    $players = loadPlayers();
    if (isset($players[$playerId]) && !empty($players[$playerId]['mapaAtual'])) {
        return $players[$playerId];
    }
    
    // Vila inicial baseada na vila do jogador
    $vilaMapa = [
        1 => 'Vila Oculta da Folha',
        2 => 'Vila Oculta da Areia',
        3 => 'Vila Oculta da Névoa',
        4 => 'Vila Oculta da Nuvem',
        5 => 'Vila Oculta da Pedra',
        6 => 'Vila Oculta do Som',
        7 => 'Vila Oculta da Chuva',
        8 => 'Vila Akatsuki'
    ];
    
    $mapaInicial = isset($vilaMapa[$playerVila]) ? $vilaMapa[$playerVila] : 'Vila Oculta da Folha';
    return ['x' => 10, 'y' => 10, 'mapaAtual' => $mapaInicial];
}

function savePlayerPosition($playerId, $playerName, $x, $y, $mapaAtual, $vila = 0, $isBot = false) {
    if (empty($playerId) || $playerId === null || $playerId == 0) {
        return;
    }
    
    $players = loadPlayers();
    $players[$playerId] = [
        'id' => $playerId,
        'nome' => $playerName,
        'x' => (int)$x,
        'y' => (int)$y,
        'mapaAtual' => $mapaAtual,
        'vila' => (int)$vila,
        'isBot' => $isBot,
        'lastUpdate' => time()
    ];
    
    foreach ($players as $id => $p) {
        if (empty($id) || $id === '' || (isset($p['lastUpdate']) && (time() - $p['lastUpdate']) > 300)) {
            unset($players[$id]);
        }
    }
    
    savePlayers($players);
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

switch ($action) {
    case 'getConfig':
        $config = loadMapConfig();
        $playerId = (isset($db) && is_array($db) && isset($db['id'])) ? $db['id'] : 0;
        $playerVila = (isset($db) && is_array($db) && isset($db['vila'])) ? (int)$db['vila'] : 1;
        $playerPos = ($playerId > 0) ? getPlayerPosition($playerId, $playerVila) : ['x' => 10, 'y' => 10, 'mapaAtual' => 'MapaBase'];
        echo json_encode([
            'success' => true,
            'config' => $config,
            'player' => $playerPos
        ]);
        break;
        
    case 'getIcons':
        $iconsDir = __DIR__ . '/../_img/Icones_map';
        $icons = [];
        if (is_dir($iconsDir)) {
            $files = scandir($iconsDir);
            foreach ($files as $file) {
                if (preg_match('/\.(png|jpg|gif)$/i', $file)) {
                    $icons[] = '_img/Icones_map/' . $file;
                }
            }
        }
        echo json_encode(['success' => true, 'icons' => $icons]);
        break;
        
    case 'getPlayers':
        $players = loadPlayers();
        $activePlayers = [];
        foreach ($players as $p) {
            if (isset($p['lastUpdate']) && (time() - $p['lastUpdate']) <= 60) {
                $activePlayers[] = $p;
            }
        }
        echo json_encode(['success' => true, 'players' => $activePlayers]);
        break;
        
    case 'savePosition':
        if (!isset($db) || !is_array($db) || !isset($db['id']) || !$db['id']) {
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            break;
        }
        $x = isset($_POST['x']) ? (int)$_POST['x'] : 50;
        $y = isset($_POST['y']) ? (int)$_POST['y'] : 50;
        $mapaAtual = isset($_POST['mapaAtual']) ? $_POST['mapaAtual'] : 'MapaBase';
        $vila = isset($db['vila']) ? (int)$db['vila'] : 0;
        
        savePlayerPosition($db['id'], $db['usuario'], $x, $y, $mapaAtual, $vila, false);
        echo json_encode(['success' => true]);
        break;
        
    case 'addEntrada':
        if (!$isAdmin) {
            echo json_encode(['success' => false, 'message' => 'Acesso negado']);
            break;
        }
        
        $mapa = isset($_POST['mapa']) ? $_POST['mapa'] : '';
        $x = isset($_POST['x']) ? (int)$_POST['x'] : 0;
        $y = isset($_POST['y']) ? (int)$_POST['y'] : 0;
        $destino = isset($_POST['destino']) ? $_POST['destino'] : '';
        $destinoX = isset($_POST['destinoX']) ? (int)$_POST['destinoX'] : 10;
        $destinoY = isset($_POST['destinoY']) ? (int)$_POST['destinoY'] : 10;
        $icone = isset($_POST['icone']) ? $_POST['icone'] : '';
        
        $config = loadMapConfig();
        if (!isset($config[$mapa])) {
            $config[$mapa] = ['tileSize' => 40, 'entradas' => [], 'saidas' => [], 'bloqueados' => []];
        }
        if (!isset($config[$mapa]['entradas'])) {
            $config[$mapa]['entradas'] = [];
        }
        
        $existingIndex = null;
        foreach ($config[$mapa]['entradas'] as $i => $e) {
            if ((int)$e['x'] === $x && (int)$e['y'] === $y) {
                $existingIndex = $i;
                break;
            }
        }
        
        $entrada = ['x' => $x, 'y' => $y, 'destino' => $destino, 'destinoX' => $destinoX, 'destinoY' => $destinoY];
        if ($icone) $entrada['icone'] = $icone;
        
        if ($existingIndex !== null) {
            $config[$mapa]['entradas'][$existingIndex] = $entrada;
        } else {
            $config[$mapa]['entradas'][] = $entrada;
        }
        
        saveMapConfig($config);
        echo json_encode(['success' => true, 'config' => $config]);
        break;
        
    case 'addSaida':
        if (!$isAdmin) {
            echo json_encode(['success' => false, 'message' => 'Acesso negado']);
            break;
        }
        
        $mapa = isset($_POST['mapa']) ? $_POST['mapa'] : '';
        $x = isset($_POST['x']) ? (int)$_POST['x'] : 0;
        $y = isset($_POST['y']) ? (int)$_POST['y'] : 0;
        $destino = isset($_POST['destino']) ? $_POST['destino'] : 'MapaBase';
        $destinoX = isset($_POST['destinoX']) ? (int)$_POST['destinoX'] : 50;
        $destinoY = isset($_POST['destinoY']) ? (int)$_POST['destinoY'] : 50;
        $icone = isset($_POST['icone']) ? $_POST['icone'] : '';
        
        $config = loadMapConfig();
        if (!isset($config[$mapa])) {
            $config[$mapa] = ['tileSize' => 40, 'entradas' => [], 'saidas' => [], 'bloqueados' => []];
        }
        if (!isset($config[$mapa]['saidas'])) {
            $config[$mapa]['saidas'] = [];
        }
        
        $existingIndex = null;
        foreach ($config[$mapa]['saidas'] as $i => $s) {
            if ((int)$s['x'] === $x && (int)$s['y'] === $y) {
                $existingIndex = $i;
                break;
            }
        }
        
        $saida = ['x' => $x, 'y' => $y, 'destino' => $destino, 'destinoX' => $destinoX, 'destinoY' => $destinoY];
        if ($icone) $saida['icone'] = $icone;
        
        if ($existingIndex !== null) {
            $config[$mapa]['saidas'][$existingIndex] = $saida;
        } else {
            $config[$mapa]['saidas'][] = $saida;
        }
        
        saveMapConfig($config);
        echo json_encode(['success' => true, 'config' => $config]);
        break;
        
    case 'removePortal':
        if (!$isAdmin) {
            echo json_encode(['success' => false, 'message' => 'Acesso negado']);
            break;
        }
        
        $mapa = isset($_POST['mapa']) ? $_POST['mapa'] : '';
        $x = isset($_POST['x']) ? (int)$_POST['x'] : 0;
        $y = isset($_POST['y']) ? (int)$_POST['y'] : 0;
        
        $config = loadMapConfig();
        
        if (isset($config[$mapa]['entradas'])) {
            $config[$mapa]['entradas'] = array_values(array_filter($config[$mapa]['entradas'], function($e) use ($x, $y) {
                return !((int)$e['x'] === $x && (int)$e['y'] === $y);
            }));
        }
        
        if (isset($config[$mapa]['saidas'])) {
            $config[$mapa]['saidas'] = array_values(array_filter($config[$mapa]['saidas'], function($s) use ($x, $y) {
                return !((int)$s['x'] === $x && (int)$s['y'] === $y);
            }));
        }
        
        saveMapConfig($config);
        echo json_encode(['success' => true, 'config' => $config]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Ação inválida']);
}
