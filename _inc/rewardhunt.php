<?php require_once('trava.php'); ?>
<?php
$atual=date('Y-m-d H:i:s');
if($db['hunt']==0){ echo "<script>self.location='?p=home'</script>"; break; } else {
	if($atual<$db['hunt_fim']){ echo "<script>self.location='?p=busyhunt'</script>"; break; } else {
		$exp=rand(2,10);
		switch($exp){
			case 2: $yens=rand(160,240); break;
			case 3: $yens=rand(241,330); break;
			case 4: $yens=rand(331,420); break;
			case 5: $yens=rand(421,510); break;
			case 6: $yens=rand(511,600); break;
			case 7: $yens=rand(601,690); break;
			case 8: $yens=rand(691,780); break;
			case 9: $yens=rand(781,870); break;
			case 10: $yens=rand(871,960); break;
		}
		if(date('Y-m-d H:i:s')<$db['vip']) $bonus=3; else $bonus=0;
		$exp=$exp+$bonus;
		mysql_query("UPDATE usuarios SET hunt=0, yens=yens+".$yens.", yens_fat=yens_fat+".$yens.", exp=exp+".$exp.", exptotal=exptotal+".$exp." WHERE id=".$db['id']);
		$exp=$exp-$bonus;
		$db['yens']=$db['yens']+$yens;
		$db['yens']=$db['yens_fat']+$yens;
		$db['exp']=$db['exp']+$exp+$bonus;
		$db['exptotal']=$db['exptotal']+$exp+$bonus;
	}
}
?>
<div class="box_top">Caça Finalizada</div>
<div class="box_middle"><div class="aviso">Parabéns por conseguir terminar esta caça. Abaixo estão suas recompensas.<br />Clique <a href="?p=hunt">aqui</a> para voltar às caças.<div class="sep"></div><?php if($yens>0){ ?>- <b><?php echo number_format($yens,2,',','.'); ?> yens</b><br /><?php } if($exp>0){ ?>- <b><?php echo $exp; ?> ponto<?php if($exp>1) echo 's'; ?> de Experiência</b><?php } ?><?php if($bonus>0){ ?><br />- <b><?php echo $bonus; ?> pontos de Experiência (Bônus)</b><?php } ?></div>
</div>
<div class="box_bottom"></div>
<?php
$chance=rand(1,100);
if(($chance<=10)or($chance>=91)){
if($chance<=10){
	$sqli=mysql_query("SELECT * FROM table_itens ORDER BY RAND() LIMIT 1");
	$dbi=mysql_fetch_assoc($sqli);
	mysql_query("INSERT INTO inventario (usuarioid, itemid, categoria) VALUES (".$db['id'].", ".$dbi['id'].", '".$dbi['categoria']."')");
	mysql_query("UPDATE usuarios SET ganho=ganho+1 WHERE id=".$db['id']);
}
if($chance>=91){
	$sqli=mysql_query("SELECT * FROM table_usaveis ORDER BY RAND() LIMIT 1");
	$dbi=mysql_fetch_assoc($sqli);
	mysql_query("INSERT INTO usaveis (usuarioid, itemid) VALUES (".$db['id'].", ".$dbi['id'].")");
}
?>
<div class="box_top">Item Encontrado!</div>
<div class="box_middle">Parabéns! Você encontrou este item enquanto realizava sua caça!<div class="sep"></div>
	<table width="100%" cellpadding="0" cellspacing="1">
    <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
    	<td align="center" width="140"><img src="_img/equipamentos/<?php echo $dbi['imagem']; ?>.jpg" /></td>
        <td style="padding:5px;">
        	<b><?php echo $dbi['nome']; ?></b><br />
            <span class="sub2"><?php echo $dbi['descricao']; ?></span>
            <?php if($chance<=10){ ?>
            <br />
            <b><?php if($dbi['taijutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbi['taijutsu']).'] em Taijutsu<br />'; ?>
            <?php if($dbi['ninjutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbi['ninjutsu']).'] em Ninjutsu<br />'; ?>
            <?php if($dbi['genjutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbi['genjutsu']).'] em Genjutsu<br />'; ?></b>
            <?php } ?>
          </td>
  	</tr>
    </table>
</div>
<div class="box_bottom">
<?php } ?>