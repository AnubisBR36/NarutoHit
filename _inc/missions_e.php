<?php
$nivel=$db['nivel']+4;
$sqlm=mysql_query("SELECT * FROM table_missoes ORDER BY rank DESC");
$dbm=mysql_fetch_assoc($sqlm);
$sqlp=mysql_query("SELECT * FROM personagens WHERE usuarioid=".$db['id']);
$dbp=mysql_fetch_assoc($sqlp);
?>
<div align="center">
<table width="100%" cellpadding="0" cellspacing="1">
<?php $cor='#323232'; do{ ?>
<tr>
	<td colspan="3"><div class="sep"></div></td>
</tr>
<tr class="table_dados" style="background:#323232">
    <td style="background:#282828;" width="120"><img src="_img/personagens/<?php echo $dbm['personagem']; ?>/0.jpg" /></td>
    <td><b>Rank S</b><br /><span class="sub2">3.000,00 yens<br />por hora</span></td>
    <td>
    <form method="post" id="missao" name="missao" action="?p=missions" onsubmit="subm.value='Carregando...';subm.disabled=true;">
    <input type="hidden" id="mis_rank" name="mis_rank" value="S">
    <select id="mis_tempo" name="mis_tempo">
    	<?php $i=1; do{ ?>
    	<option value="<?php echo $i; ?>"><?php echo $i; ?> hora<?php if($i>1) echo 's'; ?></option>
        <?php $i++; } while($i<13); ?>
    </select>
    <br /><span class="sub2">Selecione a quantidade de horas</span>
    <input type="submit" id="subm" name="subm" class="botao" value="Escolher">
    </form>    </td>
</tr>
<?php if($cor=='#323232') $cor='#2C2C2C'; else $cor='#323232'; } while($dbm=mysql_fetch_assoc($sqlm)); ?>
</table>
</div>
<?php
@mysql_free_result($sqlm);
@mysql_free_result($sqlp);
?>