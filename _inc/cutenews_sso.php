<?php
// Sistema de SSO para integração com CuteNews
// Este arquivo deve ser incluído após o login bem-sucedido no site principal

// Define um segredo compartilhado (DEVE SER O MESMO do auto_login_admin.php)
if(!defined('CUTENEWS_SSO_SECRET')) {
    define('CUTENEWS_SSO_SECRET', 'AnubisServeRP_CuteNews_Secret_2025_' . md5(__DIR__ . '/../news'));
}

/**
 * Gera um token SSO seguro para integração com CuteNews
 * 
 * @param int $user_id ID do usuário
 * @param string $username Nome de usuário
 * @param int $is_admin 1 se for admin, 0 caso contrário
 * @return string Token HMAC SHA-256
 */
function cutenews_generate_sso_token($user_id, $username, $is_admin) {
    $data = $user_id . '|' . $username . '|' . $is_admin . '|' . date('Y-m-d');
    return hash_hmac('sha256', $data, CUTENEWS_SSO_SECRET);
}

/**
 * Configura o token SSO na sessão após login bem-sucedido
 * Deve ser chamado após definir $_SESSION['logado']
 * 
 * @param int $user_id ID do usuário
 * @param string $username Nome de usuário  
 * @param int $is_admin 1 se for admin, 0 caso contrário
 */
function cutenews_set_sso_token($user_id, $username, $is_admin) {
    $_SESSION['sso_token'] = cutenews_generate_sso_token($user_id, $username, $is_admin);
}

// NOTA: Não auto-gerar tokens para sessões existentes
// Tokens só devem ser gerados durante o processo de login autenticado
?>
