<?php
if(date('Y-m-d H:i:s')>=$db['vip']){ echo "<script>self.location='?p=msgvip'</script>"; break; }
if(isset($_POST['id'])){
	vn($_POST['valor']);
	if($_POST['valor']<=0) $valor=1; else $valor=$_POST['valor'];
	$sqli=mysql_query("SELECT * FROM inventario WHERE id=".$_POST['id']);
	$dbi=mysql_fetch_assoc($sqli);
	$sqlv=mysql_query("SELECT valor FROM table_itens WHERE id=".$dbi['itemid']);
	$dbv=mysql_fetch_assoc($sqlv);
	$maximo=(($dbi['upgrade']+1)*$dbv['valor']);
	if($_POST['valor']>$maximo){ echo "<script>self.location='?p=addmy&id=".$_POST['id']."&msg=1'</script>"; break; }
	if($dbi['usuarioid']<>$db['id']){ echo "<script>self.location='?p=home'</script>"; break; }
	mysql_query("UPDATE inventario SET venda='sim', status='off', valor=".$valor." WHERE id=".$_POST['id']);
	echo "<script>self.location='?p=myshop&msg=1'</script>";
}
if(!isset($_GET['id'])){ echo "<script>self.location='?p=home'</script>"; break; }
$sqli=mysql_query("SELECT i.id,i.status,i.upgrade,i.usuarioid,t.categoria,t.descricao,t.taijutsu,t.ninjutsu,t.genjutsu,t.nome,t.imagem,t.valor FROM inventario i LEFT OUTER JOIN table_itens t ON i.itemid=t.id WHERE i.id=".$_GET['id']." ORDER BY status ASC");
$dbi=mysql_fetch_assoc($sqli);
if(mysql_num_rows($sqli)==0){ echo "<script>self.location='?p=home'</script>"; break; }
if($dbi['usuarioid']<>$db['id']){ echo "<script>self.location='?p=home'</script>"; break; }
?>
<div class="box_top">Anunciar Item</div>
<div class="box_middle">Digite as informações de venda do item escolhido no formulário abaixo. Todo item anunciado por você ficará visível para os usuários em seu perfil, assim como no comércio do jogo. Caso esteja anunciando um item equipado, ele será automaticamente retirado após a confirmação.<div class="sep"></div>
	<?php
	if(isset($_GET['msg'])) echo '<div class="aviso">Valor excede em muitas vezes o valor normal.</div><div class="sep"></div>'; ?>
	<table width="100%" cellpading="0" cellspacing="1">
    <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
    	<td align="center" width="140" valign="top"><img src="_img/equipamentos/<?php echo $dbi['imagem']; ?>.jpg" /></td>
        <td style="padding:5px;">
        	<b><?php echo $dbi['nome']; ?><?php if($dbi['upgrade']>0) echo ' +'.$dbi['upgrade']; ?></b><br />
            <span class="sub2"><?php echo $dbi['descricao']; ?></span><br />
            <b><?php if($dbi['taijutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbi['taijutsu']+$dbi['upgrade']).'] em Taijutsu<br />'; ?>
            <?php if($dbi['ninjutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbi['ninjutsu']+$dbi['upgrade']).'] em Ninjutsu<br />'; ?>
            <?php if($dbi['genjutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbi['genjutsu']+$dbi['upgrade']).'] em Genjutsu<br />'; ?></b>
            <br />
            <b>Valor de Venda (em yens)</b><br />
            <form method="post" action="?p=addmy" onsubmit="subm.value='Carregando...';subm.disabled=true;">
            <input type="hidden" id="id" name="id" value="<?php echo $_GET['id']; ?>" />
        	<input type="text" id="valor" name="valor" value="" size="10" />&nbsp;
       		<input type="submit" id="subm" name="subm" class="botao" value="Anunciar" /><br />
            <span class="sub2">Valor máximo: <?php echo number_format((($dbi['upgrade']+1)*$dbi['valor']),2,',','.'); ?> yens</span>
            </form>
          </td>
  	</tr>
    </table>
</div>
<div class="box_bottom"></div>