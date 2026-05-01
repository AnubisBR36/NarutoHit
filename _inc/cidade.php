<div id="city" style="display:none;">
<div class="box_top">Mapa da Vila [<a href="#" onClick="document.getElementById('city').style.display='none'">Fechar</a>]</div>
<div class="box_middle">
	<script>
	function nome(nom){
		document.getElementById('divnome').innerHTML=nom;
	}
	function nomeout(){
		document.getElementById('divnome').innerHTML='<b>Passe o mouse sobre um ponto no mapa para visualizar sua descrição.</b>';
	}
	</script>
	<div class="modalExemplo" style="background:#282828;padding-top:0px;padding-bottom:0px;margin-top:0px;margin-bottom:0px;">
		<div style="background:url(_img/city/city.jpg) no-repeat center;width:505px;height:315px;">
			<a href="?p=ramen"><img src="_img/city/point.png" border="0" style="position:relative; left:383px; top:243px;" onmouseover="nome('<b>Ichiraku Bar</b>: Entre e experimente o melhor ramen da vila!')" onmouseout="nomeout()" /></a>
			<a href="?p=missions"><img src="_img/city/point.png" border="0" style="position:relative; left:171px; top:150px;" onmouseover="nome('<b>Sala do Kage</b>: Realize missões para o kage da vila, e ganhe recompensas!')" onmouseout="nomeout()" /></a>    
			<a href="?p=school"><img src="_img/city/point.png" border="0" style="position:relative; left:123px; top:173px;" onmouseover="nome('<b>Escola Ninja</b>: Aprenda e aperfeiçoe jutsus com nossos ótimos senseis!')" onmouseout="nomeout()" /></a>
			<a href="?p=hunt"><img src="_img/city/point.png" border="0" style="position:relative; left:287px; top:59px;" onmouseover="nome('<b>Caças</b>: Procure e enfrente ninjas de todo o mundo shinobi!')" onmouseout="nomeout()" /></a>
			<a href="?p=<?php if($db['orgid']>0) echo 'my'; ?>org"><img src="_img/city/point.png" border="0" style="position:relative; left:287px; top:167px;" onmouseover="nome('<?php if($db['orgid']>0) echo '<b>Meu Clã</b>: Visite seu clã ninja atual!'; else echo '<b>Clãs</b>: Visualize os clãs existentes na vila!'; ?>')" onmouseout="nomeout()" /></a>
			<a href="?p=shop"><img src="_img/city/point.png" border="0" style="position:relative; left:87px; top:233px;" onmouseover="nome('<b>Comércio</b>: Temos uma grande variedade de equipamentos para sua longa jornada!')" onmouseout="nomeout()" /></a>
        </div>
      <div class="sep"></div>
	  <div id="divnome" class="aviso"><b>Passe o mouse sobre um ponto no mapa para visualizar sua descrição.</b></div>
	</div>
</div>
<div class="box_bottom"></div>
</div>