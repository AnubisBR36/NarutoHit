<?php
session_start();
require_once(__DIR__ . '/conexao.php');

header('Content-Type: application/json');

if (!isset($_SESSION['logado'])) {
    echo json_encode(['tem_nova' => false]);
    exit;
}

$usuario_id = (int)$_SESSION['logado'];

try {
    $stmt = $conexao->prepare("
        SELECT n.id, n.titulo, n.conteudo, n.data_criacao 
        FROM noticias n
        WHERE n.id NOT IN (
            SELECT noticia_id 
            FROM noticia_lida 
            WHERE usuario_id = ?
        )
        ORDER BY n.data_criacao DESC
        LIMIT 1
    ");
    $stmt->execute([$usuario_id]);
    $noticia = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($noticia) {
        echo json_encode([
            'tem_nova' => true,
            'noticia' => [
                'id' => $noticia['id'],
                'titulo' => $noticia['titulo'],
                'data' => date('d/m/Y H:i', strtotime($noticia['data_criacao']))
            ]
        ]);
    } else {
        echo json_encode(['tem_nova' => false]);
    }
} catch (PDOException $e) {
    error_log("Erro ao verificar notícia nova: " . $e->getMessage());
    echo json_encode(['tem_nova' => false]);
}
