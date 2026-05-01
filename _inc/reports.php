<?php
if((!isset($_GET['type']))or(isset($_GET['type']))&&($_GET['type']=='a')) $type=1; else $type=2;
?>
<div class="box_top">Relatórios de <?php if($type==1) echo 'Ataque'; else echo 'Defesa'; ?></div>
	<div class="box_middle">
	<?php require_once('reports'.$type.'.php'); ?>
    <div class="sep"></div>
    <div align="center"><a href="?p=reports&amp;type=a">Relatórios de Ataque</a> | <a href="?p=reports&amp;type=d">Relatórios de Defesa</a></div>
    </div>
<div class="box_bottom"></div>
<?php
// PDO automatically frees result sets, no need for manual cleanup
?>