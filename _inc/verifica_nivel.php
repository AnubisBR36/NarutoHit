<?php
if((isset($_SESSION['logado']))&&($db['exp']>=$db['expmax'])){
	do{
		$novaexpmax=$db['expmax']+$db['nivel']*10;
		$difexp=$db['exp']-$db['expmax'];
		$db['nivel']=$db['nivel']+1;
		$db['exp']=$difexp;
		if($db['nivel']>=14){
			$db['exp']=$db['exp']+$db['nivel']*9;
			$difexp=$difexp+$db['nivel']*9;
		}
		$db['expmax']=$novaexpmax;
		$db['yens']=$db['yens']+300;
		$db['yens_fat']=$db['yens_fat']+300;
		$db['energia']=$db['energia']+100;
		$db['energiamax']=$db['energiamax']+100;
		mysql_query("UPDATE usuarios SET nivel=nivel+1, exp=$difexp, expmax=$novaexpmax, yens=yens+300, yens_fat=yens_fat+300, energia=energia+100, energiamax=energiamax+100 WHERE id=".$db['id']);
		mysql_query("INSERT INTO atualizacoes (usuarioid, texto, hora) VALUES (".$db['id'].", '<a href=?p=view&view=".strtolower($db['usuario']).">".$db['usuario']."</a> alcançou o <b>Nível ".$db['nivel']."</b>.', '".time(date('Y-m-d H:i:s'))."')");
		if((isset($_GET['p']))&&($_GET['p']<>'view')&&($_GET['p']<>'prepare')&&($_GET['p']<>'attack')){ ?>
		<?php if($db['exp']<$db['expmax']){ ?><script type="text/javascript">$(document).modal({url:'novonivel.php?lvl=<?php echo $db['nivel']; ?>',autoOpen:true});</script><a id="novonivel" href="city.php" class="modal" rel="modal" style="display:none;">NovoNivel</a>
<?php }}} while($db['exp']>=$db['expmax']); } ?>

<?php
if((isset($_SESSION['logado']))&&($db['doujutsu_exp']>=$db['doujutsu_expmax'])&&($db['doujutsu_nivel']<30)){
	do{
		$novaexpmax=$db['doujutsu_expmax']+$db['doujutsu_nivel']*10;
		$difexp=$db['doujutsu_exp']-$db['doujutsu_expmax'];
		$db['doujutsu_nivel']=$db['doujutsu_nivel']+1;
		$db['doujutsu_exp']=$difexp;
		$db['doujutsu_expmax']=$novaexpmax;
		if($db['doujutsu']==1) $doujutsu='sharingan'; else
		if($db['doujutsu']==2) $doujutsu='byakugan'; else
		if($db['doujutsu']==3) $doujutsu='rinnegan';
		mysql_query("UPDATE usuarios SET doujutsu_nivel=doujutsu_nivel+1, doujutsu_exp=$difexp, doujutsu_expmax=$novaexpmax WHERE id=".$db['id']);
		if((isset($_GET['p']))&&($_GET['p']<>'view')&&($_GET['p']<>'prepare')&&($_GET['p']<>'attack')){ ?>
		<?php if($db['doujutsu_exp']<$db['doujutsu_expmax']){ ?><script type="text/javascript">$(document).modal({url:'novonivel_doujutsu.php?lvl=<?php echo $db['doujutsu_nivel']; ?>&douj=<?php echo $doujutsu; ?>',autoOpen:true});</script><a id="novonivel" href="city.php" class="modal" rel="modal" style="display:none;">NovoNivel</a>
<?php }}} while($db['exp']>=$db['expmax']); } ?>