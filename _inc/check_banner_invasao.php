<?php
header('Content-Type: application/json');

// Iniciar sessão se não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
if(!isset($_SESSION['logado']) && !isset($_SESSION['userid']) && !isset($_SESSION['uid'])) {
    echo json_encode(['mostrar_banner' => false]);
    exit;
}

require_once('conexao.php');

try {
    // Determinar o ID do usuário logado
    $user_id = $_SESSION['logado'] ?? $_SESSION['userid'] ?? $_SESSION['uid'];
    
    $response = ['mostrar_banner' => false];
    
    // Obter servidor do usuário logado
    $stmt_srv = $conexao->prepare("SELECT servidor_id FROM usuarios WHERE id = ?");
    $stmt_srv->execute([$user_id]);
    $user_srv_row = $stmt_srv->fetch(PDO::FETCH_ASSOC);
    $user_servidor_id = isset($_SESSION['servidor_id']) ? (int)$_SESSION['servidor_id'] : (int)($user_srv_row['servidor_id'] ?? 0);

    // Verificar invasão ativa para banner de início (apenas do servidor do usuário)
    $stmt = $conexao->prepare("
        SELECT i.*, 
               (SELECT COUNT(*) FROM banner_invasao_visualizado 
                WHERE usuario_id = ? AND invasao_id = i.id AND tipo_banner = 'inicio') as ja_viu_inicio
        FROM invasoes i 
        WHERE i.status = 'ativa' AND i.servidor_id = ?
        ORDER BY i.id DESC 
        LIMIT 1
    ");
    $stmt->execute([$user_id, $user_servidor_id]);
    $invasao_ativa = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Mapa de nome do invasor para arquivo de imagem do popup
    $mapa_imagens_popup = [
        'Uma Cauda'   => 'Uma.png',
        'Duas Caudas' => 'Duas.png',
        'Tres Caudas' => 'Tres.png',
        'Três Caudas' => 'Tres.png',
        'Quatro Caudas' => 'Quatro.png',
        'Cinco Caudas'  => 'Cinco.png',
        'Seis Caudas'   => 'Seis.png',
        'Sete Caudas'   => 'Sete.png',
        'Oito Caudas'   => 'Oito.png',
        'Nove Caudas'   => 'Nove.png',
        'Dez Caudas'    => 'Dez.png',
    ];

    if($invasao_ativa && $invasao_ativa['ja_viu_inicio'] == 0) {
        // Usuário ainda não viu o banner de início desta invasão
        $imagem_popup = $mapa_imagens_popup[$invasao_ativa['nome_invasor']] ?? 'Uma.png';
        $response = [
            'mostrar_banner' => true,
            'tipo' => 'inicio',
            'invasao_id' => $invasao_ativa['id'],
            'nome_invasor' => $invasao_ativa['nome_invasor'],
            'imagem_popup' => $imagem_popup,
            'premio_yens' => number_format($invasao_ativa['premio_yens']),
            'bonus_vila' => $invasao_ativa['bonus_vila']
        ];
    } else {
        // Verificar invasão finalizada recente para banner de fim (apenas do servidor do usuário)
        $stmt = $conexao->prepare("
            SELECT i.*, u.usuario as vencedor_nome,
                   (SELECT COUNT(*) FROM banner_invasao_visualizado 
                    WHERE usuario_id = ? AND invasao_id = i.id AND tipo_banner = 'fim') as ja_viu_fim
            FROM invasoes i 
            LEFT JOIN usuarios u ON i.vencedor_id = u.id
            WHERE i.status = 'finalizada' 
            AND i.servidor_id = ?
            AND i.vencedor_id IS NOT NULL
            AND i.data_fim > " . Database::dateOffsetLiteral('-', 1, 'hours') . "
            ORDER BY i.id DESC 
            LIMIT 1
        ");
        $stmt->execute([$user_id, $user_servidor_id]);
        $invasao_finalizada = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($invasao_finalizada && $invasao_finalizada['ja_viu_fim'] == 0) {
            // Buscar nome da vila vencedora
            $vilas = [1 => 'Folha', 2 => 'Areia', 3 => 'Névoa', 4 => 'Pedra', 5 => 'Nuvem', 6 => 'Som', 7 => 'Chuva', 8 => 'Akatsuki'];
            
            $stmt_vila = $conexao->prepare("SELECT vila FROM usuarios WHERE id = ?");
            $stmt_vila->execute([$invasao_finalizada['vencedor_id']]);
            $vila_data = $stmt_vila->fetch(PDO::FETCH_ASSOC);
            $vila_vencedora = $vilas[$vila_data['vila']] ?? 'Desconhecida';
            
            $imagem_popup_fim = $mapa_imagens_popup[$invasao_finalizada['nome_invasor']] ?? 'Uma.png';
            $response = [
                'mostrar_banner' => true,
                'tipo' => 'fim',
                'invasao_id' => $invasao_finalizada['id'],
                'nome_invasor' => $invasao_finalizada['nome_invasor'],
                'imagem_popup' => $imagem_popup_fim,
                'vencedor_nome' => $invasao_finalizada['vencedor_nome'],
                'vila_vencedora' => $vila_vencedora,
                'premio_yens' => number_format($invasao_finalizada['premio_yens']),
                'bonus_vila' => $invasao_finalizada['bonus_vila']
            ];
        }
    }
    
    echo json_encode($response);
    
} catch(Exception $e) {
    echo json_encode(['mostrar_banner' => false, 'erro' => $e->getMessage()]);
}
?>
