<?php
$categoria='boots';
$dif=(floor($db['taijutsu']+$db['ninjutsu']+$db['genjutsu'])/3)+7;
$sqls=mysql_query("SELECT * FROM table_itens WHERE categoria='calcado' AND reqtai<$dif AND reqnin<$dif AND reqgen<$dif ORDER BY reqtai ASC");
$dbs=mysql_fetch_assoc($sqls);
?>
<table width="100%" cellpadding="0" cellspacing="1">
    <?php if(mysql_num_rows($sqls)==0) echo '<tr><td colspan="3"><div class="aviso">Nenhum item encontrado.</div></td></tr>'; else do{ if(date('Y-m-d H:i:s')<$db['vip']) $dbs['valor']=$dbs['valor']-($dbs['valor']*0.15); ?>
    <tr style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
    	<td align="center" width="140"><img src="_img/equipamentos/<?php echo $dbs['imagem']; ?>.jpg" /></td>
        <td valign="top" style="padding:5px;text-align:center;">
        	<b><?php echo $dbs['nome']; ?></b><br />
            <span class="sub2"><?php echo $dbs['descricao']; ?></span><br />
            <b><?php if($dbs['taijutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.$dbs['taijutsu'].'] em Taijutsu<br />'; ?>
            <?php if($dbs['ninjutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.$dbs['ninjutsu'].'] em Ninjutsu<br />'; ?>
            <?php if($dbs['genjutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.$dbs['genjutsu'].'] em Genjutsu<br />'; ?></b>
      </td>
        <td align="center" width="20%">
        	<b>Atributos Mínimos</b><br />
            <span class="sub2"><?php echo $dbs['reqtai']; ?> pontos</span><br /><br />
            <b>Valor Unitário</b><br />
            <span class="sub2"><?php echo number_format($dbs['valor'],2,',','.'); ?> yens</span><br /><br />
            <form method="post" action="?p=shop" onsubmit="subm.value='Carregando...';subm.disabled=true;">
            <input type="hidden" id="buy_id" name="buy_id" value="<?php echo $dbs['id']; ?>" />
            <input type="hidden" id="buy_page" name="buy_page" value="<?php echo $categoria; ?>" /> 
            <input type="hidden" id="buy_cat" name="buy_cat" value="<?php echo $dbs['categoria']; ?>" />
            <?php if($dbs['vip']=='nao'){ ?><input type="submit" id="subm" name="subm" class="botao" value="Comprar" /><?php } else { if(date('Y-m-d H:i:s')>=$db['vip']) echo '<span class="sub2">Exclusiva para VIP.</span>'; else { ?><input type="submit" id="subm" name="subm" class="botao" value="Comprar" /><?php }} ?>
            </form>
        </td>
  </tr>
    <?php } while($dbs=mysql_fetch_assoc($sqls)); ?>
</table>
<?php
@mysql_free_result($sqls);
?>