<?php
header('Content-Type: application/json');

// Iniciar sessão se não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
if(!isset($_SESSION['logado']) && !isset($_SESSION['userid']) && !isset($_SESSION['uid'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'Usuário não logado']);
    exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'erro' => 'Método não permitido']);
    exit;
}

require_once('conexao.php');

try {
    // Determinar o ID do usuário logado
    $user_id = $_SESSION['logado'] ?? $_SESSION['userid'] ?? $_SESSION['uid'];
    
    // Ler dados JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if(!isset($input['invasao_id']) || !isset($input['tipo'])) {
        echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos']);
        exit;
    }
    
    $invasao_id = (int)$input['invasao_id'];
    $tipo = $input['tipo'];
    
    // Validar tipo
    if(!in_array($tipo, ['inicio', 'fim'])) {
        echo json_encode(['sucesso' => false, 'erro' => 'Tipo inválido']);
        exit;
    }
    
    // Inserir registro de visualização (ou ignorar se já existe)
    $insIgnore = Database::isMysql() ? "INSERT IGNORE INTO" : "INSERT OR IGNORE INTO";
    $stmt = $conexao->prepare("
        $insIgnore banner_invasao_visualizado 
        (usuario_id, invasao_id, tipo_banner, data_visualizacao) 
        VALUES (?, ?, ?, CURRENT_TIMESTAMP)
    ");
    
    $resultado = $stmt->execute([$user_id, $invasao_id, $tipo]);
    
    if($resultado) {
        echo json_encode(['sucesso' => true]);
    } else {
        echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar']);
    }
    
} catch(Exception $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
?>
