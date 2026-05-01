<?php
require_once('trava.php');

// Criar tabela de fragmentos se não existir
try {
    $pk = Database::autoIncPK('id');
    $conexao->exec("CREATE TABLE IF NOT EXISTS buff_fragmentos (
        $pk,
        usuarioid INTEGER NOT NULL,
        itemid INTEGER NOT NULL,
        quantidade INTEGER NOT NULL DEFAULT 1,
        UNIQUE(usuarioid, itemid)
    )");
} catch (PDOException $e) {}

// Combinar 3 fragmentos em 1 cristal
if (isset($_GET['combinar_buff']) && is_numeric($_GET['combinar_buff'])) {
    $frag_itemid = (int)$_GET['combinar_buff'];
    try {
        $stmtf = $conexao->prepare("SELECT quantidade FROM buff_fragmentos WHERE usuarioid = ? AND itemid = ?");
        $stmtf->execute([$db['id'], $frag_itemid]);
        $frow = $stmtf->fetch(PDO::FETCH_ASSOC);
        if ($frow && $frow['quantidade'] >= 3) {
            $conexao->prepare("UPDATE buff_fragmentos SET quantidade = quantidade - 3 WHERE usuarioid = ? AND itemid = ?")->execute([$db['id'], $frag_itemid]);
            $conexao->prepare("DELETE FROM buff_fragmentos WHERE usuarioid = ? AND itemid = ? AND quantidade <= 0")->execute([$db['id'], $frag_itemid]);
            $conexao->prepare("INSERT INTO usaveis (usuarioid, itemid) VALUES (?, ?)")->execute([$db['id'], $frag_itemid]);
            echo "<script>location.href='?p=cristais_buff&msg=combinado'</script>"; exit;
        }
    } catch (PDOException $e) {}
    echo "<script>location.href='?p=cristais_buff'</script>"; exit;
}

// Carregar dados
try {
    $stmt = $conexao->prepare("SELECT u.id AS inv_id, t.id AS item_id, t.nome, t.imagem, t.descricao,
        COALESCE(t.tipo_efeito,'') AS tipo_efeito, COALESCE(t.valor_efeito,'') AS valor_efeito
        FROM usaveis u JOIN table_usaveis t ON u.itemid = t.id
        WHERE u.usuarioid = ? AND t.categoria = 'cristal_buff'");
    $stmt->execute([$db['id']]);
    $buff_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conexao->prepare("SELECT bf.id, bf.itemid, bf.quantidade, t.nome, t.imagem FROM buff_fragmentos bf JOIN table_usaveis t ON bf.itemid = t.id WHERE bf.usuarioid = ? AND bf.quantidade > 0");
    $stmt->execute([$db['id']]);
    $buff_frags = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conexao->prepare("SELECT tipo_buff, pct, expira_em FROM buff_ativos WHERE usuarioid = ? AND expira_em > CURRENT_TIMESTAMP");
    $stmt->execute([$db['id']]);
    $buff_ativo = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $buff_items = [];
    $buff_frags = [];
    $buff_ativo = null;
}

$buff_cor_map  = ['taijutsu'=>'#4ea8e8','ninjutsu'=>'#c97fd4','genjutsu'=>'#5ecf6e','cura_total'=>'#22c55e'];
$tipo_by_name  = ['Cristal de Taijutsu' => 'taijutsu', 'Cristal de Ninjutsu' => 'ninjutsu', 'Cristal de Genjutsu' => 'genjutsu'];

/**
 * Gera a frase descritiva do efeito de um cristal usando os campos
 * tipo_efeito + valor_efeito (preenchidos pelo ADM em adm/cristal.php).
 * Se o cristal for legado (sem tipo_efeito), faz fallback pelo nome.
 */
function descrever_efeito_cristal_buff($te, $valor_json, $nome) {
    if (!$te) {
        $map = ['Cristal de Taijutsu'=>['taijutsu',5,3], 'Cristal de Ninjutsu'=>['ninjutsu',5,3], 'Cristal de Genjutsu'=>['genjutsu',5,3]];
        if (!isset($map[$nome])) return 'Efeito não cadastrado.';
        [$te,$pct,$h] = $map[$nome];
        return "+{$pct}% ".ucfirst($te)." por {$h} hora(s).";
    }
    $p = $valor_json ? (json_decode($valor_json, true) ?: []) : [];
    if (in_array($te, ['taijutsu','ninjutsu','genjutsu'], true)) {
        $pct = (int)($p['pct'] ?? 5); $h = (int)($p['horas'] ?? 3);
        return "+{$pct}% ".ucfirst($te)." por {$h} hora(s).";
    }
    if ($te === 'cura_total') {
        return "💚 Restaura HP e Chakra ao máximo (instantâneo).";
    }
    return 'Efeito: '.$te;
}
?>
<div class="box_top">💎 Cristais de Buff</div>
<div class="box_middle">
    Abaixo estão seus Cristais de Buff. Cada cristal tem um <b>efeito próprio</b> — confira a descrição antes de ativar.
    <div class="sep"></div>
    <div align="center">
        <a href="?p=inventory">Itens</a> |
        <a href="?p=parchments">Cristais de Refinamento</a> |
        <a href="?p=cristais_buff"><b>Cristais de Buff</b></a>
    </div>
    <div class="sep"></div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'combinado'): ?>
    <div class="aviso" style="margin:6px 0;">💎 3 fragmentos combinados — cristal formado!</div>
    <div class="sep"></div>
<?php endif; ?>

<?php if ($buff_ativo): $ba_nome = ucfirst($buff_ativo['tipo_buff']); $ba_cor = $buff_cor_map[$buff_ativo['tipo_buff']] ?? '#FFD700'; ?>
    <div class="aviso" style="margin-bottom:8px;">
        ✅ Buff ativo: <b style="color:<?php echo $ba_cor; ?>">+<?php echo $buff_ativo['pct']; ?>% <?php echo $ba_nome; ?></b>
        — Expira: <b><?php echo date('d/m H:i', strtotime($buff_ativo['expira_em'])); ?></b>
    </div>
<?php endif; ?>

    <table width="100%" cellpadding="0" cellspacing="1">

    <?php if (empty($buff_items) && empty($buff_frags)): ?>
        <tr><td><div class="sep"></div></td></tr>
        <tr><td>
            <div class="aviso">Você não possui nenhum Cristal de Buff.<br/>
            <span class="sub2">Cristais podem ser obtidos como drop em batalhas PvP (3%) ou missões de clã (5%).</span></div>
        </td></tr>
    <?php endif; ?>

    <?php foreach ($buff_items as $bi):
        $te = $bi['tipo_efeito'] ?: ($tipo_by_name[$bi['nome']] ?? '');
        $bcor = $buff_cor_map[$te] ?? '#FFD700';
        $efeito_str = descrever_efeito_cristal_buff($bi['tipo_efeito'], $bi['valor_efeito'], $bi['nome']);
        $eh_cura = ($te === 'cura_total');
    ?>
    <tr><td colspan="2"><div class="sep"></div></td></tr>
    <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
        <td align="center" width="100" style="padding:10px;">
            <img src="_img/Buff/<?php echo htmlspecialchars($bi['imagem']); ?>"
                 onerror="this.style.display='none'"
                 style="width:64px;height:64px;object-fit:contain;" />
        </td>
        <td style="padding:8px;">
            <b style="color:<?php echo $bcor; ?>;"><?php echo htmlspecialchars($bi['nome']); ?></b><br/>
            <?php if ($bi['descricao'] !== ''): ?>
                <span class="sub2"><?php echo htmlspecialchars($bi['descricao']); ?></span><br/>
            <?php endif; ?>
            <span class="sub2" style="color:<?php echo $bcor; ?>;"><b><?php echo htmlspecialchars($efeito_str); ?></b></span><br/>
            <?php if (!$eh_cura): ?>
                <span class="sub2">Ativar cancela qualquer buff anterior.</span><br/>
            <?php endif; ?>
            <br/>
            <a href="?p=usar_cristal_buff&id=<?php echo $bi['inv_id']; ?>"
               onclick="return confirm('<?php echo $eh_cura ? 'Usar' : 'Ativar'; ?> <?php echo htmlspecialchars($bi['nome']); ?>?<?php echo $eh_cura ? '' : ' Qualquer buff ativo será substituído.'; ?>');"
               style="display:inline-block;padding:4px 16px;background:url('_img/fundo_botao.jpg') repeat-x center;color:#fff;border:1px solid #555;font-size:12px;font-weight:bold;text-decoration:none;text-shadow:1px 1px 2px #000;">
               <?php echo $eh_cura ? '💚 Curar' : '✨ Ativar'; ?>
            </a>
        </td>
    </tr>
    <?php endforeach; ?>

    <?php foreach ($buff_frags as $bf):
        if ($bf['quantidade'] <= 0) continue;
        $tipo_buff = $tipo_by_name[$bf['nome']] ?? 'taijutsu';
        $bcor = $buff_cor_map[$tipo_buff] ?? '#888'; ?>
    <tr><td colspan="2"><div class="sep"></div></td></tr>
    <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
        <td align="center" width="100" style="padding:10px;">
            <div style="position:relative;display:inline-block;">
                <img src="_img/Buff/<?php echo htmlspecialchars($bf['imagem']); ?>"
                     onerror="this.style.display='none'"
                     style="width:64px;height:64px;object-fit:contain;filter:grayscale(0.6) brightness(0.8);" />
                <div style="position:absolute;top:-6px;right:-6px;background:#000;color:#FFD700;font-size:10px;font-weight:bold;padding:1px 5px;border:1px solid #FFD700;">
                    x<?php echo (int)$bf['quantidade']; ?>
                </div>
            </div>
        </td>
        <td style="padding:8px;">
            <b>Fragmento de <?php echo htmlspecialchars($bf['nome']); ?></b>
            <span style="background:#000;color:#FFD700;font-size:10px;font-weight:bold;padding:1px 5px;margin-left:5px;border:1px solid #FFD700;">FRAGMENTO</span><br/>
            <span class="sub2">Junte 3 fragmentos para formar o cristal completo.</span><br/><br/>
            <?php if ($bf['quantidade'] >= 3): ?>
            <a href="?p=cristais_buff&combinar_buff=<?php echo $bf['itemid']; ?>"
               onclick="return confirm('Combinar 3 fragmentos de <?php echo htmlspecialchars($bf['nome']); ?>?');"
               style="display:inline-block;padding:4px 16px;background:url('_img/fundo_botao.jpg') repeat-x center;color:#fff;border:1px solid #555;font-size:12px;font-weight:bold;text-decoration:none;text-shadow:1px 1px 2px #000;">
               Combinar (<?php echo (int)$bf['quantidade']; ?>/3)
            </a>
            <?php else: ?>
            <span class="sub2">Faltam <?php echo 3 - (int)$bf['quantidade']; ?> fragmento(s)</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>

    </table>
</div>
<div class="box_bottom"></div>
