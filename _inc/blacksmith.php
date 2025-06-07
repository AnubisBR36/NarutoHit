<?php
if(!isset($_GET['id'])){ echo "<script>self.location='?p=home'</script>"; return; }
vn($_GET['id']);
if(isset($_GET['add'])){
	vn($_GET['add']);
	$sqli=mysql_query("SELECT * FROM inventario WHERE id=".$_GET['id']);
	$dbi=mysql_fetch_assoc($sqli);
	if(mysql_num_rows($sqli)==0){ echo "<script>self.location='?p=home'</script>"; return; }
	if($dbi['usuarioid']<>$db['id']){ echo "<script>self.location='?p=home'</script>"; return; }
	$sqli=mysql_query("SELECT usuarioid FROM usaveis WHERE id=".$_GET['add']);
	$dbi=mysql_fetch_assoc($sqli);
	if(mysql_num_rows($sqli)==0){ echo "<script>self.location='?p=home'</script>"; return; }
	if($dbi['usuarioid']<>$db['id']){ echo "<script>self.location='?p=home'</script>"; return; }
	mysql_query("UPDATE usaveis SET status='on' WHERE id=".$_GET['add']);
	echo "<script>self.location='?p=blacksmith&id=".$_GET['id']."'</script>";
}
if(isset($_GET['remove'])){
	vn($_GET['remove']);
	$sqli=mysql_query("SELECT * FROM inventario WHERE id=".$_GET['id']);
	$dbi=mysql_fetch_assoc($sqli);
	if(mysql_num_rows($sqli)==0){ echo "<script>self.location='?p=home'</script>"; return; }
	if($dbi['usuarioid']<>$db['id']){ echo "<script>self.location='?p=home'</script>"; return; }
	$sqli=mysql_query("SELECT usuarioid FROM usaveis WHERE id=".$_GET['remove']);
	$dbi=mysql_fetch_assoc($sqli);
	if(mysql_num_rows($sqli)==0){ echo "<script>self.location='?p=home'</script>"; return; }
	if($dbi['usuarioid']<>$db['id']){ echo "<script>self.location='?p=home'</script>"; return; }
	mysql_query("UPDATE usaveis SET status='off' WHERE id=".$_GET['remove']);
	echo "<script>self.location='?p=blacksmith&id=".$_GET['id']."'</script>"; return;
}
if(isset($_GET['try'])){
	$sqli=mysql_query("SELECT * FROM inventario WHERE id=".$_GET['id']);
	$dbi=mysql_fetch_assoc($sqli);
	if(mysql_num_rows($sqli)==0){ echo "<script>self.location='?p=home'</script>"; return; }
	if($dbi['venda']=='sim'){ echo "<script>self.location='?p=home'</script>"; return; }
	if($dbi['usuarioid']<>$db['id']){ echo "<script>self.location='?p=home'</script>"; return; }
	$custo=($dbi['upgrade']+1)*500;
	if($db['yens']<$custo){ echo "<script>self.location='?p=blacksmith&id=".$_GET['id']."&msg=3'</script>"; return; }
	switch($dbi['upgrade']){
		case 0: $chance=100; break;
		case 1: $chance=90; break;
		case 2: $chance=80; break;
		case 3: $chance=70; break;
		case 4: $chance=60; break;
		case 5: $chance=50; break;
		case 6: $chance=40; break;
		case 7: $chance=30; break;
		case 8: $chance=20; break;
		case 9: $chance=10; break;
	}
	$sqlu=mysql_query("SELECT itemid FROM usaveis WHERE status='on' AND usuarioid=".$db['id']);
	$dbu=mysql_fetch_assoc($sqlu);
	$pchance=0;
	if(mysql_num_rows($sqlu)>0) do{
		switch($dbu['itemid']){
			case 1: $pchance=$pchance+2; break;
			case 2: $pchance=$pchance+5; break;
			case 3: $pchance=$pchance+10; break;
		}
	} while($dbu=mysql_fetch_assoc($sqlu));
	$total=$chance+$pchance;
	mysql_query("DELETE FROM usaveis WHERE usuarioid=".$db['id']." AND status='on'");
	mysql_query("UPDATE usuarios SET yens=yens-$custo WHERE id=".$db['id']);
	if($total>=100) $sucesso=1; else {
		$sort=rand(1,100);
		if($sort<=$total) $sucesso=1; else $sucesso=0;
	}
	if($sucesso==1){
		mysql_query("UPDATE inventario SET upgrade=upgrade+1 WHERE id=".$_GET['id']);
		echo "<script>self.location='?p=blacksmith&id=".$_GET['id']."&msg=1'</script>";
	} else {
		mysql_query("UPDATE inventario SET upgrade=upgrade-1 WHERE id=".$_GET['id']);
		echo "<script>self.location='?p=blacksmith&id=".$_GET['id']."&msg=2'</script>";
	}
}
$sqli=mysql_query("SELECT i.id,i.status,i.upgrade,i.usuarioid,i.venda,t.categoria,t.descricao,t.taijutsu,t.ninjutsu,t.genjutsu,t.nome,t.imagem,t.valor FROM inventario i LEFT OUTER JOIN table_itens t ON i.itemid=t.id WHERE i.id=".$_GET['id']);
if(mysql_num_rows($sqli)==0){ echo "<script>self.location='?p=home'</script>"; return; }
$dbi=mysql_fetch_assoc($sqli);
if($dbi['venda']=='sim'){ echo "<script>self.location='?p=home'</script>"; return; }
if($dbi['usuarioid']<>$db['id']){ echo "<script>self.location='?p=home'</script>"; return; }
if($dbi['upgrade']>=10){ echo "<script>self.location='?p=home'</script>"; return; }
$sqlu=mysql_query("SELECT u.id,u.status,u.usuarioid,u.itemid,t.descricao,t.nome,t.imagem FROM usaveis u LEFT OUTER JOIN table_usaveis t ON u.itemid=t.id WHERE u.usuarioid=".$db['id']." ORDER BY status DESC");
$dbu=mysql_fetch_assoc($sqlu);
$pchance=0;
?>
<div class="box_top">Ferreiro</div>
<div class="box_middle">Bem-vindo ao ferreiro! Aqui temos os instrumentos necessários para aprimorar equipamentos em geral. Com o aprimoramento, seus itens podem ficar mais resistentes. Cada item pode chegar a um nível de aprimoramento +10. As chances de se obter sucesso no aprimoramento vão reduzindo a cada nível que seu item é melhorado. Você pode usar pergaminhos para aumentar as chances de sucesso de aprimoramento. Caso o aprimoramento falhe, o item não será perdido, apenas regredirá 1 nível.<div class="sep"></div>
	<?php
	if(isset($_GET['msg'])){
		switch($_GET['msg']){
			case 1: $msg='Aprimoramento realizado com sucesso!'; break;
			case 2: $msg='Tentativa de aprimoramento sem sucesso.'; break;
			case 3: $msg='Yens insuficientes para fazer o aprimoramento.'; break;
		}
		echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>';
	}
	?>
    <div style="background:url(_img/gradient.jpg) repeat-y;color:#FFFFAA;"><img src="_img/yens.png" width="14" height="14" /> <b>Meus Yens: <?php echo number_format($db['yens'],2,',','.'); ?> yens</b></div><div class="sep"></div>
	<table width="100%" cellpadding="0" cellspacing="1">
        <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
    	<td align="center" width="140"><img src="_img/equipamentos/<?php echo $dbi['imagem']; ?>.jpg" /></td>
        <td style="padding:5px;">
        	<b><?php echo $dbi['nome']; ?><?php if($dbi['upgrade']>0) echo ' +'.$dbi['upgrade']; ?></b><br />
            <span class="sub2"><?php echo $dbi['descricao']; ?></span><br />
            <b><?php if($dbi['taijutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbi['taijutsu']+$dbi['upgrade']).'] em Taijutsu<br />'; ?>
            <?php if($dbi['ninjutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbi['ninjutsu']+$dbi['upgrade']).'] em Ninjutsu<br />'; ?>
            <?php if($dbi['genjutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbi['genjutsu']+$dbi['upgrade']).'] em Genjutsu<br />'; ?></b>
            <br />
            <b>Custo para Aprimoramento: </b><?php $custo=($dbi['upgrade']+1)*500; echo number_format($custo,2,',','.'); ?> yens
          </td>
  	</tr>
    </table>
    <div class="sep"></div>
    <table width="100%" cellpadding="0" cellspacing="1">
    	<?php if(mysql_num_rows($sqlu)==0) echo '<tr><td><div class="aviso">Nenhum item de aprimoramento encontrado.</div></td></tr>'; else do{ ?>
        <?php
		if($dbu['status']=='on'){
			switch($dbu['itemid']){
				case 1: $pchance=$pchance+2; break;
				case 2: $pchance=$pchance+5; break;
				case 3: $pchance=$pchance+10; break;
			}
		}
		?>
        <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
        	<td width="30"><img src="_img/<?php if($dbu['status']=='on') echo 'online'; else echo 'offline'; ?>.png" /></td>
        	<td width="10%"><img src="_img/equipamentos/<?php echo $dbu['imagem']; ?>.jpg" height="45" /></td>
            <td align="left"><b><?php echo $dbu['nome']; ?></b><br /><span class="sub2"><?php echo $dbu['descricao']; ?></span></td>
            <td width="20%"><?php if($dbu['status']=='on') echo '<a href="?p=blacksmith&id='.$_GET['id'].'&remove='.$dbu['id'].'">Remover</a>'; else echo '<a href="?p=blacksmith&id='.$_GET['id'].'&add='.$dbu['id'].'">Adicionar</a>'; ?></td>
        </tr>
        <?php } while($dbu=mysql_fetch_assoc($sqlu)); ?>
    </table>
    <div class="sep"></div>
    <table width="100%" cellpading="0" cellspacing="1">
    	<tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
        	<td width="50%" align="center"><b>Chance de Aprimoramento Padrão</b><br />
            <?php
            switch($dbi['upgrade']){
				case 0: $chance=100; break;
				case 1: $chance=90; break;
				case 2: $chance=80; break;
				case 3: $chance=70; break;
				case 4: $chance=60; break;
				case 5: $chance=50; break;
				case 6: $chance=40; break;
				case 7: $chance=30; break;
				case 8: $chance=20; break;
				case 9: $chance=10; break;
			}
			echo $chance.'%';
			?></td>
            <td width="50%" align="center"><b>Bônus de Aprimoramento dos Pergaminhos</b><br /><?php echo $pchance.'%'; ?></td>
        </tr>
        <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
            <td colspan="2" align="center"><b>Chance Total de Aprimoramento</b><br /><?php if($chance+$pchance>=100) echo '100%'; else echo $chance+$pchance.'%'; ?><div class="sep"></div><input type="button" class="botao" value="Aprimorar" onclick="javascript:location.href='?p=blacksmith&id=<?php echo $_GET['id']; ?>&try=true'" /></td>
        </tr>
    </table>
</div>
<div class="box_bottom"></div>