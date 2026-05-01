<?php
// Iniciar buffer de saída para evitar problemas com headers
ob_start();

// Carrega utilitários de segurança (configura cookies de sessão e envia headers
// ANTES de qualquer session_start ou saída).
require_once __DIR__ . '/security.php';

// Iniciar sessão apenas se não estiver iniciada (já configurada por security.php)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {
    // Conexão centralizada (MySQL — ver config/database.php).
    require_once __DIR__ . '/Database.php';
    $conexao = Database::conn();
} catch (PDOException $e) {
    // Em caso de erro, redirecionar para o instalador
    if (file_exists(__DIR__ . '/../install/install.php')) {
        header('Location: install/install.php');
        exit;
    }
    if (!file_exists('setup.php')) {
        die("Erro de conexão com o banco de dados: " . $e->getMessage());
    }
    header("Location: setup.php");
    exit;
}

// O esquema completo (CREATE TABLE / colunas / tipos) vive em database.sql,
// que é importado por install/install.php no primeiro acesso. Nenhuma
// migração runtime acontece aqui — qualquer coluna nova deve ser adicionada
// em database.sql e o instalador re-executado (ou aplicada manualmente via
// phpMyAdmin/console MySQL).

// Camada de compatibilidade para código legado que chama mysql_*
require_once __DIR__ . '/mysql_compat.php';

// SSO para o CuteNews
require_once __DIR__ . '/cutenews_sso.php';

// Tick de backup automático (verifica se está na hora; se não, retorna em microsegundos)
if (isset($conexao) && $conexao instanceof PDO) {
    @require_once __DIR__ . '/backup_engine.php';
    if (function_exists('backup_tick_check')) {
        backup_tick_check($conexao);
    }
}
?>
