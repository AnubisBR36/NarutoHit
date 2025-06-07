<?php
/*if(date('N')==2){ echo "<script>window.close()</script>"; break; }*/
if(isset($_POST['pixel'])){
	require_once('_inc/conexao.php');
}
?>
<script type="text/javascript">
var posX, posY;
if (!document.all)
document.captureEvents(Event.MOUSEDOWN);
document.onmousedown = mouseDown;

function pos(){
if (document.all) {
posX = event.clientX;
posY = event.clientY;
}
else {
posX= evt.pageX+"px";
posY = evt.pageY+"px";
}
  //posX = window.event.clientX - document.getElementById('imgpix').offsetLeft;
  //posY = window.event.clientY - document.getElementById('imgpix').offsetTop-1;
  document.getElementById('position').innerHTML = posX + " , " + posY;
}
</script>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>narutoHIT - Evento Pixel Premiado!</title>
<link href="_css/naruto.css" rel="stylesheet" type="text/css" />
</head>

<body>
<div align="center" style="margin-top:20px;"><b>Bem-vindo ao Evento do Pixel Premiado!</b><br />Abaixo temos uma imagem com 90.000 pixels, sendo que um deles contém um prêmio.<br />Você pode tentar quantas vezes quiser, até a meia-noite de hoje.<br />Boa Sorte!<br /><br />
<div>
	<span class="sub2">Posição do Mouse</span>
    <div id="position" style="font-size:14px;font-weight:bold;">0 , 0</div>
</div><br />
<form method="post" action="pixel.php">
	<input type="hidden" id="pixel" name="pixel" value="" />
    <div style="width:400px;background:#222222;padding:3px;">
    	<img id="imgpix" src="_img/pixel/pixel1.jpg" style="position:relative;cursor:pointer;" onMouseMove="pos()" onClick="document.forms[0].pixel.value=pos(); document.forms[0].submit();" />
    </div>
</form>
<br />
<span class="sub2">Hora Atual: <?php echo date('H:i:s'); ?></span>
</div>
</body>
</html>