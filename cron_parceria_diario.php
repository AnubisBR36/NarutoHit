<?php
require_once('conexao.php');
$sql=mysql_query("SELECT email, site, cliques FROM parceiros WHERE envio='dia' AND email<>''");
$db=mysql_fetch_assoc($sql);
do{
	$mensagem='<div align="center"><img src="http://www.narutohit.net/_img/support/minilogo2.jpg" style="border-bottom:1px solid #DDDDDD" /><br /><br /><b>Relatório de Parceria</b><br />Como solicitado, abaixo estão os dados de acesso:<br /><br /><b>O site '.$db['site'].' obteve '.$db['cliques'].' acessos,<br />partindo de nosso site (narutoHIT), através<br />da parceria firmada entre ambas as partes.</b><br /><span style="font-size:10px;">Não responder este email</span><br /><br /><b><span style="color:#CC0000">A equipe narutoHIT lhe deseja um bom jogo!</span></b><br /><br />Para mais informações, visite nossa <a href="http://www.orkut.com.br/Main#Community?cmm=95565573">comunidade no orkut</a>.<br /><br />Atenciosamente, equipe narutoHIT.</div>';
	$assunto='Resumo de Parceria';
	$remetente='narutoHIT <contato@narutohit.net>';
	$headers = implode ( "\n",array ( "From: $remetente","Subject: ".$assunto,"Return-Path: $remetente","MIME-Version: 1.0","X-Priority: 3","Content-Type: text/html" ) );
	mail($db['email'],'',$mensagem,$headers);
} while($db=mysql_fetch_assoc($sql));
?>