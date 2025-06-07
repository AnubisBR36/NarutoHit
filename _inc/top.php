<?php if(!isset($_SESSION['logado'])){ ?>
<table width="700" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="700" align="center"><a href="?p=login">Login</a> &times; <a href="?p=terms">Registrar</a> &times; <a href="?p=recover">Nova Senha</a> &times; <a href="http://pt.wikipedia.org/wiki/Narutohit" target="_blank">Manual</a> &times; <a href="?p=contact">Contato</a> &times; <a href="http://www.orkut.com.br/Main#Community?cmm=95565573" target="_blank">Orkut</a> &times; <a href="?p=faq">FAQ</a></td>
  </tr>
</table>
<?php } else { ?>
<?php require_once('trava.php'); ?>
<table width="700" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td height="20" align="center"><a href="?p=home">Home</a> &times; <a href="?p=messages">Mensagens</a> &times; <a href="?p=reports">Relatórios</a> &times; <a href="?p=inventory">Inventário</a> <?php /*&times; <a href="?p=myshop">Minha Loja</a> */ ?>&times; <a href="?p=jutsus">Jutsus</a> &times; <?php /*<script type="text/javascript">$('a#linkmapa').modal();</script><a id="linkmapa" href="city.php?id=<?php echo $db['orgid']; ?>" class="modal" rel="modal">Cidade</a>*/ ?> <a href="javascript:void(0);" onclick="document.getElementById('city').style.display='block'">Cidade</a> &times; <a href="?p=mapa">Mapa</a> &times; <a href="?p=myshop">Minha Loja</a> &times; <a href="?p=chat">Chat</a> <?php if($db['config_radio']<>''){ ?> &times; <a href="?p=radio">Rádio</a><?php } ?></td>
  </tr>
  <tr>
    <td height="20" align="center"><a href="?p=news">News</a> &times; <a href="?p=book">Bingo Book</a> &times; <a href="?p=config">Configurações</a> &times; <?php /*<a href="?p=events">Eventos</a> &times;*/ ?> <a href="?p=rank&amp;filter=<?php if($db['renegado']=='sim') echo 7; else echo $db['vila']; ?>">Ranking</a> &times; <a href="?p=support">Suporte</a> &times; <a href="?p=vip">VIP</a> &times; <a href="http://www.orkut.com.br/Main#Community?cmm=95565573" target="_blank">Orkut</a> &times; <a href="?p=faq">FAQ</a> &times; <a href="http://pt.wikipedia.org/wiki/Narutohit" target="_blank">Manual</a> &times; <a href="?p=logout">Logout</a></td>
  </tr>
</table>
<?php } ?>