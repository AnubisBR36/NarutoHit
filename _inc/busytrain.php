<?php
$atual=date('Y-m-d H:i:s');
if($db['treino']>0){
	if($atual<$db['treino_fim']){
		$fim=$db['treino_fim'];
		$sqltempo=mysql_fetch_assoc(mysql_query("SELECT timediff('$fim','$atual') as fim"));
		$fim=$sqltempo['fim'];
		$msgconc='Seu treino foi concluído! Clique <a href="?p=rewardtrain">aqui</a> para atualizar suas habilidades!';
		$msg='Você está realizando um treinamento na escola ninja.<br />Faltam <b><span id="treino_tempo">'.$fim.'</span></b> para terminar o treino.';
	} else $msgconc='Seu treino foi concluído! Clique <a href="?p=rewardtrain">aqui</a> para atualizar suas habilidades!';
} else { echo "<script>self.location='?p=home'</script>"; break; }
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
	document.title='['+temp+'] :: narutoHIT - mesmo nome, nova história! ::';
	}
}
<?php if($atual<$db['treino_fim']) echo "window.setInterval('calculafim(\"treino_tempo\",\"mensagem\")',1000);"; ?>
function atualiza(div,divtotal){
  	if((document.getElementById(div).value) < "00:00:01"){
  		self.location="?p=rewardtrain";
  		conc=1;
	}
}
</script>
<div class="box_top">Ocupado</div>
<div class="box_middle">
<div class="aviso" id="mensagem">
	<?php
	if(($atual<$db['treino_fim']&&($db['treino']>0)))
		echo $msg;
	else
		echo $msgconc;
	?>
</div>
<div class="sep"></div>
<div align="center">Um ninja precisa conhecer seus limites. Você pode cancelar seu treinamento a qualquer momento, caso necessite, clicando no botão abaixo. Lembre-se que ao cancelar, você não ganhará nada como recompensa, pois será considerado como um treino falho.<div class="sep"></div><input type="button" class="botao" value="Cancelar Treino" onclick="self.location='?p=school&cancel=true'" /></div>
</div>
<div class="box_bottom"></div>