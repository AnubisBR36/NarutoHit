<?php
/**
 * Helper de autenticação GM para páginas externas do painel.
 * Inclua DEPOIS de carregar $conexao, $usuario_logado e $user_id.
 *
 * Uso:
 *   $modulo_necessario = 'equipamentos'; // chave do módulo
 *   require_once('_gm_auth.php');
 */
if(!isset($modulo_necessario)) $modulo_necessario = '';

$adm_nivel = (int)($usuario_logado['adm'] ?? 0);

if($adm_nivel == 0) {
    header('Location: adm.php'); exit;
}

$is_admin_ext = ($adm_nivel == 1);
$is_mod_ext   = ($adm_nivel == 2);

if($is_mod_ext && !empty($modulo_necessario)) {
    $permitido_gm = false;
    try {
        $stmt_gm_perm = $conexao->prepare(
            "SELECT permitido FROM gm_permissions WHERE usuario_id = ? AND modulo = ?"
        );
        $stmt_gm_perm->execute([$user_id, $modulo_necessario]);
        $perm_row = $stmt_gm_perm->fetch(PDO::FETCH_ASSOC);
        $permitido_gm = ($perm_row && (bool)$perm_row['permitido']);
    } catch(Exception $e) {}

    if(!$permitido_gm) {
        header('Location: adm.php'); exit;
    }
}
