<?php
require_once('trava.php');
require_once('Encrypt.php');
$c=new C_Encrypt();

if(isset($_POST['char_id'])){
	$buy=$c->decode($_POST['char_id'],$chaveuniversal);
	$nivel=$c->decode($_POST['char_nivel'],$chaveuniversal);
	vn($buy); vn($nivel);
	$personagem=$c->decode($_POST['char_char'],$chaveuniversal);
	if($db['nivel']<$nivel){ echo "<script>self.location='?p=shop&category=characters&msg=4'</script>"; return; }
	mysql_query("UPDATE personagens SET ".$personagem."=1 WHERE usuarioid=".$db['id']);
	echo "<script>self.location='?p=shop&category=characters&msg=5'</script>";
}

if(isset($_POST['buy_id'])){
	$buy=$_POST['buy_id'];
	vn($buy);
	if($buy<1){ echo "<script>self.location='?p=home'</script>"; return; }
	$category=$_POST['buy_cat'];
	if(($category<>'arma')&&($category<>'vestimenta')&&($category<>'calcado')){ echo "<script>self.location='?p=home'</script>"; return; }
	switch($category){
		case 'arma': $sqlb=mysql_query("SELECT valor, reqtai, vip FROM table_itens WHERE id=$buy"); break;
		case 'vestimenta': $sqlb=mysql_query("SELECT valor, reqgen, vip FROM table_itens WHERE id=$buy"); break;
		case 'calcado': $sqlb=mysql_query("SELECT valor, reqtai, vip FROM table_itens WHERE id=$buy"); break;
	}
	if(mysql_num_rows($sqlb)==0){ echo "<script>self.location='?p=home'</script>"; return; }
	$dbb=mysql_fetch_assoc($sqlb);
	if(($dbb['vip']=='sim')&&(date('Y-m-d H:i:s')>=$db['vip'])){ echo "<script>self.location='?p=home'</script>"; return; }
	$valor=$dbb['valor'];
	if(date('Y-m-d H:i:s')<$db['vip']) $valor=floor($valor*(0.8));
	if(($db['renegado']=='nao')&&($buy==1)){ echo "<script>self.location='?p=home'</script>"; return; }
	$page=$_POST['buy_page'];
	$bloq=0;
	if($category=='arma')
		if($db['taijutsu']<$dbb['reqtai']) $bloq=3;
	if($category=='vestimenta')
		if($db['genjutsu']<$dbb['reqgen']) $bloq=6;
	if($category=='calcado'){
		if($db['taijutsu']<$dbb['reqtai']) $bloq=7;
		if($db['ninjutsu']<$dbb['reqtai']) $bloq=7;
		if($db['genjutsu']<$dbb['reqtai']) $bloq=7;
	}
	if($bloq>0){ echo "<script>self.location='?p=shop&category=".$page."&msg=".$bloq."'</script>"; return; }
	if($db['yens']<$valor){ echo "<script>self.location='?p=shop&category=".$page."&msg=1'</script>"; return; }
	$sqli=mysql_query("SELECT count(id) conta FROM inventario WHERE usuarioid=".$db['id']." AND itemid=".$buy);
	$dbi=mysql_fetch_assoc($sqli);
	if($dbi['conta']>0){ echo "<script>self.location='?p=shop&category=".$page."&msg=8'</script>"; return; }
	mysql_query("UPDATE usuarios SET yens=yens-$valor, compra=compra+1, gastoloja=gastoloja+$valor WHERE id=".$db['id']);
	mysql_query("INSERT INTO inventario (usuarioid,itemid,categoria) VALUES (".$db['id'].",".$buy.",'".$category."')");
	echo "<script>self.location='?p=shop&category=".$page."&msg=2'</script>";
}
?>
<div class="box_top">Comércio</div>
<div class="box_middle">Bem-vindo ao centro comercial da vila. Temos tudo que você precisa para sua jornada no mundo ninja! Selecione uma das categorias abaixo e boas compras!<?php if(date('Y-m-d H:i:s')<$db['vip']) echo '<br /><b>OBS: Os itens estão com 20% de desconto pela sua VIP.</b>'; ?><div class="sep"></div>
	<div style="background:url(_img/gradient.jpg) repeat-y;color:#FFFFAA;"><img src="_img/yens.png" width="14" height="14" /> <b>Meus Yens: <?php echo number_format($db['yens'],2,',','.'); ?> yens</b></div><div class="sep"></div>
	<?php
	if(isset($_GET['msg'])){
		switch($_GET['msg']){
			case 1: $msg='Yens insuficientes para comprar este item.'; break;
			case 2: $msg='Item comprado com sucesso! Visite seu <a href="?p=inventory">inventário</a> agora mesmo!'; break;
			case 3: $msg='Taijutsu insuficiente para comprar este item.'; break;
			case 4: $msg='Nível insuficiente para desbloquear este personagem.'; break;
			case 5: $msg='Personagem desbloqueado!'; break;
			case 6: $msg='Genjutsu insuficiente para comprar este item.'; break;
			case 7: $msg='Taijutsu, Ninjutsu ou Genjutsu insuficiente para comprar este item.'; break;
			case 8: $msg='Você já possui este item em seu inventário.'; break;
		}
	echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>';
	}
	?>
    <div align="center"><a href="?p=shop&amp;category=weapons">Armas</a> | <a href="?p=shop&amp;category=armors">Vestimentas</a> | <a href="?p=shop&amp;category=boots">Calçados</a> | <a href="?p=shop&amp;category=characters">Personagens</a> | <a href="?p=shops">Comércio Interno</a></div>
    <div class="sep"></div>
    <?php
	if(!isset($_GET['category'])) require_once('shop_weapons.php'); else
    if(isset($_GET['category'])){
		switch($_GET['category']){
			case 'weapons': require_once('shop_weapons.php'); break;
			case 'armors': require_once('shop_armors.php'); break;
			case 'characters': require_once('shop_characters.php'); break;
			case 'boots': require_once('shop_boots.php'); break;
		}
	}
	?>
</div>
<div class="box_bottom"></div>