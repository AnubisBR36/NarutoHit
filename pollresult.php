<?php require_once('_inc/conexao.php'); ?>
<?php
if(!isset($_GET['id'])){ echo "<script>self.location='?p=home'</script>"; break; }
$sqlp=mysql_query("SELECT * FROM enquetes WHERE id=".$_GET['id']);
$dbp=mysql_fetch_assoc($sqlp);
$array=explode('/',$dbp['respostas']);
$total=$dbp['resp_a']+$dbp['resp_b']+$dbp['resp_c']+$dbp['resp_d']+$dbp['resp_e'];
?>
<div class="modalExemplo">
<div class="city_div" style="text-align:left;width:300px;">
    <b><?php echo $dbp['pergunta']; ?></b><br />
    <?php $i=0; do{ ?>
    	<?php
        switch($i){
			case 0: $resp='a'; break;
			case 1: $resp='b'; break;
			case 2: $resp='c'; break;
			case 3: $resp='d'; break;
			case 4: $resp='e'; break;
		}
		?>
        <b>[<?php echo round(($dbp['resp_'.$resp]*100)/$total); ?>%]</b> <?php echo $array[$i]; ?><br />
    <?php $i++; } while($i<count($array)); ?>
</div>
<div align="center" style="margin-top:7px;"><a href="#" rel="modalclose" style="color:#666666;"><img src="_inc/_img/close.jpg" border="0" align="absmiddle" width="12" height="12" /> Fechar</a></div>
</div>
<?php
@mysql_free_result($sqlp);
?>