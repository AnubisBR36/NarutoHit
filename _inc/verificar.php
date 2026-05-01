<?php
// Verificar se existe sessão ativa
if(!isset($_SESSION['logado']) || empty($_SESSION['logado'])) {
    $db = false;
    return;
}

// Buscar dados do usuário logado
try {
    $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['logado']]);
    $db = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$db) {
        $_SESSION = array();
        session_destroy();
        $db = false;
        return;
    }
} catch (PDOException $e) {
    $db = false;
    return;
}

$atual=date('Y-m-d H:i:s');
if($db['missao']>0){ echo "<script>self.location='?p=busymission'</script>"; exit; }
if($db['hunt']>0){ echo "<script>self.location='?p=busyhunt'</script>"; exit; }
if($db['treino']>0){ echo "<script>self.location='?p=busytrain'</script>"; exit; }
if((isset($_GET['p']))&&($_GET['p']<>'train')){
        $is_adm_verif = isset($db['adm']) && ($db['adm']==1 || $db['adm']==2);
        if(!$is_adm_verif && date('Y-m-d H:i:s')<$db['penalidade_fim']){ echo "<script>self.location='?p=penalty'</script>"; exit; }
}
try {
    $stmt = $conexao->prepare("SELECT count(id) AS conta FROM salas WHERE usuarioid=:usuarioid AND fim>:atual");
    $stmt->bindParam(':usuarioid', $db['id'], PDO::PARAM_INT);
    $stmt->bindParam(':atual', $atual, PDO::PARAM_STR);
    $stmt->execute();
    $dbs = $stmt->fetch(PDO::FETCH_ASSOC);
    $num_salas = $stmt->rowCount();

    // Se o usuário tem uma sala ativa mas não está na school, room, learn, elements, schooltrain, chakra, ou discover
    $school_pages = array('school', 'room', 'learn', 'elements', 'schooltrain', 'chakra', 'discover');
    $current_page = isset($_GET['p']) ? $_GET['p'] : '';
    
    if($dbs && ($num_salas > 0) && !in_array($current_page, $school_pages)){
        // Liberar a sala automaticamente
        try {
            $clear_stmt = $conexao->prepare("UPDATE salas SET usuarioid=0, fim='0000-00-00 00:00:00' WHERE usuarioid=?");
            $clear_stmt->execute([$db['id']]);
        } catch (PDOException $e) {
            // Handle clear error silently
        }
    }
} catch (PDOException $e) {
    // Handle database error (e.g., log it, display a user-friendly message)
    echo "Database error: " . $e->getMessage();
    exit; // Or redirect to an error page
}

// Verificar restrição de viagem - jogador fora da vila não pode acessar outras páginas
$PLAYERS_FILE = __DIR__ . '/../map_players.json';
$current_page = isset($_GET['p']) ? $_GET['p'] : '';

// Páginas permitidas mesmo fora da vila
$allowed_pages_outside = array('mapa', 'logout', 'map_api', 'chat', 'mensagem', 'mensagens', 'index', '');

// Verificar se não é uma página permitida
if (!empty($current_page) && !in_array($current_page, $allowed_pages_outside)) {
    // Por padrão, permitir acesso - só bloquear se condições específicas forem atendidas
    $shouldBlock = false;
    $blockMessage = '';
    
    // Verificar se o arquivo de jogadores existe e é legível
    if (file_exists($PLAYERS_FILE) && is_readable($PLAYERS_FILE)) {
        $content = @file_get_contents($PLAYERS_FILE);
        
        if ($content !== false && strlen($content) > 0) {
            $players = @json_decode($content, true);
            
            // Só verificar restrições se temos dados COMPLETOS e RECENTES do jogador
            $playerData = (is_array($players) && isset($db['id']) && isset($players[$db['id']])) ? $players[$db['id']] : null;
            $hasValidData = $playerData && !empty($playerData['mapaAtual']) && isset($playerData['lastUpdate']);
            $isRecent = $hasValidData && (time() - $playerData['lastUpdate']) < 86400; // 24 horas
            
            if ($hasValidData && $isRecent) {
                $playerMap = $playerData['mapaAtual'];
                
                // Mapa da vila do jogador
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
                
                // Alianças entre vilas
                $aliancas = [
                    'Vila Oculta da Folha' => ['Vila Oculta da Areia'],
                    'Vila Oculta da Areia' => ['Vila Oculta da Folha'],
                    'Vila Oculta da Névoa' => [],
                    'Vila Oculta da Nuvem' => [],
                    'Vila Oculta da Pedra' => [],
                    'Vila Oculta do Som' => [],
                    'Vila Oculta da Chuva' => [],
                    'Vila Akatsuki' => []
                ];
                
                $playerVila = isset($db['vila']) ? (int)$db['vila'] : 1;
                $homeVila = isset($vilaMapa[$playerVila]) ? $vilaMapa[$playerVila] : 'Vila Oculta da Folha';
                
                // Verificar se está no mapa mundial
                if ($playerMap === 'MapaBase') {
                    $shouldBlock = true;
                    $blockMessage = 'Você precisa retornar à sua vila antes de acessar esta área!';
                } else {
                    // Verificar se está em vila inimiga
                    $isHome = ($playerMap === $homeVila);
                    $isAllied = isset($aliancas[$homeVila]) && in_array($playerMap, $aliancas[$homeVila]);
                    
                    if (!$isHome && !$isAllied) {
                        $shouldBlock = true;
                        $blockMessage = 'Você está em território inimigo! Retorne à sua vila ou vila aliada.';
                    }
                }
            }
            // Se não tem dados do jogador no arquivo, permitir acesso
        }
        // Se arquivo está vazio ou corrompido, permitir acesso
    }
    // Se arquivo não existe, permitir acesso
    
    // Aplicar bloqueio se necessário
    if ($shouldBlock && !empty($blockMessage)) {
        echo "<script>alert('" . addslashes($blockMessage) . "'); self.location='?p=mapa';</script>";
        exit;
    }
}
?>