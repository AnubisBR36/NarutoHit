<?php
        $mensagem='<div align="center"><img src="http://www.anubisserve.net/_img/minilogo.jpg" style="border-bottom:1px solid #DDDDDD" /><br /><br /><b>Mensagem Importante</b><br />A data para início dos testes já foi publicada:<br /><br /><b>Segunda-Feira, 28 de Dezembro de 2009, às 14h</b><br /><span style="font-size:10px;">(horário oficial de Brasília)</span><br /><br />No momento do registro, não se esqueça de utilizar sua<br />chave de registro. Caso a tenha perdido, solicite uma nova,<br />enviando um email para contato@anubisserve.net.<br /><br /><b><span style="color:#CC0000">A equipe '.nome_servidor().' lhe deseja um Feliz Natal, e um próspero Ano Novo!</span></b><br /><br />Atenciosamente, equipe '.nome_servidor().'.</div>';
        $assunto='Data dos Testes';
        $remetente='testaccount@anubisserve.net';
        $headers = implode ( "\n",array ( "From: testaccount@anubisserve.net","To: leander_90@hotmail.com","Subject: testmail","X-Sender: testaccount@anubisserve.net" ) );
        mail('leander_90@hotmail.com','hfuhfus','fkdj djfdsjl',$headers);
?>