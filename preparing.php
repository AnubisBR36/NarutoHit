<?php
require_once('_inc/conexao.php');
$atual=date('Y-m-d H:i:s');
$inicio='2010-04-06 08:00:00';
if($atual>=$inicio){ echo "<script>self.location='index.php?p=login'</script>"; break; }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>:: narutoHIT - mesmo nome, nova história! ::</title>
<link href="_css/preparing.css" type="text/css" rel="stylesheet" />
</head>

<body>
	<div align="center" style="background:url(_img/preparing.jpg) no-repeat top;height:220px;" border="0" /> </div>
    <div align="center" style="margin:30px;">
    	<div id="mensagem">narutoHIT está passando por uma <b>manutenção</b>.<br />Retorno: <b>8 da manhã do dia 06/04</b><br /><br /><b>CONSIDERAÇÕES</b><br />Nem preciso dizer o que aconteceu, acho que todos já sabem.<br />O backup será restaurado, e iniciaremos o pós-update novamente.<br />Coisas assim acontecem quando recebo várias mensagens de jogadores<br />dizendo que vão parar de jogar porque não fazemos coisas novas no jogo.<br />E o que fazemos? Updates às pressas, sem o mínimo de testes<br />para publicarmos algo que funciona!<br />Para o próximo, isso não ocorrerá, alguns jogadores irão fazer<br />incansáveis testes, com o objetivo de evitar novos "desastres".<br />Também cabe a vocês, jogadores, não usarem as falhas do jogo.<br />Se fossem poucos os que tivessem feito isso, não precisaríamos chegar a ponto de um backup.<br /><br />Atenciosamente, equipe narutoHIT.</div>
    </div>
    <div align="center" style="margin-top:20px;font-size:11px;color:#222222;">
    	<!--<script type="text/javascript" src="http://widgets.amung.us/small.js"></script><script type="text/javascript">WAU_small('s41zbpa4u2ms')</script><br />-->
    	Copyright 2009 &copy; Direitos do <b>Jogo e Sistema</b> Reservados &agrave; <b>narutoHIT.net</b><br />
      	Copyright 2002 &copy; Direitos do <b>Anime e Imagens</b> Reservados à <b>Masashi Kishimoto</b>
    </div>
</body>
</html>