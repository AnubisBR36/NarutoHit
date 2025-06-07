<?php
if(date('Y-m-d H:i:s')>=$db['vip']){ echo "<script>self.location='?p=msgvip'</script>"; break; }
if(isset($_GET['buy'])){
	vn($_GET['buy']);
	$sqli=mysql_query("SELECT i.usuarioid,i.venda,i.valor,t.nome FROM inventario i LEFT OUTER JOIN table_itens t ON i.itemid=t.id WHERE i.id=".$_GET['buy']);
	if(mysql_num_rows($sqli)==0){ echo "<script>self.location='?p=viewshop&shop=".strtolower($_GET['shop'])."&msg=1'</script>"; break; }
	$dbi=mysql_fetch_assoc($sqli);
	if($dbi['usuarioid']==$db['id']){ echo "<script>self.location='?p=viewshop&shop=".strtolower($_GET['shop'])."&msg=3'</script>"; break; }
	if($dbi['venda']=='nao'){ echo "<script>self.location='?p=home'</script>"; break; }
	if($db['yens']<$dbi['valor']){ echo "<script>self.location='?p=viewshop&shop=".strtolower($_GET['shop'])."&msg=2'</script>"; break; }
	mysql_query("UPDATE usuarios SET yens=yens-".$dbi['valor'].", compraloja=compraloja+1 WHERE id=".$db['id']);
	mysql_query("INSERT INTO vendas (usuarioid, valor) VALUES (".$dbi['usuarioid'].", ".$dbi['valor'].")");
	mysql_query("INSERT INTO mensagens (data, origem, destino, assunto, msg) VALUES ('".date('Y-m-d H:i:s')."', 0, ".$dbi['usuarioid'].", 'Item Vendido!', 'Parabéns, o item <b>".$dbi['nome']."</b>, que estava anunciado em sua loja, foi vendido por <b>".number_format($dbi['valor'],2,',','.')." yens</b>. O valor ficará guardado em sua loja até que você faça um saque para sua reserva.')");
	mysql_query("UPDATE inventario SET venda='nao', valor=0, usuarioid=".$db['id']." WHERE id=".$_GET['buy']);
	echo "<script>self.location='?p=viewshop&shop=".strtolower($_GET['shop'])."&msg=4'</script>";
}
if(!isset($_GET['shop'])){ echo "<script>self.location='?p=home'</script>"; break; }
$sqlv=mysql_query("SELECT id FROM usuarios WHERE usuario='".$_GET['shop']."'");
if(mysql_num_rows($sqlv)==0){ echo "<script>self.location='?p=home'</script>"; break; }
$dbv=mysql_fetch_assoc($sqlv);
$sqli=mysql_query("SELECT i.id,i.valor anunciado,i.usuarioid,i.upgrade,t.categoria,t.descricao,t.taijutsu,t.ninjutsu,t.genjutsu,t.nome,t.imagem,t.valor FROM inventario i LEFT OUTER JOIN table_itens t ON i.itemid=t.id WHERE i.usuarioid=".$dbv['id']." AND venda='sim' ORDER BY id ASC");
$dbi=mysql_fetch_assoc($sqli);
?>
<div class="box_top">Loja de <?php echo ucfirst($_GET['shop']); ?></div>
<div class="box_middle">Abaixo estão os itens da loja de <?php echo ucfirst($_GET['shop']); ?>.
	<?php
	if(isset($_GET['msg'])){
		switch($_GET['msg']){
			case 1: $msg='Este item já foi comprado por outra pessoa.'; break;
			case 2: $msg='Yens insuficientes para comprar este item.'; break;
			case 3: $msg='Você não pode comprar um item que já lhe pertence.'; break;
			case 4: $msg='Item comprado com sucesso!'; break;
		}
	echo '<div class="sep"></div><div class="aviso">'.$msg.'</div>';
	}
	?>
	<table width="100%" cellpadding="0" cellspacing="1">
	<?php if(mysql_num_rows($sqli)==0) echo '<tr><td colspan="2"><div class="sep"></div></td></tr><tr<tr><td colspan="2"><div class="aviso">Nenhum item na loja de '.ucfirst($_GET['shop']).'.</div></td></tr>'; else do{ ?>
    <tr>
    	<td colspan="2"><div class="sep"></div></td>
    </tr>
    <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
    	<td align="center" width="140" valign="top"><img src="_img/equipamentos/<?php echo $dbi['imagem']; ?>.jpg" /></td>
        <td style="padding:5px;">
        	<b><?php echo $dbi['nome']; ?><?php if($dbi['upgrade']>0) echo ' +'.$dbi['upgrade']; ?></b><br />
            <span class="sub2"><?php echo $dbi['descricao']; ?></span><br />
            <b><?php if($dbi['taijutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbi['taijutsu']+$dbi['upgrade']).'] em Taijutsu<br />'; ?>
            <?php if($dbi['ninjutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbi['ninjutsu']+$dbi['upgrade']).'] em Ninjutsu<br />'; ?>
            <?php if($dbi['genjutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbi['genjutsu']+$dbi['upgrade']).'] em Genjutsu<br />'; ?></b>
            <br />
            <span style="font-size:14px;">Valor Anunciado: <b><?php echo number_format($dbi['anunciado'],2,',','.'); ?> yens</b></span><br />
            <span class="sub2">Valor Normal: <?php echo number_format($dbi['valor'],2,',','.'); ?> yens</span>
            <?php
            if($db['id']<>$dbi['usuarioid']){ ?>
            <br /><br />
            <input type="button" class="botao" value="Comprar" onclick="javascript:location.href='?p=viewshop&shop=<?php echo strtolower($_GET['shop']); ?>&buy=<?php echo $dbi['id']; ?>'" />
            <?php } ?>
          </td>
  	</tr>
    <?php } while($dbi=mysql_fetch_assoc($sqli)); ?>
    </table>
</div>
<div class="box_bottom"></div>