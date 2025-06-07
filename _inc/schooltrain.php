<?php
require_once('Encrypt.php');
$c=new C_Encrypt();

if(!isset($_GET['id'])){ echo "<script>self.location='?p=school'</script>"; return; }
require_once('verificar_sala.php');
$atual=date('Y-m-d H:i:s');
$fim=$dbr['fim'];
if($atual<$dbr['fim']){
	$sqltempo=mysql_fetch_assoc(mysql_query("SELECT timediff('$fim','$atual') as fim"));
	$fim=$sqltempo['fim'];
	$msgconc='<b>Tempo Restante: <span id="sala_tempo" style="color:#FFFFFF">'.$fim.'</span></b>';
	$msg='<b>Tempo Restante: <span id="sala_tempo" style="color:#FFFFFF">'.$fim.'</span></b>';
} else { echo "<script>self.location='?p=school'</script>"; return; }
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
if(isset($_POST['train'])){
	$train=$c->decode($_POST['train'],$chaveuniversal);
	$tempo=$c->decode($_POST['tempo'],$chaveuniversal);
	vn($train); vn($tempo);
	if(($tempo<10)or($tempo>480)){ echo "<script>self.location='?p=home'</script>"; break; }
	$soma=mktime(date('H'), date('i')+$tempo, date('s'));
	$treinofim=date('Y-m-d H:i:s',$soma);
	mysql_query("UPDATE salas SET fim='0000-00-00 00:00:00', usuarioid=0 WHERE id=".$_GET['id']);
	mysql_query("UPDATE usuarios SET treino=".$train.", treino_tempo=".$tempo.", treino_fim='".$treinofim."' WHERE id=".$db['id']);
	echo "<script>self.location='?p=busytrain'</script>";
}
$sqlj=mysql_query("SELECT j.*, t.nome, t.id FROM jutsus j LEFT OUTER JOIN table_jutsus t ON j.jutsu=t.id WHERE j.usuarioid=".$db['id']." ORDER BY natureza DESC");
$dbj=mysql_fetch_assoc($sqlj);
?>
<div class="box_top">Treinar</div>
<div class="box_middle">Aperfeiçoe tudo que você aprendeu até o momento! Escolha o que deseja treinar, e por quanto tempo. Lembre-se que a cada 10 minutos treinados, você adquire 2 pontos de experiência para a habilidade escolhida. Seu treinamento não será feito na sala, e sim no pátio da escola, portanto esta sala ficará disponível para outros ninjas.
  <div class="sep"></div>
	<div class="aviso" id="mensagem">
    <b>
	<?php
	if($atual<$dbr['fim'])
		echo $msg; 
	else
		echo $msgconc;
	?>
    </b></div><div class="sep"></div>
     <?php if(mysql_num_rows($sqlj)==0) echo '<div class="aviso">Nenhum jutsu aprendido até o momento.</div><div class="sep"></div>'; else { ?>
    <table width="100%" cellpadding="0" cellspacing="1">
    <?php do{ ?>
    <tr class="table_dados" style="background:#323232">
        <td width="230"><img src="_img/jutsus/<?php echo $dbj['jutsu']; ?>.jpg" onmouseover="Tip('<div class=tooltip><?php echo $dbj['nome']; ?></div>')" onmouseout="UnTip()" /></td>
        <td width="100"><b>Nível <?php echo $dbj['nivel']; ?></b><br /><span class="sub2">Experiência<br /><?php echo $dbj['exp'].' / '.$dbj['expmax']; ?></span></td>
        <td>
        <?php if($dbj['nivel']==5) echo 'Nível máximo alcançado!'; else { ?>
        <form method="post" id="missao" name="missao" action="?p=schooltrain&amp;id=<?php echo $_GET['id']; ?>" onsubmit="subm.value='Carregando...';subm.disabled=true;">
        <input type="hidden" id="train" name="train" value="<?php echo $c->encode($dbj['jutsu'],$chaveuniversal); ?>">
        <select id="tempo" name="tempo">
            <?php $i=1; do{ ?>
            <option value="<?php echo $c->encode(($i*10),$chaveuniversal); ?>"><?php echo $i*10; ?> minutos</option>
            <?php $i++; } while($i<49); ?>
        </select>
        <br /><span class="sub2">Selecione a quantidade de minutos</span>
        <input type="submit" id="subm" name="subm" class="botao" value="Escolher">
        </form>    <?php } ?></td>
    </tr>
    <tr>
    	<td colspan="3"><div class="sep"></div></td>
    </tr>
    <?php } while($dbj=mysql_fetch_assoc($sqlj)); ?>
    </table>
    <?php } ?>
    <div align="center"><input type="button" class="botao" value="Sair da Sala" onclick="location.href='?p=room&leave=true'" /></div>
</div>
<div class="box_bottom"></div>
<?php
@mysql_free_result($sqlj);
?>