<script>
function nome(nom){
	document.getElementById('divnome').innerHTML=nom;
}
function nomeout(){
	document.getElementById('divnome').innerHTML='<b>Passe o mouse sobre um ponto no mapa para visualizar sua descrição.</b>';
}
</script>
<div class="modalExemplo">
	<div style="background:url(_img/city/city.jpg) no-repeat center;width:620px;height:400px;">
    	<a href="?p=ramen"><img src="_img/city/point.png" border="0" style="position:absolute; left:506px; top:336px;" onmouseover="nome('<b>Ichiraku Bar</b>: Entre e experimente o melhor ramen da vila!')" onmouseout="nomeout()" /></a>
        <a href="?p=missions"><img src="_img/city/point.png" border="0" style="position:absolute; left:239px; top:204px;" onmouseover="nome('<b>Sala do Kage</b>: Realize missões para o kage da vila, e ganhe recompensas!')" onmouseout="nomeout()" /></a>    
        <a href="?p=school"><img src="_img/city/point.png" border="0" style="position:absolute; left:173px; top:213px;" onmouseover="nome('<b>Escola Ninja</b>: Aprenda e aperfeiçoe jutsus com nossos ótimos senseis!')" onmouseout="nomeout()" /></a>
        <a href="?p=hunt"><img src="_img/city/point.png" border="0" style="position:absolute; left:417px; top:109px;" onmouseover="nome('<b>Caças</b>: Procure e enfrente ninjas de todo o mundo shinobi!')" onmouseout="nomeout()" /></a>
        <?php /*<a href="?p=<?php if($_GET['id']>0) echo 'my'; ?>org"><img src="_img/city/point.png" border="0" style="position:absolute; left:407px; top:167px;" onmouseover="nome('<?php if($_GET['id']>0) echo '<b>Meu Clã</b>: Visite seu clã ninja atual!'; else echo '<b>Clãs</b>: Visualize os clãs existentes na vila!'; ?>')" onmouseout="nomeout()" /></a>*/ ?>
        <a href="?p=shop"><img src="_img/city/point.png" border="0" style="position:absolute; left:152px; top:370px;" onmouseover="nome('<b>Comércio</b>: Temos uma grande variedade de equipamentos para sua longa jornada!')" onmouseout="nomeout()" /></a>
        <a href="?p=blacksmith"><img src="_img/city/point.png" border="0" style="position:absolute; left:172px; top:350px;" onmouseover="nome('<b>Ferreiro</b>: Faça um upgrade em seus equipamentos!')" onmouseout="nomeout()" /></a>    </div>
  <div id="divnome" class="city_div"><b>Passe o mouse sobre um ponto no mapa para visualizar sua descrição.</b></div>
	<div align="center" style="margin-top:7px;"><a href="#" rel="modalclose" style="color:#666666;"><img src="_img/close.jpg" border="0" align="absmiddle" width="12" height="12" /> Fechar</a></div>
</div>