<?php require_once('_inc/conexao.php'); ?>
<?php
switch($_GET['douj']){
	case 1: $txtdoujutsu='Sharingan'; $habilidades='- Bônus de 10% por nível no Genjutsu;<br />- Chance de bônus de defesa.'; break;
	case 2: $txtdoujutsu='Byakugan'; $habilidades='- Bônus de 10% por nível no Taijutsu;<br />- Chance de bônus de ataque.'; break;
	case 3: $txtdoujutsu='Rinnegan'; $habilidades='- Bônus de 10% por nível no Ninjutsu;<br />- Chance de bônus de ataque.'; break;
}
?>
<div class="modalExemplo" style="width:450px;">
<div class="city_div">Parabéns, você acabou de despertar seu doujutsu!<br />Ele inicia no nível 1, podendo evoluir até o nível 10.
	<table width="100%" cellpadding="0" cellspacing="1">
    <tr class="table_dados" style="background:#E3E3E3;">
        <td width="220"><img src="_img/doujutsus/<?php echo strtolower($txtdoujutsu); ?>.jpg" onmouseover="Tip('<div class=tooltip><?php echo $txtdoujutsu; ?></div>')" onmouseout="UnTip()" /></td>
        <td width="80"><b>Nível 1</b><br /><span class="sub2" style="color:#666666;">Experiência<br />0 / 150</span></td>
        <td><span class="sub2" style="color:#666666"><?php echo $habilidades; ?></span></td>
    </tr>
    </table>
</div>
<div align="center" style="margin-top:7px;"><a href="#" rel="modalclose" style="color:#666666;"><img src="_img/close.jpg" border="0" align="absmiddle" width="12" height="12" /> Fechar</a></div>
</div>