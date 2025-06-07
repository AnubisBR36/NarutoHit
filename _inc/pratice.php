<?php
require_once('verificar_sala.php');
if(!isset($_GET['id'])){ echo "<script>self.location='?p=school'</script>"; return; }
if(!isset($_GET['jutsu'])){ echo "<script>self.location='?p=school'</script>"; return; }
vn($c->decode($_GET['jutsu'],$chaveuniversal));
$atual=date('Y-m-d H:i:s');
$fim=$dbr['fim'];
if($atual<$dbr['fim']){
	$sqltempo=mysql_fetch_assoc(mysql_query("SELECT timediff('$fim','$atual') as fim"));
	$fim=$sqltempo['fim'];
	$msgconc='<b>Tempo Restante: <span id="sala_tempo" style="color:#FFFFFF">'.$fim.'</span></b>';
	$msg='<b>Tempo Restante: <span id="sala_tempo" style="color:#FFFFFF">'.$fim.'</span></b>';
} else $msgconc='<b>Tempo Restante: <span id="sala_tempo" style="color:#FFFFFF">'.$fim.'</span></b>';
?>
<script language="javascript" type="text/javascript">
var conc=0;
function calculafim(div,divtotal){
	if(conc==0){
	var navegador=navigator.appName;
	var tmp = document.getElementById(div).innerHTML.split(":");
	var s = tmp[2];
	var m = tmp[1];
	var h = tmp[0];
	s--;
	if (s < 00){ s = 59;	m--; }
	if (m < 00){ m = 59;	h--; };
	s = new String(s); if (s.length < 2) s = "0" + s;
	m = new String(m); if (m.length < 2) m = "0" + m;
	h = new String(h); if (h.length < 2) h = "0" + h;
	
	var temp = h + ":" + m + ":" + s;
	
	document.getElementById(div).innerHTML = temp;
	document.getElementById(div).value = temp;
	atualiza(div,divtotal);
	}
}
<?php if($atual<$dbr['fim']) echo "window.setInterval('calculafim(\"sala_tempo\",\"mensagem\")',1000);"; ?>
function atualiza(div,divtotal){
  	if((document.getElementById(div).value) < "00:00:01"){
  		self.location="?p=school";
  		conc=1;
	}
}
</script>
<?php
$sqlj=mysql_query("SELECT id,nome,natureza,forca,nivel,valor FROM table_jutsus WHERE id=".$c->decode($_GET['jutsu'],$chaveuniversal));
$dbj=mysql_fetch_assoc($sqlj);
if($dbj['nivel']>$db['nivel']){ echo "<script>self.location='?p=learn&id=".$_GET['id']."'</script>"; break; }
if($dbj['valor']>$db['yens']){ echo "<script>self.location='?p=learn&id=".$_GET['id']."&msg=2'</script>"; break; }
if(($dbj['natureza']<>'nenhum')&&($db['natureza1']<>$dbj['natureza'])&&($db['natureza2']<>$dbj['natureza'])&&($db['natureza3']<>$dbj['natureza'])){ echo "<script>self.location='?p=learn&id=".$_GET['id']."'</script>"; break; }
if(isset($_POST['contador'])){
	if($_POST['contador']>=floor($dbj['forca']/4)){
		$sqlv=mysql_query("SELECT count(id) conta FROM jutsus WHERE usuarioid=".$db['id']." AND jutsu=".$dbj['id']);
		$dbv=mysql_fetch_assoc($sqlv);
		if($dbv['conta']==0){
			if($dbj['valor']>$db['yens']){ echo "<script>self.location='?p=learn&id=".$_GET['id']."&msg=2'</script>"; break; }
			mysql_query("INSERT INTO jutsus (usuarioid, jutsu, nivel, exp, expmax) VALUES (".$db['id'].", ".$dbj['id'].", 1, 0, 50)");
			mysql_query("INSERT INTO atualizacoes (usuarioid, texto, hora) VALUES (".$db['id'].", '<a href=?p=view&view=".strtolower($db['usuario']).">".$db['usuario']."</a> aprendeu <b>".$dbj['nome']."</b>.', '".time(date('Y-m-d H:i:s'))."')");
			mysql_query("UPDATE usuarios SET yens=yens-".$dbj['valor']." WHERE id=".$db['id']);
			echo "<script>self.location='?p=room&id=".$_GET['id']."&msg=2'</script>"; break;
		} else { echo "<script>self.location='?p=room&id=".$_GET['id']."'</script>"; break; }
	}
}
?>
<div class="box_top">Aprender Jutsu</div>
<div class="box_middle">É hora de treinar! Seja o mais rápido possível, e faça os selos que lhe mostrarei. Caso você erre, começará tudo novamente. Completando os <?php echo floor($dbj['forca']/4); ?> selos, você estará pronto para utilizar o jutsu!<div class="sep"></div>
	<div class="aviso" id="mensagem">
    <?php
	if(isset($_GET['msg'])){
		switch($_GET['msg']){
			case 1: $errmsg='Você não está pronto para controlar a natureza do seu chakra.<br />Volte quando estiver no nível 15.';
		}
	echo $errmsg.'<div class="sep"></div>';
	}
	?>
    <b>
	<?php
	if($atual<$dbr['fim'])
		echo $msg; 
	else
		echo $msgconc;
	?>
    </b><br /><a href="?p=room&amp;leave=true">Sair da Sala</a></div><div class="sep"></div>
    <div align="center" style="margin-top:10px;" id="div_minigame">
    <?php
    $inicial=rand(1,3);
	$selos=array('bode','dragao','cobra','cachorro','coelho','boi','macaco','cavalo','tigre','rato','passaro');
	?>
    <script>
	inicial=<?php echo $inicial; ?>;
	conta=0;
	function randOrd() {
    	return (Math.round(Math.random())-0.5);
	}
	function minigame(id){
		var selos=Array();
		selos[0]='bode';
		selos[1]='dragao';
		selos[2]='javali';
		selos[3]='cobra';
		selos[4]='cachorro';
		selos[5]='coelho';
		selos[6]='boi';
		selos[7]='macaco';
		selos[8]='cavalo';
		selos[9]='tigre';
		selos[10]='rato';
		selos[11]='passaro';
		if(inicial==id){
			conta=conta+1;
			document.getElementById('status').innerHTML='Acertou!!';
			document.getElementById('status').style.color='#00CC00';
		} else {
			conta=0;
			document.getElementById('status').innerHTML='Errou!!';
			document.getElementById('status').style.color='#FF0000';
		}
		selos.sort(randOrd);
		numero=Math.floor(Math.random()*8);
		novo=selos[numero];
		inicial=numero+1;
		document.getElementById('repeat').setAttribute("src","_img/school/selo_"+novo+".jpg");
		document.getElementById('t1').setAttribute("src","_img/school/selo_"+selos[0]+".jpg");
		document.getElementById('t2').setAttribute("src","_img/school/selo_"+selos[1]+".jpg");
		document.getElementById('t3').setAttribute("src","_img/school/selo_"+selos[2]+".jpg");
		document.getElementById('t4').setAttribute("src","_img/school/selo_"+selos[3]+".jpg");
		document.getElementById('t5').setAttribute("src","_img/school/selo_"+selos[4]+".jpg");
		document.getElementById('t6').setAttribute("src","_img/school/selo_"+selos[5]+".jpg");
		document.getElementById('t7').setAttribute("src","_img/school/selo_"+selos[6]+".jpg");
		document.getElementById('t8').setAttribute("src","_img/school/selo_"+selos[7]+".jpg");
		document.getElementById('contador').value=conta;
		document.getElementById('count').innerHTML=conta;
		if(conta>=<?php echo floor($dbj['forca']); ?>){
			document.form_minigame.submit();
		}
	}
	</script> 
    <table width="100%" cellpadding="0" cellspacing="1" style="background:url(_img/school/fundo_selos.jpg) center no-repeat;">
    	<tr>
    	  <td width="43%" rowspan="2" align="center" height="125"><img id="repeat" src="_img/school/selo_<?php echo $selos[$inicial-1]; ?>.jpg" /></td>
    	  <td align="center"><div id="conta" style="font-size:18px;font-weight:bold;margin-top:7px;margin-right:25px;color:#666666;">Acertos: <span id="count">0</span>/<?php echo floor($dbj['forca']); ?></div></td>
  	  </tr>
    	<tr>
        	<td align="center" valign="top"><div id="status" style="font-size:18px;font-weight:bold;margin-right:25px;">-</div></td>
      </tr>
    </table>
    <br />
    <img id="t1" src="_img/school/selo_<?php echo $selos[0]; ?>.jpg" onclick="minigame(1)" style="cursor:pointer;" />
    <img id="t2" src="_img/school/selo_<?php echo $selos[1]; ?>.jpg" onclick="minigame(2)" style="cursor:pointer;" />
    <img id="t3" src="_img/school/selo_<?php echo $selos[2]; ?>.jpg" onclick="minigame(3)" style="cursor:pointer;" />
    <img id="t4" src="_img/school/selo_<?php echo $selos[3]; ?>.jpg" onclick="minigame(4)" style="cursor:pointer;" />
    <br />
    <img id="t5" src="_img/school/selo_<?php echo $selos[4]; ?>.jpg" onclick="minigame(5)" style="cursor:pointer;" />
    <img id="t6" src="_img/school/selo_<?php echo $selos[5]; ?>.jpg" onclick="minigame(6)" style="cursor:pointer;" />
    <img id="t7" src="_img/school/selo_<?php echo $selos[6]; ?>.jpg" onclick="minigame(7)" style="cursor:pointer;" />
    <img id="t8" src="_img/school/selo_<?php echo $selos[7]; ?>.jpg" onclick="minigame(8)" style="cursor:pointer;" />
    <form method="post" action="?p=pratice&amp;id=<?php echo $_GET['id']; ?>&amp;jutsu=<?php echo $c->encode($dbj['id'],$chaveuniversal); ?>" name="form_minigame" id="form_minigame">
    	<input type="hidden" id="contador" name="contador" value="0" />
    </form>
  </div>
</div>
<div class="box_bottom"></div>
<?php
@mysql_free_result($sqlj);
@mysql_free_result($sqln);
@mysql_free_result($sqlv);
?>