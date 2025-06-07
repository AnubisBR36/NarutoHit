<div align="center">
<?php
$sqlx=mysql_query("SELECT * FROM table_missoes WHERE orgid=".$db['orgid']." AND status='aguardo' ORDER BY id ASC LIMIT 3");
$dbx=mysql_fetch_assoc($sqlx);
?>
	<?php if(mysql_num_rows($sqlx)==0) echo '<div class="sub2">Nenhuma missão disponível.<br />Novas missões são enviadas para cada clã a cada 4 horas.</div>'; else do{ ?>
    	<?php
		switch($dbx['logo']){
			case 1: $rank='D'; break;
			case 2: $rank='C'; break;
			case 3: $rank='B'; break;
			case 4: $rank='A'; break;
			case 5: $rank='S'; break;
		}
		$texto='<b><h2>Missão Rank '.$rank.'</h2></b><b>Membros Inscritos:</b> '.$dbx['membros'].' de '.$dbx['maximo'].'<br /><b>Recompensa:</b> '.number_format($dbx['yens'],2,',','.').' yens<br /><b>Experiência:</b> '.$dbx['exp'].' ponto';
		if($dbx['exp']>1) $texto.='s';
		$texto.='<br /><b>Duração:</b> '.($dbx['logo']*20).' minutos';
		$texto.='<br />Reputação: '.$dbx['logo'].' ponto';
		if($dbx['logo']>1) $texto.='s';
		if($db['orgmissao']==$dbx['id']) $texto.='<br /><b><i>Você já está inscrito para esta missão.<br />Aguarde a formação do time.</i></b>';
		?>
    	<a href="?p=misorg&amp;id=<?php echo $c->encode($dbx['id'].','.$dbx['orgid'],$chaveuniversal); ?>"><img src="_img/org/missao_<?php echo strtolower($rank); ?>.jpg" onmouseover="Tip('<div class=tooltip style=text-align:center;><?php echo $texto; ?></div>')" onmouseout="UnTip()" border="0" /></a>
    <?php } while($dbx=mysql_fetch_assoc($sqlx)); ?>
<div class="sep"></div>
</div>