<?php
/**
 * _inc/chakra_regen.php
 *
 * Regeneração passiva de chakra (energia) ao longo do tempo.
 * Recupera 5% da energia máxima por hora, calculado por horas completas
 * desde a última vez que o sistema rodou para este jogador.
 *
 * Incluído no pipeline principal de index.php logo após $db ser carregado.
 * Silencioso: não exibe nada, apenas atualiza o banco se necessário.
 */

if (!isset($db['id']) || !isset($conexao)) return;

// Corrigir overflow: se energia > energiamax, normalizar no banco e em memória
if ((int)$db['energia'] > (int)$db['energiamax'] && (int)$db['energiamax'] > 0) {
    $conexao->prepare("UPDATE usuarios SET energia = energiamax WHERE id = ? AND energia > energiamax")
            ->execute([$db['id']]);
    $db['energia'] = $db['energiamax'];
}

// Nada a recuperar se já está cheio
if ((int)$db['energia'] >= (int)$db['energiamax']) return;

define('CHAKRA_REGEN_PCT_POR_HORA', 0.03); // 3% por hora

$agora = time();
$ultima = !empty($db['chakra_regen_ultima']) ? strtotime($db['chakra_regen_ultima']) : null;

// Se nunca rodou, registra agora e sai (começa a contar da próxima vez)
if ($ultima === null || $ultima === false) {
    $conexao->prepare("UPDATE usuarios SET chakra_regen_ultima=? WHERE id=?")
            ->execute([date('Y-m-d H:i:s', $agora), $db['id']]);
    $db['chakra_regen_ultima'] = date('Y-m-d H:i:s', $agora);
    return;
}

$segundos_passados = $agora - $ultima;
$horas_completas   = (int)floor($segundos_passados / 3600);

// Menos de 1 hora passou, nada a fazer
if ($horas_completas <= 0) return;

$regen_por_hora = max(1, (int)floor($db['energiamax'] * CHAKRA_REGEN_PCT_POR_HORA));
$total_regen    = $horas_completas * $regen_por_hora;

$nova_energia = min((int)$db['energiamax'], (int)$db['energia'] + $total_regen);

// Avançar o timestamp apenas pelas horas consumidas (preserva segundos extras)
$nova_ultima = date('Y-m-d H:i:s', $ultima + ($horas_completas * 3600));

$conexao->prepare("UPDATE usuarios SET energia=?, chakra_regen_ultima=? WHERE id=?")
        ->execute([$nova_energia, $nova_ultima, $db['id']]);

// Atualiza $db em memória para o resto da requisição refletir o novo valor
$db['energia']           = $nova_energia;
$db['chakra_regen_ultima'] = $nova_ultima;
