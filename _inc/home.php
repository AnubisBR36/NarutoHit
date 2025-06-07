<?php require_once('trava.php'); ?>
<?php if(isset($db['config_recuperacao']) && $db['config_recuperacao']==0) require_once('avisorecuperacao.php'); ?>
<?php require_once('update.php'); ?>
<?php
require_once('Encrypt.php');
$c=new C_Encrypt();

if(isset($_GET['off'])){
	if($db['orgid']==-1) mysql_query("UPDATE usuarios SET orgid=0 WHERE id=".$db['id']);
	echo "<script>self.location='?p=home'</script>";
}

$array=array("t"=>$db['taijutsu'],"n"=>$db['ninjutsu'],"g"=>$db['genjutsu']);
rsort($array);
$array2=array("t"=>$db['taijutsu'],"n"=>$db['ninjutsu'],"g"=>$db['genjutsu']);
arsort($array2);
$tam=220;
require_once('funcoes.php');
?>
<?php if($db['hunt']>0) require_once('busyhunt.php'); ?>
<?php if($db['missao']>0) require_once('busymission.php'); ?>
<div class="box_top">nLink</div>
<div class="box_middle">Utilize seu nLink para divulgar o narutoHIT. Ao mesmo tempo, você ganhará <b>100,00 yens</b> para cada usuário que se cadastrar no jogo utilizando seu nLink. Avisamos desde já que qualquer prática de spam não será tolerada, resultando em banimento de sua conta. Esta função lhe ajudará apenas com os yens, e os ninjas cadastrados utilizando seu nLink não estarão ligados à sua conta.<div class="sep"></div>
	<div align="center"><a style="font-size:10px;" href="http://servidor02.narutohit.net/?p=reg&amp;nlink=<?php $key=$db['usuario']; echo $key; ?>">http://servidor02.narutohit.net/?p=reg&amp;nlink=<?php echo $key; ?></a></div>
</div>
<div class="box_bottom"></div>

<?php
$max=250;
$src="_img/bars/bar.png";
$array=array("t"=>$db['taijutsu'],"n"=>$db['ninjutsu'],"g"=>$db['genjutsu']);
rsort($array);
$array2=array("t"=>$db['taijutsu'],"n"=>$db['ninjutsu'],"g"=>$db['genjutsu']);
arsort($array2);
?>
<div class="box_top">Meus Atributos</div>
<div class="box_middle">Seus atributos de combate, yens atuais, nível e experiência.<div class="sep"></div>
	<?php
		if($db['renegado']=='sim'){
			$sqlx=mysql_query("SELECT id FROM usuarios WHERE renegado='sim' ORDER BY nivel DESC, yens_fat DESC LIMIT 1");
		  	$dbx=mysql_fetch_assoc($sqlx);
			if($dbx && $dbx['id']==$db['id']) $nivel='Líder da Akatsuki'; else $nivel='Nukenin';
		} else {
        	$sqlx=mysql_query("SELECT id FROM usuarios WHERE vila=".$db['vila']." AND renegado='nao' ORDER BY nivel DESC, yens_fat DESC LIMIT 1");
		  	$dbx=mysql_fetch_assoc($sqlx);
		  	if($dbx && $dbx['id']==$db['id']){
		  		switch($db['vila']){
					case 1: $nivel='Hokage'; break;
					case 2: $nivel='Kazekage'; break;
					case 3: $nivel='Otokage'; break;
					case 4: $nivel='Líder da Vila da Chuva'; break;
					case 5: $nivel='Raikage'; break;
					case 6: $nivel='Mizukage'; break;
					case 8: $nivel='Tsuchikage'; break;
				}
		  	} else $nivel=rankNinja($db['nivel']); 
		}
		?>
	<table width="100%" cellpadding="0" cellspacing="0"<?php if($dbx && $dbx['id']==$db['id']){
		echo ' style="background:url(_img/kage/kage';
		if($db['renegado']=='sim') echo '1'; else
		switch($db['vila']){
			case 1: echo '1'; break;
			case 2: echo '2'; break;
			case 3: echo '1'; break;
			case 4: echo '1'; break;
			case 5: echo '5'; break;
			case 6: echo '6'; break;
			case 8: echo '1'; break;
		}
		echo '.jpg) no-repeat right top;"'; } ?>>
    	<tr style="background:url(_img/gradient2.jpg) repeat-y;color:#FFFFAA;">
        	<td align="right" style="padding-right:10px;"><img src="_img/yens.png" width="14" height="14" align="absmiddle" /> <b>Meus Yens:</b></td>
            <td colspan="2"><b><?php echo number_format($db['yens'],2,',','.'); ?> yens</b></td>
        </tr>
        <tr>
        	<td colspan="3"><div class="sep"></div>
        </tr>
<tr style="background:url(_img/gradient2.jpg) repeat-y;">
        	<td width="20%" align="right" style="padding-right:10px;"><b>Registro:</b></td>
      <td colspan="2"><?php $reg=explode(' ',$db['reg']); $datareg=explode('-',$reg[0]); echo $datareg[2].'/'.$datareg[1].'/'.$datareg[0].', às '.$reg[1]; ?></td>
        </tr>
        <tr>
        	<td align="right" style="padding-right:10px;"><b>Personagem:</b></td>
          <td colspan="2"><?php fpersonagem($db['personagem']); ?></td>
        </tr>
        <tr style="background:url(_img/gradient2.jpg) repeat-y;">
        	<td align="right" style="padding-right:10px;"><b>Vila:</b></td>
      <td colspan="2"><?php echo $txtvila; ?></td>
        </tr>
        <?php /*<tr>
        	<td align="right" style="padding-right:10px;"><b>Aluno:</b></td>
          <td colspan="2"><?php /*if($db['alunoid']=='') echo '-'; else echo '<a href="?p=view&view='.strtolower($db['alunoid']).'">'.$db['alunoid'].'</a>'; ?></td>
        </tr>
        <tr style="background:url(_img/gradient.jpg) repeat-y;">
        	<td align="right" style="padding-right:10px;"><b>Sensei:</b></td>
      <td colspan="2"><?php /*if($db['senseiid']=='') echo '-'; else echo '<a href="?p=view&view='.strtolower($db['senseiid']).'">'.$db['senseiid'].'</a>'; ?></td>
        </tr>*/ ?>
        <tr>
        	<td align="right" style="padding-right:10px;"><b>Clã:</b></td>
          <td colspan="2"><?php if($db['orgid']==-1) echo '<div class="aviso">O clã em que você estava foi destruído.<br />Recomendamos que procure um outro clã.<br /><a href="?p=home&off=1">Clique aqui para desativar esta mensagem.</a></div>'; else if($db['orgid']==0) echo '-'; else echo '<a href="?p=myorg">'.$db['orgnome'].'</a>'; ?></td>
        </tr>
        <tr style="background:url(_img/gradient2.jpg) repeat-y;">
        	<td align="right" style="padding-right:10px;"><b>Nível:</b></td>
          <td colspan="2"><?php echo $nivel; ?><b> [<?php echo $db['nivel']; ?>]</b></td>
        </tr>
        <tr>
        	<td colspan="3"><div class="sep"></div></td>
        </tr>
        <tr>
        	<td align="right" style="padding-right:10px;"><b>Taijutsu:</b></td>
          <td><img src="_img/bars/bar_left.jpg" /><?php
			if($array[0]==$array2["t"]) echo '<img src="'.$src.'" width="'.($max*$array[0])/$array[0].'" height="22" />'; else
			if($array[1]==$array2["t"]) echo '<img src="'.$src.'" width="'.($max*$array[1])/$array[0].'" height="22" />'; else
			if($array[2]==$array2["t"]) echo '<img src="'.$src.'" width="'.($max*$array[2])/$array[0].'" height="22" />';
			?><img src="_img/bars/bar_right.jpg" /></td>
            <td width="25%"><b>| <?php echo $db['taijutsu']; ?> |</b>&nbsp;&nbsp;&nbsp;<span id="atrtai"><?php echo $db['orgnivel']; ?></span></td>
        </tr>
        <tr>
        	<td align="right" style="padding-right:10px;"><b>Ninjutsu:</b></td>
          <td><img src="_img/bars/bar_left.jpg" /><?php
			if($array[0]==$array2["n"]) echo '<img src="'.$src.'" width="'.($max*$array[0])/$array[0].'" height="22" />'; else
			if($array[1]==$array2["n"]) echo '<img src="'.$src.'" width="'.($max*$array[1])/$array[0].'" height="22" />'; else
			if($array[2]==$array2["n"]) echo '<img src="'.$src.'" width="'.($max*$array[2])/$array[0].'" height="22" />';
			?><img src="_img/bars/bar_right.jpg" />
            </td>
            <td><b>| <?php echo $db['ninjutsu']; ?> |</b>&nbsp;&nbsp;&nbsp;<span id="atrnin"><?php echo $db['orgnivel']; ?></span></td>
        </tr>
        <tr>
        	<td align="right" style="padding-right:10px;"><b>Genjutsu:</b></td>
          <td><img src="_img/bars/bar_left.jpg" /><?php
			if($array[0]==$array2["g"]) echo '<img src="'.$src.'" width="'.($max*$array[0])/$array[0].'" height="22" />'; else
			if($array[1]==$array2["g"]) echo '<img src="'.$src.'" width="'.($max*$array[1])/$array[0].'" height="22" />'; else
			if($array[2]==$array2["g"]) echo '<img src="'.$src.'" width="'.($max*$array[2])/$array[0].'" height="22" />';
			?><img src="_img/bars/bar_right.jpg" />
          </td>
            <td><b>| <?php echo $db['genjutsu']; ?> |</b>&nbsp;&nbsp;&nbsp;<span id="atrgen"><?php echo $db['orgnivel']; ?></span></td>
        </tr>
        <tr>
        	<td colspan="3"><div class="sep"></div></td>
        </tr>
        <tr>
        	<td align="right" style="padding-right:10px;"><b>Energia:</b></td>
          <td><img src="_img/bars/bar_left.png" /><img src="<?php echo $src; ?>" width="<?php echo (($db['energia']*$max)/$db['energiamax']); ?>" height="22" /><img src="_img/bars/bar_right.png" /></td>
            <td><b>| <?php echo $db['energia']; ?> / <?php echo $db['energiamax']; ?> |</b></td>
        </tr>
        <tr>
        	<td align="right" style="padding-right:10px;"><b>Experiência:</b></td>
          <td height="22" style="background:url(_img/bars/empty_bar.jpg) no-repeat;"><?php if($db['exp']==0) echo '&nbsp;'; else { ?><img src="_img/bars/bar_left.png" /><img src="<?php echo $src; ?>" width="<?php echo (($db['exp']*$max)/$db['expmax']); ?>" height="22" /><img src="_img/bars/bar_right.png" /><?php } ?></td>
            <td><b>| <?php echo $db['exp']; ?> / <?php echo $db['expmax']; ?> |</b></td>
        </tr>
    </table>
    <?php if(($db['hunt']==0)&&($db['missao']==0)&&($db['treino']==0)){ ?>
    <div class="sep"></div>
    <div align="center"><input type="button" class="botao" value="Realizar Treino" onclick="location.href='?p=train'" /></div>
    <?php } ?>
</div>
<div class="box_bottom"></div>
<?php
if(isset($_POST['ram_id'])){
	$id=$c->decode($_POST['ram_id'],$chaveuniversal);
	$tipo=$c->decode($_POST['ram_tipo'],$chaveuniversal);
	vn($id); vn($tipo);
	$sqlr=mysql_query("SELECT count(id) conta FROM ramen WHERE usuarioid=".$db['id']." AND id=".$id);
	$dbr=mysql_fetch_assoc($sqlr);
	if($dbr['conta']>0){
		$energia=$db['energia'];
		switch($tipo){
			case 1: $hp=50; break;
			case 2: $hp=100; break;
			case 3: $hp=250; break;
			case 4: $hp=500; break;
			case 5: $hp=1000; break;
		}
		if($energia+$hp>=$db['energiamax']) $energia=$db['energiamax']; else $energia=$energia+$hp;
		mysql_query("DELETE FROM ramen WHERE id=".$id);
		mysql_query("UPDATE usuarios SET energia=$energia WHERE id=".$db['id']);
		echo "<script>self.location='?p=home&msg=1&e=".$hp."'</script>";
	}
}
$sqlr=mysql_query("SELECT * FROM ramen WHERE usuarioid=".$db['id']." ORDER BY RAND() LIMIT 3");
if(mysql_num_rows($sqlr)>0){ $dbr=mysql_fetch_assoc($sqlr); require_once('inventario.php'); } ?>
<?php
$sqle=mysql_query("SELECT i.upgrade, t.* FROM inventario i LEFT OUTER JOIN table_itens t ON i.itemid=t.id WHERE i.usuarioid=".$db['id']." AND status='on' ORDER BY categoria");
if(mysql_num_rows($sqle)>0){ $dbe=mysql_fetch_assoc($sqle); require_once('equipamentos.php'); }
if($db['doujutsu']>0) require_once('meudoujutsu.php'); ?>
<div class="box_top">narutoHIT - version 2</div>
<div class="box_middle"><div align="center"><img src="_img/v2_soon.jpg" /></div></div>
<div class="box_bottom"></div>
<div class="box_top">Minhas Estatísticas</div>
<div class="box_middle">Todas as estatísticas de sua conta.<div class="sep"></div>
	<div style="background:url(_img/stats.jpg) no-repeat right top;">
	<table width="60%" cellpadding="0" cellspacing="0">
    	<tr style="background:url(_img/gradient.jpg) right;">
        	<td width="50%" style="padding-left:3px;"><b>Meus Yens</b></td>
            <td><?php echo number_format($db['yens'],2,',','.'); ?> yens</td>
        </tr>
        <tr>
        	<td style="padding-left:3px;"><b>Yens Faturados</b></td>
            <td><?php echo number_format($db['yens_fat'],2,',','.'); ?> yens</td>
        </tr>
        <tr style="background:url(_img/gradient.jpg) right;">
        	<td style="padding-left:3px;"><b>Yens Perdidos</b></td>
            <td><?php echo number_format(isset($db['yens_perd']) ? $db['yens_perd'] : 0,2,',','.'); ?> yens</td>
        </tr>
        <tr>
        	<td style="padding-left:3px;"><b>Batalhas</b></td>
            <td><?php echo isset($db['batalhas']) ? $db['batalhas'] : 0; ?> batalhas</td>
        </tr>
        <tr style="background:url(_img/gradient.jpg) right;">
        	<td style="padding-left:3px;"><b>Vitórias</b></td>
            <td><?php echo isset($db['vitorias']) ? $db['vitorias'] : 0; ?> vitórias</td>
        </tr>
        <tr>
        	<td style="padding-left:3px;"><b>Derrotas</b></td>
            <td><?php echo isset($db['derrotas']) ? $db['derrotas'] : 0; ?> derrotas</td>
        </tr>
        <tr style="background:url(_img/gradient.jpg) right;">
        	<td style="padding-left:3px;"><b>Empates</b></td>
            <td><?php echo isset($db['empates']) ? $db['empates'] : 0; ?> empates</td>
        </tr>
        <tr>
        	<td style="padding-left:3px;"><b>Experiência Total</b></td>
            <td><?php echo isset($db['exptotal']) ? $db['exptotal'] : 0; ?> pontos</td>
        </tr>
    </table>
    </div>
</div>
<div class="box_bottom"></div>
<?php require_once('atualizacoes.php'); ?>
<?php if(isset($db['config_twitter']) && $db['config_twitter']<>'') require_once('twitter.php'); ?>
<script>
if(((document.getElementById('atrtai').innerHTML)*1)>0) document.getElementById('atrtai').innerHTML='+'+document.getElementById('atrtai').innerHTML; else document.getElementById('atrtai').innerHTML='';
if(((document.getElementById('atrnin').innerHTML)*1)>0) document.getElementById('atrnin').innerHTML='+'+document.getElementById('atrnin').innerHTML; else document.getElementById('atrnin').innerHTML='';
if(((document.getElementById('atrgen').innerHTML)*1)>0) document.getElementById('atrgen').innerHTML='+'+document.getElementById('atrgen').innerHTML; else document.getElementById('atrgen').innerHTML='';
</script>
<?php
@mysql_free_result($sqlr);
?>