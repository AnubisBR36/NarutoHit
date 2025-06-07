<?php require_once('trava.php'); ?>
<script language = "JavaScript">
function abrir(){
	window.open("pixel.php",",","status=no,resizable=no,scrollbars=no,menubar=no,width=460,height=366,left=150,top=100");
}
</script>
<div class="box_top">Eventos</div>
<div class="box_middle">A cada dia da semana ocorrerá um evento diferente. Abaixo estão todos os eventos programados pela narutoHIT, o dia da semana em que o evento acontecerá, e a descrição de cada um.<div class="sep"></div>
	<div<?php if(date('N')==1) echo ' style="background:#323232;"'; ?>>
	<img src="_img/star.png" align="absmiddle" /><b>Segunda-Feira:</b> Pixel Premiado!
    <div class="sub2" style="margin-left:16px;text-align:left;">Neste evento, o usuário deverá encontrar o pixel que contém um prêmio, em uma imagem. Para quem ainda não sabe, pixel é o menor ponto que forma uma imagem digital, e o conjunto de pixels forma uma imagem. Uma imagem será exibida (de tamanho 400x225 pixels, resultando em 90.000 pixels), sendo que em um destes pixels estará escondido o prêmio do evento (que poderá incluir uma VIP de 1 mês).<?php if(date('N')<>8){ ?><br /><a href="pixel.php" target="_blank">Clique aqui para acessar o evento.</a><?php } ?></div>
    </div>
    <div class="sep"></div>
    <div<?php if(date('N')==2) echo ' style="background:#323232;"'; ?>>
    <img src="_img/star.png" align="absmiddle" /><b>Terça-Feira:</b> Kage Bunshin no Jutsu!
    <div class="sub2" style="margin-left:16px;text-align:left;">Às terças, teremos um evento simples, onde o usuário deverá escolher entre 3 bunshins do Naruto, com a intenção de descobrir o verdadeiro. O prêmio para o vencedor será o triplo do valor apostado.<?php if(date('N')==8) echo '<br /><a href="?p=bunshin" target="_blank">Clique aqui para acessar o evento.</a>'; ?></div>
    </div>
    <div class="sep"></div>
    <div<?php if(date('N')==3) echo ' style="background:#323232;"'; ?>>
    <img src="_img/star.png" align="absmiddle" /><b>Quarta-Feira:</b> Evento a decidir.
    <div class="sub2" style="margin-left:16px;text-align:left;">-</div>
    </div>
    <div class="sep"></div>
    <div<?php if(date('N')==4) echo ' style="background:#323232;"'; ?>>
    <img src="_img/star.png" align="absmiddle" /><b>Quinta-Feira:</b> Evento a decidir
    <div class="sub2" style="margin-left:16px;text-align:left;">-</div>
    </div>
    <div class="sep"></div>
    <div<?php if(date('N')==5) echo ' style="background:#323232;"'; ?>>
    <img src="_img/star.png" align="absmiddle" /><b>Sexta-Feira:</b> Ataque à Vila!
    <div class="sub2" style="margin-left:16px;text-align:left;">Este é o dia dos bijuus! Às sextas, haverão ataques de bijuus em todas as vilas do mundo shinobi. O ninja que der o golpe fatal no bijuu será o vencedor, e receberá a recompensa. Será possível atacar o bijuu quantas vezes quiser, obedecendo a penalidade de 10 minutos de espera após uma batalha (5 minutos para jogadores VIP). Mesmo perdendo a batalha, seus yens não serão alterados, e você ainda ganhará 1 ponto de experiência. Lembrando que o bijuu também atacará jogadores da vila a cada 2 minutos (caso perca a batalha por ser atacado, valerão as regras de uma batalha normal).</div>
    </div>
    <div class="sep"></div>
    <div<?php if(date('N')==6) echo ' style="background:#323232;"'; ?>>
    <img src="_img/star.png" align="absmiddle" /><b>Sábado:</b> Experiência em Dobro!
    <div class="sub2" style="margin-left:16px;text-align:left;">Talvez o dia da semana mais alvejado pelos ninjas. Aos sábados, a experiência ganha em missões e caças por tempo será dobrada! Sua evolução neste dia poderá superar qualquer expectativa!</div>
    </div>
    <div class="sep"></div>
    <div<?php if(date('N')==7) echo ' style="background:#323232;"'; ?>>
    <img src="_img/star.png" align="absmiddle" /><b>Domingo:</b> Yens ao Extremo!
    <div class="sub2" style="margin-left:16px;text-align:left;">Domingo é o dia de ganhar yens! Todas as missões e caças por tempo lhe darão um adicional de 30% em yens (referente ao total de yens ganhos na missão/caça).</div>
    </div>
</div>
<div class="box_bottom"></div>