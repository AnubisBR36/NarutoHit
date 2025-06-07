<?php
$sqls=mysql_query("SELECT i.id,i.valor anunciado,i.upgrade,t.categoria,t.descricao,t.taijutsu,t.ninjutsu,t.genjutsu,t.nome,t.imagem,t.valor FROM inventario i LEFT OUTER JOIN table_itens t ON i.itemid=t.id WHERE i.usuarioid=".$db['id']." AND venda='sim' ORDER BY id ASC");
$dbs=mysql_fetch_assoc($sqls);
if(mysql_num_rows($sqls)>0){
?>
<div class="box_top">Minha Loja</div>
<div class="box_middle">Alguns itens sendo vendidos na loja de <?php echo $db['usuario']; ?>.<div class="sep"></div>
	<table width="100%" cellspacing="1" cellpadding="0">
    	<?php do{ ?>
        <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
            <td align="center" width="140" valign="top"><img src="_img/equipamentos/<?php echo $dbs['imagem']; ?>.jpg" /></td>
            <td style="padding:5px;">
                <b><?php echo $dbs['nome']; ?><?php if($dbs['upgrade']>0) echo ' +'.$dbs['upgrade']; ?></b><br />
                <span class="sub2"><?php echo $dbs['descricao']; ?></span><br />
                <b><?php if($dbs['taijutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbs['taijutsu']+$dbs['upgrade']).'] em Taijutsu<br />'; ?>
                <?php if($dbs['ninjutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbs['ninjutsu']+$dbs['upgrade']).'] em Ninjutsu<br />'; ?>
                <?php if($dbs['genjutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbs['genjutsu']+$dbs['upgrade']).'] em Genjutsu<br />'; ?></b>
                <br />
                <span style="font-size:14px;">Valor Anunciado: <b><?php echo number_format($dbs['anunciado'],2,',','.'); ?> yens</b></span><br />
                <span class="sub2">Valor Normal: <?php echo number_format($dbs['valor'],2,',','.'); ?> yens</span>
              </td>
        </tr>
        <tr>
        	<td colspan="2"><div class="sep"></div></td>
        </tr>
        <?php } while($dbs=mysql_fetch_assoc($sqls)); ?>
    </table>
    <div align="center"><input type="button" class="botao" value="Visualizar Loja" onclick="javascript:location.href='?p=viewshop&shop=<?php echo strtolower($db['usuario']); ?>'" /></div>
</div>
<div class="box_bottom"></div>
<?php } ?>