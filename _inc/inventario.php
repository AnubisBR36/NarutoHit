<div class="box_top">Meu Inventário</div>
<div class="box_middle">Abaixo estão os itens comprados no Ichiraku Bar. Caso você tenha muitos itens em seu inventário, serão mostrados apenas 3, aleatóriamente. Para visualizar todos, utilize <a href="?p=inventory">esta</a> página.
	<?php
	if(isset($_GET['msg'])){
		switch($_GET['msg']){
			case 1: $msg='Ramen utilizado com sucesso!<br />Sua energia foi regenerada em <b>'.$_GET['e'].' pontos.</b>'; break;
		}
	echo '<div class="sep"></div><div class="aviso">'.$msg.'</div>';
	}
	?>
	<table width="100%" cellpadding="0" cellspacing="1">
    <?php do{
	switch($dbr['ramenid']){
		case 1: $nome='Ramen Super-Simples'; $reg=50; break;
		case 2: $nome='Ramen Simples'; $reg=100; break;
		case 3: $nome='Ramen com Ovo'; $reg=250; break;
		case 4: $nome='Ramen Vegetariano'; $reg=500; break;
		case 5: $nome='Ramen Completo'; $reg='1.000'; break;
	}
	?>
    <tr>
    	<td colspan="2"><div class="sep"></div></td>
    </tr>
    <tr class="table_dados" style="background:#323232">
    	<td width="140"><img src="_img/ramen/ramen<?php echo $dbr['ramenid']; ?>.jpg" /></td>
        <td><b><?php echo $nome; ?></b><br /><span class="sub2">Regenera <?php echo $reg; ?> pontos de Energia</span>
        <form method="post" action="?p=home" onsubmit="subm.value='Carregando...';subm.disabled=true;">
        <input type="hidden" id="ram_id" name="ram_id" value="<?php echo $c->encode($dbr['id'],$chaveuniversal); ?>" />
        <input type="hidden" id="ram_tipo" name="ram_tipo" value="<?php echo $c->encode($dbr['ramenid'],$chaveuniversal); ?>" />
        <input type="submit" id="subm" name="subm" class="botao" value="Usar" />
        </form>
        </td>
    </tr>
    <?php } while($dbr=mysql_fetch_assoc($sqlr)); ?>
    <?php if(mysql_num_rows($sqlr)>=3){ ?>
    <tr>
    	<td colspan="2"><div class="sep"></div></td>
    </tr>
    <tr>
    	<td align="center" colspan="3"><input type="button" class="botao" value="Visualizar Inventário" onclick="location.href='?p=inventory'" /></td>
    </tr>
    <?php } ?>
    </table>
</div>
<div class="box_bottom"></div>