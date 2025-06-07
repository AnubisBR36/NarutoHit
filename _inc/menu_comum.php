<div style="width:170px;">
<?php if(isset($_SESSION['logado'])) if(date('Y-m-d H:i:s')<$db['vip']){ if((isset($_GET['p']))&&($_GET['p']<>'view')&&($_GET['p']<>'prepare')){
?>
<div class="box2_top">Conta VIP</div>
<div class="box2_middle" style="text-align:left;">
	<span class="sub2">
    <img src="_img/star.png" /><b>Sua conta é VIP!</b><div class="sep"></div>
    <div style="text-align:left;">Início: <?php $ex=explode(' ',$db['vip_inicio']); $data=explode('-',$ex[0]); echo $data[2].'/'.$data[1].'/'.$data[0]; ?> <?php echo $ex[1]; ?><br />Fim: <?php $ex=explode(' ',$db['vip']); $data=explode('-',$ex[0]); echo $data[2].'/'.$data[1].'/'.$data[0]; ?> <?php echo $ex[1]; ?></div>
    </span>
</div>
<div class="box2_bottom"></div>
<?php }} ?>

<div class="box2_top">Parceiros</div>
<div class="box2_middle" style="text-align:center;">
	<a href="parceria.php?id=1" target="_blank"><img src="_img/parceiros/anime_monstrosity.gif" border="0" style="margin-bottom:3px;" /></a>
    <a href="parceria.php?id=2" target="_blank"><img src="_img/parceiros/naruto_fox.gif" border="0" style="margin-bottom:3px;" /></a>
    <a href="parceria.php?id=3" target="_blank"><img src="_img/parceiros/liga_naruto.gif" border="0" style="margin-bottom:3px;" /></a>
    <a href="parceria.php?id=4" target="_blank"><img src="_img/parceiros/anime100.gif" border="0" style="margin-bottom:3px;" /></a>
    <a href="parceria.php?id=5" target="_blank"><img src="_img/parceiros/blitz_mangas.jpg" border="0" style="margin-bottom:3px;" /></a>
    <a href="parceria.php?id=6" target="_blank"><img src="_img/parceiros/familia_anime.gif" border="0" style="margin-bottom:3px;" /></a>
    <a href="parceria.php?id=7" target="_blank"><img src="http://www.narutormvb.net/imgs/nrmvb.gif" border="0" name="NarutoRMVB - Naruto como voce nunca viu!" title="NarutoRMVB - Naruto como voce nunca viu!" style="margin-bottom:3px;" /></a>
    <a href="parceria.php?id=8" target="_blank"><img src="http://www.naruto-ex.com/imagens/parceria/narutoexbutton2.gif" border="0" style="margin-bottom:3px;" /></a>
    <a href="parceria.php?id=9" target="_blank"><img src="http://img222.imageshack.us/img222/931/anigifoh.gif" width="88" height="31" border="0" style="margin-bottom:3px" /></a>
    <a href="parceria.php?id=10" target="_blank"><img src="http://animesplus.com.br/portal/images/publicidade/button.gif" width="88" height="31" border="0" style="margin-bottom:3px;" /></a>
  <div class="sep"></div>
    <div class="sub2">Seja parceiro do narutoHIT! Clique <a href="mailto:contato@narutohit.net">aqui</a> e contacte-nos!</div>
</div>
<div class="box2_bottom"></div>

<div class="box2_top">Twitter</div>
<div class="box2_middle" style="text-align:center">Siga-nos no Twitter!<div class="sep"></div><a href="http://twitter.com/narutohitnet" target="_blank"><img src="_img/twitter.png" border="0" width="140" height="32" /></a></div>
<div class="box2_bottom"></div>

<?php /*<div class="box2_top">Anúncios</div>
<div class="box2_middle">Reservado para anúncios.</div>
<div class="box2_bottom"></div>*/ ?>

<?php //if((isset($_GET['p']))&&($_GET['p']<>'prepare')&&($_GET['p']<>'view')&&($db['config_radio']<>'')) require_once('../radio_'.$db['config_radio'].'.php'); ?>

<?php /*if(!isset($_SESSION['logado'])) require_once('_inc/anuncio_lateral.php'); else {
		if((date('Y-m-d H:i:s')>=$db['vip'])&&(isset($_GET['p']))&&($_GET['p']<>'view')&&($_GET['p']<>'prepare')) require_once('_inc/anuncio_lateral.php');
}*/ ?>

<div class="box2_top">Estatísticas</div>
<div class="box2_middle" style="text-align:center;">
	Usuários online: <span id="usuarios-online">--</span><br />
    <div class="sep"></div>
    Total de usuários: <span id="total-usuarios">--</span><br />
    <div class="sep"></div>
    <!-- Site Meter removido temporariamente -->
<?php /*<!-- AddThis Button BEGIN -->
<!-- Comentado scripts externos WAU que podem causar erros -->
<!-- <a class="addthis_button" href="http://www.addthis.com/bookmark.php?v=250&amp;username=narutohit"><img src="http://s7.addthis.com/static/btn/v2/lg-share-en.gif" width="125" height="16" alt="Bookmark and Share" style="border:0"/></a><script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js#username=narutohit"></script> -->
<!-- <script type="text/javascript" src="http://widgets.amung.us/classic.js"></script><script type="text/javascript">WAU_classic('da6rpwwysyc4')</script> -->
<!-- <script type="text/javascript" src="http://widgets.amung.us/colored.js"></script><script type="text/javascript">WAU_colored('sg1uw9ltr03f', '000000a7a9ac')</script> -->
<!-- <div class="sep"></div><a href="http://www.melhoresdanet.com/index.php?a=in&u=narutohit" target='_blank'>MelhoresDaNet</a> -->*/ ?></div>
<div class="box2_bottom"></div>
</div>