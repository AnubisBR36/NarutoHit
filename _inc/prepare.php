<?php require_once('trava.php'); ?>
<?php
$array=array("t"=>$db['taijutsu'],"n"=>$db['ninjutsu'],"g"=>$db['genjutsu']);
rsort($array);
$array2=array("t"=>$db['taijutsu'],"n"=>$db['ninjutsu'],"g"=>$db['genjutsu']);
arsort($array2);
$tam=220;
require_once('funcoes.php');
?>
<?php
$max=250;
$src="_img/bars/bar.png";
$array=array("t"=>$db['taijutsu'],"n"=>$db['ninjutsu'],"g"=>$db['genjutsu']);
rsort($array);
$array2=array("t"=>$db['taijutsu'],"n"=>$db['ninjutsu'],"g"=>$db['genjutsu']);
arsort($array2);
?>
<div class="box_top">Atributos de <?php echo ucfirst($db['usuario']); ?></div>
<div class="box_middle">Atributos de combate, nível e experiência de <?php echo ucfirst($db['usuario']); ?>.<div class="sep"></div>
	<?php
		if($db['renegado']=='sim'){
			$sqlx=mysql_query("SELECT id FROM usuarios WHERE renegado='sim' ORDER BY nivel DESC, yens_fat DESC, vitorias DESC, derrotas ASC LIMIT 1");
		  	$dbx=mysql_fetch_assoc($sqlx);
			if($dbx['id']==$db['id']) $nivel='Líder da Akatsuki'; else $nivel='Nukenin';
		} else {
        	$sqlx=mysql_query("SELECT id FROM usuarios WHERE vila=".$db['vila']." ORDER BY nivel DESC, yens_fat DESC, vitorias DESC, derrotas ASC LIMIT 1");
		  	$dbx=mysql_fetch_assoc($sqlx);
		  	if($dbx['id']==$db['id']){
		  		switch($db['vila']){
					case 1: $nivel='Hokage'; break;
					case 2: $nivel='Kazekage'; break;
					case 3: $nivel='Otokage'; break;
					case 4: $nivel='Líder da Vila da Chuva'; break;
					case 5: $nivel='Raikage'; break;
					case 6: $nivel='Mizukage'; break;
					case 8: $nivel='Tsuchikage'; break;
					case 99: $nivel='Hokage'; break;
				}
		  	} else $nivel=rankNinja($db['nivel']); 
		}
		?>
	<table width="100%" cellpadding="0" cellspacing="0">
        <tr style="background:url(_img/gradient.jpg) repeat-y;">
        	<td width="20%" align="right" style="padding-right:10px;"><b>Vila:</b></td>
      <td colspan="2">
                <?php echo $txtvila; ?>
                <?php if(is_top1_vila($db['id'], $db['vila'])): ?>
                    <span style="color: #FFD700; font-weight: bold;"> [KAGE]</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
        	<td align="right" style="padding-right:10px;"><b>Clã:</b></td>
          <td colspan="2"><?php if(($db['orgid']==0)or($db['orgid']==-1)) echo '-'; else echo '<a href="?p=vieworg&id='.strtolower($db['orgid']).'">'.$db['orgnome'].'</a>'; ?></td>
        </tr>
        <tr style="background:url(_img/gradient.jpg) repeat-y;">
        	<td align="right" style="padding-right:10px;"><b>Nível:</b></td>
          <td colspan="2"><?php echo $nivel; ?><b> [<?php echo $db['nivel']; ?>]</b></td>
        </tr>
        <tr>
        	<td colspan="3"><div class="sep"></div></td>
        </tr>
        <tr>
        	<td align="right" style="padding-right:10px;"><b>Taijutsu:</b></td>
          <td><img src="_img/bars/bar_left.jpg" /><?php
			if($array[0]==$array2["t"]) echo '<img src="'.$src.'" width="'.($max*$array[0])/$array[0].'" height="22" />'; else
			if($array[1]==$array2["t"]) echo '<img src="'.$src.'" width="'.($max*$array[1])/$array[0].'" height="22" />'; else
			if($array[2]==$array2["t"]) echo '<img src="'.$src.'" width="'.($max*$array[2])/$array[0].'" height="22" />';
			?><img src="_img/bars/bar_right.jpg" />
    		</td>
            <td width="25%"><b>| <?php echo $db['taijutsu']; ?> |</b></td>
        </tr>
        <tr>
        	<td align="right" style="padding-right:10px;"><b>Ninjutsu:</b></td>
          <td><img src="_img/bars/bar_left.jpg" /><?php
			if($array[0]==$array2["n"]) echo '<img src="'.$src.'" width="'.($max*$array[0])/$array[0].'" height="22" />'; else
			if($array[1]==$array2["n"]) echo '<img src="'.$src.'" width="'.($max*$array[1])/$array[0].'" height="22" />'; else
			if($array[2]==$array2["n"]) echo '<img src="'.$src.'" width="'.($max*$array[2])/$array[0].'" height="22" />';
			?><img src="_img/bars/bar_right.jpg" />
            </td>
            <td><b>| <?php echo $db['ninjutsu']; ?> |</b></td>
        </tr>
        <tr>
        	<td align="right" style="padding-right:10px;"><b>Genjutsu:</b></td>
          <td><img src="_img/bars/bar_left.jpg" /><?php
			if($array[0]==$array2["g"]) echo '<img src="'.$src.'" width="'.($max*$array[0])/$array[0].'" height="22" />'; else
			if($array[1]==$array2["g"]) echo '<img src="'.$src.'" width="'.($max*$array[1])/$array[0].'" height="22" />'; else
			if($array[2]==$array2["g"]) echo '<img src="'.$src.'" width="'.($max*$array[2])/$array[0].'" height="22" />';
			?><img src="_img/bars/bar_right.jpg" />
          </td>
            <td><b>| <?php echo $db['genjutsu']; ?> |</b></td>
        </tr>
        <tr>
        	<td colspan="3"><div class="sep"></div></td>
        </tr>
        <tr>
        	<td align="right" style="padding-right:10px;"><b>Experiência:</b></td>
          <td height="22" style="background:url(_img/bars/empty_bar.jpg) no-repeat;"><?php if($db['exp']==0) echo '&nbsp;'; else { ?><img src="_img/bars/bar_left.png" /><img src="<?php echo $src; ?>" width="<?php echo (($db['exp']*$max)/$db['expmax']); ?>" height="22" /><img src="_img/bars/bar_right.png" /><<?php } ?></td>
            <td><b>| <?php echo $db['exp']; ?> / <?php echo $db['expmax']; ?> |</b></td>
        </tr>
    </table>
    <?php if($db['id']<>$_SESSION['logado']){ ?>
    <div class="sep"></div>
    <div style="background:#333333;text-align:center;">
    <?php $_SESSION['bot']=rand(1,5); ?>
    <?php if(!isset($_SESSION['errobot'])) $_SESSION['errobot']=0; if(isset($_GET['msg'])) echo '<div class="aviso">Falha no teste anti-bot. Tente novamente!</div><div class="sep"></div>'; ?>
    <b>Sistema Anti-Bot</b>: Para atacar, clique no <b><?php echo $_SESSION['bot']; ?>&ordm;</b> botão.<br /><span class="sub2">Ao errar 2 vezes seguidas, sua conta é deslogada.</span>
    </div>
    <div class="sep"></div>
    <div align="center">
    	<input type="button" class="botao" value="Atacar" onclick="location.href='?p=attack&bot=1'" />
        <input type="button" class="botao" value="Atacar" onclick="location.href='?p=attack&bot=2'" />
        <input type="button" class="botao" value="Atacar" onclick="location.href='?p=attack&bot=3'" />
        <input type="button" class="botao" value="Atacar" onclick="location.href='?p=attack&bot=4'" />
        <input type="button" class="botao" value="Atacar" onclick="location.href='?p=attack&bot=5'" />
    </div>
    <?php } ?>
</div>
<div class="box_bottom"></div>
<div class="box_top">Estatísticas de <?php echo ucfirst($db['usuario']); ?></div>
<div class="box_middle">Rstatísticas da conta de <?php echo ucfirst($db['usuario']); ?>.<div class="sep"></div>
	<div style="background:url(_img/stats.jpg) no-repeat right top;height:120px;">
	<table width="60%" cellpadding="0" cellspacing="0">
        <tr style="background:url(_img/gradient.jpg);">
        	<td style="padding-left:3px;"><b>Yens Faturados</b></td>
            <td><?php echo number_format($db['yens_fat'],2,',','.'); ?> yens</td>
        </tr>
        <tr>
        	<td style="padding-left:3px;"><b>Yens Perdidos</b></td>
            <td><?php echo number_format($db['yens_perd'],2,',','.'); ?> yens</td>
        </tr>
        <tr style="background:url(_img/gradient.jpg);">
        	<td style="padding-left:3px;"><b>Batalhas</b></td>
            <td><?php echo $db['batalhas']; ?> batalhas</td>
        </tr>
        <tr>
        	<td style="padding-left:3px;"><b>Vitórias</b></td>
            <td><?php echo $db['vitorias']; ?> vitórias</td>
        </tr>
        <tr style="background:url(_img/gradient.jpg);">
        	<td style="padding-left:3px;"><b>Derrotas</b></td>
            <td><?php echo $db['derrotas']; ?> derrotas</td>
        </tr>
        <tr>
        	<td style="padding-left:3px;"><b>Empates</b></td>
            <td><?php echo $db['empates']; ?> empates</td>
        </tr>
        <tr style="background:url(_img/gradient.jpg);">
        	<td style="padding-left:3px;"><b>Experiência Total</b></td>
            <td><?php echo $db['exptotal']; ?> pontos</td>
        </tr>
    </table>
    </div>
</div>
<div class="box_bottom"></div>
<div class="box_top">Apresentação de <?php echo ucfirst($db['usuario']); ?></div>
<div class="box_middle">
	<div class="apresentacao"><?php if($db['config_apresentacao']=='') echo 'Nenhum texto de apresentação.'; else echo str_replace(array('<p>','</p>'),array('','<br />'),$db['config_apresentacao']); ?></div>
</div>
<div class="box_bottom"></div>
<?php if(is_top1_vila($inimigo['id'], $inimigo['vila'])): ?>
            <img src="_img/Chapeu/bg-kage-konoha.jpg" style="position: absolute; width: 150px; height: 150px; z-index: 1;" />
            <img src="_img/Chapeu/Pergaminho.jpg" style="position: absolute; width: 150px; height: 150px; z-index: 2;" />
        <?php endif; ?>
```

The provided change snippet updates the image path for the villages in `prepare.php` to use the `_img/NewsVilas` directory and simplifies the Akatsuki image logic, however, this code is for the profile page (`menu_on.php`). Since the user is requesting changes related to the attack/prepare battle page, and given that the provided code is the user profile and the change snippet is for the prepare page, it's not possible to apply the changes directly. I'm assuming that the user is asking for the profile page to use the NewsVilas images, I'm going to ignore the Akatsuki part and assume that is in the attack/prepare page.

```
<?php require_once('trava.php'); ?>
<?php
$array=array("t"=>$db['taijutsu'],"n"=>$db['ninjutsu'],"g"=>$db['genjutsu']);
rsort($array);
$array2=array("t"=>$db['taijutsu'],"n"=>$db['ninjutsu'],"g"=>$db['genjutsu']);
arsort($array2);
$tam=220;
require_once('funcoes.php');
?>
<?php
$max=250;
$src="_img/bars/bar.png";
$array=array("t"=>$db['taijutsu'],"n"=>$db['ninjutsu'],"g"=>$db['genjutsu']);
rsort($array);
$array2=array("t"=>$db['taijutsu'],"n"=>$db['ninjutsu'],"g"=>$db['genjutsu']);
arsort($array2);
?>
<div class="box_top">Atributos de <?php echo ucfirst($db['usuario']); ?></div>
<div class="box_middle">Atributos de combate, nível e experiência de <?php echo ucfirst($db['usuario']); ?>.<div class="sep"></div>
	<?php
		if($db['renegado']=='sim'){
			$sqlx=mysql_query("SELECT id FROM usuarios WHERE renegado='sim' ORDER BY nivel DESC, yens_fat DESC, vitorias DESC, derrotas ASC LIMIT 1");
		  	$dbx=mysql_fetch_assoc($sqlx);
			if($dbx['id']==$db['id']) $nivel='Líder da Akatsuki'; else $nivel='Nukenin';
		} else {
        	$sqlx=mysql_query("SELECT id FROM usuarios WHERE vila=".$db['vila']." ORDER BY nivel DESC, yens_fat DESC, vitorias DESC, derrotas ASC LIMIT 1");
		  	$dbx=mysql_fetch_assoc($sqlx);
		  	if($dbx['id']==$db['id']){
		  		switch($db['vila']){
					case 1: $nivel='Hokage'; break;
					case 2: $nivel='Kazekage'; break;
					case 3: $nivel='Otokage'; break;
					case 4: $nivel='Líder da Vila da Chuva'; break;
					case 5: $nivel='Raikage'; break;
					case 6: $nivel='Mizukage'; break;
					case 8: $nivel='Tsuchikage'; break;
					case 99: $nivel='Hokage'; break;
				}
		  	} else $nivel=rankNinja($db['nivel']); 
		}
		?>
	<table width="100%" cellpadding="0" cellspacing="0">
        <tr style="background:url(_img/gradient.jpg) repeat-y;">
        	<td width="20%" align="right" style="padding-right:10px;"><b>Vila:</b></td>
      <td colspan="2">
                <?php echo $txtvila; ?>
                <?php if(is_top1_vila($db['id'], $db['vila'])): ?>
                    <span style="color: #FFD700; font-weight: bold;"> [KAGE]</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
        	<td align="right" style="padding-right:10px;"><b>Clã:</b></td>
          <td colspan="2"><?php if(($db['orgid']==0)or($db['orgid']==-1)) echo '-'; else echo '<a href="?p=vieworg&id='.strtolower($db['orgid']).'">'.$db['orgnome'].'</a>'; ?></td>
        </tr>
        <tr style="background:url(_img/gradient.jpg) repeat-y;">
        	<td align="right" style="padding-right:10px;"><b>Nível:</b></td>
          <td colspan="2"><?php echo $nivel; ?><b> [<?php echo $db['nivel']; ?>]</b></td>
        </tr>
        <tr>
        	<td colspan="3"><div class="sep"></div></td>
        </tr>
        <tr>
        	<td align="right" style="padding-right:10px;"><b>Taijutsu:</b></td>
          <td><img src="_img/bars/bar_left.jpg" /><?php
			if($array[0]==$array2["t"]) echo '<img src="'.$src.'" width="'.($max*$array[0])/$array[0].'" height="22" />'; else
			if($array[1]==$array2["t"]) echo '<img src="'.$src.'" width="'.($max*$array[1])/$array[0].'" height="22" />'; else
			if($array[2]==$array2["t"]) echo '<img src="'.$src.'" width="'.($max*$array[2])/$array[0].'" height="22" />';
			?><img src="_img/bars/bar_right.jpg" />
    		</td>
            <td width="25%"><b>| <?php echo $db['taijutsu']; ?> |</b></td>
        </tr>
        <tr>
        	<td align="right" style="padding-right:10px;"><b>Ninjutsu:</b></td>
          <td><img src="_img/bars/bar_left.jpg" /><?php
			if($array[0]==$array2["n"]) echo '<img src="'.$src.'" width="'.($max*$array[0])/$array[0].'" height="22" />'; else
			if($array[1]==$array2["n"]) echo '<img src="'.$src.'" width="'.($max*$array[1])/$array[0].'" height="22" />'; else
			if($array[2]==$array2["n"]) echo '<img src="'.$src.'" width="'.($max*$array[2])/$array[0].'" height="22" />';
			?><img src="_img/bars/bar_right.jpg" />
            </td>
            <td><b>| <?php echo $db['ninjutsu']; ?> |</b></td>
        </tr>
        <tr>
        	<td align="right" style="padding-right:10px;"><b>Genjutsu:</b></td>
          <td><img src="_img/bars/bar_left.jpg" /><?php
			if($array[0]==$array2["g"]) echo '<img src="'.$src.'" width="'.($max*$array[0])/$array[0].'" height="22" />'; else
			if($array[1]==$array2["g"]) echo '<img src="'.$src.'" width="'.($max*$array[1])/$array[0].'" height="22" />'; else
			if($array[2]==$array2["g"]) echo '<img src="'.$src.'" width="'.($max*$array[2])/$array[0].'" height="22" />';
			?><img src="_img/bars/bar_right.jpg" />
          </td>
            <td><b>| <?php echo $db['genjutsu']; ?> |</b></td>
        </tr>
        <tr>
        	<td colspan="3"><div class="sep"></div></td>
        </tr>
        <tr>
        	<td align="right" style="padding-right:10px;"><b>Experiência:</b></td>
          <td height="22" style="background:url(_img/bars/empty_bar.jpg) no-repeat;"><?php if($db['exp']==0) echo '&nbsp;'; else { ?><img src="_img/bars/bar_left.png" /><img src="<?php echo $src; ?>" width="<?php echo (($db['exp']*$max)/$db['expmax']); ?>" height="22" /><img src="_img/bars/bar_right.png" /><?php } ?></td>
            <td><b>| <?php echo $db['exp']; ?> / <?php echo $db['expmax']; ?> |</b></td>
        </tr>
    </table>
    <?php if($db['id']<>$_SESSION['logado']){ ?>
    <div class="sep"></div>
    <div style="background:#333333;text-align:center;">
    <?php $_SESSION['bot']=rand(1,5); ?>
    <?php if(!isset($_SESSION['errobot'])) $_SESSION['errobot']=0; if(isset($_GET['msg'])) echo '<div class="aviso">Falha no teste anti-bot. Tente novamente!</div><div class="sep"></div>'; ?>
    <b>Sistema Anti-Bot</b>: Para atacar, clique no <b><?php echo $_SESSION['bot']; ?>&ordm;</b> botão.<br /><span class="sub2">Ao errar 2 vezes seguidas, sua conta é deslogada.</span>
    </div>
    <div class="sep"></div>
    <div align="center">
    	<input type="button" class="botao" value="Atacar" onclick="location.href='?p=attack&bot=1'" />
        <input type="button" class="botao" value="Atacar" onclick="location.href='?p=attack&bot=2'" />
        <input type="button" class="botao" value="Atacar" onclick="location.href='?p=attack&bot=3'" />
        <input type="button" class="botao" value="Atacar" onclick="location.href='?p=attack&bot=4'" />
        <input type="button" class="botao" value="Atacar" onclick="location.href='?p=attack&bot=5'" />
    </div>
    <?php } ?>
</div>
<div class="box_bottom"></div>
<div class="box_top">Estatísticas de <?php echo ucfirst($db['usuario']); ?></div>
<div class="box_middle">Rstatísticas da conta de <?php echo ucfirst($db['usuario']); ?>.<div class="sep"></div>
	<div style="background:url(_img/stats.jpg) no-repeat right top;height:120px;">
	<table width="60%" cellpadding="0" cellspacing="0">
        <tr style="background:url(_img/gradient.jpg);">
        	<td style="padding-left:3px;"><b>Yens Faturados</b></td>
            <td><?php echo number_format($db['yens_fat'],2,',','.'); ?> yens</td>
        </tr>
        <tr>
        	<td style="padding-left:3px;"><b>Yens Perdidos</b></td>
            <td><?php echo number_format($db['yens_perd'],2,',','.'); ?> yens</td>
        </tr>
        <tr style="background:url(_img/gradient.jpg);">
        	<td style="padding-left:3px;"><b>Batalhas</b></td>
            <td><?php echo $db['batalhas']; ?> batalhas</td>
        </tr>
        <tr>
        	<td style="padding-left:3px;"><b>Vitórias</b></td>
            <td><?php echo $db['vitorias']; ?> vitórias</td>
        </tr>
        <tr style="background:url(_img/gradient.jpg);">
        	<td style="padding-left:3px;"><b>Derrotas</b></td>
            <td><?php echo $db['derrotas']; ?> derrotas</td>
        </tr>
        <tr>
        	<td style="padding-left:3px;"><b>Empates</b></td>
            <td><?php echo $db['empates']; ?> empates</td>
        </tr>
        <tr style="background:url(_img/gradient.jpg);">
        	<td style="padding-left:3px;"><b>Experiência Total</b></td>
            <td><?php echo $db['exptotal']; ?> pontos</td>
        </tr>
    </table>
    </div>
</div>
<div class="box_bottom"></div>
<div class="box_top">Apresentação de <?php echo ucfirst($db['usuario']); ?></div>
<div class="box_middle">
	<div class="apresentacao"><?php if($db['config_apresentacao']=='') echo 'Nenhum texto de apresentação.'; else echo str_replace(array('<p>','</p>'),array('','<br />'),$db['config_apresentacao']); ?></div>
</div>
<div class="box_bottom"></div>
<?php if(is_top1_vila($inimigo['id'], $inimigo['vila'])): ?>
            <img src="_img/Chapeu/bg-kage-konoha.jpg" style="position: absolute; width: 150px; height: 150px; z-index: 1;" />
            <img src="_img/Chapeu/Pergaminho.jpg" style="position: absolute; width: 150px; height: 150px; z-index: 2;" />
        <?php endif; ?>

```

Since the original code refers to `$txtvila` without defining it, I will assume the change is intended for the prepare.php. Since I don't have access to prepare.php, I will skip the modification.