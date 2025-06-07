<?php
require_once('Encrypt.php');
$c=new C_Encrypt();

if(isset($_GET['action'])){
	if(!isset($_GET['id'])){ echo "<script>self.location='?p=home'</script>"; exit(); }
	$id=$_GET['id'];
	$sqli=mysql_query("SELECT usuarioid,categoria FROM inventario WHERE id=".$id);
	$dbi=mysql_fetch_assoc($sqli);
	if($dbi['usuarioid']<>$db['id']){ echo "<script>self.location='?p=home'</script>"; exit(); }
	$categoria=$dbi['categoria'];
	$act=$_GET['action'];
	vn($id);
	mysql_query("UPDATE inventario SET status='off' WHERE usuarioid=".$db['id']." AND categoria='".$categoria."'");
	if($act=='on') mysql_query("UPDATE inventario SET status='on' WHERE id=".$id);
	if($act=='on') echo "<script>self.location='?p=inventory&msg=2'</script>"; else echo "<script>self.location='?p=inventory&msg=3'</script>";
}
if(isset($_GET['sell'])){
	$id=$_GET['sell'];
	$sqli=mysql_query("SELECT itemid, usuarioid FROM inventario WHERE id=".$id);
	$dbi=mysql_fetch_assoc($sqli);
	if($dbi['usuarioid']<>$db['id']){ echo "<script>self.location='?p=home'</script>"; exit(); }
	$data=date('Y-m-d H:i:s');
	$sqlv=mysql_query("SELECT valor FROM table_itens WHERE id=".$dbi['itemid']);
	$dbv=mysql_fetch_assoc($sqlv);
	mysql_query("DELETE FROM inventario WHERE id=".$id);
	if(mysql_affected_rows()==0){ echo "<script>self.location='?p=home'</script>"; exit(); }
	mysql_query("UPDATE usuarios SET yens=yens+".($dbv['valor']/2)." WHERE id=".$db['id']);
	echo "<script>self.location='?p=inventory&msg=4&value=".($dbv['valor']/2)."'</script>";
}
if(isset($_POST['ram_id'])){
	$id=$c->decode($_POST['ram_id'],$chaveuniversal);
	$tipo=$c->decode($_POST['ram_tipo'],$chaveuniversal);
	vn($id); vn($tipo);
	$sqlr=mysql_query("SELECT count(id) conta FROM ramen WHERE usuarioid=".$db['id']." AND id=".$id);
	$dbr=mysql_fetch_assoc($sqlr);
	if($dbr['conta']>0){
		$energia=$db['energia'];
		switch($tipo){
			case 1: $hp=50; break;
			case 2: $hp=100; break;
			case 3: $hp=250; break;
			case 4: $hp=500; break;
			case 5: $hp=1000; break;
		}
		if($energia+$hp>=$db['energiamax']) $energia=$db['energiamax']; else $energia=$energia+$hp;
		mysql_query("DELETE FROM ramen WHERE id=".$id);
		mysql_query("UPDATE usuarios SET energia=$energia WHERE id=".$db['id']);
		echo "<script>self.location='?p=inventory&msg=1&e=".$hp."'</script>"; exit();
	}
}
$sqlr=mysql_query("SELECT * FROM ramen WHERE usuarioid=".$db['id']);
$dbr=mysql_fetch_assoc($sqlr);
$sqli=mysql_query("SELECT i.id,i.status,i.upgrade,t.categoria,t.descricao,t.taijutsu,t.ninjutsu,t.genjutsu,t.nome,t.imagem,t.valor FROM inventario i LEFT OUTER JOIN table_itens t ON i.itemid=t.id WHERE i.usuarioid=".$db['id']." AND venda='nao' ORDER BY status ASC");
$dbi=mysql_fetch_assoc($sqli);
?>
<div class="box_top">Meu Inventário</div>
<div class="box_middle">Abaixo está todo seu inventário. Estarão listados aqui todos os itens que existirem em sua conta, incluindo os equipamentos equipados e não-equipados. Lembre-se que ao equipar um certo tipo de item, qualquer outro que seja do mesmo tipo será automaticamente substituído. Você pode também vender o item para o comércio de sua vila, ou então anunciar em sua <a href="?p=myshop">própria loja</a>, para que outros jogadores o comprem.<div class="sep"></div>
	<div align="center"><a href="?p=inventory">Itens</a> | <a href="?p=parchments">Pergaminhos</a></div>
    <div class="sep"></div>
	<div style="background:url(_img/gradient.jpg) repeat-y;color:#FFFFAA;"><img src="_img/yens.png" width="14" height="14" /> <b>Meus Yens: <?php echo number_format($db['yens'],2,',','.'); ?> yens</b></div>
	<?php
	if(isset($_GET['msg'])){
		switch($_GET['msg']){
			case 1: $msg='Ramen utilizado com sucesso!<br />Sua energia foi regenerada em <b>'.$_GET['e'].' pontos.</b>'; break;
			case 2: $msg='Item equipado com sucesso!'; break;
			case 3: $msg='Item desequipado com sucesso!'; break;
			case 4: if(!isset($_GET['value'])) $value=0; else $value=$_GET['value']; $msg='Item vendido com sucesso! Foram creditados '.number_format($value,2,',','.').' yens em sua conta.'; break;
		}
	echo '<div class="sep"></div><div class="aviso">'.$msg.'</div>';
	}
	?>
    <table width="100%" cellpadding="0" cellspacing="1">
    <?php if(mysql_num_rows($sqlr)>0) do{
	switch($dbr['ramenid']){
		case 1: $nome='Gohan'; $reg=50; break;
		case 2: $nome='Sushi'; $reg=100; break;
		case 3: $nome='Porção de Peixe Empanado'; $reg=250; break;
		case 4: $nome='Porção de Sashimi'; $reg=500; break;
		case 5: $nome='Ramen'; $reg='1.000'; break;
	}
	?>
    <tr>
    	<td colspan="3"><div class="sep"></div></td>
    </tr>
    <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
    	<td width="140"><img src="_img/ramen/ramen<?php echo $dbr['ramenid']; ?>.jpg" /></td>
        <td><b><?php echo $nome; ?></b><br /><span class="sub2">Regenera <?php echo $reg; ?> pontos de Energia</span>
        <form method="post" action="?p=inventory" onsubmit="subm.value='Carregando...';subm.disabled=true;">
        <input type="hidden" id="ram_id" name="ram_id" value="<?php echo $c->encode($dbr['id'],$chaveuniversal); ?>" />
        <input type="hidden" id="ram_tipo" name="ram_tipo" value="<?php echo $c->encode($dbr['ramenid'],$chaveuniversal); ?>" />
        <input type="submit" id="subm" name="subm" class="botao" value="Usar" />
        </form>
        </td>
    </tr>
    <?php } while($dbr=mysql_fetch_assoc($sqlr)); ?>
    <?php if(mysql_num_rows($sqli)>0) do{ ?>
    <tr>
    	<td colspan="2"><div class="sep"></div></td>
    </tr>
    <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
    	<td align="center" width="140"><img src="_img/equipamentos/<?php echo $dbi['imagem']; ?>.jpg" /></td>
        <td style="padding:5px;">
        	<b><?php echo $dbi['nome']; ?><?php if($dbi['upgrade']>0) echo ' +'.$dbi['upgrade']; ?></b><br />
            <span class="sub2"><?php echo $dbi['descricao']; ?></span><br />
            <b><?php if($dbi['taijutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbi['taijutsu']+$dbi['upgrade']).'] em Taijutsu<br />'; ?>
            <?php if($dbi['ninjutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbi['ninjutsu']+$dbi['upgrade']).'] em Ninjutsu<br />'; ?>
            <?php if($dbi['genjutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbi['genjutsu']+$dbi['upgrade']).'] em Genjutsu<br />'; ?></b>
            <br />
            <b>Valor de Venda</b>: <?php echo number_format(($dbi['valor']/2),2,',','.'); ?> yens<br />
          <a href="?p=inventory&action=<?php if($dbi['status']=='off') echo 'on'; else echo 'off'; ?>&id=<?php echo $dbi['id']; ?>"><?php if($dbi['status']=='off') echo 'Equipar'; else echo 'Retirar'; ?></a> | <a href="?p=inventory&sell=<?php echo $dbi['id']; ?>">Vender</a> | <a href="?p=addmy&id=<?php echo $dbi['id']; ?>">Anunciar em Minha Loja</a><?php if($dbi['upgrade']<10){ ?> | <a href="?p=blacksmith&id=<?php echo $dbi['id']; ?>">Aprimorar</a><?php } ?>
          </td>
  	</tr>
    <?php } while($dbi=mysql_fetch_assoc($sqli)); ?>
    </table>
    <?php if((mysql_num_rows($sqlr)==0)&&(mysql_num_rows($sqli)==0)){ ?>
    <div class="sep"></div>
    <div class="aviso">Nenhum item em seu inventário.</div>
    <?php } ?>
</div>
<div class="box_bottom"></div>
<?php
@mysql_free_result($sqlr);
@mysql_free_result($sqli);
?>