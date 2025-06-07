<?php
if(date('Y-m-d H:i:s')>=$db['vip']){ echo "<script>self.location='?p=msgvip'</script>"; break; }
if(isset($_GET['buy'])){
	vn($_GET['buy']);
	$sqli=mysql_query("SELECT i.usuarioid,i.venda,i.valor,t.nome FROM inventario i LEFT OUTER JOIN table_itens t ON i.itemid=t.id WHERE i.id=".$_GET['buy']);
	if(mysql_num_rows($sqli)==0){ echo "<script>self.location='?p=shops&item=".$_GET['item']."&msg=1'</script>"; break; }
	$dbi=mysql_fetch_assoc($sqli);
	if($dbi['usuarioid']==$db['id']){ echo "<script>self.location='?p=shops&item=".$_GET['item']."&msg=3'</script>"; break; }
	if($dbi['venda']=='nao'){ echo "<script>self.location='?p=home'</script>"; break; }
	if($db['yens']<$dbi['valor']){ echo "<script>self.location='?p=shops&item=".$_GET['item']."&msg=2'</script>"; break; }
	mysql_query("UPDATE usuarios SET yens=yens-".$dbi['valor'].", compraloja=compraloja+1 WHERE id=".$db['id']);
	mysql_query("INSERT INTO vendas (usuarioid, valor) VALUES (".$dbi['usuarioid'].", ".$dbi['valor'].")");
	mysql_query("INSERT INTO mensagens (data, origem, destino, assunto, msg) VALUES ('".date('Y-m-d H:i:s')."', 0, ".$dbi['usuarioid'].", 'Item Vendido!', 'Parabéns, o item <b>".$dbi['nome']."</b>, que estava anunciado em sua loja, foi vendido por <b>".number_format($dbi['valor'],2,',','.')." yens</b>. O valor ficará guardado em sua loja até que você faça um saque para sua reserva.')");
	mysql_query("UPDATE inventario SET venda='nao', valor=0, usuarioid=".$db['id']." WHERE id=".$_GET['buy']);
	echo "<script>self.location='?p=shops&item=".$_GET['item']."&msg=4'</script>";
}
$reqtai=$db['taijutsu']+7;
$reqnin=$db['ninjutsu']+7;
$reqgen=$db['genjutsu']+7;
$sqls=mysql_query("SELECT id,nome,categoria FROM table_itens ORDER BY nome ASC");
$dbs=mysql_fetch_assoc($sqls);
?>
<div class="box_top">Comércio Interno</div>
<div class="box_middle">Bem-vindo ao comércio interno do jogo. Aqui você encontrará as lojas criadas pelos próprios ninjas. Normalmente, os itens vendidos aqui são mais baratos do que os itens vendidos no comércio da vila. Selecione o item que deseja visualizar, e boas compras!<div class="sep"></div>
	<?php
	if(isset($_GET['msg'])){
		switch($_GET['msg']){
			case 1: $msg='Este item já foi comprado por outra pessoa.'; break;
			case 2: $msg='Yens insuficientes para comprar este item.'; break;
			case 3: $msg='Você não pode comprar um item que já lhe pertence.'; break;
			case 4: $msg='Item comprado com sucesso!'; break;
		}
	echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>';
	}
	?>
	<b>Selecione o Item:</b><br />
    <form method="get" action="?p=shops">
    <input type="hidden" id="p" name="p" value="shops" />
	<select id="item" name="item">
    	<?php do{
		switch($dbs['categoria']){
			case 'arma': $categoria='Arma'; break;
			case 'vestimenta': $categoria='Vestimenta'; break;
			case 'calcado': $categoria='Calçado'; break;
		}
		?>
        <option value="<?php echo $dbs['id']; ?>"<?php if((isset($_GET['item']))&&($_GET['item']==$dbs['id'])) echo ' selected="selected"'; ?>><?php echo $dbs['nome']; ?> [<?php echo $categoria; ?>]</option>
        <?php } while($dbs=mysql_fetch_assoc($sqls)); ?>
    </select>&nbsp;<input type="submit" class="botao" value="Pesquisar" /></form>
    <span class="sub2">Selecione o item que deseja pesquisar.</span>
    <?php if(isset($_GET['item'])){ ?>
    <?php
	vn($_GET['item']);
	$sqlv=mysql_query("SELECT * FROM table_itens WHERE id=".$_GET['item']);
	$dbv=mysql_fetch_assoc($sqlv);
	if(mysql_num_rows($sqlv)==0){ echo "<script>self.location='?p=home'</script>"; break; }
	$sqli=mysql_query("SELECT i.id,i.valor,i.usuarioid,i.upgrade,u.usuario FROM inventario i LEFT OUTER JOIN usuarios u ON i.usuarioid=u.id WHERE i.itemid=".$_GET['item']." AND i.venda='sim' ORDER BY i.id ASC");
	$dbi=mysql_fetch_assoc($sqli);
	?>
    <table width="100%" cellpadding="0" cellspacing="1">
    <tr>
    	<td colspan="3"><div class="sep"></div></td>
    </tr>
    <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
    	<td align="center" width="140" valign="top"><img src="_img/equipamentos/<?php echo $dbv['imagem']; ?>.jpg" /></td>
        <td colspan="2" style="padding:5px;">
        	<b><?php echo $dbv['nome']; ?></b><br />
            <span class="sub2"><?php echo $dbv['descricao']; ?></span><br />
            <b><?php if($dbv['taijutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.$dbv['taijutsu'].'] em Taijutsu<br />'; ?>
            <?php if($dbv['ninjutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.$dbv['ninjutsu'].'] em Ninjutsu<br />'; ?>
            <?php if($dbv['genjutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.$dbv['genjutsu'].'] em Genjutsu<br />'; ?></b>
            <br />
            <span style="font-size:14px;">Valor Normal: <b><?php echo number_format($dbv['valor'],2,',','.'); ?> yens</b></span>
          </td>
  	</tr>
    <?php if(mysql_num_rows($sqli)==0) echo '<tr><td colspan="2"><div class="sep"></div></td></tr><tr<tr><td colspan="2"><div class="aviso">Nenhum item encontrado.</div></td></tr>'; else do{ ?>
    <tr>
    	<td colspan="3"><div class="sep"></div></td>
    </tr>
    <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
    	<td align="left" colspan="2"><a href="?p=view&view=<?php echo strtolower($dbi['usuario']); ?>"><?php echo $dbi['usuario']; ?></a> vendendo por <b><?php echo number_format($dbi['valor'],2,',','.'); ?> yens</b> [+<?php echo $dbi['upgrade']; ?>]</td>
        <td align="center" width="30%"><?php if($dbi['usuarioid']<>$db['id']){ ?><a href="?p=shops<?php if(isset($_GET['item'])) echo '&item='.$_GET['item']; ?>&buy=<?php echo $dbi['id']; ?>">Comprar</a> | <?php } ?><a href="?p=viewshop&shop=<?php echo strtolower($dbi['usuario']); ?>">Visitar Loja</a></td>
    </tr>
    <?php } while($dbi=mysql_fetch_assoc($sqli)); ?>
    </table>
    <?php } ?>
</div>
<div class="box_bottom"></div>