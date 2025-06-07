<?php
if((isset($_SESSION['logado']))&&($dbi['exp']>=$dbi['expmax'])){
	do{
		$novaexpmax=$dbi['expmax']+$dbi['nivel']*10;
		$difexp=$dbi['exp']-$dbi['expmax'];
		$dbi['nivel']=$dbi['nivel']+1;
		$dbi['exp']=$difexp;
		if($dbi['nivel']>=14){
			$dbi['exp']=$dbi['exp']+$dbi['nivel']*9;
			$difexp=$difexp+$dbi['nivel']*9;
		}
		$dbi['expmax']=$novaexpmax;
		$dbi['yens']=$dbi['yens']+300;
		$dbi['yens_fat']=$dbi['yens_fat']+300;
		$dbi['energia']=$dbi['energia']+100;
		$dbi['energiamax']=$dbi['energiamax']+100;
		mysql_query("UPDATE usuarios SET nivel=nivel+1, exp=$difexp, expmax=$novaexpmax, yens=yens+300, yens_fat=yens_fat+300, energia=energia+100, energiamax=energiamax+100 WHERE id=".$dbi['id']);
		mysql_query("INSERT INTO atualizacoes (usuarioid, texto, hora) VALUES (".$dbi['id'].", '<a href=?p=view&view=".strtolower($dbi['usuario']).">".$dbi['usuario']."</a> alcançou o <b>Nível ".$dbi['nivel']."</b>.', '".time(date('Y-m-d H:i:s'))."')");
} while($dbi['exp']>=$dbi['expmax']); } ?>
<?php
if((isset($_SESSION['logado']))&&($dbi['doujutsu_exp']>=$dbi['doujutsu_expmax'])&&($dbi['doujutsu_nivel']<10)){
	do{
		$novaexpmax=$dbi['doujutsu_expmax']+$dbi['doujutsu_nivel']*10;
		$difexp=$dbi['doujutsu_exp']-$dbi['doujutsu_expmax'];
		$dbi['doujutsu_nivel']=$dbi['doujutsu_nivel']+1;
		$dbi['doujutsu_exp']=$difexp;
		$dbi['doujutsu_expmax']=$novaexpmax;
		mysql_query("UPDATE usuarios SET doujutsu_nivel=doujutsu_nivel+1, doujutsu_exp=$difexp, doujutsu_expmax=$novaexpmax WHERE id=".$dbi['id']); } while($dbi['doujutsu_exp']>=$dbi['doujutsu_expmax']); } ?>