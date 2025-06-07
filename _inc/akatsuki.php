<?php
if(isset($_GET['act'])){
	switch($_GET['act']){
		case 'rebel':
			if($db['renegado']=='sim'){ echo "<script>self.location='?p=akatsuki'</script>"; break; }
			if($db['nivel']<10){ echo "<script>self.location='?p=akatsuki&msg=2'</script>"; break; }
			mysql_query("UPDATE usuarios SET renegado='sim' WHERE id=".$db['id']);
			mysql_query("INSERT INTO atualizacoes (usuarioid, texto, hora) VALUES (".$db['id'].", '<a href=?p=view&view=".strtolower($db['usuario']).">".$db['usuario']."</a> se tornou um membro da <b>Akatsuki</b>.', '".time(date('Y-m-d H:i:s'))."')");
			mysql_query("DELETE FROM membros WHERE usuarioid=".$db['id']);
			echo "<script>self.location='?p=akatsuki&msg=1'</script>"; break;
	}
}
?>
<div class="box_top">Rebelar-se</div>
<div class="box_middle">Você poderá se tornar um ninja renegado utilizando o botão abaixo. Para preservar a fama da Akatsuki, apenas ninjas que estejam no nível 10 ou superior serão aceitos em nossa organização. Não cobraremos nenhum custo para que você se junte a nós. Lembre-se que não há volta após se tornar um membro da Akatsuki, pois sua vila não o aceitará de volta. Você também será retirado da organização a quem pertence.<div class="sep"></div>
	<?php
	if(isset($_GET['msg'])){
		switch($_GET['msg']){
			case 1: $msg='Parabéns! Agora você faz parte da Akatsuki!<br />Como todo membro da Akatsuki que se preze, sua bandana ganhou um corte, simbolizando que você é um ninja renegado.'; break;
			case 2: $msg='Nível insuficiente para se juntar à Akatsuki.'; break;
		}
	echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>';
	}
	?>
	<div style="background:url(_img/gradient.jpg) repeat-y;color:#FFFFAA;"><?php if($db['renegado']=='nao'){ ?><img src="_img/yens.png" width="14" height="14" /> <b>Meus Yens: <?php echo number_format($db['yens'],2,',','.'); ?> yens</b><?php } else echo '<div class="aviso">Você já é um ninja renegado.</div>'; ?></div>
    <?php if($db['renegado']=='nao'){ ?>
    <div class="sep"></div>
    <div align="center"><input type="button" class="botao" value="Rebelar-se" onclick="location.href='?p=akatsuki&act=rebel'" /></div>
    <?php } ?>
</div>
<div class="box_bottom"></div>