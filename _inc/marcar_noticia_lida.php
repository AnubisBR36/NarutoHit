<?php
session_start();
require_once(__DIR__ . '/conexao.php');

header('Content-Type: application/json');

if (!isset($_SESSION['logado']) || !isset($_POST['noticia_id'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'Dados inválidos']);
    exit;
}

$usuario_id = (int)$_SESSION['logado'];
$noticia_id = (int)$_POST['noticia_id'];

try {
    $insIgnore = Database::isMysql() ? "INSERT IGNORE INTO" : "INSERT OR IGNORE INTO";
    $stmt = $conexao->prepare("
        $insIgnore noticia_lida (usuario_id, noticia_id) 
        VALUES (?, ?)
    ");
    $stmt->execute([$usuario_id, $noticia_id]);
    
    echo json_encode(['sucesso' => true]);
} catch (PDOException $e) {
    error_log("Erro ao marcar notícia como lida: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'erro' => 'Erro no servidor']);
}
