<?php require_once('trava.php'); ?>
<?php
$atual=date('Y-m-d H:i:s');
if($db['treino']==0){ echo "<script>self.location='?p=home'</script>"; break; } else {
	if($atual<$db['treino_fim']){ echo "<script>self.location='?p=busytrain'</script>"; break; } else {
		mysql_query("UPDATE usuarios SET treino=0 WHERE id=".$db['id']);
		$sqlj=mysql_query("SELECT id, exp, expmax, nivel FROM jutsus WHERE usuarioid=".$db['id']." AND jutsu=".$db['treino']);
		$dbj=mysql_fetch_assoc($sqlj);
		$exp=$db['treino_tempo']/5;
		$dbj['exp']=$dbj['exp']+$exp;
		$msgadicional='';
		if($dbj['exp']>=$dbj['expmax']){
			$dbj['nivel']=$dbj['nivel']+1;
			$expatual=$dbj['exp']-$dbj['expmax'];
			if($dbj['nivel']<5) $dbj['expmax']=$dbj['expmax']+30; else $dbj['expmax']=99999;
			$msgadicional='Seu jutsu alcançou o <b>nível '.$dbj['nivel'].'</b>.';
			mysql_query("UPDATE jutsus SET exp=".$expatual.", expmax=".$dbj['expmax'].", nivel=nivel+1 WHERE id=".$dbj['id']);
		} else mysql_query("UPDATE jutsus SET exp=exp+".$exp." WHERE id=".$dbj['id']);
	}
}
?>
<div id="newlvl">
</div>
<div class="box_top">Treino Finalizado</div>
<div class="box_middle"><div class="aviso">Parabéns por conseguir terminar seu treino.<br />Você adquiriu <b><?php echo $exp; ?> pontos de experiência</b> para o jutsu treinado.<br /><?php echo $msgadicional; ?>Clique <a href="?p=school">aqui</a> para voltar à escola.</div>
</div>
<div class="box_bottom"></div>
<?php
@mysql_free_result($sqlj);
?>