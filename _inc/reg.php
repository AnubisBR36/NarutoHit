<?php
require_once('Encrypt.php');
$c=new C_Encrypt();

if(isset($_SESSION['logado'])){ header("Location: index.php?p=home"); exit; }
$sqlc=$conexao->query("SELECT count(id) conta FROM usuarios");
$dbc=$sqlc->fetch(PDO::FETCH_ASSOC);
if(!$dbc) $dbc = array('conta' => 0);
$vagas=30000;
if($dbc['conta']>=$vagas){ header("Location: index.php?p=login"); exit; }
if(isset($_POST['reg_submit'])){
	$erro=0;
	if(@$_POST['reg_termos']=='') $erro=11;
	if(!isset($_POST['reg_termos'])) $erro=8;
	if($_POST['reg_senha']<>$_POST['reg_senha2']) $erro=7;
	if($_POST['reg_vila']=='') $erro=6;
	if($_POST['reg_personagem']=='') $erro=5;
	if($_POST['reg_email']=='') $erro=4;
	if($_POST['reg_senha2']=='') $erro=3;
	if($_POST['reg_senha']=='') $erro=2;
	if($_POST['reg_usuario']=='') $erro=1;
	$personagem=$c->decode($_POST['reg_personagem'],$chaveuniversal);
	$vila=$c->decode($_POST['reg_vila'],$chaveuniversal);
	// Usar nome de usuário exatamente como digitado
	$usuario = $_POST['reg_usuario'];
	$sqlc=$conexao->prepare("SELECT count(id) as conta FROM usuarios WHERE usuario=?");
	$sqlc->execute([$usuario]);
	$dbc=$sqlc->fetch(PDO::FETCH_ASSOC);
	if(!$dbc) $dbc = array('conta' => 0);
	if($dbc['conta']>0) $erro=12;
	$sqlc=$conexao->prepare("SELECT count(id) as conta FROM usuarios WHERE email=?");
	$sqlc->execute([$_POST['reg_email']]);
	$dbc=$sqlc->fetch(PDO::FETCH_ASSOC);
	if(!$dbc) $dbc = array('conta' => 0);
	if($dbc['conta']>0) $erro=12;
	if($_POST['reg_nlink']<>''){
		$nlink=$_POST['reg_nlink'];
		$sqlv=$conexao->prepare("SELECT count(id) as conta FROM usuarios WHERE usuario=?");
		$sqlv->execute([$nlink]);
		$dbv=$sqlv->fetch(PDO::FETCH_ASSOC);
		if(!$dbv) $dbv = array('conta' => 0);
		if($dbv['conta']==0) $erro=13;
	}
	// Verificação de multi-contas removida temporariamente
	if(isset($_POST['nlink'])) $link='&nlink='.$_POST['nlink']; else $link='';
	if($erro>0){ header("Location: index.php?p=reg&user=".$_POST['reg_usuario']."&mail=".$_POST['reg_email']."&char=".$personagem."&village=".$vila."&erro=".$erro.$link); exit; }
	else {
		if(isset($_POST['reg_akatsuki'])) $renegado='sim'; else $renegado='nao';
		$soma=mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y'));
		$vipfim=date('Y-m-d H:i:s',$soma);
		// Usar o nome de usuário original, sem modificações
		$stmt = $conexao->prepare("INSERT INTO usuarios (usuario, senha, email, personagem, vila, renegado, hunt_restantes, reg, natureza1, natureza2, natureza3, ip, vip, vip_inicio) VALUES (?, ?, ?, ?, ?, ?, 14, ?, '', '', '', ?, ?, ?)");
		$stmt->execute([$usuario, md5($_POST['reg_senha']), strtolower($_POST['reg_email']), $personagem, $vila, $renegado, date('Y-m-d H:i:s'), ip2long($_SERVER['REMOTE_ADDR']), $vipfim, date('Y-m-d H:i:s')]);

		if($_POST['reg_nlink']<>'') {
			$stmt2 = $conexao->prepare("UPDATE usuarios SET yens=yens+100, yens_fat=yens_fat+100 WHERE usuario=?");
			$stmt2->execute([$_POST['reg_nlink']]);
		}
		$mensagem='<div align="center"><img src="http://www.narutohit.net/_img/support/minilogo2.jpg" style="border-bottom:1px solid #DDDDDD" /><br /><br /><b>Ativação de Conta</b><br />Utilize o link abaixo para ativar sua conta:<br /><br /><b>http://www.narutohit.net/ativation.php?token='.md5($_POST['reg_senha']).'</b><br /><br /><b><span style="color:#CC0000">Bom jogo!</span></b><br /><br />Para mais informações, visite nossa <a href="http://www.orkut.com.br/Main#Community?cmm=95565573">comunidade no orkut</a>.<br /><br />Atenciosamente, equipe narutoHIT.</div>';
		$assunto='Ativar Conta';
		$remetente='narutoHIT - CBT <contato@narutohit.net>';
		$headers = implode ( "\n",array ( "From: $remetente","Subject: ","Return-Path: $remetente","MIME-Version: 1.0","X-Priority: 3","Content-Type: text/html;" ) );
		//mail(strtolower($_POST['reg_email']),$assunto,$mensagem,$headers);
		header("Location: index.php?p=reg2"); exit;
	}
}
?>
<?php
$txtnaruto='<b>Nome:</b> Uzumaki Naruto<br /><b>Vila:</b> Vila da Folha<br /><b>Classificação:</b> Gennin';
$txtsakura='<b>Nome:</b> Haruno Sakura<br /><b>Vila:</b> Vila da Folha<br /><b>Classificação:</b> Chuunin';
$txtsasuke='<b>Nome:</b> Uchiha Sasuke<br /><b>Vila:</b> Vila da Folha<br /><b>Classificação:</b> Nukenin';
$txtkakashi='<b>Nome:</b> Hatake Kakashi<br /><b>Vila:</b> Vila da Folha<br /><b>Classificação:</b> Jounnin';
$sqlc=$conexao->query("SELECT count(id) conta FROM usuarios");
$dbc=$sqlc->fetch(PDO::FETCH_ASSOC);
if(!$dbc) $dbc = array('conta' => 0);
?>
<div class="box_top">Registrar</div><div class="box_middle">Seja bem-vindo ao narutoHIT! Para que você possa se aventurar no mundo de Naruto, é necessário criar uma conta de acesso. Para isso, basta preencher o formulário abaixo com os dados solicitados. Todos os campos solicitados são obrigatórios, sem exceção.<br /><div class="sep"></div><div style="background:url(_img/gradient.jpg) repeat-y;padding-left:5px;">Neste momento, temos <b><?php echo $vagas-$dbc['conta']; ?> vagas</b> disponíveis para registro.<?php if(($vagas-$dbc['conta'])<10) echo ' Seja rápido!'; ?></div><div class="sep"></div>
<?php if(isset($_GET['erro'])){
	switch($_GET['erro']){
		case 1: $msg='Favor preencha o campo <b>NOME DE USUÁRIO</b>.'; break;
		case 2: $msg='Favor preencha o campo <b>SENHA</b>.'; break;
		case 3: $msg='Favor preencha o campo <b>CONFIRMAR SENHA</b>.'; break;
		case 4: $msg='Favor preencha o campo <b>EMAIL</b>.'; break;
		case 5: $msg='Favor selecione um <b>PERSONAGEM</b>.'; break;
		case 6: $msg='Favor selecione uma <b>VILA</b>.'; break;
		case 7: $msg='<b>SENHAS</b> digitadas não conferem.'; break;
		case 8: $msg='Você precisa aceitar os <b>TERMOS E CONDIÇÕES</b> do jogo antes de prosseguir.'; break;
		case 9: $msg='<b>CHAVE DE REGISTRO</b> não existe.'; break;
		case 10: $msg='<b>CHAVE DE REGISTRO</b> já foi utilizada.'; break;
		case 11: $msg='Favor preencha o campo <b>CHAVE DE REGISTRO</b>.'; break;
		case 12: $msg='<b>USUÁRIO</b> ou <b>EMAIL</b> já está em uso!'; break;
		case 13: $msg='Código <b>NLINK</b> utilizado é inválido.'; break;
		case 14: $msg='<b>IP</b> já está em uso por outra conta.'; break;
		case 15: $msg='Erro interno do sistema.'; break;
	}
	echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>';
} ?>
<form method="post" action="?p=reg" name="reg" id="reg" style="background:url(_img/reg.jpg) no-repeat right top;" onsubmit="subm.value='Carregando...';subm.disabled=true;">
<input type="hidden" id="reg_submit" name="reg_submit" value="1" />
<input type="hidden" id="reg_nlink" name="reg_nlink" value="<?php if(isset($_GET['nlink'])) echo $_GET['nlink']; ?>" />
<fieldset>
	<legend>Dados da Conta</legend>
    <span class="destaque">Nome de Usuário:</span><br />
    <input type="text" id="reg_usuario" name="reg_usuario" maxlength="15" onfocus="className='input'" onblur="className=''" <?php if(isset($_GET['user'])) echo 'value="'.$_GET['user'].'"'; ?>/><br />
    <span class="sub2">Digite um nome de usuário com até<br />15 caracteres. Este também será seu<br />login no jogo, e o nome do personagem.</span><br /><br />

    <span class="destaque">Senha:</span><br />
    <input type="password" id="reg_senha" name="reg_senha" maxlength="15" onfocus="className='input'" onblur="className=''" /><br />
    <span class="sub2">Digite uma senha de acesso<br />com até 15 caracteres.</span><br /><br />

    <span class="destaque">Confirmar Senha:</span><br />
    <input type="password" id="reg_senha2" name="reg_senha2" maxlength="15" onfocus="className='input'" onblur="className=''" /><br />
    <span class="sub2">Digite novamente a senha.</span><br /><br />

    <span class="destaque">Email:</span><br />
    <input type="text" id="reg_email" name="reg_email" maxlength="250" onfocus="className='input'" onblur="className=''" <?php if(isset($_GET['mail'])) echo 'value="'.$_GET['mail'].'"'; ?>/><br />
    <span class="sub2">Digite um email válido<br />(será necessário ativar a conta em alguns dias).</span>
</fieldset>
<fieldset>
	<legend>Personagem</legend>
    <span class="sub2">Escolha um personagem entre os 4 abaixo. Durante sua evolução no jogo, novos personagens são liberados para uso. A troca de personagem pode ser feita uma vez por dia.</span><div class="sep"></div>
    <div align="center">
    <table>
   	  <tr>
       	  <td width="116" height="65" align="center"><img src="_img/personagens/reg_naruto.jpg" onclick="document.getElementById('reg_personagem1').checked=true" onmouseover="Tip('<div class=tooltip><?php echo $txtnaruto; ?></div>')" onmouseout="UnTip()" /></td>
          	<td width="116" align="center"><img src="_img/personagens/reg_sakura.jpg" onclick="document.getElementById('reg_personagem2').checked=true" onmouseover="Tip('<div class=tooltip><?php echo $txtsakura; ?></div>')" onmouseout="UnTip()" /></td>
          <td width="116" align="center"><img src="_img/personagens/reg_sasuke.jpg" onclick="document.getElementById('reg_personagem3').checked=true" onmouseover="Tip('<div class=tooltip><?php echo $txtsasuke; ?></div>')" onmouseout="UnTip()" /></td>
            <td width="116" align="center"><img src="_img/personagens/reg_kakashi.jpg" onclick="document.getElementById('reg_personagem4').checked=true" onmouseover="Tip('<div class=tooltip><?php echo $txtkakashi; ?></div>')" onmouseout="UnTip()" /></td>
        </tr>
        <tr>
        	<td align="center"><input type="radio" id="reg_personagem1" name="reg_personagem" value="<?php echo $c->encode('naruto',$chaveuniversal); ?>" <?php if((!isset($_GET['char']))or(isset($_GET['char']))&&($_GET['char']=='naruto')) echo 'checked="checked"'; ?>/></td>
            <td align="center"><input type="radio" id="reg_personagem2" name="reg_personagem" value="<?php echo $c->encode('sakura',$chaveuniversal); ?>" <?php if((isset($_GET['char']))&&($_GET['char']=='sakura')) echo 'checked="checked"'; ?>/></td>
            <td align="center"><input type="radio" id="reg_personagem3" name="reg_personagem" value="<?php echo $c->encode('sasuke',$chaveuniversal); ?>" <?php if((isset($_GET['char']))&&($_GET['char']=='sasuke')) echo 'checked="checked"'; ?>/></td>
          <td align="center"><input type="radio" id="reg_personagem4" name="reg_personagem" value="<?php echo $c->encode('kakashi',$chaveuniversal); ?>" <?php if((isset($_GET['char']))&&($_GET['char']=='kakashi')) echo 'checked="checked"'; ?>/></td>
        </tr>
    </table>
    </div>
</fieldset>
<fieldset>
	<legend>Vila</legend>
    <span class="sub2">Escolha uma vila para representar. Jogadores da mesma vila n&atilde;o podem se enfrentar. Qualquer jogador poder&aacute; se tornar um Akatsuki.</span><div class="sep"></div>
    <div align="center">
    <table>
   	  <tr>
        	<td width="70" height="65" align="center"><img src="_img/vilas/reg_folha.jpg" onclick="document.getElementById('reg_vila1').checked=true" onmouseover="Tip('<div class=tooltip>Vila da Folha</div>')" onmouseout="UnTip()" /></td>
          	<td width="70" align="center"><img src="_img/vilas/reg_areia.jpg" onclick="document.getElementById('reg_vila2').checked=true" onmouseover="Tip('<div class=tooltip>Vila da Areia</div>')" onmouseout="UnTip()" /></td>
            <td width="70" align="center"><img src="_img/vilas/reg_som.jpg" onclick="document.getElementById('reg_vila3').checked=true" onmouseover="Tip('<div class=tooltip>Vila do Som</div>')" onmouseout="UnTip()" /></td>
            <td width="70" align="center"><img src="_img/vilas/reg_chuva.jpg" onclick="document.getElementById('reg_vila4').checked=true" onmouseover="Tip('<div class=tooltip>Vila da Chuva</div>')" onmouseout="UnTip()" /></td>
            <td width="70" align="center"><img src="_img/vilas/reg_nuvem.jpg" onclick="document.getElementById('reg_vila5').checked=true" onmouseover="Tip('<div class=tooltip>Vila da Nuvem</div>')" onmouseout="UnTip()" /></td>
            <td width="70" align="center"><img src="_img/vilas/reg_nevoa.jpg" onclick="document.getElementById('reg_vila6').checked=true" onmouseover="Tip('<div class=tooltip>Vila da N&eacute;voa</div>')" onmouseout="UnTip()" /></td>
            <td width="70" align="center"><img src="_img/vilas/reg_pedra.jpg" onclick="document.getElementById('reg_vila8').checked=true" onmouseover="Tip('<div class=tooltip>Vila da Pedra</div>')" onmouseout="UnTip()" /></td>
        </tr>
        <tr>
        	<td align="center"><input type="radio" id="reg_vila1" name="reg_vila" value="<?php echo $c->encode('1',$chaveuniversal); ?>" <?php if((!isset($_GET['village']))or(isset($_GET['village']))&&($_GET['village']==1)) echo 'checked="checked"'; ?>/></td>
            <td align="center"><input type="radio" id="reg_vila2" name="reg_vila" value="<?php echo $c->encode('2',$chaveuniversal); ?>" <?php if((isset($_GET['village']))&&($_GET['village']==2)) echo 'checked="checked"'; ?>/></td>
            <td align="center"><input type="radio" id="reg_vila3" name="reg_vila" value="<?php echo $c->encode('3',$chaveuniversal); ?>" <?php if((isset($_GET['village']))&&($_GET['village']==3)) echo 'checked="checked"'; ?>/></td>
            <td align="center"><input type="radio" id="reg_vila4" name="reg_vila" value="<?php echo $c->encode('4',$chaveuniversal); ?>" <?php if((isset($_GET['village']))&&($_GET['village']==4)) echo 'checked="checked"'; ?>/></td>
            <td align="center"><input type="radio" id="reg_vila5" name="reg_vila" value="<?php echo $c->encode('5',$chaveuniversal); ?>" <?php if((isset($_GET['village']))&&($_GET['village']==5)) echo 'checked="checked"'; ?>/></td>
            <td align="center"><input type="radio" id="reg_vila6" name="reg_vila" value="<?php echo $c->encode('6',$chaveuniversal); ?>" <?php if((isset($_GET['village']))&&($_GET['village']==6)) echo 'checked="checked"'; ?>/></td>
            <td align="center"><input type="radio" id="reg_vila8" name="reg_vila" value="<?php echo $c->encode('8',$chaveuniversal); ?>" <?php if((isset($_GET['village']))&&($_GET['village']==8)) echo 'checked="checked"'; ?>/></td>
        </tr>
    </table>
    </div>
    <div class="sep"></div>
    <input type="checkbox" id="reg_akatsuki" name="reg_akatsuki" /> Desejo iniciar o jogo como sendo um ninja renegado (membro da Akatsuki).
</fieldset>
<fieldset>
	<legend>Termos e Condi&ccedil;&otilde;es</legend>
    <input type="checkbox" id="reg_termos" name="reg_termos" /> Declaro que <b>li</b> e <b>aceito</b> os termos propostos, e que estou ciente das regras do jogo.
    <div class="sep"></div>
    <div align="center"><input type="submit" class="botao" id="subm" name="subm" value="Registrar" /></div>
</fieldset>
</form>
</div>
<div class="box_bottom"></div>
<?php
// PDO automatically frees result sets
?>