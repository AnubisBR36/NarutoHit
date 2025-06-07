<?php

$sqlp=mysql_query("SELECT count(id) conta FROM usuarios");
$dbp=mysql_fetch_assoc($sqlp);
$valorinicial=20;

if(isset($_POST['ram_id'])){
	$id=$_POST['ram_id'];
	switch($id){
		case 1: $valor=$valorinicial; break;
		case 2: $valor=$valorinicial*2; break;
		case 3: $valor=$valorinicial*5; break;
		case 4: $valor=$valorinicial*10; break;
		case 5: $valor=$valorinicial*20; break;
	}
	$qtdade=$_POST['ram_qtdade'];
	vn($id); vn($qtdade);
	if(($id>5)or($id<=0)){ echo "<script>self.location='?p=ramen'</script>"; break; }
	if(($qtdade>5)or($qtdade<=0)){ echo "<script>self.location='?p=ramen'</script>"; break; }
	$total=$valor*$qtdade;
	if($db['yens']<$total){ echo "<script>self.location='?p=ramen&msg=1'</script>"; break; }
	mysql_query("UPDATE usuarios SET yens=yens-$total WHERE id=".$db['id']);
	$i=0;
	do{
		mysql_query("INSERT INTO ramen (usuarioid, ramenid) VALUES (".$db['id'].",".$id.")");
		$i++;
	} while($i<$qtdade);
	echo "<script>self.location='?p=ramen&msg=2'</script>"; break;
}
?>
<div class="box_top">Ichiraku Ramen Bar</div>
<div class="box_middle"><div style="background:url(_img/ramen/ramen_back.jpg) no-repeat right top;padding-right:320px;height:150px;">Bem-vindo ao Ichiraku Ramen Bar! Servimos o ramen mais gostoso da vila, e quem sabe de todo o mundo shinobi! Veja nosso menu abaixo, e fique à vontade para escolher. Por enqüanto só servimos ramen, mas pretendemos ampliar os negócios em breve.<br /><br /><img src="_img/yens.png" width="14" height="14" align="absmiddle" /> <span style="color:#FFFFAA;"><b>Meus yens: <?php echo number_format($db['yens'],2,',','.'); ?> yens</b></span></div><div class="sep"></div>
	<?php
	if(isset($_GET['msg'])){
		switch($_GET['msg']){
			case 1: $msg='Yens insuficientes para realizar esta ação.'; break;
			case 2: $msg='Ramen comprado com sucesso! Visite sua página inicial para usá-lo.'; break;
		}
	echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>';
	}
	?>
	<table width="100%" cellpadding="0" cellspacing="1">
    <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
    	<td width="140"><img src="_img/ramen/ramen1.jpg" /></td>
        <td><b>Gohan</b><br /><span class="sub2">Regenera 50 pontos<br />de Energia</span><br /><br /><b><?php echo number_format($valorinicial,2,',','.'); ?> yens</b><br /><span class="sub2">Valor Unitário</span></td>
        <td width="20%">
        <form method="post" id="ramen" name="ramen" action="?p=ramen" onsubmit="subm.value='Carregando...';subm.disabled=true;">
        <input type="hidden" id="ram_id" name="ram_id" value="1" />
        <select id="ram_qtdade" name="ram_qtdade">
            <?php $i=1; do{ ?>
            <option value="<?php echo $i; ?>"><?php echo $i; ?> Unidade<?php if($i>1) echo 's'; ?></option>
            <?php $i++; } while($i<6); ?>
        </select>
        <br /><span class="sub2">Selecione a quantidade</span><br />
        <input type="submit" id="subm" name="subm" class="botao" value="Comprar">
        </form>
        </td>
    </tr>
    <tr>
    	<td colspan="3"><div class="sep"></div></td>
    </tr>
    <tr class="table_dados" style="background:#323232">
    	<td><img src="_img/ramen/ramen2.jpg" /></td>
        <td><b>Sushi</b><br /><span class="sub2">Regenera 100 pontos<br />de Energia</span><br /><br /><b><?php echo number_format(($valorinicial*2),2,',','.'); ?> yens</b><br /><span class="sub2">Valor Unitário</span></td>
        <td>
        <form method="post" id="ramen" name="ramen" action="?p=ramen" onsubmit="subm.value='Carregando...';subm.disabled=true;">
        <input type="hidden" id="ram_id" name="ram_id" value="2" />
        <select id="ram_qtdade" name="ram_qtdade">
            <?php $i=1; do{ ?>
            <option value="<?php echo $i; ?>"><?php echo $i; ?> Unidade<?php if($i>1) echo 's'; ?></option>
            <?php $i++; } while($i<6); ?>
        </select>
        <br /><span class="sub2">Selecione a quantidade</span><br />
        <input type="submit" id="subm" name="subm" class="botao" value="Comprar">
        </form>
        </td>
    </tr>
    <tr>
    	<td colspan="3"><div class="sep"></div></td>
    </tr>
    <tr class="table_dados" style="background:#323232">
    	<td><img src="_img/ramen/ramen3.jpg" /></td>
        <td><b>Porção de Peixe Empanado</b><br /><span class="sub2">Regenera 250 pontos<br />de Energia</span><br /><br /><b><?php echo number_format(($valorinicial*5),2,',','.'); ?> yens</b><br /><span class="sub2">Valor Unitário</span></td>
        <td>
        <form method="post" id="ramen" name="ramen" action="?p=ramen" onsubmit="subm.value='Carregando...';subm.disabled=true;">
        <input type="hidden" id="ram_id" name="ram_id" value="3" />
        <select id="ram_qtdade" name="ram_qtdade">
            <?php $i=1; do{ ?>
            <option value="<?php echo $i; ?>"><?php echo $i; ?> Unidade<?php if($i>1) echo 's'; ?></option>
            <?php $i++; } while($i<6); ?>
        </select>
        <br /><span class="sub2">Selecione a quantidade</span><br />
        <input type="submit" id="subm" name="subm" class="botao" value="Comprar">
        </form>
        </td>
    </tr>
    <tr>
    	<td colspan="3"><div class="sep"></div></td>
    </tr>
    <tr class="table_dados" style="background:#323232">
    	<td><img src="_img/ramen/ramen4.jpg" /></td>
        <td><b>Porção de Sashimi</b><br /><span class="sub2">Regenera 500 pontos<br />de Energia</span><br /><br /><b><?php echo number_format(($valorinicial*10),2,',','.'); ?> yens</b><br /><span class="sub2">Valor Unitário</span></td>
        <td>
        <form method="post" id="ramen" name="ramen" action="?p=ramen" onsubmit="subm.value='Carregando...';subm.disabled=true;">
        <input type="hidden" id="ram_id" name="ram_id" value="4" />
        <select id="ram_qtdade" name="ram_qtdade">
            <?php $i=1; do{ ?>
            <option value="<?php echo $i; ?>"><?php echo $i; ?> Unidade<?php if($i>1) echo 's'; ?></option>
            <?php $i++; } while($i<6); ?>
        </select>
        <br /><span class="sub2">Selecione a quantidade</span><br />
        <input type="submit" id="subm" name="subm" class="botao" value="Comprar">
        </form>
        </td>
    </tr>
    <tr>
    	<td colspan="3"><div class="sep"></div></td>
    </tr>
    <tr class="table_dados" style="background:#323232">
    	<td><img src="_img/ramen/ramen5.jpg" /></td>
        <td><b>Ramen</b><br /><span class="sub2">Regenera 1.000 pontos<br />de Energia</span><br /><br /><b><?php echo number_format(($valorinicial*20),2,',','.'); ?> yens</b><br /><span class="sub2">Valor Unitário</span></td>
        <td>
        <form method="post" id="ramen" name="ramen" action="?p=ramen" onsubmit="subm.value='Carregando...';subm.disabled=true;">
        <input type="hidden" id="ram_id" name="ram_id" value="5" />
        <select id="ram_qtdade" name="ram_qtdade">
            <?php $i=1; do{ ?>
            <option value="<?php echo $i; ?>"><?php echo $i; ?> Unidade<?php if($i>1) echo 's'; ?></option>
            <?php $i++; } while($i<6); ?>
        </select>
        <br /><span class="sub2">Selecione a quantidade</span><br />
        <input type="submit" id="subm" name="subm" class="botao" value="Comprar">
        </form>
        </td>
    </tr>
    </table>
</div>
<div class="box_bottom"></div>