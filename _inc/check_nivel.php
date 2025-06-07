<?php require_once('conexao.php'); ?>
<?php
$mudanca=0;
if($_SESSION['exp']>=$_SESSION['expmax']){
	$novaexpmax=$_SESSION['expmax']+$_SESSION['nivel']*10;
	$difexp=$_SESSION['exp']-$_SESSION['expmax'];
	mysql_query("UPDATE usuarios SET nivel=nivel+1, exp=$difexp, expmax=$novaexpmax, yens=yens+100, yens_fat=yens_fat+100, energia=energia+100, energiamax=energiamax+100 WHERE id=".$_SESSION['id']);
	$mudanca=1;
}
?>
<?php if($mudanca==1){ ?>
<script type="text/javascript">$('a#novonivel').modal({autoOpen:true});</script>
<div id="novonivel">
<div class="box_top">Novo Nível Alcançado!</div>
<div class="box_middle">
<div class="aviso">Parabéns! Você alcançou o nível <b><?php echo ($_SESSION['nivel']+1); ?></b>.<br />Continue a ganhar experiência para subir mais e mais no ranking!<br />Como recompensa, nós lhe daremos <b>5 pontos de atributos</b> e <b>100,00 yens</b>.</div>
</div>
<div class="box_bottom"></div>
</div>
<?php } ?>