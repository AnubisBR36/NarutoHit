<div class="box_top">Bem-vindo ao narutoHIT!</div>
<div class="box_middle" style="text-align:center;">
	<img src="_img/updates/noupdate.jpg" />
    <div class="sep"></div>
    <?php
	$dica=rand(1,5);
	switch($dica){
		case 1: $dica='Administradores e Gamemasters <b>JAMAIS</b> requisitarão a senha de sua conta. Denuncie qualquer tentativa de roubo de sua conta/senha, enviando uma mensagem para nossa equipe pela página de <a href="?p=contact">contato</a>. Faça o upload de uma imagem que prove a denúncia, de modo que identifiquemos o meliante.'; break;
		case 2: $dica='Jogadores VIP obtém certos benefícios em relação aos demais, como por exemplo missões especiais. Confira todas as vantagens de ser jogador VIP clicando <a href="?p=vip">aqui</a>.'; break;
		case 3: $dica='Não envie mensagens contendo palavrões, imagens obscenas ou qualquer tipo de ofensa a outros jogadores. A <b>narutoHIT</b> ainda não possui um sistema de detecção, portanto cabe exclusivamente ao jogador nos informar do ocorrido, caso aconteça.'; break;
		case 4: $dica='Todas as contas que ficarem inativas por 30 dias serão automaticamente deletadas.'; break;
		case 5: $dica='Utilize nosso <a href="?p=faq">FAQ</a> para tirar possíveis dúvidas que você possa ter. As dúvidas mais freqüentes de nossos jogadores estarão respondidas nesta página. Caso sua dúvida não esteja lá, favor envie-nos um email pela página de <a href="?p=contact">contato</a>.'; break;
		case 6: $dica='A <b>narutoHIT</b> recomenda o uso do navegador Firefox, para que o usuário tenha uma melhor experiência do jogo.'; break;
		case 7: $dica='Não recomendamos o uso da versão 6.0 do Internet Explorer. O jogo possui algumas imagens transparentes em PNG, que não são processadas corretamente neste navegador. Utilize uma versão superior do IE, ou então migre para o Firefox.'; break;
	}
	echo '<div class="sub2"><b>DICA: </b>'.$dica.'</div>';
	?>
</div>
<div class="box_bottom"></div>