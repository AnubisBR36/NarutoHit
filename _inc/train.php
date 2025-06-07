<?php
require_once('trava.php');
require_once('verificar.php');
if(isset($_POST['train_taijutsu'])){
	if($_POST['restante']<0){ echo "<script>self.location='?p=home'</script>"; exit; }
	$taijutsu=$_POST['train_taijutsu'];
	$ninjutsu=$_POST['train_ninjutsu'];
	$genjutsu=$_POST['train_genjutsu'];
	if(($taijutsu<=0)or($ninjutsu<=0)or($genjutsu<=0)){ echo "<script>self.location='?p=home'</script>"; exit; }
	$total=0;
	if($taijutsu>$db['taijutsu']) do{
		$total=$total+round(($db['taijutsu']*2)+($db['taijutsu']*$db['taijutsu'])+($db['taijutsu']*0.2));
		$db['taijutsu']=$db['taijutsu']+1;
	} while($taijutsu>$db['taijutsu']);
	if($ninjutsu>$db['ninjutsu']) do{
		$total=$total+round(($db['ninjutsu']*2)+($db['ninjutsu']*$db['ninjutsu'])+($db['ninjutsu']*0.2));
		$db['ninjutsu']=$db['ninjutsu']+1;
	} while($ninjutsu>$db['ninjutsu']);
	if($genjutsu>$db['genjutsu']) do{
		$total=$total+round(($db['genjutsu']*2)+($db['genjutsu']*$db['genjutsu'])+($db['genjutsu']*0.2));
		$db['genjutsu']=$db['genjutsu']+1;
	} while($genjutsu>$db['genjutsu']);
	if($total>$db['yens']){ echo "<script>self.location='?p=train&msg=2'</script>"; exit; }
	mysql_query("UPDATE usuarios SET yens=yens-$total, taijutsu=$taijutsu, ninjutsu=$ninjutsu, genjutsu=$genjutsu WHERE id=".$db['id']);
	echo "<script>self.location='?p=train&msg=1&yens=".$total."'</script>";
}
?>
<script>
var taijutsu=<?php echo $db['taijutsu']; ?>;
var taipadrao=taijutsu;
var ninjutsu=<?php echo $db['ninjutsu']; ?>;
var ninpadrao=ninjutsu;
var genjutsu=<?php echo $db['genjutsu']; ?>;
var genpadrao=genjutsu;
var total=0;
var yens=<?php echo $db['yens']; ?>;
function visibility(){
	setataijutsu=document.getElementById('taidown');
	setaninjutsu=document.getElementById('nindown');
	setagenjutsu=document.getElementById('gendown');
	if(taijutsu<=taipadrao) setataijutsu.style.visibility='hidden'; else setataijutsu.style.visibility='visible';
	if(ninjutsu<=ninpadrao) setaninjutsu.style.visibility='hidden'; else setaninjutsu.style.visibility='visible';
	if(genjutsu<=genpadrao) setagenjutsu.style.visibility='hidden'; else setagenjutsu.style.visibility='visible';
	if((taijutsu<=taipadrao)&&(ninjutsu<=ninpadrao)&&(genjutsu<=genpadrao)) document.getElementById('train_button').style.display='none'; else document.getElementById('train_button').style.display='block';
}
function float2moeda(num) {
   x = 0;
   if(num<0) {
      num = Math.abs(num);
      x = 1;
   }
      if(isNaN(num)) num = "0";
      cents = Math.floor((num*100+0.5)%100);
   num = Math.floor((num*100+0.5)/100).toString();
   if(cents < 10) cents = "0" + cents;
      for (var i = 0; i < Math.floor((num.length-(1+i))/3); i++)
         num = num.substring(0,num.length-(4*i+3))+'.'
               +num.substring(num.length-(4*i+3));
			   ret = num + ',' + cents;
			   if (x == 1) ret = ' - ' + ret;return ret;
}
function soma(ind,direcao){
	if(ind=='tai'){
		if(direcao=='up')
			somar=Math.round((taijutsu*2)+(taijutsu*taijutsu)+(taijutsu*0.2));
		else {
			somar=Math.round(((taijutsu-1)*2)+((taijutsu-1)*(taijutsu-1))+((taijutsu-1)*0.2));
			somar=(somar)*(-1);
		}
	}
	if(ind=='nin'){
		if(direcao=='up')
			somar=Math.round((ninjutsu*2)+(ninjutsu*ninjutsu)+(ninjutsu*0.2));
		else {
			somar=Math.round(((ninjutsu-1)*2)+((ninjutsu-1)*(ninjutsu-1))+((ninjutsu-1)*0.2));
			somar=(somar)*(-1);
		}
	}
	if(ind=='gen'){
		if(direcao=='up')
			somar=Math.round((genjutsu*2)+(genjutsu*genjutsu)+(genjutsu*0.2));
		else {
			somar=Math.round(((genjutsu-1)*2)+((genjutsu-1)*(genjutsu-1))+((genjutsu-1)*0.2));
			somar=(somar)*(-1);
		}
	}
	total=total+somar;
	restante=yens-total;
	document.getElementById('totaltrain').innerHTML=float2moeda(total);
	document.getElementById('resttrain').innerHTML=float2moeda(restante);
	document.forms[0].restante.value=restante;
}
function newvalues(att){
	if(att=='tai'){
		valor=Math.round((taijutsu*2)+(taijutsu*taijutsu)+(taijutsu*0.2));
		document.getElementById('taivalue').innerHTML=float2moeda(valor)+' yens';
	} else
	if(att=='nin'){
		valor=Math.round((ninjutsu*2)+(ninjutsu*ninjutsu)+(ninjutsu*0.2));
		document.getElementById('ninvalue').innerHTML=float2moeda(valor)+' yens';
	} else
	if(att=='gen'){
		valor=Math.round((genjutsu*2)+(genjutsu*genjutsu)+(genjutsu*0.2));
		document.getElementById('genvalue').innerHTML=float2moeda(valor)+' yens';
	}
}
function sortNumber(a,b){
	return b - a;
}
function atualizabarras(){
	max=194;
	array=new Array(taijutsu,ninjutsu,genjutsu);
	array.sort(sortNumber);
	array2=new Array(taijutsu,ninjutsu,genjutsu);
	if(array[0]==array2[0]) document.getElementById('taibar').setAttribute('width',Math.round((max*array[0])/array[0])); else
	if(array[1]==array2[0]) document.getElementById('taibar').setAttribute('width',Math.round((max*array[1])/array[0])); else
	if(array[2]==array2[0]) document.getElementById('taibar').setAttribute('width',Math.round((max*array[2])/array[0]));
	if(array[0]==array2[1]) document.getElementById('ninbar').setAttribute('width',Math.round((max*array[0])/array[0])); else
	if(array[1]==array2[1]) document.getElementById('ninbar').setAttribute('width',Math.round((max*array[1])/array[0])); else
	if(array[2]==array2[1]) document.getElementById('ninbar').setAttribute('width',Math.round((max*array[2])/array[0]));
	if(array[0]==array2[2]) document.getElementById('genbar').setAttribute('width',Math.round((max*array[0])/array[0])); else
	if(array[1]==array2[2]) document.getElementById('genbar').setAttribute('width',Math.round((max*array[1])/array[0])); else
	if(array[2]==array2[2]) document.getElementById('genbar').setAttribute('width',Math.round((max*array[2])/array[0]));
}
function change(at,dir){
	if(dir>0)
		soma(at,'up');
	else
		soma(at,'down');
	if(at=='tai'){
		el=document.getElementById('tai');
		el.innerHTML=taijutsu+dir;
		taijutsu=taijutsu+dir;
		document.forms[0].train_taijutsu.value=(document.forms[0].train_taijutsu.value*1)+dir;
		visibility();
		newvalues('tai');
	} else
	if(at=='nin'){
		el=document.getElementById('nin');
		el.innerHTML=ninjutsu+dir;
		ninjutsu=ninjutsu+dir;
		document.forms[0].train_ninjutsu.value=(document.forms[0].train_ninjutsu.value*1)+dir;
		visibility();
		newvalues('nin');
	} else
	if(at=='gen'){
		el=document.getElementById('gen');
		el.innerHTML=genjutsu+dir;
		genjutsu=genjutsu+dir;
		document.forms[0].train_genjutsu.value=(document.forms[0].train_genjutsu.value*1)+dir;
		visibility();
		newvalues('gen');
	}
	if(restante<0)
		document.getElementById('train_button').style.display='none';
	atualizabarras();
}
</script>
<?php
$max=194;
function equacao($atr){
	$resultado=round(($atr*2)+($atr*$atr)+($atr*0.2));
	return $resultado;
}
$src="_img/bars/bar.png";
$array=array("t"=>$db['taijutsu'],"n"=>$db['ninjutsu'],"g"=>$db['genjutsu']);
rsort($array);
$array2=array("t"=>$db['taijutsu'],"n"=>$db['ninjutsu'],"g"=>$db['genjutsu']);
arsort($array2);
?>
<div class="box_top">Treino</div>
<form method="post" action="?p=train" onsubmit="subm.value='Carregando...';subm.disabled=true;">
<input type="hidden" id="train_taijutsu" name="train_taijutsu" value="<?php echo $db['taijutsu']; ?>" />
<input type="hidden" id="train_ninjutsu" name="train_ninjutsu" value="<?php echo $db['ninjutsu']; ?>" />
<input type="hidden" id="train_genjutsu" name="train_genjutsu" value="<?php echo $db['genjutsu']; ?>" />
<input type="hidden" id="restante" name="restante" value="" />
<div class="box_middle">Esta é sua área de treino de atributos. Utilize as setas abaixo para aumentar ou diminuir os atributos, e assim que estiver satisfeito, clique no botão Treinar. Os yens só serão gastos após a confirmação do treino.<div class="sep"></div>
	<?php if(isset($_GET['msg'])){
		switch($_GET['msg']){
			case 1: if(isset($_GET['yens'])) $yens=$_GET['yens']; else $yens=0; $msg='Treino realizado com sucesso! Foram gastos <b>'.number_format($yens,2,',','.').' yens</b> para realizar o treino.'; break;
			case 2: $msg='Yens insuficientes!'; break;
		}
	echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>';
	} ?>
	<div style="padding-left:5px;background:url(_img/gradient.jpg) repeat-y;color:#FFFFAA;"><img src="_img/yens.png" width="14" height="14" align="absmiddle" /> <b>Meus Yens: <?php echo number_format($db['yens'],2,',','.'); ?> yens</b></div>
    <div class="sep"></div>
	<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
        	<td width="13%" align="right" style="padding-right:10px;"><b>Taijutsu:</b></td>
          <td><img src="_img/bars/bar_left.jpg" /><?php
			if($array[0]==$array2["t"]) echo '<img id="taibar" src="'.$src.'" width="'.($max*$array[0])/$array[0].'" height="22" />'; else
			if($array[1]==$array2["t"]) echo '<img id="taibar" src="'.$src.'" width="'.($max*$array[1])/$array[0].'" height="22" />'; else
			if($array[2]==$array2["t"]) echo '<img id="taibar" src="'.$src.'" width="'.($max*$array[2])/$array[0].'" height="22" />';
			?><img src="_img/bars/bar_right.jpg" />
    		</td>
            <td width="8%"><img src="_img/up_arrow.png" style="cursor:pointer" onclick="change('tai',1);" /> <img id="taidown" src="../_img/down_arrow.png" style="cursor:pointer;visibility:hidden;" onclick="change('tai',-1);" /></td>
            <td width="12%" align="center"><b>| <span id="tai"><?php echo $db['taijutsu']; ?></span> |</b></td>
          <td width="22%" align="right"><b><div id="taivalue"><?php echo number_format(equacao($db['taijutsu']),2,',','.'); ?> yens</div></b></td>
        </tr>
        <tr>
        	<td align="right" style="padding-right:10px;"><b>Ninjutsu:</b></td>
          <td><img src="_img/bars/bar_left.jpg" /><?php
			if($array[0]==$array2["n"]) echo '<img id="ninbar" src="'.$src.'" width="'.($max*$array[0])/$array[0].'" height="22" />'; else
			if($array[1]==$array2["n"]) echo '<img id="ninbar" src="'.$src.'" width="'.($max*$array[1])/$array[0].'" height="22" />'; else
			if($array[2]==$array2["n"]) echo '<img id="ninbar" src="'.$src.'" width="'.($max*$array[2])/$array[0].'" height="22" />';
			?><img src="_img/bars/bar_right.jpg" />
            </td>
            <td><img src="_img/up_arrow.png" style="cursor:pointer" onclick="change('nin',1);" /> <img id="nindown" src="../_img/down_arrow.png" style="cursor:pointer;visibility:hidden;" onclick="change('nin',-1);" /></td>
            <td align="center"><b>| <span id="nin"><?php echo $db['ninjutsu']; ?></span> |</b></td>
          <td align="right"><b><div id="ninvalue"><?php echo number_format(equacao($db['ninjutsu']),2,',','.'); ?> yens</div></b></td>
        </tr>
        <tr>
        	<td align="right" style="padding-right:10px;"><b>Genjutsu:</b></td>
          <td><img src="_img/bars/bar_left.jpg" /><?php
			if($array[0]==$array2["g"]) echo '<img id="genbar" src="'.$src.'" width="'.($max*$array[0])/$array[0].'" height="22" />'; else
			if($array[1]==$array2["g"]) echo '<img id="genbar" src="'.$src.'" width="'.($max*$array[1])/$array[0].'" height="22" />'; else
			if($array[2]==$array2["g"]) echo '<img id="genbar" src="'.$src.'" width="'.($max*$array[2])/$array[0].'" height="22" />';
			?><img src="_img/bars/bar_right.jpg" />
          </td>
            <td><img src="_img/up_arrow.png" style="cursor:pointer" onclick="change('gen',1);" /> <img id="gendown" src="../_img/down_arrow.png" style="cursor:pointer;visibility:hidden;" onclick="change('gen',-1);" /></td>
            <td align="center"><b>| <span id="gen"><?php echo $db['genjutsu']; ?></span> |</b></td>
          <td align="right"><b><div id="genvalue"><?php echo number_format(equacao($db['genjutsu']),2,',','.'); ?> yens</div></b></td>
        </tr>
    </table>
  <div class="sep"></div>
    <div style="padding-left:5px;background:url(_img/gradient.jpg) repeat-y"><img src="_img/yens_neg.png" align="absmiddle" /> <b>Total: <span id="totaltrain"><?php echo number_format(0,2,',','.'); ?></span> yens</b></div>
    <div class="sep"></div>
    <div style="padding-left:5px;background:url(_img/gradient.jpg) repeat-y"><img src="_img/yens.png" width="14" height="14" align="absmiddle" /> <b>Restará: <span id="resttrain"><?php echo number_format($db['yens'],2,',','.'); ?></span> yens</b></div>
    <div id="train_button" style="display:none;"><div class="sep"></div>
    <div align="center"><input type="submit" id="subm" name="subm" class="botao" value="Treinar" /></div></div>
</div>
</form>
<div class="box_bottom"></div>