<?php
if(isset($_POST['rec_usuario'])){
	$erro=0;
	if($_POST['rec_email']=='') $erro=1;
	if($_POST['rec_usuario']=='') $erro=2;
	if($erro==0){
		$sqlr=mysql_query("SELECT id, senha, config_pergunta, config_resposta FROM usuarios WHERE usuario='".$_POST['rec_usuario']."' AND email='".$_POST['rec_email']."'");
		$dbr=mysql_fetch_assoc($sqlr);
		if($dbr['senha']=='') $erro=3;
		if($dbr['config_pergunta']<>$_POST['rec_pergunta']) $erro=4;
		if($dbr['config_resposta']<>$_POST['rec_resposta']) $erro=4;
		if($erro==0){
			$link='http://www.narutohit.net/newpass.php?user='.strtolower($_POST['rec_usuario']).'&token='.md5($dbr['id']);
			$mensagem='<div align="center"><img src="http://www.narutohit.net/_img/support/minilogo2.jpg" style="border-bottom:1px solid #DDDDDD" /><br /><br /><b>Mensagem Importante</b><br />Você solicitou uma nova senha para sua conta.<br />Utilize o link abaixo caso queira realmente uma nova senha.<br /><br /><b><a href="'.$link.'">'.$link.'</a></b><br /><span style="font-size:10px;">Caso você não tenha feito a solicitação, apenas ignore este email.</span><br /><br /><b><span style="color:#CC0000">A equipe narutoHIT lhe deseja um bom jogo!</span></b><br /><br />Para mais informações, visite nossa <a href="http://www.orkut.com.br/Main#Community?cmm=95565573">comunidade no orkut</a>.<br /><br />Atenciosamente, equipe narutoHIT.</div>';
			$assunto='Solicitar Nova Senha';
			$remetente='narutoHIT <contato@narutohit.net>';
			$headers = implode ( "\n",array ( "From: $remetente","Subject: ".$assunto,"Return-Path: $remetente","MIME-Version: 1.0","X-Priority: 3","Content-Type: text/html" ) );
			mail($_POST['rec_email'],'',$mensagem,$headers);
		}
	}
	echo "<script>self.location='?p=recover&msg=".$erro."'</script>"; break;
}
?>
<div class="box_top">Recuperar Senha</div>
<div class="box_middle">Devido à criptografia usada em nosso sistema, não é possível lhe enviar a senha que você utilizava. Mas podemos criar uma nova senha! Para isso, preencha os dois campos abaixo com seu nome de usuário e email utilizado no momento do registro. É possível que nossa mensagem seja transferida para o lixo eletrônico de sua caixa de mensagens.<div class="sep"></div>
    <?php if(isset($_GET['msg'])){
		switch($_GET['msg']){
			case 0: $msg='Sua senha foi enviada para o email informado!'; break;
			case 1: $msg='Digite um <b>EMAIL</b> válido.'; break;
			case 2: $msg='Digite um <b>NOME DE USUÁRIO</b> válido.'; break;
			case 3: $msg='Nenhum registro encontrado com os dados informados.'; break;
			case 4: $msg='Pergunta ou resposta secreta não confere.'; break;
		}
	echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>'; } ?>
    <fieldset><legend>Formulário de Recuperação de Senha</legend>
    <form method="post" action="?p=recover" style="background:url(_img/recover.jpg) no-repeat right top;" onsubmit="rec_botao.value='Processando...';rec_botao.disabled=true;">
    	<div class="destaque">Nome do Usuário:</div>
    	<input type="text" id="rec_usuario" name="rec_usuario" onfocus="className='input'" onblur="className=''" /><br />
        <span class="sub2">Digite o nome de usuário (login) da sua conta.</span><br /><br />
        <div class="destaque">Email:</div>
        <input type="text" id="rec_email" name="rec_email" onfocus="className='input'" onblur="className=''" /><br />
        <span class="sub2">Digite o endereço de email cadastrado no sistema.</span><br /><br />
        <div class="destaque">Pergunta Secreta:</div>
    	<select id="rec_pergunta" name="rec_pergunta">
    		<option value="0" selected="selected">-- Selecionar --</option>
        	<option value="1">Qual o nome de sua mãe?</option>
        	<option value="2">Qual o nome de seu pai?</option>
        	<option value="3">Qual o nome de seu animal de estimação?</option>
        	<option value="4">Qual a data de nascimento da sua tia?</option>
        	<option value="5">Qual o nome de sua escola?</option>
        	<option value="6">Qual sua cor preferida?</option>
    	</select><br />
        <span class="sub2">Digite o nome de usuário (login) da sua conta.</span><br /><br />
        <div class="destaque">Resposta da Pergunta Secreta:</div>
    	<input type="text" id="rec_resposta" name="rec_resposta" onfocus="className='input'" onblur="className=''" /><br />
        <span class="sub2">Digite o nome de usuário (login) da sua conta.</span>
        <div class="sep"></div>
        <div align="center"><input type="submit" class="botao" name="rec_botao" value="Recuperar Senha" /></div>
    </form>
    </fieldset>
</div>
<div class="box_bottom"></div>
<?php
@mysql_free_result($sqlr);
?>