<?php
if (!defined('INDEX')) die('Acesso negado');

define('RATE_LIMIT_MAX_FALHAS',    5);     // tentativas antes do bloqueio
define('RATE_LIMIT_BLOQUEIO_SEG',  900);   // duração do bloqueio em segundos (15 min)

function _rl_tabela($conexao) {
    static $criada = false;
    if ($criada) return;
    $conexao->exec("CREATE TABLE IF NOT EXISTS login_tentativas (
        ip          BIGINT UNSIGNED NOT NULL,
        tentativas  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        primeiro_erro DATETIME NOT NULL,
        ultimo_erro   DATETIME NOT NULL,
        bloqueado_ate DATETIME DEFAULT NULL,
        PRIMARY KEY (ip),
        INDEX idx_bloqueio (bloqueado_ate)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $criada = true;
}

function rl_ip_num() {
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $num = ip2long($ip);
    return ($num !== false) ? sprintf('%u', $num) : 0;
}

function rl_verificar($conexao) {
    _rl_tabela($conexao);
    $ip = rl_ip_num();
    if (!$ip) return ['bloqueado' => false, 'segundos' => 0, 'tentativas' => 0];

    $stmt = $conexao->prepare(
        "SELECT tentativas, bloqueado_ate FROM login_tentativas WHERE ip = ?"
    );
    $stmt->execute([$ip]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return ['bloqueado' => false, 'segundos' => 0, 'tentativas' => 0];

    if (!empty($row['bloqueado_ate'])) {
        $ate   = strtotime($row['bloqueado_ate']);
        $agora = time();
        if ($agora < $ate) {
            return [
                'bloqueado'  => true,
                'segundos'   => $ate - $agora,
                'tentativas' => (int)$row['tentativas'],
            ];
        }
        // Bloqueio expirado — limpar
        $conexao->prepare("DELETE FROM login_tentativas WHERE ip = ?")->execute([$ip]);
    }
    return ['bloqueado' => false, 'segundos' => 0, 'tentativas' => (int)($row['tentativas'] ?? 0)];
}

function rl_registrar_falha($conexao) {
    _rl_tabela($conexao);
    $ip  = rl_ip_num();
    if (!$ip) return;
    $max = RATE_LIMIT_MAX_FALHAS;
    $bl  = RATE_LIMIT_BLOQUEIO_SEG;
    $stmt = $conexao->prepare(
        "INSERT INTO login_tentativas (ip, tentativas, primeiro_erro, ultimo_erro)
         VALUES (?, 1, NOW(), NOW())
         ON DUPLICATE KEY UPDATE
             tentativas    = tentativas + 1,
             ultimo_erro   = NOW(),
             bloqueado_ate = IF(tentativas + 1 >= ?,
                                DATE_ADD(NOW(), INTERVAL ? SECOND),
                                bloqueado_ate)"
    );
    $stmt->execute([$ip, $max, $bl]);
}

function rl_limpar($conexao) {
    _rl_tabela($conexao);
    $ip = rl_ip_num();
    if (!$ip) return;
    $conexao->prepare("DELETE FROM login_tentativas WHERE ip = ?")->execute([$ip]);
}

function rl_formatar_tempo($segundos) {
    $segundos = max(0, (int)$segundos);
    if ($segundos >= 3600) {
        $h = floor($segundos / 3600);
        $m = floor(($segundos % 3600) / 60);
        return $h . 'h' . ($m > 0 ? ' ' . $m . 'min' : '');
    }
    if ($segundos >= 60) return floor($segundos / 60) . ' minuto(s)';
    return $segundos . ' segundo(s)';
}

function rl_listar_bloqueados($conexao) {
    _rl_tabela($conexao);
    $stmt = $conexao->query(
        "SELECT ip, tentativas, primeiro_erro, ultimo_erro, bloqueado_ate
         FROM login_tentativas
         WHERE bloqueado_ate IS NOT NULL AND bloqueado_ate > NOW()
         ORDER BY bloqueado_ate DESC"
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function rl_desbloquear_ip($conexao, $ip_num) {
    _rl_tabela($conexao);
    $conexao->prepare("DELETE FROM login_tentativas WHERE ip = ?")->execute([(int)$ip_num]);
}

function rl_limpar_todos($conexao) {
    _rl_tabela($conexao);
    $conexao->exec("DELETE FROM login_tentativas WHERE bloqueado_ate IS NOT NULL");
}

function rl_ip_num_to_str($ip_num) {
    $signed = $ip_num > 2147483647 ? $ip_num - 4294967296 : $ip_num;
    return long2ip($signed);
}
