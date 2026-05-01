<?php
require_once('conexao.php');

$moeda_labels = ['yens'=>'Yens','cristal1'=>'Cristal Refinado','cristal2'=>'Cristal Bruto','cristal3'=>'Chakra Forjado'];
$moeda_icons  = ['yens'=>'_img/yens.png','cristal1'=>'_img/ferreiro/Cristal de Chakra Refinado.png','cristal2'=>'_img/ferreiro/Cristal de Chakra Bruto.png','cristal3'=>'_img/ferreiro/Chakra Forjado.png'];
$cristal_iid  = ['cristal1'=>1,'cristal2'=>2,'cristal3'=>3];

if(date('Y-m-d H:i:s')>=$db['vip']){ echo "<script>self.location='?p=msgvip'</script>"; exit; }

if(isset($_GET['receive'])){
        try {
                $stmt = $conexao->prepare("SELECT moeda_tipo, SUM(valor) as soma FROM vendas WHERE usuarioid=? GROUP BY moeda_tipo");
                $stmt->execute([$db['id']]);
                $somas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if(empty($somas)){ echo "<script>self.location='?p=home'</script>"; exit; }

                $conexao->beginTransaction();
                $stmt_delete = $conexao->prepare("DELETE FROM vendas WHERE usuarioid=?");
                $stmt_delete->execute([$db['id']]);

                foreach($somas as $soma_row){
                        $tipo  = $soma_row['moeda_tipo'];
                        $valor = $soma_row['soma'];
                        if($tipo === 'yens'){
                                $conexao->prepare("UPDATE usuarios SET yens=yens+?, yens_fat=yens_fat+?, venda=venda+? WHERE id=?")->execute([$valor,$valor,$valor,$db['id']]);
                        } elseif(isset($cristal_iid[$tipo])){
                                // Crédito de cristais: insere a quantidade vendida no inventário (usaveis)
                                $cid = $cristal_iid[$tipo];
                                $qty = (int)$valor;
                                if($qty > 0){
                                        $sa = $conexao->prepare("INSERT INTO usaveis (usuarioid, itemid, status) VALUES (?, ?, 'off')");
                                        for($i=0; $i<$qty; $i++) $sa->execute([$db['id'], $cid]);
                                }
                        }
                }
                $conexao->commit();
                echo "<script>self.location='?p=myshop&msg=3'</script>";
                exit;
        } catch(PDOException $e) {
                if($conexao->inTransaction()) $conexao->rollback();
                echo "<script>self.location='?p=home'</script>";
                exit;
        }
}

if(isset($_GET['remove'])){
        vn($_GET['remove']);
        try {
                $stmt = $conexao->prepare("SELECT usuarioid FROM inventario WHERE id=?");
                $stmt->execute([$_GET['remove']]);
                $dbi = $stmt->fetch(PDO::FETCH_ASSOC);

                if(!$dbi || $dbi['usuarioid'] != $db['id']){ echo "<script>self.location='?p=home'</script>"; exit; }

                $stmt_update = $conexao->prepare("UPDATE inventario SET venda='nao', valor=0, moeda_tipo='yens' WHERE id=?");
                $stmt_update->execute([$_GET['remove']]);

                echo "<script>self.location='?p=myshop&msg=2'</script>";
                exit;
        } catch(PDOException $e) {
                echo "<script>self.location='?p=home'</script>";
                exit;
        }
}

try {
        $stmt_vendas = $conexao->prepare("SELECT moeda_tipo, SUM(valor) as soma FROM vendas WHERE usuarioid=? GROUP BY moeda_tipo");
        $stmt_vendas->execute([$db['id']]);
        $pendentes = $stmt_vendas->fetchAll(PDO::FETCH_ASSOC);

        $stmt_itens = $conexao->prepare("SELECT i.id,i.valor as anunciado,i.upgrade,i.moeda_tipo,t.categoria,t.descricao,t.taijutsu,t.ninjutsu,t.genjutsu,t.nome,t.imagem,t.valor FROM inventario i LEFT OUTER JOIN table_itens t ON i.itemid=t.id WHERE i.usuarioid=? AND venda='sim' ORDER BY i.id ASC");
        $stmt_itens->execute([$db['id']]);
        $itens_loja = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
        $pendentes  = [];
        $itens_loja = [];
}
?>
<div class="box_top">Minha Loja</div>
<div class="box_middle">Abaixo estão os itens que você está vendendo em sua loja. Você pode utilizar esta página para remover os itens que não deseja mais vender. Caso venda algum item, o valor ganho ficará guardado em sua loja, e será adicionado à sua reserva apenas quando preferir.
        <?php
        if(isset($_GET['msg'])){
                switch($_GET['msg']){
                        case 1: $msg='Item anunciado com sucesso!'; break;
                        case 2: $msg='Item foi removido de sua loja.'; break;
                        case 3: $msg='Valores recebidos e adicionados à sua reserva!'; break;
                        case 4: if(!isset($_GET['value'])) $value=0; else $value=$_GET['value']; $msg='Item vendido com sucesso! Foram creditados '.number_format($value,2,',','.').' à sua conta.'; break;
                }
        echo '<div class="sep"></div><div class="aviso">'.$msg.'</div>';
        }
        ?>
        <table width="100%" cellpadding="0" cellspacing="1">
<?php if(!empty($pendentes)) { ?>
<tr><td colspan="2"><div class="sep"></div></td></tr>
<tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
        <td colspan="2">Você possui valores a receber:
        <?php foreach($pendentes as $p) {
                $p_dec = ($p['moeda_tipo'] === 'yens') ? 2 : 0; // yens com centavos; cristais inteiros
        ?>
                <b><?php echo number_format($p['soma'],$p_dec,',','.'); ?> <img src="<?php echo $moeda_icons[$p['moeda_tipo']] ?? '_img/yens.png'; ?>" width="16" height="16" align="absmiddle" /> <?php echo $moeda_labels[$p['moeda_tipo']] ?? $p['moeda_tipo']; ?></b><?php echo $p !== end($pendentes) ? ', ' : ''; ?>
        <?php } ?>
        . Clique <a href="?p=myshop&receive=true">aqui</a> para receber.</td>
</tr>
<?php } ?>
<?php foreach($itens_loja as $dbi) { 
        $mtype = $dbi['moeda_tipo'] ?? 'yens';
        $micon = $moeda_icons[$mtype] ?? '_img/yens.png';
        $mlbl  = $moeda_labels[$mtype] ?? 'Yens';
        $mdec  = ($mtype === 'yens') ? 2 : 0; // cristais não têm casas decimais
?>
<tr><td colspan="2"><div class="sep"></div></td></tr>
<tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
        <td align="center" width="140" valign="top"><img src="_img/equipamentos/<?php echo $dbi['imagem']; ?>.png" /></td>
        <td style="padding:5px;">
                <b><?php echo $dbi['nome']; ?><?php if($dbi['upgrade']>0) echo ' +'.$dbi['upgrade']; ?></b><br />
                <span class="sub2"><?php echo $dbi['descricao']; ?></span><br />
                <b><?php if($dbi['taijutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbi['taijutsu']+$dbi['upgrade']).'] em Taijutsu<br />'; ?>
                <?php if($dbi['ninjutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbi['ninjutsu']+$dbi['upgrade']).'] em Ninjutsu<br />'; ?>
                <?php if($dbi['genjutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbi['genjutsu']+$dbi['upgrade']).'] em Genjutsu<br />'; ?></b>
                <br />
                <span style="font-size:14px;">Preço: <b><?php echo number_format($dbi['anunciado'],$mdec,',','.'); ?> <img src="<?php echo $micon; ?>" width="16" height="16" align="absmiddle" /> <?php echo $mlbl; ?></b></span><br />
                <a href="?p=myshop&remove=<?php echo $dbi['id']; ?>">Remover</a>
        </td>
</tr>
<?php } ?>
</table>
<?php if(empty($itens_loja)){ ?>
<div class="sep"></div>
<div class="aviso">Nenhum item em sua loja.</div>
<div class="sep"></div>
<div align="center"><input type="button" class="botao" value="Adicionar Item" onclick="javascript:location.href='?p=inventory'" /></div>
<?php } ?>
</div>
<div class="box_bottom"></div>
