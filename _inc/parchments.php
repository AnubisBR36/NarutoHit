<?php
require_once('trava.php');

// Garante a tabela e colunas do sistema de fragmentos de cristal de craft
try {
    $pkCF = Database::autoIncPK('id');
    $conexao->exec("CREATE TABLE IF NOT EXISTS craft_fragmentos (
        $pkCF,
        usuarioid INTEGER NOT NULL,
        itemid INTEGER NOT NULL,
        quantidade INTEGER NOT NULL DEFAULT 0,
        UNIQUE(usuarioid, itemid)
    )");
} catch (PDOException $e) {}
try { $conexao->exec("ALTER TABLE table_usaveis ADD COLUMN IF NOT EXISTS cristal_alvo_id INTEGER NULL DEFAULT NULL"); } catch (PDOException $e) {}
try { $conexao->exec("ALTER TABLE table_usaveis ADD COLUMN IF NOT EXISTS fragmentos_necessarios INTEGER NULL DEFAULT NULL"); } catch (PDOException $e) {}

try {
    $stmt = $conexao->prepare("
        SELECT COUNT(u.id) AS conta, t.id AS itemid, t.descricao, t.nome, t.imagem
        FROM usaveis u
        LEFT JOIN table_usaveis t ON u.itemid = t.id
        WHERE u.usuarioid = ? AND t.categoria = 'cristal'
        GROUP BY u.itemid
        ORDER BY u.itemid ASC
    ");
    $stmt->execute([$db['id']]);
    $cristais_ref = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $cristais_ref = [];
}

// Cristais de craft completos no inventário
try {
    $stmtC = $conexao->prepare("
        SELECT COUNT(u.id) AS conta, t.id AS itemid, t.descricao, t.nome, t.imagem
        FROM usaveis u
        LEFT JOIN table_usaveis t ON u.itemid = t.id
        WHERE u.usuarioid = ? AND t.categoria = 'cristal_craft'
        GROUP BY u.itemid
        ORDER BY u.itemid ASC
    ");
    $stmtC->execute([$db['id']]);
    $cristais_craft = $stmtC->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $cristais_craft = [];
}

// Fragmentos de cristal de craft — agora são entidades próprias (cat='fragmento_craft')
// com cristal_alvo_id apontando para o cristal completo (cat='cristal_craft') que vão formar.
// fragmentos_necessarios = quantidade configurada pelo admin em adm/cristal.php (NULL/0 → fallback de 5).
try {
    $stmtF = $conexao->prepare("
        SELECT cf.itemid, cf.quantidade, t.nome, t.imagem, t.descricao,
               t.fragmentos_necessarios AS precisa_cfg,
               c.nome   AS alvo_nome,
               c.imagem AS alvo_imagem
        FROM craft_fragmentos cf
        LEFT JOIN table_usaveis t ON cf.itemid = t.id
        LEFT JOIN table_usaveis c ON t.cristal_alvo_id = c.id AND c.categoria = 'cristal_craft'
        WHERE cf.usuarioid = ? AND cf.quantidade > 0 AND t.categoria = 'fragmento_craft'
        ORDER BY cf.quantidade DESC, t.nome ASC
    ");
    $stmtF->execute([$db['id']]);
    $craft_frags = $stmtF->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $craft_frags = [];
}

$PRECISA_FRAG_DEFAULT = 5; // fallback caso o admin não tenha configurado
?>
<div class="box_top">Cristais de Refinamento</div>
<div class="box_middle">
    Abaixo estão os cristais de refinamento que você possui.
    <div class="sep"></div>
    <div align="center">
        <a href="?p=inventory">Itens</a> |
        <a href="?p=parchments"><b>Cristais de Refinamento</b></a> |
        <a href="?p=cristais_buff">Cristais de Buff</a>
    </div>
    <div class="sep"></div>
    <table width="100%" cellpadding="0" cellspacing="1">
    <?php if (empty($cristais_ref)): ?>
        <tr><td colspan="2"><div class="sep"></div></td></tr>
        <tr><td colspan="2"><div class="aviso">Nenhum cristal de refinamento encontrado.</div></td></tr>
    <?php else: ?>
        <?php foreach ($cristais_ref as $c): ?>
        <tr><td colspan="2"><div class="sep"></div></td></tr>
        <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
            <td align="center" width="140" style="padding:8px;">
                <img src="_img/ferreiro/<?php echo htmlspecialchars($c['imagem']); ?>"
                     onerror="this.src='_img/default.png'"
                     style="max-width:80px; max-height:80px; object-fit:contain;" />
            </td>
            <td style="padding:8px;">
                <b><?php echo htmlspecialchars($c['nome']); ?></b><br />
                <span class="sub2"><?php echo htmlspecialchars($c['descricao']); ?></span>
                <br /><br />
                Quantidade: <b><?php echo (int)$c['conta']; ?> unidade<?php if ($c['conta'] > 1) echo 's'; ?></b>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </table>

    <div class="sep"></div>
    <div style="background:#1a001a;border:1px solid #cf6ecf;border-radius:4px;padding:8px;margin-top:10px;">
        <div style="color:#cf6ecf;font-weight:bold;font-size:13px;margin-bottom:4px;">
            ⚙️ Cristais de Craft
        </div>
        <div style="color:#aaa;font-size:11px;">
            Cristais especiais obtidos em Missões de Clã. Junte os <b style="color:#cf6ecf;">fragmentos do mesmo tipo</b>
            no Ferreiro para formar 1 cristal completo (combinação garantida — sem chance de falha).
            A quantidade necessária varia por cristal — veja o badge abaixo de cada fragmento.
        </div>
    </div>

    <table width="100%" cellpadding="0" cellspacing="1">

    <?php if (empty($cristais_craft) && empty($craft_frags)): ?>
        <tr><td colspan="2"><div class="sep"></div></td></tr>
        <tr><td colspan="2">
            <div class="aviso">Você ainda não possui Cristais de Craft nem fragmentos.
            <br/><span class="sub2">Conclua Missões de Clã para tentar obter (4% de chance por missão).</span></div>
        </td></tr>
    <?php endif; ?>

    <?php foreach ($cristais_craft as $cc): ?>
        <tr><td colspan="2"><div class="sep"></div></td></tr>
        <tr class="table_dados" style="background:#1a001a;" onmouseover="style.background='#2a0a2a'" onmouseout="style.background='#1a001a'">
            <td align="center" width="140" style="padding:8px;">
                <img src="_img/Craft/<?php echo htmlspecialchars($cc['imagem']); ?>"
                     onerror="this.src='_img/default.png'"
                     style="max-width:80px; max-height:80px; object-fit:contain;" />
            </td>
            <td style="padding:8px;">
                <b style="color:#cf6ecf;"><?php echo htmlspecialchars($cc['nome']); ?></b>
                <span style="background:#5a005a;color:#fff;font-size:10px;font-weight:bold;padding:1px 5px;margin-left:5px;border-radius:3px;">CRISTAL DE CRAFT</span>
                <br />
                <span class="sub2"><?php echo htmlspecialchars($cc['descricao']); ?></span>
                <br /><br />
                Quantidade: <b><?php echo (int)$cc['conta']; ?> unidade<?php if ($cc['conta'] > 1) echo 's'; ?></b>
                <br/>
                <span class="sub2">Use no Ferreiro para criar/aprimorar equipamentos.</span>
            </td>
        </tr>
    <?php endforeach; ?>

    <?php foreach ($craft_frags as $cf):
        $qtd = (int)$cf['quantidade'];
        $precisa_cfg = (int)($cf['precisa_cfg'] ?? 0);
        $PRECISA_FRAG = ($precisa_cfg >= 2 && $precisa_cfg <= 20) ? $precisa_cfg : $PRECISA_FRAG_DEFAULT;
        $faltam = $PRECISA_FRAG - $qtd;
        $pode = ($qtd >= $PRECISA_FRAG);
        // Imagem própria do fragmento (em _img/Fragmento de Cristal/).
        $img_src = '_img/Fragmento%20de%20Cristal/' . rawurlencode($cf['imagem'] ?? '');
        $img_filter = '';
        $alvo_nome = trim((string)($cf['alvo_nome'] ?? ''));
    ?>
        <tr><td colspan="2"><div class="sep"></div></td></tr>
        <tr class="table_dados" style="background:#1a001a;" onmouseover="style.background='#2a0a2a'" onmouseout="style.background='#1a001a'">
            <td align="center" width="140" style="padding:8px;">
                <div style="position:relative;display:inline-block;">
                    <img src="<?php echo htmlspecialchars($img_src); ?>"
                         onerror="this.src='_img/default.png'"
                         style="max-width:80px; max-height:80px; object-fit:contain; <?php echo $img_filter; ?>" />
                    <div style="position:absolute;bottom:-4px;right:-4px;background:#5a005a;color:#cf6ecf;font-size:10px;font-weight:bold;padding:1px 6px;border:1px solid #cf6ecf;border-radius:3px;">
                        x<?php echo $qtd; ?>
                    </div>
                </div>
            </td>
            <td style="padding:8px;">
                <b style="color:#cf6ecf;"><?php echo htmlspecialchars($cf['nome']); ?></b>
                <span style="background:#5a005a;color:#cf6ecf;font-size:10px;font-weight:bold;padding:1px 5px;margin-left:5px;border:1px solid #cf6ecf;border-radius:3px;">FRAGMENTO</span>
                <br/>
                <span class="sub2">
                    <?php echo $PRECISA_FRAG; ?> fragmentos →
                    <b style="color:#90EE90;"><?php echo htmlspecialchars($alvo_nome !== '' ? $alvo_nome : '(cristal alvo não configurado)'); ?></b>
                </span>
                <br/>
                Quantidade atual: <b><?php echo $qtd; ?>/<?php echo $PRECISA_FRAG; ?></b>
                <?php if ($pode): ?>
                    <span style="color:#90EE90;font-weight:bold;">— pronto para combinar!</span>
                <?php else: ?>
                    <span style="color:#aaa;">— faltam <b><?php echo $faltam; ?></b></span>
                <?php endif; ?>
                <br/><br/>
                <a href="?p=blacksmith"
                   style="display:inline-block;padding:4px 16px;background:url('_img/fundo_botao.jpg') repeat-x center;
                          color:<?php echo $pode ? '#cf6ecf' : '#aaa'; ?>;
                          border:1px solid <?php echo $pode ? '#cf6ecf' : '#555'; ?>;
                          font-size:12px;font-weight:bold;text-decoration:none;text-shadow:1px 1px 2px #000;">
                    💎 <?php echo $pode ? 'Combinar no Ferreiro' : 'Ir ao Ferreiro'; ?>
                </a>
            </td>
        </tr>
    <?php endforeach; ?>

    </table>
</div>
<div class="box_bottom"></div>
