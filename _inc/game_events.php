<?php
/**
 * Helper de multiplicadores de eventos temporários de bônus.
 * Combina multiplicadores globais (configuracoes) + eventos ativos (eventos_bonus).
 *
 * Uso: $m = get_event_mults();
 *      $exp  = (int)round($exp  * $m['exp']);
 *      $yens = (int)round($yens * $m['yens']);
 *      $chance_drop = min(50, (int)round($chance * $m['drop']));
 */
if (!function_exists('get_event_mults')) {
    function get_event_mults(): array {
        global $conexao;
        static $mults = null;
        if ($mults !== null) return $mults;

        // Base: multiplicadores globais de config (padrão 1.0 = sem alteração)
        $mults = [
            'exp'  => max(0.1, (float)get_gc('global_exp_mult',  '1.0')),
            'yens' => max(0.1, (float)get_gc('global_yens_mult', '1.0')),
            'drop' => max(0.1, (float)get_gc('global_drop_mult', '1.0')),
        ];

        // Empilhar eventos temporários ativos (multiplicativamente)
        try {
            $now = date('Y-m-d H:i:s');
            $rows = $conexao->query(
                "SELECT mult_exp, mult_yens, mult_drop FROM eventos_bonus
                  WHERE inicio <= '$now' AND fim >= '$now'"
            )->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $mults['exp']  *= max(0.1, (float)$r['mult_exp']);
                $mults['yens'] *= max(0.1, (float)$r['mult_yens']);
                $mults['drop'] *= max(0.1, (float)$r['mult_drop']);
            }
        } catch (Exception $e) {}

        return $mults;
    }
}
