<?php
$sql=mysql_query("SELECT config_apresentacao FROM usuarios WHERE usuario='".$_GET['view']."'");
$db=mysql_fetch_assoc($sql);
?>
<div class="box_top">Apresentação de <?php echo ucfirst($_GET['view']); ?></div>
<div class="box_middle">
	<div class="apresentacao" style="width:520px;"><?php if($db['config_apresentacao']=='') echo 'Nenhum texto de apresentação.'; else echo str_replace(array('<p>','</p>'),array('','<br />'),$db['config_apresentacao']); ?></div>
</div>
<div class="box_bottom"></div>
<?php
@mysql_free_result($sql);
?>