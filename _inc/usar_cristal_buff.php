<?php require_once('trava.php'); ?>
<?php
/**
 * ?p=usar_cristal_buff&id=<inv_id>
 *
 * Consome um Cristal de Buff do inventário do jogador e aplica o efeito
 * conforme `table_usaveis.tipo_efeito` + `table_usaveis.valor_efeito`
 * (campos cadastrados pelo ADM em `adm/cristal.php`).
 *
 * Tipos de efeito suportados:
 *   - taijutsu / ninjutsu / genjutsu  → grava em buff_ativos (pct + horas)
 *   - cura_total                       → restaura HP/Chakra ao máximo (instantâneo)
 *
 * Compatibilidade legado: cristais antigos cadastrados sem tipo_efeito
 * usam o nome ("Cristal de Taijutsu", etc.) como fallback, +5% por 3h.
 */

if (!isset($_GET['id'])) {
    echo "<script>self.location='?p=inventory'</script>"; exit;
}
$usavel_id = (int)$_GET['id'];

// Busca o cristal no inventário do jogador (já com tipo_efeito/valor_efeito).
try {
    $stmt = $conexao->prepare("
        SELECT u.id, t.id AS itemid, t.nome, t.imagem, t.descricao, t.categoria,
               t.tipo_efeito, t.valor_efeito
        FROM usaveis u
        JOIN table_usaveis t ON u.itemid = t.id
        WHERE u.id = ? AND u.usuarioid = ? AND t.categoria = 'cristal_buff'
    ");
    $stmt->execute([$usavel_id, $db['id']]);
    $cristal = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $cristal = null; }

if (!$cristal) {
    echo "<script>self.location='?p=inventory'</script>"; exit;
}

// ── Decide qual efeito aplicar ─────────────────────────────────────────────
$tipo_efeito = $cristal['tipo_efeito'] ?? null;
$valor_json  = $cristal['valor_efeito'] ?? null;
$params      = [];
if ($valor_json) {
    $tmp = json_decode($valor_json, true);
    if (is_array($tmp)) $params = $tmp;
}

// Fallback legado (cristais cadastrados antes da coluna tipo_efeito existir)
if (!$tipo_efeito) {
    $tipo_legacy = [
        'Cristal de Taijutsu' => 'taijutsu',
        'Cristal de Ninjutsu' => 'ninjutsu',
        'Cristal de Genjutsu' => 'genjutsu',
    ];
    $tipo_efeito = $tipo_legacy[$cristal['nome']] ?? null;
    if ($tipo_efeito) $params = ['pct'=>5, 'horas'=>3]; // valores antigos
}

if (!$tipo_efeito) {
    echo "<script>alert('Este cristal não tem efeito cadastrado. Avise um administrador.'); self.location='?p=inventory'</script>"; exit;
}

// ── Aplica o efeito ────────────────────────────────────────────────────────
$resumo_titulo = '';
$resumo_corpo  = '';
$resumo_cor    = '#FFD700';

try {
    $conexao->beginTransaction();

    if (in_array($tipo_efeito, ['taijutsu','ninjutsu','genjutsu'], true)) {
        // Buff temporário em buff_ativos
        $pct   = isset($params['pct'])   ? max(1, min(1000, (int)$params['pct']))   : 5;
        $horas = isset($params['horas']) ? max(1, min(720,  (int)$params['horas'])) : 3;
        $expira_em = date('Y-m-d H:i:s', strtotime("+{$horas} hours"));

        $conexao->prepare("DELETE FROM buff_ativos WHERE usuarioid = ?")->execute([$db['id']]);
        $conexao->prepare("INSERT INTO buff_ativos (usuarioid, tipo_buff, pct, expira_em) VALUES (?,?,?,?)")
                ->execute([$db['id'], $tipo_efeito, $pct, $expira_em]);

        $cor_map = ['taijutsu'=>'#4ea8e8','ninjutsu'=>'#c97fd4','genjutsu'=>'#5ecf6e'];
        $resumo_cor    = $cor_map[$tipo_efeito];
        $resumo_titulo = '✨ '.$cristal['nome'].' ativado!';
        $resumo_corpo  = "+{$pct}% de <b style='color:{$resumo_cor}'>".ucfirst($tipo_efeito)."</b> "
                       . "durante <b style='color:#FFD700'>{$horas} hora(s)</b>.<br>"
                       . "<span class='sub2'>⏳ Expira em ".date('d/m H:i', strtotime($expira_em))
                       . " — qualquer buff anterior foi substituído.</span>";

    } elseif ($tipo_efeito === 'cura_total') {
        // Cura instantânea — restaura HP e Chakra ao máximo.
        $sel = $conexao->prepare("SELECT vidamax, chakramax FROM usuarios WHERE id = ?");
        $sel->execute([$db['id']]);
        $cur = $sel->fetch(PDO::FETCH_ASSOC) ?: ['vidamax'=>0,'chakramax'=>0];
        $vmx = (int)$cur['vidamax'];
        $cmx = (int)$cur['chakramax'];

        $conexao->prepare("UPDATE usuarios SET vida = ?, chakra = ? WHERE id = ?")
                ->execute([$vmx, $cmx, $db['id']]);

        $resumo_cor    = '#22c55e';
        $resumo_titulo = '💚 '.$cristal['nome'].' usado!';
        $resumo_corpo  = "Você foi totalmente curado!<br>"
                       . "❤️ Vida: <b style='color:#22c55e'>{$vmx}/{$vmx}</b> &nbsp; "
                       . "💧 Chakra: <b style='color:#3b82f6'>{$cmx}/{$cmx}</b>";

    } else {
        $conexao->rollBack();
        echo "<script>alert('Tipo de efeito desconhecido: ".addslashes($tipo_efeito)."'); self.location='?p=inventory'</script>"; exit;
    }

    // Consome o cristal
    $conexao->prepare("DELETE FROM usaveis WHERE id = ? AND usuarioid = ?")
            ->execute([$usavel_id, $db['id']]);

    $conexao->commit();
} catch (PDOException $e) {
    if ($conexao->inTransaction()) $conexao->rollBack();
    echo "<script>alert('Erro ao usar cristal: ".addslashes($e->getMessage())."'); self.location='?p=inventory'</script>"; exit;
}
?>
<div class="box_top"><?php echo htmlspecialchars($resumo_titulo); ?></div>
<div class="box_middle">
<div style="text-align:center;padding:15px;">
    <img src="_img/Buff/<?php echo htmlspecialchars($cristal['imagem']); ?>"
         onerror="this.style.display='none'"
         style="width:80px;height:80px;object-fit:contain;
                filter:drop-shadow(0 0 12px <?php echo $resumo_cor; ?>);
                margin-bottom:12px;" />
    <div style="font-size:18px;font-weight:bold;color:<?php echo $resumo_cor; ?>;
                text-shadow:0 0 8px <?php echo $resumo_cor; ?>;margin-bottom:8px;">
        <?php echo htmlspecialchars($cristal['nome']); ?>
    </div>
    <div style="background:#0a0a0a;border:1px solid <?php echo $resumo_cor; ?>;
                border-radius:6px;padding:10px 20px;display:inline-block;margin-bottom:12px;">
        <span style="color:#fff;font-size:13px;line-height:1.6;">
            <?php echo $resumo_corpo; // já tem tags HTML internas seguras ?>
        </span>
    </div>
    <br/>
    <a href="?p=home" class="botao">Voltar à Home</a>
    &nbsp;
    <a href="?p=inventory" class="botao">Inventário</a>
    &nbsp;
    <a href="?p=cristais_buff" class="botao">Cristais</a>
</div>
</div>
<div class="box_bottom"></div>
