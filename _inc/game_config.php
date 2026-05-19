<?php
/**
 * Helper de configurações de jogo.
 * Lê da tabela `configuracoes` (chave/valor) com cache por requisição.
 *
 * Uso: $v = get_gc('caca_vip_bonus_exp', 3);
 */
if (!function_exists('get_gc')) {
    function get_gc(string $key, $default = null) {
        global $conexao;
        static $cache = null;
        if ($cache === null) {
            $cache = [];
            try {
                $rows = $conexao->query("SELECT nome, valor FROM configuracoes")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $r) $cache[$r['nome']] = $r['valor'];
            } catch (Exception $e) {}
        }
        return array_key_exists($key, $cache) ? $cache[$key] : $default;
    }
}
