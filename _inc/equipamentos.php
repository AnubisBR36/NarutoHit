<script>
var taijutsu=0;
var ninjutsu=0;
var genjutsu=0;
</script>
<?php /*<div class="box_top">Meus Equipamentos</div>
<div class="box_middle">Itens atualmente equipados.<div class="sep"></div>
	<div style="background:url(_img/equipamentos/body.jpg) center no-repeat;height:300px;">
    <?php do{ ?>
    	<?php
		switch($dbe['categoria']){
			case 'acessorio': $left=52; $top=12; break;
			case 'arma': $left=52; $top=133; break;
			case 'sandalia': $left=271; $top=198; break;
			case 'vestimenta': $left=271; $top=18; break;
		}
		$texto='<b>'.$dbe['nome'].'</b><br />'.$dbe['descricao'].'<br /><b>';
		if($dbe['taijutsu']>0) $texto.='<img src=_img/equipamentos/up.png width=14 height=14 align=absmiddle /> [+'.$dbe['taijutsu'].'] em Taijutsu<br />';
		if($dbe['ninjutsu']>0) $texto.='<img src=_img/equipamentos/up.png width=14 height=14 align=absmiddle /> [+'.$dbe['ninjutsu'].'] em Ninjutsu<br />';
		if($dbe['genjutsu']>0) $texto.='<img src=_img/equipamentos/up.png width=14 height=14 align=absmiddle /> [+'.$dbe['genjutsu'].'] em Genjutsu<br />';
        $texto.='</b>';
		?>
    	<img src="_img/equipamentos/<?php echo $dbe['imagem']; ?>.jpg" style="position:relative;left:<?php echo $left; ?>px;top:<?php echo $top; ?>px;" width="100" onmouseover="Tip('<div class=tooltip><?php echo $texto; ?></div>')" onmouseout="UnTip()" />
        <script>
	  	if(<?php echo $dbe['taijutsu']; ?>>0) document.getElementById('atrtai').innerHTML=((document.getElementById('atrtai').innerHTML)*1)+<?php echo $dbe['taijutsu']; ?>;
	  	if(<?php echo $dbe['ninjutsu']; ?>>0) document.getElementById('atrnin').innerHTML=((document.getElementById('atrnin').innerHTML)*1)+<?php echo $dbe['ninjutsu']; ?>;
	  	if(<?php echo $dbe['genjutsu']; ?>>0) document.getElementById('atrgen').innerHTML=((document.getElementById('atrgen').innerHTML)*1)+<?php echo $dbe['genjutsu']; ?>;
	  	</script>
    <?php } while($dbe=mysql_fetch_assoc($sqle)); ?>
    </div>
    <div class="sep"></div>
    <div align="center"><input type="button" class="botao" value="Meus Equipamentos" onclick="location.href='?p=inventory'" /></div>
</div>
<div class="box_bottom"></div>
<?php
@mysql_free_result($sqle);
?>*/ ?>
<div class="box_top">Meus Equipamentos</div>
<div class="box_middle">Itens atualmente equipados.<div class="sep"></div>
    <table width="100%" cellpadding="0" cellspacing="1">
        <?php if(mysql_num_rows($sqle)==0) echo '<tr><td colspan="3"><div class="aviso">Nenhum item encontrado.</div></td></tr>'; else do{ if(date('Y-m-d H:i:s')<$db['vip']) $dbe['valor']=$dbe['valor']-($dbe['valor']*0.15); ?>
        <tr style="background:#323232;">
            <td align="center" width="140"><img src="_img/equipamentos/<?php echo $dbe['imagem']; ?>.jpg" /></td>
            <td valign="top" style="padding:5px;">
                <b><?php echo $dbe['nome']; ?><?php if($dbe['upgrade']>0) echo ' +'.$dbe['upgrade']; ?></b><br />
                <span class="sub2"><?php echo $dbe['descricao']; ?></span><br />
                <b><?php if($dbe['taijutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbe['taijutsu']+$dbe['upgrade']).'] em Taijutsu<br />'; ?>
                <?php if($dbe['ninjutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbe['ninjutsu']+$dbe['upgrade']).'] em Ninjutsu<br />'; ?>
                <?php if($dbe['genjutsu']>0) echo '<img src="_img/equipamentos/up.png" width="14" height="14" align="absmiddle" /> [+'.($dbe['genjutsu']+$dbe['upgrade']).'] em Genjutsu<br />'; ?></b>
          </td>
            <td align="center" width="20%">
            	<form method="post" action="?p=inventory" onsubmit="subm.value='Carregando...';subm.disabled=true;">
        		<input type="hidden" id="inv_id" name="inv_id" value="<?php echo $c->encode($dbe['id'],$chaveuniversal); ?>" />
            	<input type="hidden" id="inv_cat" name="inv_cat" value="<?php echo $c->encode($dbe['categoria'],$chaveuniversal); ?>" />
            	<input type="hidden" id="inv_act" name="inv_act" value="<?php echo $c->encode('off',$chaveuniversal); ?>" />
        		<input type="submit" id="subm" name="subm" class="botao" value="Retirar" />
        		</form>
            </td>
      </tr>
      <script>
	  	if(<?php echo $dbe['taijutsu']; ?>>0) document.getElementById('atrtai').innerHTML=((document.getElementById('atrtai').innerHTML)*1)+<?php echo $dbe['taijutsu']; ?>;
	  	if(<?php echo $dbe['ninjutsu']; ?>>0) document.getElementById('atrnin').innerHTML=((document.getElementById('atrnin').innerHTML)*1)+<?php echo $dbe['ninjutsu']; ?>;
	  	if(<?php echo $dbe['genjutsu']; ?>>0) document.getElementById('atrgen').innerHTML=((document.getElementById('atrgen').innerHTML)*1)+<?php echo $dbe['genjutsu']; ?>;
	  	</script>
        <?php } while($dbe=mysql_fetch_assoc($sqle)); ?>
    </table>
</div>
<div class="box_bottom"></div>