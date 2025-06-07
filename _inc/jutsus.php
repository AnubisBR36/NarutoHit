<?php
$sqlj=mysql_query("SELECT *,j.nivel as niveldojutsu FROM jutsus j LEFT OUTER JOIN table_jutsus t ON j.jutsu=t.id WHERE usuarioid=".$db['id']." ORDER BY natureza DESC");
$dbj=mysql_fetch_assoc($sqlj);
$sqln=mysql_query("SELECT * FROM natureza WHERE usuarioid=".$db['id']." ORDER BY nivel DESC, id ASC");
$dbn=mysql_fetch_assoc($sqln);
$tam=200;
require_once('funcoes.php');
?>
<div class="box_top">Jutsus</div>
<div class="box_middle">Abaixo estão listadas as informações de todos os elementos (natureza do chakra) que você domina. Mais abaixo estão informações de todos os jutsus que você aprendeu, como experiência e nível. Algumas informações como o elemento e a força do jutsu também estão descritas nesta página. Se deseja aprender novos jutsus, ou aprimorar os listados abaixo, clique <a href="?p=school">aqui</a>.
    <div align="center">
    <?php if(mysql_num_rows($sqlj)==0) echo '<div class="sep"></div><div class="aviso">Nenhum jutsu aprendido até o momento.</div>'; else { ?>
    <table width="100%" cellpadding="0" cellspacing="1">
    <?php do{ ?>
    <tr>
        <td colspan="3"><div class="sep"></div></td>
    </tr>
    <?php
	$texto='<div align=center><b>Elemento</b><br /><span class="sub2">';
	switch($dbj['natureza']){
		case 'fogo': $texto.='Fogo'; break;
		case 'agua': $texto.='Água'; break;
		case 'raio': $texto.='Raio'; break;
		case 'terra': $texto.='Terra'; break;
		case 'vento': $texto.='Vento'; break;
		case 'nenhum': $texto.='Nenhum'; break;
	}
	$texto.='</span><br /><br /><b>Força do Jutsu</b><br /><span class="sub2">'.($dbj['forca']+(floor(($dbj['niveldojutsu']-1)*5))).' pontos</span></div>';
	?>
    <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
        <td width="180"><img src="_img/jutsus/<?php echo $dbj['jutsu']; ?>.jpg" onmouseover="Tip('<div class=tooltip><?php echo $dbj['nome']; ?></div>')" onmouseout="UnTip()" /></td>
        <td width="140"><b><?php echo $dbj['nome']; ?><br /><br />Nível <?php echo $dbj['niveldojutsu']; ?></b><br /><span class="sub2">Experiência<br /><?php echo $dbj['exp'].' / '.$dbj['expmax']; ?></span></td>
        <td><?php echo $texto; ?></td>
    </tr>
    <?php } while($dbj=mysql_fetch_assoc($sqlj)); ?>
    </table>
    <?php } ?>
    </div>
</div>
<div class="box_bottom"></div>
<?php
@mysql_free_result($sqlj);
@mysql_free_result($sqln);
?>