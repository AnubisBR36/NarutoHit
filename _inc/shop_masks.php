<?php
try {
    $stmt = $conexao->prepare("SELECT * FROM table_itens WHERE categoria='mascara' AND disponivel_shop='sim' ORDER BY valor ASC");
    $stmt->execute();
    $masks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $masks = [];
}
?>

<table width="100%" cellpadding="0" cellspacing="1">
<?php if(empty($masks)) { ?>
    <tr><td colspan="3"><div class="aviso">Nenhuma máscara disponível na loja.</div></td></tr>
<?php } else { 
    foreach($masks as $mask) { 
        $valor = $mask['valor'];
        if(isset($db['vip']) && date('Y-m-d H:i:s') < $db['vip']) {
            $valor = floor($valor * 0.8);
        }

        // Check if user already has this item
        try {
            $stmt_check = $conexao->prepare("SELECT id FROM inventario WHERE usuarioid=? AND itemid=?");
            $stmt_check->execute([$db['id'], $mask['id']]);
            $has_item = $stmt_check->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $has_item = false;
        }
?>
    <tr>
        <td colspan="2"><div class="sep"></div></td>
    </tr>
    <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
        <td align="center" width="140"><img src="_img/equipamentos/<?php echo htmlspecialchars($mask['imagem']); ?>.png" /></td>
        <td style="padding:5px;">
            <b><?php echo htmlspecialchars($mask['nome']); ?></b><br />
            <span class="sub2"><?php echo htmlspecialchars($mask['descricao']); ?></span><br />
            <b>
                <?php if($mask['taijutsu'] > 0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.intval($mask['taijutsu']).'] em Taijutsu<br />'; ?>
                <?php if($mask['ninjutsu'] > 0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.intval($mask['ninjutsu']).'] em Ninjutsu<br />'; ?>
                <?php if($mask['genjutsu'] > 0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.intval($mask['genjutsu']).'] em Genjutsu<br />'; ?>
            </b>
        </td>
        <td align="center" width="20%">
            <b>Requisitos Mínimos</b><br />
            <span class="sub2">
                <?php 
                $reqs = [];
                if($mask['reqtai'] > 0) $reqs[] = intval($mask['reqtai']).' Taijutsu';
                if($mask['reqnin'] > 0) $reqs[] = intval($mask['reqnin']).' Ninjutsu';
                if($mask['reqgen'] > 0) $reqs[] = intval($mask['reqgen']).' Genjutsu';
                echo !empty($reqs) ? implode('<br />', $reqs) : 'Nenhum';
                ?>
            </span><br /><br />
            <b>Valor</b><br />
            <span class="sub2"><?php echo number_format($valor, 2, ',', '.'); ?> yens</span><br /><br />

            <?php if($has_item) { ?>
                <span class="sub2">Você já possui este item</span>
            <?php } elseif($mask['vip'] == 'sim' && (!isset($db['vip']) || date('Y-m-d H:i:s') >= $db['vip'])) { ?>
                <span class="sub2">Exclusivo para VIP</span>
            <?php } elseif($db['yens'] < $valor) { ?>
                <span class="sub2">Yens insuficientes</span>
            <?php } elseif($db['taijutsu'] < $mask['reqtai'] || $db['ninjutsu'] < $mask['reqnin'] || $db['genjutsu'] < $mask['reqgen']) { ?>
                <span class="sub2">Atributos insuficientes</span>
            <?php } else { ?>
                <form method="post" action="?p=shop" onsubmit="subm.value='Carregando...';subm.disabled=true;">
                    <input type="hidden" name="buy_id" value="<?php echo intval($mask['id']); ?>" />
                    <input type="hidden" name="buy_page" value="masks" />
                    <input type="hidden" name="buy_cat" value="mascara" />
                    <input type="submit" id="subm" name="subm" class="botao" value="Comprar" />
                </form>
            <?php } ?>
        </td>
    </tr>
<?php } } ?>
</table>
