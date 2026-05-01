<?php
$moeda_labels  = ['yens'=>'Yens','cristal1'=>'Cristal Refinado','cristal2'=>'Cristal Bruto','cristal3'=>'Chakra Forjado'];
$moeda_icons   = ['yens'=>'_img/yens.png','cristal1'=>'_img/ferreiro/Cristal de Chakra Refinado.png','cristal2'=>'_img/ferreiro/Cristal de Chakra Bruto.png','cristal3'=>'_img/ferreiro/Chakra Forjado.png'];
$cristal_iid   = ['cristal1'=>1,'cristal2'=>2,'cristal3'=>3];

if(isset($_GET['buy'])){
        $buy_id = filter_var($_GET['buy'], FILTER_VALIDATE_INT);
        $shop   = isset($_GET['shop']) ? strtolower($_GET['shop']) : '';
        if(!$buy_id){ echo "<script>self.location='?p=viewshop&shop=".urlencode($shop)."&msg=1'</script>"; exit; }

        try {
                $conexao->beginTransaction();

                $stmt = $conexao->prepare("SELECT i.id,i.usuarioid,i.venda,i.valor,i.moeda_tipo,t.nome FROM inventario i LEFT JOIN table_itens t ON i.itemid=t.id WHERE i.id=? AND i.venda='sim'");
                $stmt->execute([$buy_id]);
                $dbi = $stmt->fetch(PDO::FETCH_ASSOC);

                if(!$dbi){
                        $conexao->rollback();
                        echo "<script>self.location='?p=viewshop&shop=".urlencode($shop)."&msg=1'</script>"; exit;
                }
                if($dbi['usuarioid']==$db['id']){
                        $conexao->rollback();
                        echo "<script>self.location='?p=viewshop&shop=".urlencode($shop)."&msg=3'</script>"; exit;
                }

                $moeda  = $dbi['moeda_tipo'] ?? 'yens';
                $valor  = intval($dbi['valor']);
                $seller = $dbi['usuarioid'];

                if($moeda === 'yens'){
                        if($db['yens'] < $dbi['valor']){
                                $conexao->rollback();
                                echo "<script>self.location='?p=viewshop&shop=".urlencode($shop)."&msg=2&m=".urlencode($moeda)."'</script>"; exit;
                        }
                        $conexao->prepare("UPDATE usuarios SET yens=yens-?, compraloja=compraloja+1 WHERE id=?")->execute([$dbi['valor'], $db['id']]);
                        $conexao->prepare("INSERT INTO vendas (usuarioid, valor, moeda_tipo) VALUES (?, ?, 'yens')")->execute([$seller, $dbi['valor']]);

                } elseif(isset($cristal_iid[$moeda])){
                        $cid    = $cristal_iid[$moeda];
                        $needed = $valor;
                        $sc = $conexao->prepare("SELECT COUNT(*) as cnt FROM usaveis WHERE usuarioid=? AND itemid=? AND status='off'");
                        $sc->execute([$db['id'], $cid]);
                        $cnt = $sc->fetch(PDO::FETCH_ASSOC)['cnt'];
                        if($cnt < $needed){
                                $conexao->rollback();
                                echo "<script>self.location='?p=viewshop&shop=".urlencode($shop)."&msg=2&m=".urlencode($moeda)."'</script>"; exit;
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
                $msg_txt = 'O item <b>'.htmlspecialchars($dbi['nome']).'</b> foi vendido por <b>'.number_format($valor,0,',','.').' '.$mlbl.'</b>. O valor ficará guardado até que você faça um saque.';
                $conexao->prepare("INSERT INTO mensagens (data, origem, destino, assunto, msg) VALUES (?, 0, ?, 'Item Vendido!', ?)")->execute([date('Y-m-d H:i:s'), $seller, $msg_txt]);

                // Histórico permanente do mercado
                try {
                    $hi = $conexao->prepare("SELECT t.imagem FROM inventario i LEFT JOIN table_itens t ON i.itemid=t.id WHERE i.id=?");
                    $hi->execute([$buy_id]);
                    $hrow = $hi->fetch(PDO::FETCH_ASSOC) ?: ['imagem'=>''];
                    $conexao->prepare("INSERT INTO mercado_historico (vendedor_id, comprador_id, item_id, item_nome, item_imagem, valor, moeda_tipo, data) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                            ->execute([$seller, $db['id'], $buy_id, $dbi['nome'] ?? '', $hrow['imagem'] ?? '', $valor, $moeda, date('Y-m-d H:i:s')]);
                } catch (Throwable $e) { /* não bloqueia a venda */ }

                // Snapshot do nome do vendedor (para o tag no inventário do comprador)
                $sn = $conexao->prepare("SELECT usuario FROM usuarios WHERE id=?");
                $sn->execute([$seller]);
                $sellerNome = $sn->fetchColumn() ?: '';
                $conexao->prepare("UPDATE inventario SET venda='nao', valor=0, moeda_tipo='yens', usuarioid=?, comprado_de_id=?, comprado_de_nome=?, comprado_valor=?, comprado_moeda=?, comprado_data=NOW() WHERE id=?")
                        ->execute([$db['id'], $seller, $sellerNome, $valor, $moeda, $buy_id]);

                $conexao->commit();
                echo "<script>self.location='?p=viewshop&shop=".urlencode($shop)."&msg=4'</script>";
                exit;
        } catch(PDOException $e) {
                if($conexao->inTransaction()) $conexao->rollback();
                echo "<script>self.location='?p=viewshop&shop=".urlencode($shop)."&msg=6'</script>"; exit;
        }
}

if(!isset($_GET['shop'])){ echo "<script>self.location='?p=home'</script>"; exit; }

try {
        $stmt_user = $conexao->prepare("SELECT id FROM usuarios WHERE LOWER(usuario)=LOWER(?)");
        $stmt_user->execute([$_GET['shop']]);
        $dbv = $stmt_user->fetch(PDO::FETCH_ASSOC);

        if(!$dbv){ echo "<script>self.location='?p=home'</script>"; exit; }

        $stmt_items = $conexao->prepare("SELECT i.id,i.valor as anunciado,i.usuarioid,i.upgrade,i.moeda_tipo,t.categoria,t.descricao,t.taijutsu,t.ninjutsu,t.genjutsu,t.nome,t.imagem,t.valor FROM inventario i LEFT JOIN table_itens t ON i.itemid=t.id WHERE i.usuarioid=? AND venda='sim' ORDER BY i.id ASC");
        $stmt_items->execute([$dbv['id']]);
        $shop_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
        echo "<script>self.location='?p=home'</script>";
        exit;
}
?>
<div class="box_top">Loja de <?php echo htmlspecialchars(ucfirst($_GET['shop'])); ?></div>
<div class="box_middle">Abaixo estão os itens da loja de <?php echo htmlspecialchars(ucfirst($_GET['shop'])); ?>.
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
                if(!empty($msg)) echo '<div class="sep"></div><div class="aviso">'.htmlspecialchars($msg).'</div>';
        }
        ?>
        <table width="100%" cellpadding="0" cellspacing="1">
<?php if(empty($shop_items)) { ?>
<tr><td colspan="2"><div class="sep"></div></td></tr>
<tr><td colspan="2"><div class="aviso">Nenhum item na loja de <?php echo htmlspecialchars(ucfirst($_GET['shop'])); ?>.</div></td></tr>
<?php } else { foreach($shop_items as $dbi) {
        $mtype = $dbi['moeda_tipo'] ?? 'yens';
        $micon = $moeda_icons[$mtype] ?? '_img/yens.png';
        $mlbl  = $moeda_labels[$mtype] ?? 'Yens';
?>
<tr><td colspan="2"><div class="sep"></div></td></tr>
<tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
        <td align="center" width="140" valign="top"><img src="_img/equipamentos/<?php echo htmlspecialchars($dbi['imagem']); ?>.png" /></td>
        <td style="padding:5px;">
                <b><?php echo htmlspecialchars($dbi['nome']); ?><?php if($dbi['upgrade']>0) echo ' +'.$dbi['upgrade']; ?></b><br />
                <span class="sub2"><?php echo htmlspecialchars($dbi['descricao']); ?></span><br />
                <b><?php if($dbi['taijutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbi['taijutsu']+$dbi['upgrade']).'] em Taijutsu<br />'; ?>
                <?php if($dbi['ninjutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbi['ninjutsu']+$dbi['upgrade']).'] em Ninjutsu<br />'; ?>
                <?php if($dbi['genjutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbi['genjutsu']+$dbi['upgrade']).'] em Genjutsu<br />'; ?></b>
                <br />
                <span style="font-size:14px;">Preço: <b><?php echo number_format($dbi['anunciado'],0,',','.'); ?> <img src="<?php echo htmlspecialchars($micon); ?>" width="20" height="20" align="absmiddle" style="margin:0 2px;" /> <?php echo htmlspecialchars($mlbl); ?></b></span><br />
                <span class="sub2">Valor Normal: <?php echo number_format($dbi['valor'],2,',','.'); ?> yens</span>
                <?php if($db['id'] != $dbi['usuarioid']) { ?>
                <br /><br />
                <input type="button" class="botao" value="Comprar" onclick="javascript:location.href='?p=viewshop&shop=<?php echo urlencode(strtolower($_GET['shop'])); ?>&buy=<?php echo $dbi['id']; ?>'" />
                <?php } ?>
        </td>
</tr>
<?php } } ?>
</table>
</div>
<div class="box_bottom"></div>
