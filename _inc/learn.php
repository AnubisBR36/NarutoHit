<?php
require_once('verificar_sala.php');
if(!isset($_GET['id'])){ echo "<script>self.location='?p=school'</script>"; return; }
$atual=date('Y-m-d H:i:s');
if($atual<$dbr['fim']){
	$fim=$dbr['fim'];
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
	var element = document.getElementById(div);
	if(!element) return;
	var timeValue = element.value || element.innerHTML;
	if(timeValue && timeValue <= "00:00:01"){
		conc=1;
		self.location="?p=school";
	}
}
</script>
<?php
$sqltext='';
$sqladd='';
$sqlv=mysql_query("SELECT jutsu FROM jutsus WHERE usuarioid=".$db['id']);
while($row=mysql_fetch_array($sqlv)){
	$sqltext.=" AND id<>".$row['jutsu']." ";
}

if($db['natureza1']<>'') $sqltext.=" OR nivel<=".$db['nivel']." AND natureza='".$db['natureza1']."' ".$sqladd;
if($db['natureza2']<>'') $sqltext.=" OR nivel<=".$db['nivel']." AND natureza='".$db['natureza2']."' ".$sqladd;
if($db['natureza3']<>'') $sqltext.=" OR nivel<=".$db['nivel']." AND natureza='".$db['natureza3']."' ".$sqladd;

$sqlj=mysql_query("SELECT * FROM table_jutsus WHERE nivel<=".$db['nivel']."+2 AND natureza='nenhum' ".$sqltext." ORDER BY natureza DESC, nivel ASC");
$dbj=mysql_fetch_assoc($sqlj);
?>
<div class="box_top">Aprender Jutsu</div>
<div class="box_middle">Estes são os jutsus que posso lhe ensinar no momento, por isso não mostrarei os outros. Escolha um deles, e vamos ao treinamento. Caso deseje voltar, clique <a href="?p=room&amp;id=<?php echo $_GET['id']; ?>">aqui</a>.<div class="sep"></div>
	<div class="aviso" id="mensagem">
    <?php
	if(isset($_GET['msg'])){
		switch($_GET['msg']){
			case 1: $errmsg='Você não está pronto para controlar a natureza do seu chakra.<br />Volte quando estiver no nível 15.';
			case 2: $errmsg='Yens insuficientes para aprender este jutsu.'; break;
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
    </b></div><div class="sep"></div>
    <table width="100%" cellpadding="0" cellspacing="1">
        <?php if(mysql_num_rows($sqlj)==0) echo '<tr><td colspan="3"><div class="aviso">Nenhum jutsu disponível.</div><div class="sep"></div></td></tr>'; else do{ ?>
        <?php if(($dbj['doujutsu']>0)&&($db['doujutsu']<>$dbj['doujutsu'])){ } else { ?>
        <?php if($db['doujutsu_nivel']>=$dbj['doujutsu_nivel']){ ?>
        <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
        	<td width="180"><img src="_img/jutsus/<?php echo $dbj['id']; ?>.jpg" onmouseover="Tip('<div class=tooltip><?php echo $dbj['nome']; ?></div>')" onmouseout="UnTip()" /></td>
            <td><b>Natureza</b><br /><?php if($dbj['natureza']<>'nenhum'){
				switch($dbj['natureza']){
					case 'agua': $nat='Água (Suiton)'; break;
					case 'fogo': $nat='Fogo (Katon)'; break;
					case 'raio': $nat='Raio (Raiton)'; break;
					case 'terra': $nat='Terra (Doton)'; break;
					case 'vento': $nat='Vento (Fuuton)'; break;
				}
			echo '<span class="sub2"><img src="_img/jutsus/'.$dbj['natureza'].'.png" height="14" /> '.$nat.'</span>'; } else echo '<span class="sub2">Nenhuma</span>'; ?><br /><br /><b>Força do Jutsu</b><br /><span class="sub2"><?php echo $dbj['forca']; ?> pontos</span></td>
            <td><b>Requerimentos</b><br /><span class="sub2">Nível <?php echo $dbj['nivel']; ?><br /><?php echo number_format($dbj['valor'],2,',','.'); ?> yens</span><br /><br /><?php if($db['nivel']>=$dbj['nivel']){ ?><input type="button" class="botao" value="Aprender" onclick="location.href='?p=pratice&id=<?php echo $_GET['id']; ?>&jutsu=<?php echo $c->encode($dbj['id'],$chaveuniversal); ?>'" /><?php } ?></td>
        </tr>
        <tr>
        	<td colspan="3"><div class="sep"></div></td>
        </tr>
        <?php }} ?>
        <?php } while($dbj=mysql_fetch_assoc($sqlj)); ?>
    </table>
    <div align="center"><input type="button" class="botao" value="Sair da Sala" onclick="location.href='?p=room&leave=true'" /></div>
</div>
<div class="box_bottom"></div>
<?php
@mysql_free_result($sqln);
@mysql_free_result($sqlj);
@mysql_free_result($sqlv);
?>