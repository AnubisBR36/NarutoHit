<?php require_once('trava.php'); ?>
<?php
if(($db['missao']>900) && ($db['missao']<=905)){
	$agora=time();
	$fim=strtotime($db['missao_fim']);

	if(isset($db['missao_inicio']) && !empty($db['missao_inicio'])){
		$inicio = strtotime($db['missao_inicio']);
	} else {
		$inicio = $fim - ($db['missao_tempo'] * 3600);
	}

	if($agora>=$fim){
		// Calcular recompensa baseada no tempo total da missão
		$tempo_total_segundos = $fim - $inicio;
		$tempo_total_horas = $tempo_total_segundos / 3600;

		// Definir valor por hora baseado no rank da missão
		switch($db['missao']){
			case 901: $yens_por_hora = 250; $exp_por_hora = 1; break;  // Rank D
			case 902: $yens_por_hora = 550; $exp_por_hora = 1; break;  // Rank C
			case 903: $yens_por_hora = 1000; $exp_por_hora = 1; break; // Rank B
			case 904: $yens_por_hora = 1800; $exp_por_hora = 1; break; // Rank A
			case 905: $yens_por_hora = 3000; $exp_por_hora = 1; break; // Rank S
			default: $yens_por_hora = 250; $exp_por_hora = 1; break;   // Fallback para Rank D
		}

		$yens_ganhos = floor($yens_por_hora * $tempo_total_horas);
		$exp_ganha = floor($exp_por_hora * $tempo_total_horas);

		// Garantir que ganhe pelo menos alguma coisa
		if($yens_ganhos < 1) $yens_ganhos = 1;
		if($exp_ganha < 1) $exp_ganha = 1;

		// Atualizar dados do usuário
		mysql_query("UPDATE usuarios SET yens=yens+".$yens_ganhos.", yens_fat=yens_fat+".$yens_ganhos.", exp=exp+".$exp_ganha.", missao=0, orgmissao=0 WHERE id=".$db['id']);

		echo "<script>self.location='?p=missions&msg=mission_completed&yens=".$yens_ganhos."&exp=".$exp_ganha."'</script>";
		exit;
	} else {
		echo "<script>self.location='?p=busymission'</script>";
		exit;
	}
} else {
	echo "<script>self.location='?p=home'</script>";
	exit;
}
?>
<div id="newlvl">
</div>
<div class="box_top">Missão Finalizada</div>
<div class="box_middle"><div class="aviso">Parabéns por conseguir terminar esta missão. Como recompensa, estamos lhe dando <b><?php echo number_format($yens,2,',','.'); ?> yens</b>. Além disso, você adquiriu <b><?php echo $exp; ?> ponto<?php if($exp>1) echo 's'; ?> de experiência</b>.<?php if(isset($dbr['logo'])) echo ' Seu clã também recebeu <b>'.$dbr['logo'].' pontos</b> de reputação pelo término da missão.'; ?>
    </div>
</div>
<div class="box_bottom"></div>
<?php
$chance=rand(1,100);
if(($chance<=10)or($chance>=91)){
if($chance<=10){
	$sqli=mysql_query("SELECT * FROM table_itens ORDER BY RAND() LIMIT 1");
	$dbi=mysql_fetch_assoc($sqli);
	mysql_query("INSERT INTO inventario (usuarioid, itemid, categoria) VALUES (".$db['id'].", ".$dbi['id'].", '".$dbi['categoria']."')");
}
if($chance>=91){
	$sqli=mysql_query("SELECT * FROM table_usaveis ORDER BY RAND() LIMIT 1");
	$dbi=mysql_fetch_assoc($sqli);
	mysql_query("INSERT INTO usaveis (usuarioid, itemid) VALUES (".$db['id'].", ".$dbi['id'].")");
}
?>
<div class="box_top">Item Encontrado!</div>
<div class="box_middle">Parabéns! Você encontrou este item enquanto realizava sua missão!<div class="sep"></div>
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
<?php
@mysql_free_result($sqlm);
?>