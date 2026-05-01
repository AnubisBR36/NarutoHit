<?php
$moeda_labels  = ['yens'=>'Yens','cristal1'=>'Cristal Refinado','cristal2'=>'Cristal Bruto','cristal3'=>'Chakra Forjado'];
$moeda_icons   = ['yens'=>'_img/yens.png','cristal1'=>'_img/ferreiro/Cristal de Chakra Refinado.png','cristal2'=>'_img/ferreiro/Cristal de Chakra Bruto.png','cristal3'=>'_img/ferreiro/Chakra Forjado.png'];
$cristal_iid   = ['cristal1'=>1,'cristal2'=>2,'cristal3'=>3];

if(isset($_POST['buy_id'])) {
    $buy = intval($_POST['buy_id']);
    if($buy < 1) { echo "<script>self.location='?p=home'</script>"; exit; }
    
    try {
        $conexao->beginTransaction();

        $stmt = $conexao->prepare("SELECT i.id,i.usuarioid,i.valor,i.moeda_tipo,t.nome FROM inventario i LEFT JOIN table_itens t ON i.itemid=t.id WHERE i.id=? AND i.venda='sim'");
        $stmt->execute([$buy]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$item) {
            $conexao->rollback();
            echo "<script>self.location='?p=shops&msg=1'</script>"; exit;
        }
        if($item['usuarioid'] == $db['id']) {
            $conexao->rollback();
            echo "<script>self.location='?p=shops&msg=3'</script>"; exit;
        }

        $moeda  = $item['moeda_tipo'] ?? 'yens';
        $valor  = intval($item['valor']);
        $seller = $item['usuarioid'];

        if($moeda === 'yens'){
            if($db['yens'] < $item['valor']){
                $conexao->rollback();
                echo "<script>self.location='?p=shops&msg=2&m=".urlencode($moeda)."'</script>"; exit;
            }
            $conexao->prepare("UPDATE usuarios SET yens=yens-?, compraloja=compraloja+1 WHERE id=?")->execute([$item['valor'], $db['id']]);
            $conexao->prepare("INSERT INTO vendas (usuarioid, valor, moeda_tipo) VALUES (?, ?, 'yens')")->execute([$seller, $item['valor']]);

        } elseif(isset($cristal_iid[$moeda])){
            $cid    = $cristal_iid[$moeda];
            $needed = $valor;
            $sc = $conexao->prepare("SELECT COUNT(*) as cnt FROM usaveis WHERE usuarioid=? AND itemid=? AND status='off'");
            $sc->execute([$db['id'], $cid]);
            $cnt = $sc->fetch(PDO::FETCH_ASSOC)['cnt'];
            if($cnt < $needed){
                $conexao->rollback();
                echo "<script>self.location='?p=shops&msg=2&m=".urlencode($moeda)."'</script>"; exit;
            }
            // Debita os cristais do comprador
            $si = $conexao->prepare("SELECT id FROM usaveis WHERE usuarioid=? AND itemid=? AND status='off' LIMIT ?");
            $si->execute([$db['id'], $cid, $needed]);
            $cids = $si->fetchAll(PDO::FETCH_COLUMN);
            if(!empty($cids)){
                $ph = implode(',', array_fill(0, count($cids), '?'));
                $conexao->prepare("DELETE FROM usaveis WHERE id IN ($ph)")->execute($cids);
            }
            // Padrão "Retirar": valor fica pendente em vendas até o vendedor sacar (igual Yens)
            $conexao->prepare("INSERT INTO vendas (usuarioid, valor, moeda_tipo) VALUES (?, ?, ?)")->execute([$seller, $needed, $moeda]);
            $conexao->prepare("UPDATE usuarios SET compraloja=compraloja+1 WHERE id=?")->execute([$db['id']]);
        } else {
            $conexao->rollback();
            echo "<script>self.location='?p=home'</script>"; exit;
        }

        $mlbl = $moeda_labels[$moeda] ?? 'Yens';
        $message = "Um item da sua loja foi vendido por ".number_format($valor,0,',','.')." {$mlbl}. O valor ficará guardado até que você faça um saque para sua reserva.";
        $conexao->prepare("INSERT INTO mensagens (data, origem, destino, assunto, msg) VALUES (?, 0, ?, 'Item Vendido!', ?)")->execute([date('Y-m-d H:i:s'), $seller, $message]);

        // Histórico permanente do mercado (lê nome+imagem do item agora, antes do UPDATE)
        try {
            $hi = $conexao->prepare("SELECT t.nome, t.imagem FROM inventario i LEFT JOIN table_itens t ON i.itemid=t.id WHERE i.id=?");
            $hi->execute([$buy]);
            $hrow = $hi->fetch(PDO::FETCH_ASSOC) ?: ['nome'=>$item['nome'] ?? '', 'imagem'=>''];
            $conexao->prepare("INSERT INTO mercado_historico (vendedor_id, comprador_id, item_id, item_nome, item_imagem, valor, moeda_tipo, data) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                    ->execute([$seller, $db['id'], $buy, $hrow['nome'] ?? '', $hrow['imagem'] ?? '', $valor, $moeda, date('Y-m-d H:i:s')]);
        } catch (Throwable $e) { /* não bloqueia a venda */ }

        // Snapshot do nome do vendedor (para o tag no inventário do comprador)
        $sn = $conexao->prepare("SELECT usuario FROM usuarios WHERE id=?");
        $sn->execute([$seller]);
        $sellerNome = $sn->fetchColumn() ?: '';
        $conexao->prepare("UPDATE inventario SET venda='nao', valor=0, moeda_tipo='yens', usuarioid=?, comprado_de_id=?, comprado_de_nome=?, comprado_valor=?, comprado_moeda=?, comprado_data=NOW() WHERE id=?")
                ->execute([$db['id'], $seller, $sellerNome, $valor, $moeda, $buy]);
        
        $conexao->commit();
        echo "<script>self.location='?p=shops&msg=4'</script>"; exit;
        
    } catch (PDOException $e) {
        if($conexao->inTransaction()) $conexao->rollback();
        echo "<script>self.location='?p=shops&msg=6'</script>"; exit;
    }
}

try {
    $stmt = $conexao->prepare("SELECT id, nome, categoria FROM table_itens ORDER BY nome ASC");
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $items = [];
}
?>
<div class="box_top">Comércio Interno</div>
<div class="box_middle">Bem-vindo ao comércio interno do jogo. Aqui você encontrará as lojas criadas pelos próprios ninjas. Selecione o item que deseja visualizar e boas compras!<div class="sep"></div>
        <?php
        if(isset($_GET['msg'])){
                $mtipo = isset($_GET['m']) ? preg_replace('/[^a-z0-9]/i','', (string)$_GET['m']) : '';
                $mlbl_msg = isset($moeda_labels[$mtipo]) ? $moeda_labels[$mtipo] : 'Moeda';
                switch(intval($_GET['msg'])){
                        case 1: $msg='Este item já foi comprado por outra pessoa.'; break;
                        case 2: $msg=$mlbl_msg.' insuficiente para comprar este item.'; break;
                        case 3: $msg='Você não pode comprar um item que já lhe pertence.'; break;
                        case 4: $msg='Item comprado com sucesso!'; break;
                        case 6: $msg='Erro ao processar a compra. Tente novamente.'; break;
                        default: $msg='';
                }
                if(!empty($msg)) echo '<div class="aviso">'.htmlspecialchars($msg).'</div><div class="sep"></div>';
        }
        ?>
        <b>Selecione o Item:</b><br />
<form method="get" action="?p=shops">
<input type="hidden" name="p" value="shops" />
        <select name="item">
        <?php foreach($items as $item_option) {
                switch($item_option['categoria']){
                        case 'arma': $cat='Arma'; break;
                        case 'vestimenta': $cat='Vestimenta'; break;
                        case 'calcado': $cat='Calçado'; break;
                        default: $cat='Item'; break;
                }
                ?>
        <option value="<?php echo $item_option['id']; ?>"<?php if(isset($_GET['item'])&&$_GET['item']==$item_option['id']) echo ' selected'; ?>><?php echo htmlspecialchars($item_option['nome']); ?> [<?php echo $cat; ?>]</option>
        <?php } ?>
</select>&nbsp;<input type="submit" class="botao" value="Pesquisar" /></form>
<span class="sub2">Selecione o item que deseja pesquisar.</span>

<?php if(isset($_GET['item'])){ 
    $item_id = intval($_GET['item']);
    try {
        $stmt_item = $conexao->prepare("SELECT * FROM table_itens WHERE id=?");
        $stmt_item->execute([$item_id]);
        $selected_item = $stmt_item->fetch(PDO::FETCH_ASSOC);
        
        if($selected_item) {
            $stmt_sales = $conexao->prepare("SELECT i.id, i.valor, i.usuarioid, i.upgrade, i.moeda_tipo, u.usuario FROM inventario i LEFT JOIN usuarios u ON i.usuarioid=u.id WHERE i.itemid=? AND i.venda='sim' ORDER BY i.id ASC");
            $stmt_sales->execute([$item_id]);
            $sales = $stmt_sales->fetchAll(PDO::FETCH_ASSOC);
?>
<table width="100%" cellpadding="0" cellspacing="1">
<tr><td colspan="3"><div class="sep"></div></td></tr>
<tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
    <td align="center" width="140" valign="top"><img src="_img/equipamentos/<?php echo htmlspecialchars($selected_item['imagem']); ?>.png" /></td>
    <td colspan="2" style="padding:5px;">
        <b><?php echo htmlspecialchars($selected_item['nome']); ?></b><br />
        <span class="sub2"><?php echo htmlspecialchars($selected_item['descricao']); ?></span><br />
        <b><?php if($selected_item['taijutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.$selected_item['taijutsu'].'] em Taijutsu<br />'; ?>
        <?php if($selected_item['ninjutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.$selected_item['ninjutsu'].'] em Ninjutsu<br />'; ?>
        <?php if($selected_item['genjutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.$selected_item['genjutsu'].'] em Genjutsu<br />'; ?></b>
        <span style="font-size:14px;">Valor Normal: <b><?php echo number_format($selected_item['valor'],2,',','.'); ?> yens</b></span>
    </td>
</tr>
<?php if(empty($sales)) { ?>
<tr><td colspan="3"><div class="sep"></div></td></tr>
<tr><td colspan="3"><div class="aviso">Nenhum item encontrado.</div></td></tr>
<?php } else { foreach($sales as $sale) {
    $mtype = $sale['moeda_tipo'] ?? 'yens';
    $micon = $moeda_icons[$mtype] ?? '_img/yens.png';
    $mlbl  = $moeda_labels[$mtype] ?? 'Yens';
?>
<tr><td colspan="3"><div class="sep"></div></td></tr>
<tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
    <td align="left" colspan="2">
        <a href="?p=view&view=<?php echo strtolower($sale['usuario']); ?>"><?php echo htmlspecialchars($sale['usuario']); ?></a>
        vendendo por <b><?php echo number_format($sale['valor'],0,',','.'); ?> <img src="<?php echo htmlspecialchars($micon); ?>" width="20" height="20" align="absmiddle" style="margin:0 2px;" /> <?php echo htmlspecialchars($mlbl); ?></b>
        [+<?php echo $sale['upgrade']; ?>]
    </td>
    <td align="center" width="30%">
        <?php if($sale['usuarioid'] != $db['id']) { ?>
        <form method="post" action="?p=shops<?php if(isset($_GET['item'])) echo '&item='.$_GET['item']; ?>" style="display:inline;">
            <input type="hidden" name="buy_id" value="<?php echo $sale['id']; ?>" />
            <input type="submit" value="Comprar" class="botao" />
        </form> |
        <?php } ?>
        <a href="?p=viewshop&shop=<?php echo strtolower($sale['usuario']); ?>">Visitar Loja</a>
    </td>
</tr>
<?php } } ?>
</table>
<?php 
        }
    } catch (PDOException $e) {
        echo '<div class="aviso">Erro ao carregar item.</div>';
    }
} ?>
</div>
<div class="box_bottom"></div>
