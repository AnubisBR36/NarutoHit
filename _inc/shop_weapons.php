<?php
try {
    $stmt = $conexao->prepare("SELECT * FROM table_itens WHERE categoria='arma' AND disponivel_shop='sim' ORDER BY valor ASC");
    $stmt->execute();
    $weapons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $weapons = [];
}
?>

<table width="100%" cellpadding="0" cellspacing="1">
<?php if(empty($weapons)) { ?>
    <tr><td colspan="3"><div class="aviso">Nenhuma arma disponível na loja.</div></td></tr>
<?php } else { 
    foreach($weapons as $weapon) { 
        $valor = $weapon['valor'];
        if(isset($db['vip']) && date('Y-m-d H:i:s') < $db['vip']) {
            $valor = floor($valor * 0.8);
        }
        
        // Check if user already has this item
        try {
            $stmt_check = $conexao->prepare("SELECT id FROM inventario WHERE usuarioid=? AND itemid=?");
            $stmt_check->execute([$db['id'], $weapon['id']]);
            $has_item = $stmt_check->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $has_item = false;
        }
?>
    <tr>
        <td colspan="2"><div class="sep"></div></td>
    </tr>
    <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
        <td align="center" width="140"><img src="_img/equipamentos/<?php echo htmlspecialchars($weapon['imagem']); ?>.png" /></td>
        <td style="padding:5px;">
            <b><?php echo htmlspecialchars($weapon['nome']); ?></b><br />
            <span class="sub2"><?php echo htmlspecialchars($weapon['descricao']); ?></span><br />
            <b>
                <?php if($weapon['taijutsu'] > 0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.intval($weapon['taijutsu']).'] em Taijutsu<br />'; ?>
                <?php if($weapon['ninjutsu'] > 0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.intval($weapon['ninjutsu']).'] em Ninjutsu<br />'; ?>
                <?php if($weapon['genjutsu'] > 0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.intval($weapon['genjutsu']).'] em Genjutsu<br />'; ?>
            </b>
        </td>
        <td align="center" width="20%">
            <b>Requisitos</b><br />
            <span class="sub2"><?php echo intval($weapon['reqtai']); ?> Taijutsu</span><br /><br />
            <b>Valor</b><br />
            <span class="sub2"><?php echo number_format($valor, 2, ',', '.'); ?> yens</span><br /><br />
            
            <?php if($has_item) { ?>
                <span class="sub2">Você já possui este item</span>
            <?php } elseif($weapon['vip'] == 'sim' && (!isset($db['vip']) || date('Y-m-d H:i:s') >= $db['vip'])) { ?>
                <span class="sub2">Exclusivo para VIP</span>
            <?php } elseif($db['yens'] < $valor) { ?>
                <span class="sub2">Yens insuficientes</span>
            <?php } elseif($db['taijutsu'] < $weapon['reqtai']) { ?>
                <span class="sub2">Taijutsu insuficiente</span>
            <?php } else { ?>
                <form method="post" action="?p=shop" onsubmit="subm.value='Carregando...';subm.disabled=true;">
                    <input type="hidden" name="buy_id" value="<?php echo intval($weapon['id']); ?>" />
                    <input type="hidden" name="buy_page" value="weapons" />
                    <input type="hidden" name="buy_cat" value="arma" />
                    <input type="submit" id="subm" name="subm" class="botao" value="Comprar" />
                </form>
            <?php } ?>
        </td>
    </tr>
<?php } } ?>
</table>
