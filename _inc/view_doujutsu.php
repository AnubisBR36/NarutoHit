<?php
switch($db['doujutsu']){
	case 1: $txtdoujutsu='Sharingan'; $habilidades='- Bônus de 2% por nível no Genjutsu.'; break;
	case 2: $txtdoujutsu='Byakugan'; $habilidades='- Bônus de 2% por nível no Taijutsu.'; break;
	case 3: $txtdoujutsu='Rinnegan'; $habilidades='- Bônus de 2% por nível no Ninjutsu.'; break;
}
?>
<div class="box_top">Doujutsu de <?php echo $db['usuario']; ?></div>
<div class="box_middle">
	<table width="100%" cellpadding="0" cellspacing="1">
    <tr class="table_dados" style="background:#323232;">
        <td width="220"><img src="_img/doujutsus/<?php echo strtolower($txtdoujutsu); ?>.jpg" onmouseover="Tip('<div class=tooltip><?php echo $txtdoujutsu; ?></div>')" onmouseout="UnTip()" /></td>
        <td><span class="sub2"><b>Nível Desconhecido!<br /></b><?php echo $habilidades; ?></span></td>
    </tr>
    </table>
</div>
<div class="box_bottom"></div>