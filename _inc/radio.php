<?php if($db['config_radio']==''){ echo "<script>self.location='?p=home'</script>"; exit; } ?>
<div class="box_top">Rádio</div>
<div class="box_middle">Abaixo está o painel da rádio que você selecionou. Recomendamos que deixe esta página aberta em uma outra janela/aba, dependendo de seu navegador, para que você possa ouvir sem intervalos.<div class="sep"></div>
	<div align="center"><?php require_once('radio_'.$db['config_radio'].'.php'); ?></div>
    <div class="sep"></div>
    <div align="center"><input type="button" class="botao" value="Selecionar Outra Rádio" onclick="location.href='?p=config&type=conn'" /></div>
</div>
<div class="box_bottom"></div>