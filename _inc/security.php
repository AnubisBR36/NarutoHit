<?php
// Carrega configuração de marca/identidade do servidor (define BRAND_NAME e nome_servidor()).
require_once __DIR__ . '/../config/brand.php';
/**
 * security.php — utilitários de segurança centralizados.
 *
 * Carregado por _inc/conexao.php antes de session_start().
 * Fornece:
 *   - Configuração endurecida de cookies de sessão
 *   - Headers HTTP de segurança (CSP, X-Frame-Options, etc.)
 *   - CSRF: csrf_token(), csrf_field(), csrf_validar()
 *   - Sanitização: e() (escape XSS), input_int(), input_str()
 *   - Senhas: senha_hash(), senha_verificar(), senha_precisa_rehash()
 *   - Rate limit simples por IP+ação: rate_limit_check()
 */

// ---------- Sessão segura ----------
if (session_status() === PHP_SESSION_NONE) {
    $secure = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    );
    $params = [
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax', // Strict quebra fluxos com redirect externo (recaptcha/sso)
    ];
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params($params);
    } else {
        session_set_cookie_params(
            $params['lifetime'], $params['path'] . '; samesite=' . $params['samesite'],
            $params['domain'], $params['secure'], $params['httponly']
        );
    }
    @ini_set('session.use_only_cookies', '1');
    @ini_set('session.use_strict_mode', '1');
}

// ---------- Headers de segurança ----------
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    if (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    ) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    // CSP intencionalmente permissivo (o jogo usa scripts/imagens inline antigos).
    // Apertar isso é trabalho de longo prazo — bloquear plugins/objects e mixed content já ajuda.
    header("Content-Security-Policy: default-src 'self' https: data: blob: 'unsafe-inline' 'unsafe-eval'; object-src 'none'; base-uri 'self'; frame-ancestors 'self'");
}

// ---------- Escape XSS ----------
if (!function_exists('e')) {
    function e($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

// ---------- Input helpers ----------
if (!function_exists('input_int')) {
    function input_int($v, int $default = 0): int {
        return is_numeric($v) ? (int)$v : $default;
    }
}
if (!function_exists('input_str')) {
    function input_str($v, int $max = 255): string {
        $s = is_scalar($v) ? trim((string)$v) : '';
        if ($max > 0 && mb_strlen($s) > $max) $s = mb_substr($s, 0, $max);
        return $s;
    }
}

// ---------- CSRF ----------
if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }
}
if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '" />';
    }
}
if (!function_exists('csrf_validar')) {
    function csrf_validar(?string $token = null): bool {
        $t = $token ?? ($_POST['_csrf'] ?? $_GET['_csrf'] ?? '');
        return !empty($_SESSION['_csrf']) && is_string($t) && hash_equals($_SESSION['_csrf'], $t);
    }
}

// ---------- Senhas ----------
if (!function_exists('senha_hash')) {
    function senha_hash(string $plano): string {
        return password_hash($plano, PASSWORD_DEFAULT);
    }
}
if (!function_exists('senha_verificar')) {
    /**
     * Aceita hash moderno (password_hash) ou legado (md5 de 32 chars).
     * Retorna true se a senha confere por qualquer um dos dois esquemas.
     */
    function senha_verificar(string $plano, ?string $hash): bool {
        if ($hash === null || $hash === '') return false;
        // Hash moderno começa com $2y$, $argon2..., etc.
        if (strlen($hash) >= 60 && $hash[0] === '$') {
            return password_verify($plano, $hash);
        }
        // Legado: md5 hex de 32 chars
        if (strlen($hash) === 32 && ctype_xdigit($hash)) {
            return hash_equals($hash, md5($plano));
        }
        return false;
    }
}
if (!function_exists('senha_precisa_rehash')) {
    function senha_precisa_rehash(string $hash): bool {
        if ($hash === '' || strlen($hash) < 60 || $hash[0] !== '$') return true; // legado
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }
}

// ---------- Rate limit (anti brute force, simples por sessão+IP) ----------
if (!function_exists('rate_limit_check')) {
    /**
     * Devolve true se a ação está dentro do limite (pode prosseguir).
     * Usa $_SESSION para contar tentativas (mais leve que tabela).
     * @param string $acao  identificador da ação (ex: 'login')
     * @param int    $max   nº máx. de tentativas dentro da janela
     * @param int    $janela segundos da janela
     */
    function rate_limit_check(string $acao, int $max = 8, int $janela = 300): bool {
        $key = '_rl_' . $acao;
        $agora = time();
        $rec = $_SESSION[$key] ?? ['c' => 0, 't' => $agora];
        if ($agora - $rec['t'] > $janela) {
            $rec = ['c' => 0, 't' => $agora];
        }
        $rec['c']++;
        $_SESSION[$key] = $rec;
        return $rec['c'] <= $max;
    }
    function rate_limit_reset(string $acao): void {
        unset($_SESSION['_rl_' . $acao]);
    }
}
