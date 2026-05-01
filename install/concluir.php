<?php
/**
 * concluir.php — Apaga a pasta install/ após instalação bem-sucedida
 * e leva o jogador para a tela de login pela URL limpa (?p=login).
 */
session_start();
if (empty($_SESSION['_install']['sucesso'])) {
    header('Location: install.php');
    exit;
}

// Bloqueio por IP — só o IP que iniciou a instalação pode concluir/apagar.
// Normaliza loopbacks (127.0.0.1 ⇄ ::1 ⇄ ::ffff:127.0.0.1) para evitar
// 403 quando o cliente alterna entre IPv4 e IPv6 no mesmo localhost.
function install_concluir_normalize_ip(string $ip): string {
    if ($ip === '' || $ip === '0.0.0.0') return $ip;
    if (stripos($ip, '::ffff:') === 0) {
        $maybe = substr($ip, 7);
        if (filter_var($maybe, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) $ip = $maybe;
    }
    if ($ip === '::1' || $ip === '127.0.0.1' || strpos($ip, '127.') === 0) return 'loopback';
    return $ip;
}
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if (!empty($_SESSION['_install']['ip_lock'])
    && install_concluir_normalize_ip($_SESSION['_install']['ip_lock'])
        !== install_concluir_normalize_ip($clientIp)) {
    http_response_code(403);
    echo '<h1>403 - Acesso negado</h1><p>Apenas o IP que iniciou a instalação pode concluí-la.</p>';
    exit;
}

// Registra encerramento no log antes de o arquivo ser apagado
$logPath = __DIR__ . '/install.log';
@file_put_contents(
    $logPath,
    sprintf("[%s] [%s] CONCLUIR - removendo pasta install/\n", date('Y-m-d H:i:s'), $clientIp),
    FILE_APPEND | LOCK_EX
);

$dir = __DIR__;
$erros = [];

// Apaga arquivos do diretório install/ (exceto este, deletado por último)
$selfBase = basename(__FILE__);
$it = new DirectoryIterator($dir);
foreach ($it as $f) {
    if ($f->isDot()) continue;
    if ($f->isFile() && $f->getFilename() === $selfBase) continue;
    $p = $f->getPathname();
    if ($f->isFile()) {
        if (!@unlink($p)) $erros[] = "Falha ao apagar: $p";
    }
}

// Tenta apagar a si mesmo e depois o diretório
$selfPath = __FILE__;

// Limpa a sessão antes de qualquer redirecionamento
$donationShown = true;
unset($_SESSION['_install']);

// Calcula a raiz pública do app (sobe um nível a partir de /install/).
// Usa SCRIPT_NAME para suportar instalações em subpastas (ex.: /meugame).
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/install/concluir.php')), '/');
$rootPath  = preg_replace('#/install$#', '', $scriptDir);
if ($rootPath === false || $rootPath === null) $rootPath = '';

// URL absoluta com scheme/host derivados de proxy quando disponível.
$scheme = 'http';
if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') $scheme = 'https';
elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) $scheme = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
elseif (!empty($_SERVER['REQUEST_SCHEME'])) $scheme = strtolower($_SERVER['REQUEST_SCHEME']);

$host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
$host = trim(explode(',', $host)[0]);

// Sempre usar "index.php?p=login" (NÃO "?p=login" sozinho) porque alguns
// navegadores percent-encodam o "?" inicial em URLs relativas, gerando
// "/%3Fp=login" que o servidor trata como arquivo literal (404/403).
$loginUrl = $scheme . '://' . $host . $rootPath . '/index.php?p=login';

// Tenta apagar concluir.php e a pasta install/ depois que a resposta sair.
// Se falhar (permissão, etc.) o usuário ainda chega no login normalmente.
@register_shutdown_function(function() use ($selfPath, $dir) {
    @unlink($selfPath);
    @clearstatcache();
    @rmdir($dir);
});

// Redirect server-side direto — sem tela intermediária, sem meta-refresh.
// Evita o bug do percent-encode do "?" no Brave/Chromium.
header('Location: ' . $loginUrl, true, 302);
exit;
?>
