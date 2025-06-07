<?php
if($db['doujutsu']==0){
	switch($db['nivel']){
		case 1: $nec=20; break;
		case 2: $nec=24; break;
		case 3: $nec=28; break;
		case 4: $nec=32; break;
		case 5: $nec=35; break;
		case 6: $nec=38; break;
		case 7: $nec=40; break;
		default: $nec=42; break;
	}
	$doujutsu=0;
	if($db['taijutsu']>=$nec) $doujutsu=2;
	if($db['ninjutsu']>=$nec) $doujutsu=3;
	if($db['genjutsu']>=$nec) $doujutsu=1;
	if($doujutsu>0){
		mysql_query("UPDATE usuarios SET doujutsu=$doujutsu, doujutsu_nivel=1 WHERE id=".$db['id']);
		$db['doujutsu']=$doujutsu;
		$db['doujutsu_nivel']=1;
		?>
		<script type="text/javascript">$(document).modal({url:'newdoujutsu.php?douj=<?php echo $doujutsu; ?>',autoOpen:true});</script>
        <a id="novonivel" href="city.php" class="modal" rel="modal" style="display:none;">NovoNivel</a>
        <?php
		//require_once('../newdoujutsu.php');
	}
}
?>