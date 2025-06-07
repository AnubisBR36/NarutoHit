<?php require_once('_inc/conexao.php'); ?>
<?php
if(!isset($_GET['id'])){ echo "<script>self.location='?p=home'</script>"; break; }
$sqlp=mysql_query("SELECT * FROM enquetes WHERE id=".$_GET['id']);
$dbp=mysql_fetch_assoc($sqlp);
if(date('Y-m-d H:i:s')>=$dbp['fim']){ echo "<script>self.location='?p=polls&msg=2'</script>"; break; }
$array=explode('/',$dbp['respostas']);
?>
<form method="post" action="?p=polls">
<input type="hidden" id="enq_id" name="enq_id" value="<?php echo $dbp['id']; ?>" />
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
        <input type="radio" id="enq_resposta<?php echo $i; ?>" name="enq_resposta" value="<?php echo $resp; ?>" /> <?php echo $array[$i]; ?><br />
    <?php $i++; } while($i<count($array)); ?>
</div>
<div align="center" style="margin-top:7px;"><a href="#" style="color:#666666;" onClick="document.forms[0].submit();"><img src="_img/refresh.png" border="0" align="absmiddle" width="12" height="12" /> Votar</a> | <a href="#" rel="modalclose" style="color:#666666;"><img src="_img/close.jpg" border="0" align="absmiddle" width="12" height="12" /> Fechar</a></div>
</div>
</form>
<?php
@mysql_free_result($sqlp);
?>