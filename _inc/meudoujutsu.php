<?php
switch($db['doujutsu']){
	case 1: $txtdoujutsu='Sharingan'; $habilidades='- Bônus de '.($db['doujutsu_nivel']*2).'% no Genjutsu;<br />- 3 Jutsus.'; break;
	case 2: $txtdoujutsu='Byakugan'; $habilidades='- Bônus de '.($db['doujutsu_nivel']*2).'% no Taijutsu;<br />- 3 Jutsus.'; break;
	case 3: $txtdoujutsu='Rinnegan'; $habilidades='- Bônus de '.($db['doujutsu_nivel']*2).'% no Ninjutsu;<br />- 3 Jutsus.'; break;
}
?>
<div class="box_top">Meu Doujutsu</div>
<div class="box_middle">Informações do seu doujutsu.<div class="sep"></div>
	<table width="100%" cellpadding="0" cellspacing="1">
    <tr class="table_dados" style="background:#323232;">
        <td width="220"><img src="_img/doujutsus/<?php echo strtolower($txtdoujutsu); ?>.jpg" onmouseover="Tip('<div class=tooltip><?php echo $txtdoujutsu; ?></div>')" onmouseout="UnTip()" />
        <?php
        if($db['doujutsu']==1){
			if($db['doujutsu_nivel']<=6) $txtdoujutsu.=' - Nível 1';
			if(($db['doujutsu_nivel']>6)&&($db['doujutsu_nivel'])<=12) $txtdoujutsu.=' - Nível 2';
			if(($db['doujutsu_nivel']>13)&&($db['doujutsu_nivel'])<=18) $txtdoujutsu.=' - Nível 3';
			if(($db['doujutsu_nivel']>18)&&($db['doujutsu_nivel'])<=24) $txtdoujutsu.=' - Mangekyou Sharingan';
			if($db['doujutsu_nivel']>24) $txtdoujutsu.=' - Fuumetsu Mangekyou Sharingan';
		}
		?></td>
        <td width="80"><b>Nível <?php echo $db['doujutsu_nivel']; ?></b><?php if($db['doujutsu_nivel']<30){ ?><br /><span class="sub2">Experiência<br /><?php echo $db['doujutsu_exp'].' / '.$db['doujutsu_expmax']; ?></span><?php } else { ?><br /><span class="sub2">Nível máximo alcançado.</span><?php } ?></td>
        <td><span class="sub2"><?php echo $habilidades; ?><br />
        <?php
		if(date('Y-m-d H:i:s')<$db['vip']){
        	if($db['doujutsu']==1) echo '<a href="?p=changedoujutsu&new=2">- Trocar para Byakugan</a><br /><a href="?p=changedoujutsu&new=3">- Trocar para Rinnegan</a>';
			if($db['doujutsu']==2) echo '<a href="?p=changedoujutsu&new=3">- Trocar para Rinnegan</a><br /><a href="?p=changedoujutsu&new=1">- Trocar para Sharingan</a>';
			if($db['doujutsu']==3) echo '<a href="?p=changedoujutsu&new=2">- Trocar para Byakugan</a><br /><a href="?p=changedoujutsu&new=1">- Trocar para Sharingan</a>';
		}
		?></span></td>
    </tr>
    </table>
</div>
<div class="box_bottom"></div>
<script>
<?php if($db['doujutsu']==2){ ?>document.getElementById('atrtai').innerHTML=((document.getElementById('atrtai').innerHTML)*1)+<?php echo round($db['taijutsu']*($db['doujutsu_nivel']/50)); ?>;<?php } ?>
<?php if($db['doujutsu']==3){ ?>document.getElementById('atrnin').innerHTML=((document.getElementById('atrnin').innerHTML)*1)+<?php echo round($db['ninjutsu']*($db['doujutsu_nivel']/50)); ?>;<?php } ?>
<?php if($db['doujutsu']==1){ ?>document.getElementById('atrgen').innerHTML=((document.getElementById('atrgen').innerHTML)*1)+<?php echo round($db['genjutsu']*($db['doujutsu_nivel']/50)); ?>;<?php } ?>
</script>