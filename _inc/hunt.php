<?php require_once('trava.php'); ?>
<?php require_once('verificar.php'); ?>
<?php
require_once('Encrypt.php');
$c=new C_Encrypt();

if(isset($_POST['hunt_tipo'])){
	$tipo=$c->decode($_POST['hunt_tipo'],$chaveuniversal);
	vn($tipo);
	switch($tipo){
		case 1:
			$hunt=$_POST['hunt_1'];
			if($hunt==''){echo "<script>self.location='?p=hunt&msg=6'</script>"; break; }
			if(date('Y-m-d H:i:s')>=$db['vip']) if($db['yens']<5){ echo "<script>self.location='?p=hunt&msg=3'</script>"; break; }
			if(date('Y-m-d H:i:s')>=$db['vip']) mysql_query("UPDATE usuarios SET yens=yens-5 WHERE id=".$db['id']);
			$nome=$hunt;
			$sqlh=mysql_query("SELECT id,vila,renegado,avatar,energia,preso,penalidade_fim,missao,loginip,tipo FROM usuarios WHERE usuario='".$nome."'");
			$dbh=mysql_fetch_assoc($sqlh) or die(mysql_error());
			if(mysql_num_rows($sqlh)==0){ echo "<script>self.location='?p=hunt&msg=1'</script>"; break; }
			if($dbh['energia']<25){ echo "<script>self.location='?p=hunt&msg=2'</script>"; break; }
			if($dbh['loginip']==$db['loginip']){ echo "<script>self.location='?p=hunt&msg=16'</script>"; break; }
			if($dbh['preso']==1){ echo "<script>self.location='?p=hunt&msg=4'</script>"; break; }
			if($dbh['missao']==999){ echo "<script>self.location='?p=hunt&msg=10'</script>"; break; }
			if($dbh['avatar']==0){ echo "<script>self.location='?p=hunt&msg=12'</script>"; break; }
			if(($dbh['renegado']=='sim')&&($db['renegado']=='sim')){ echo "<script>self.location='?p=hunt&msg=11'</script>"; break; }
			if(($dbh['vila']==$db['vila'])&&($dbh['renegado']=='nao')&&($db['renegado']=='nao')){ echo "<script>self.location='?p=hunt&msg=11'</script>"; break; }
			if(date('Y-m-d H:i:s')<$dbh['penalidade_fim']){ echo "<script>self.location='?p=hunt&msg=7'</script>"; break; }
			$_SESSION['prepare']=$dbh['id'];
			$sqlv=mysql_query("SELECT data FROM relatorios WHERE usuarioid=".$db['id']." AND inimigoid=".$dbh['id']." ORDER BY id DESC LIMIT 1");
			$dbv=mysql_fetch_assoc($sqlv);
			if($dbv && isset($dbv['data'])){
				$soma=mktime(date('H')-12, date('i'), date('s'));
				$penalidade=date('Y-m-d H:i:s',$soma);
				if($penalidade<$dbv['data']){ echo "<script>self.location='?p=hunt&msg=9'</script>"; break; }
			}
			$sqlv=mysql_query("SELECT data FROM relatorios WHERE usuarioid=".$dbh['id']." OR inimigoid=".$dbh['id']." ORDER BY id DESC LIMIT 1");
			$dbv=mysql_fetch_assoc($sqlv);
			$soma=mktime(date('H'), date('i')-30, date('s'));
			$penalidade=date('Y-m-d H:i:s',$soma);
			if($dbh['tipo']=='player'){
				if($penalidade<$dbv['data']){ echo "<script>self.location='?p=hunt&msg=8'</script>"; break; }
			}
			$_SESSION['hunt']=1;
			echo "<script>self.location='?p=prepare'</script>"; break;
		case 2:
			$hunt=$c->decode($_POST['hunt_2'],$chaveuniversal);
			if($hunt==0){echo "<script>self.location='?p=hunt&msg=6'</script>"; break; }
			if(($hunt<1)or($hunt>7)){ echo "<script>self.location='?p=home'</script>"; break; }
			if(date('Y-m-d H:i:s')>=$db['vip']) if($db['yens']<5){ echo "<script>self.location='?p=hunt&msg=3'</script>"; break; }
			if(date('Y-m-d H:i:s')>=$db['vip']) mysql_query("UPDATE usuarios SET yens=yens-5 WHERE id=".$db['id']);
			$vila=$hunt;
			/*if($hunt==$db['vila']){ echo "<script>self.location='?p=hunt&msg=11'</script>"; break; }*/
			if($hunt==7)
				$sqlh=mysql_query("SELECT id,energia,preso FROM usuarios WHERE avatar>0 AND energia>=25 AND renegado='sim' AND id<>".$db['id']." AND missao<>999 AND loginip<>'".$db['loginip']."' ORDER BY RAND() LIMIT 1");
			else
				$sqlh=mysql_query("SELECT id,energia,preso FROM usuarios WHERE avatar>0 AND energia>=25 AND vila=".$vila." AND renegado='nao' AND id<>".$db['id']." AND missao<>999 AND loginip<>'".$db['loginip']."' ORDER BY RAND() LIMIT 1");
			$dbh=mysql_fetch_assoc($sqlh);
			if(mysql_num_rows($sqlh)==0){ echo "<script>self.location='?p=hunt&msg=5'</script>"; break; }
			if($dbh['preso']==1){ echo "<script>self.location='?p=hunt&msg=4'</script>"; break; }
			if($vila==7){
				if(($dbh['renegado']=='sim')&&($db['renegado']=='sim')){ echo "<script>self.location='?p=hunt&msg=11'</script>"; break; }
			} else {
				if(($dbh['vila']==$db['vila'])&&($dbh['renegado']=='nao')&&($db['renegado']=='nao')){ echo "<script>self.location='?p=hunt&msg=11'</script>"; break; }
			}
			$_SESSION['prepare']=$dbh['id'];
			$_SESSION['hunt']=2;
			echo "<script>self.location='?p=prepare'</script>"; break;
		case 3:
			$hunt=$c->decode($_POST['hunt_3'],$chaveuniversal);
			if($hunt==0){ echo "<script>self.location='?p=hunt&msg=6'</script>"; break; } 
			if(($hunt<1)or($hunt>3)){ echo "<script>self.location='?p=home'</script>"; break; }
			if(date('Y-m-d H:i:s')>=$db['vip']) if($db['yens']<5){ echo "<script>self.location='?p=hunt&msg=3'</script>"; break; }
			if(date('Y-m-d H:i:s')>=$db['vip']) mysql_query("UPDATE usuarios SET yens=yens-5 WHERE id=".$db['id']);
			$nivel=$hunt;
			switch($nivel){
				case 1: $filtro='nivel<'.$db['nivel']; break;
				case 2: $filtro='nivel='.$db['nivel']; break;
				case 3: $filtro='nivel>'.$db['nivel']; break;
			}
			if($db['renegado']=='nao')
				$sqlh=mysql_query("SELECT id,energia,preso FROM usuarios WHERE avatar>0 AND energia>=25 AND ".$filtro." AND vila<>".$db['vila']." AND id<>".$db['id']." AND missao<>999 ORDER BY RAND() LIMIT 1");
			else
				$sqlh=mysql_query("SELECT id,energia,preso FROM usuarios WHERE avatar>0 AND energia>=25 AND ".$filtro." AND renegado='nao' AND id<>".$db['id']." AND missao<>999 ORDER BY RAND() LIMIT 1");
			//$sqlh=mysql_query("SELECT id,energia,preso FROM usuarios WHERE avatar>0 AND energia>=25 AND ".$filtro." AND id<>".$db['id']." AND missao<>999 AND loginip<>'".$db['loginip']."' ORDER BY RAND() LIMIT 1");
			$dbh=mysql_fetch_assoc($sqlh);
			if(mysql_num_rows($sqlh)==0){ echo "<script>self.location='?p=hunt&msg=5'</script>"; break; }
			if($dbh['preso']==1){ echo "<script>self.location='?p=hunt&msg=4'</script>"; break; }
			$_SESSION['prepare']=$dbh['id'];
			$_SESSION['hunt']=3;
			echo "<script>self.location='?p=prepare'</script>"; break;
		case 4:
			$hunt=$c->decode($_POST['hunt_4'],$chaveuniversal);
			/*if(($_POST['hunt_captcha']=='')or($_POST['hunt_captcha']<>$_SESSION['captcha'])){ echo "<script>self.location='?p=hunt&msg=15'</script>"; break; }*/
			if($db['hunt_restantes']<$hunt){ echo "<script>self.location='?p=home'</script>"; break; }
			$horas=0;
			if(($hunt<1)or($hunt>12)){ echo "<script>self.location='?p=home'</script>"; break; }
			if($hunt<6){ $minutos=$hunt; } else { $aux=$hunt; do{ $aux=$aux-6; $horas=$horas+1; } while($aux>=6); $minutos=$aux; }
			$soma=mktime(date('H')+$horas, date('i')+($minutos*10), date('s'));
			$huntfim=date('Y-m-d H:i:s',$soma);
			$_SESSION['hunt']=4;
			mysql_query("UPDATE usuarios SET hunt=4, hunt_fim='".$huntfim."', hunt_restantes=hunt_restantes-".$hunt." WHERE id=".$db['id']); echo "<script>self.location='?p=busyhunt'</script>"; break;
		case 5:
			$hunt=$c->decode($_POST['hunt_5'],$chaveuniversal);
			if($hunt==0){ echo "<script>self.location='?p=hunt&msg=6'</script>"; break; }
			if(date('Y-m-d H:i:s')>=$db['vip']){ echo "<script>self.location='?p=hunt&msg=14'</script>"; break; }
			if($db['renegado']=='sim') $minhavila=9; else $minhavila=$db['vila'];
			$timeout=time()-900;
			do{
				$vilaid=rand(1,9);
			} while($vilaid==$minhavila);
			if($hunt==1) $tempo='AND timestamp>='.$timeout;
			if($hunt==2) $tempo='AND timestamp<'.$timeout;
			if($vilaid==9)
				$sqlh=mysql_query("SELECT id,energia,preso,vila,renegado,penalidade_fim FROM usuarios WHERE energia>=25 AND avatar>0 AND id<>".$db['id']." AND missao<>999 AND loginip<>'".$db['loginip']."' ".$tempo." AND renegado='sim' ORDER BY RAND() LIMIT 1");
			else
				$sqlh=mysql_query("SELECT id,energia,preso,vila,renegado,penalidade_fim FROM usuarios WHERE energia>=25 AND avatar>0 AND id<>".$db['id']." AND missao<>999 AND loginip<>'".$db['loginip']."' ".$tempo." AND renegado='nao' AND vila=".$vilaid." ORDER BY RAND() LIMIT 1");
			$dbh=mysql_fetch_assoc($sqlh);
			if(mysql_num_rows($sqlh)==0){ echo "<script>self.location='?p=hunt&msg=5'</script>"; break; }
			if($dbh['energia']<25){ echo "<script>self.location='?p=hunt&msg=2'</script>"; break; }
			if($dbh['preso']==1){ echo "<script>self.location='?p=hunt&msg=4'</script>"; break; }
			if(($dbh['renegado']=='sim')&&($db['renegado']=='sim')){ echo "<script>self.location='?p=hunt&msg=11'</script>"; break; }
			if(($dbh['vila']==$db['vila'])&&($dbh['renegado']=='nao')&&($db['renegado']=='nao')){ echo "<script>self.location='?p=hunt&msg=11'</script>"; break; }
			if(date('Y-m-d H:i:s')<$dbh['penalidade_fim']){ echo "<script>self.location='?p=hunt&msg=7'</script>"; break; }
			$_SESSION['prepare']=$dbh['id'];
			$sqlv=mysql_query("SELECT data FROM relatorios WHERE usuarioid=".$db['id']." AND inimigoid=".$dbh['id']." ORDER BY id DESC LIMIT 1");
			$dbv=mysql_fetch_assoc($sqlv);
			if($dbv && isset($dbv['data'])){
				$soma=mktime(date('H')-12, date('i'), date('s'));
				$penalidade=date('Y-m-d H:i:s',$soma);
				if($penalidade<$dbv['data']){ echo "<script>self.location='?p=hunt&msg=9'</script>"; break; }
			}
			$sqlv=mysql_query("SELECT data FROM relatorios WHERE usuarioid=".$dbh['id']." OR inimigoid=".$dbh['id']." ORDER BY id DESC LIMIT 1");
			$dbv=mysql_fetch_assoc($sqlv);
			$soma=mktime(date('H'), date('i')-30, date('s'));
			$penalidade=date('Y-m-d H:i:s',$soma);
			if($penalidade<$dbv['data']){ echo "<script>self.location='?p=hunt&msg=8'</script>"; break; }
			$_SESSION['hunt']=5;
			echo "<script>self.location='?p=prepare'</script>"; break;
	}
}
?>
<div class="box_top">Caças</div>
<div class="box_middle">As caças são as formas mais rápidas de se ganhar experiência e yens. Escolha um dos tipos de caça abaixo, e boa sorte!<div class="sep"></div><div style="background:url(_img/gradient.jpg) repeat-y;color:#FFFFAA;"><img src="_img/yens.png" align="absmiddle" width="14" height="14" /> <b>Meus Yens: <?php echo number_format($db['yens'],2,',','.'); ?> yens</b></div><div class="sep"></div>
	<?php if(isset($_GET['msg'])){
	switch($_GET['msg']){
		case 1: $msg='Nenhum ninja encontrado com o nome informado.'; break;
		case 2: $msg='A energia do ninja está muito baixa para uma luta.'; break;
		case 3: $msg='Yens insuficientes para realizar esta ação.'; break;
		case 4: $msg='Ninja não pode ser atacado pois está na prisão de sua respectiva vila.'; break;
		case 5: $msg='No momento, não existem ninjas disponíveis para batalha.'; break;
		case 6: $msg='Dados informados não são suficientes para realizar a caça.'; break;
		case 7: $msg='Este ninja não pode ser atacado pois acabou de enfrentar um outro ninja.'; break;
		case 8: $msg='Este ninja esteve em uma terrível batalha nos últimos 30 minutos.<br />Tente novamente mais tarde.'; break;
		case 9: $msg='Você já enfrentou este ninja nas últimas 12 horas.<br />Tente novamente mais tarde.'; break;
		case 10: $msg='Ninja em período de férias!'; break;
		case 11: $msg='Este ninja reside na mesma vila que você.'; break;
		case 12: $msg='Este ninja nunca realizou o login no jogo, e por isso não pode ser atacado.'; break;
		case 13: $msg='Sua energia está muito baixa para entrar em uma batalha.'; break;
		case 14: $msg='Disponível apenas para jogadores VIP.'; break;
		case 15: $msg='Código <b>CAPTCHA</b> incorreto.'; break;
		case 16: $msg='Este ninja não pode ser encontrado.'; break;
	}
	echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>';
	} ?>
    <form method="post" action="?p=hunt" onsubmit="subm1.value='Carregando...';subm1.disabled=true;">
    <input type="hidden" id="hunt_tipo" name="hunt_tipo" value="<?php echo $c->encode('1',$chaveuniversal); ?>" />
    <fieldset><legend>Caçar Ninja Específico (<?php if(date('Y-m-d H:i:s')<$db['vip']) echo 'gratuito para jogadores VIP'; else echo '5,00 yens'; ?>)</legend>
    	<div style="width:320px;margin-right:15px;float:left;">Se você sabe quem deseja procurar, digite o nome ao lado. Lembre-se que as chances de encontrar um ninja mais experiente que você varia pela diferença de níveis.</div>
    	<div align="center"><input type="text" id="hunt_1" name="hunt_1" /><br /><input type="submit" id="subm1" name="subm1" class="botao" value="Caçar" /></div>
    	<div class="clear"></div>
    </fieldset>
    </form>
    <form method="post" action="?p=hunt" onsubmit="subm2.value='Carregando...';subm2.disabled=true;">
    <input type="hidden" id="hunt_tipo" name="hunt_tipo" value="<?php echo $c->encode('2',$chaveuniversal); ?>" />
    <fieldset><legend>Caçar Ninja por Vila (<?php if(date('Y-m-d H:i:s')<$db['vip']) echo 'gratuito para jogadores VIP'; else echo '5,00 yens'; ?>)</legend>
    	<div style="width:320px;margin-right:15px;float:left;">Você pode concentrar suas caças apenas em uma vila (ideal para conflitos entre vilas). Escolha uma das opções ao lado, e inicie suas buscas!</div>
    	<div align="center">
        	<select id="hunt_2" name="hunt_2">
            	<option value="<?php echo $c->encode('0',$chaveuniversal); ?>" selected="selected">-- Selecione --</option>
            	<option value="<?php echo $c->encode('1',$chaveuniversal); ?>">Vila da Folha</option>
                <option value="<?php echo $c->encode('2',$chaveuniversal); ?>">Vila da Areia</option>
                <option value="<?php echo $c->encode('3',$chaveuniversal); ?>">Vila do Som</option>
                <option value="<?php echo $c->encode('4',$chaveuniversal); ?>">Vila da Chuva</option>
                <option value="<?php echo $c->encode('5',$chaveuniversal); ?>">Vila da Nuvem</option>
                <option value="<?php echo $c->encode('6',$chaveuniversal); ?>">Vila da Névoa</option>
                <option value="<?php echo $c->encode('7',$chaveuniversal); ?>">Akatsuki</option>
            </select><br /><input type="submit" id="subm2" name="subm2" class="botao" value="Caçar" /></div>
    	<div class="clear"></div>
    </fieldset>
    </form>
    <form method="post" action="?p=hunt" onsubmit="subm3.value='Carregando...';subm3.disabled=true;">
    <input type="hidden" id="hunt_tipo" name="hunt_tipo" value="<?php echo $c->encode('3',$chaveuniversal); ?>" />
    <fieldset><legend>Caçar Ninja por Nível (<?php if(date('Y-m-d H:i:s')<$db['vip']) echo 'gratuito para jogadores VIP'; else echo '5,00 yens'; ?>)</legend>
    	<div style="width:320px;margin-right:15px;float:left;">Procure ninjas mais fracos, equivalentes ou mais fortes que você.<br /><?php if($db['nivel']>1){ ?>- Ninjas Mais Fracos: até nível <?php echo $db['nivel']-1; ?>;<br /><?php } ?>- Ninjas Equivalentes: nível <?php echo $db['nivel']; ?>;<br />- Ninjas Mais Fortes: acima de nível <?php echo $db['nivel']+1; ?>.</div>
    	<div align="center">
        	<select id="hunt_3" name="hunt_3">
            	<option value="<?php echo $c->encode('0',$chaveuniversal); ?>" selected="selected">-- Selecione --</option>
            	<?php if($db['nivel']>1){ ?><option value="<?php echo $c->encode('1',$chaveuniversal); ?>">Ninjas Mais Fracos</option><?php } ?>
                <option value="<?php echo $c->encode('2',$chaveuniversal); ?>">Ninjas Equivalentes</option>
                <option value="<?php echo $c->encode('3',$chaveuniversal); ?>">Ninjas Mais Fortes</option>
            </select><br /><input type="submit" id="subm3" name="subm3" class="botao" value="Caçar" /></div>
    	<div class="clear"></div>
    </fieldset>
    </form>
    <?php if(date('Y-m-d H:i:s')<$db['vip']){ ?>
    <form method="post" action="?p=hunt" onsubmit="subm5.value='Carregando...';subm5.disabled=true;">
    <input type="hidden" id="hunt_tipo" name="hunt_tipo" value="<?php echo $c->encode('5',$chaveuniversal); ?>" />
    <fieldset><legend>Caçar Ninja por Status (gratuito - exclusivo para jogadores VIP)</legend>
    	<div style="width:320px;margin-right:15px;float:left;">Procure ninjas pelo seu status atual. Você pode pesquisar por ninjas que estejam jogando neste exato momento, ou então atacar ninjas despreparados. Qualquer ninja procurado neste modo não pertencerá a mesma vila que você.</div>
    	<div align="center">
        	<select id="hunt_5" name="hunt_5">
            	<option value="<?php echo $c->encode('0',$chaveuniversal); ?>" selected="selected">-- Selecione --</option>
            	<option value="<?php echo $c->encode('1',$chaveuniversal); ?>">Online</option>
                <option value="<?php echo $c->encode('2',$chaveuniversal); ?>">Offline</option>
            </select><br /><input type="submit" id="subm5" name="subm5" class="botao" value="Caçar" /></div>
    	<div class="clear"></div>
    </fieldset>
    </form>
    <?php } ?>
    <form method="post" action="?p=hunt" onsubmit="subm4.value='Carregando...';subm4.disabled=true;">
    <input type="hidden" id="hunt_tipo" name="hunt_tipo" value="<?php echo $c->encode('4',$chaveuniversal); ?>" />
    <fieldset><legend>Caçar por Tempo</legend>
    	<div style="width:320px;margin-right:15px;float:left;padding-bottom:50px;">O melhor método para se ganhar experiência e yens rapidamente. É recomendável caçar de 10 em 10 minutos, aproveitando ao máximo o tempo diário. Não há custos para este tipo de caça.<br /><br /><img src="_img/clock.png" align="absmiddle" /> <b>Tempo Restante: <?php echo $db['hunt_restantes']*10; ?> minutos.</b></div>
    	<div align="center">
        	<?php if($db['hunt_restantes']==0) echo 'Seus minutos diários de caça se esgotaram.'; else { ?>
            <?php /*<?php $_SESSION['captcha']=rand(1000,9999); ?>
            <span class="sub2">Digite o número da imagem no campo abaixo</span>
            <img src="captcha.php?cod=<?php echo base64_encode($_SESSION['captcha']); ?>" /><br />
            <input type="text" id="hunt_captcha" name="hunt_captcha" maxlength="4" style="width:103px;" /><br /><br />*/ ?>
        	<select id="hunt_4" name="hunt_4">
            	<?php $i=1; do{ ?>
            	<option value="<?php echo $c->encode($i,$chaveuniversal); ?>"><?php echo $i*10; ?> minutos</option>
                <?php $i++; } while($i<=$db['hunt_restantes']); ?>
            </select><br /><input type="submit" id="subm4" name="subm4" class="botao" value="Caçar" /></div>
            <?php } ?>
    	<div class="clear"></div>
    </fieldset>
    </form>
</div>
<div class="box_bottom"></div>
<?php
@mysql_free_result($sqlh);
?>