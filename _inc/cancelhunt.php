<?php require_once('trava.php'); ?>
<?php
if(!isset($_SESSION['hunt_cancel_result'])) {
    echo "<script>self.location='?p=home'</script>";
    exit;
}

$result = $_SESSION['hunt_cancel_result'];
unset($_SESSION['hunt_cancel_result']);

$exp = $result['exp'];
$yens = $result['yens'];
$bonus = $result['bonus'];
$proporcao = $result['proporcao'];
?>
<div class="box_top">Caça Cancelada</div>
<div class="box_middle">
<div class="aviso">
Você cancelou sua caça após completar <b><?php echo $proporcao; ?>%</b> do tempo.<br />
Abaixo estão suas recompensas proporcionais.<br />
Clique <a href="?p=hunt">aqui</a> para voltar às caças.
<div class="sep"></div>
<?php if($yens > 0): ?>
- <b><?php echo number_format($yens, 2, ',', '.'); ?> yens</b><br />
<?php endif; ?>
<?php if($exp > 0): ?>
- <b><?php echo $exp; ?> ponto<?php if($exp > 1) echo 's'; ?> de Experiência</b>
<?php endif; ?>
<?php if($bonus > 0): ?>
<br />- <b><?php echo $bonus; ?> ponto<?php if($bonus > 1) echo 's'; ?> de Experiência (Bônus VIP)</b>
<?php endif; ?>
<?php if($yens == 0 && $exp == 0): ?>
<span style="color:#ff6666;">Tempo insuficiente para receber recompensas.</span>
<?php endif; ?>
</div>
</div>
<div class="box_bottom"></div>
