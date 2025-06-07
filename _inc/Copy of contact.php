<?php
if(isset($_POST['con_nome'])){
	$erro=0;
	if($_POST['con_assunto']=='') $erro=3;
	if($_POST['con_email']=='') $erro=2;
	if($_POST['con_nome']=='') $erro=1;
	if($erro==0){
		if(isset($db['id'])) $user=$db['usuario']; else $user='-';
		$mensagem=nl2br($_POST['con_msg']);
		$assunto=ucfirst($_POST['con_assunto']);
		$remetente=$_POST['con_email'];
		mysql_query("INSERT INTO contato (nome, email, assunto, usuario, mensagem) VALUES ('".$_POST['con_nome']."', '$remetente', '$assunto', '$user', '$mensagem')");
		//mail('contato@narutohit.net',$assunto,$mensagem,$headers);
		echo "<script>self.location='?p=contact&msg=0'</script>"; break;
	} else { echo "<script>self.location='?p=contact&&name=".$_POST['con_nome']."&email=".$_POST['con_email']."&subject=".$_POST['con_assunto']."&msg=".$erro."'</script>"; break; }
}
?>
<script language="javascript">
function count(campo,resultado,maximo){
	qt=document.getElementById(campo).value.length;
	if(qt>=maximo){
		document.getElementById(campo).value=document.getElementById(campo).value.substring(0,maximo);
	}
	document.getElementById(resultado).innerHTML=qt;
}
</script>
<div class="box_top">Contato</div>
<div class="box_middle">Entre em contato com nossa equipe, preenchendo o formulário abaixo com os dados solicitados. É extremamente importante que seja inserido um email válido, pois a resposta de sua mensagem será enviada para ele. Não utilize esta página para envio de comprovantes de pagamento de VIP. Existe um outro formulário específico para isto <a href="?p=vipform">neste link</a>.
	<div class="sep"></div>
    <?php if(isset($_GET['msg'])){
		switch($_GET['msg']){
			case 0: $msg='Mensagem enviada com sucesso! Responderemos assim que possível.'; break;
			case 1: $msg='Digite seu <b>NOME</b>.'; break;
			case 2: $msg='<b>EMAIL</b> informado é inválido.'; break;
			case 3: $msg='Selecione um <b>ASSUNTO</b> para a mensagem.'; break;
		}
	echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>'; } ?>
    <fieldset><legend>Formulário de Contato</legend>
    	<form method="post" action="?p=contact" style="background:url(_img/contact.jpg) no-repeat right top;" onsubmit="con_submit.value='Carregando...';con_submit.disabled=true;">
        	<input type="hidden" id="con_usuario" name="con_usuario" value="<?php if(isset($_COOKIE['logado'])) echo $db['usuario']; ?>">
        	<span class="destaque">Nome:</span><br />
        	<input type="text" id="con_nome" name="con_nome" onFocus="className='input'" onBlur="className=''" <?php if(isset($_GET['name'])) echo 'value="'.$_GET['name'].'"'; ?>/>
        	<br />
            <span class="sub2">Digite seu nome no campo acima.</span><br /><br />
            <span class="destaque">Email:</span><br />
          <input type="text" id="con_email" name="con_email" onfocus="className='input'" onblur="className=''" <?php if(isset($_GET['email'])) echo 'value="'.$_GET['email'].'"'; ?>/><br />
            <span class="sub2">A resposta desta mensagem<br />será enviada no email informado.</span><br /><br />
            <span class="destaque">Assunto:</span><br />
          <select id="con_assunto" name="con_assunto" onfocus="className='input'" onblur="className=''">
           	<option value="" <?php if((isset($_GET['subject']))&&($_GET['subject']=='')) echo 'selected="selected"'; ?>>-- Selecione o Assunto --</option>
            <option value="parceria" <?php if((isset($_GET['subject']))&&($_GET['subject']=='parceria')) echo 'selected="selected"'; ?>>Parceria</option>
            <option value="publicidade"<?php if((isset($_GET['subject']))&&($_GET['subject']=='publicidade')) echo 'selected="selected"'; ?>>Publicidade</option>
            <option value="bug"<?php if((isset($_GET['subject']))&&($_GET['subject']=='bug')) echo 'selected="selected"'; ?>>Reportar Bug</option>
          </select><br />
            <span class="sub2">Selecione o assunto da mensagem.</span><br /><br />
            <span class="destaque">Mensagem:</span>
            <div class="sub2" style="float:right;">Digitados <span id="contaletras">0</span> de 300 caracteres permitidos.</div><br />
          	<textarea id="con_msg" name="con_msg" onkeyup="count(this.name,'contaletras',300)" onkeydown="event.onkeyup" onfocus="className='input'" onblur="className=''" style="width:100%;" rows="5" ></textarea><br />
            <span class="sub2">Digite a mensagem.<br />
            <div class="sep"></div>
            <div align="center"><input type="submit" name="con_submit" class="botao" value="Enviar Mensagem" /></div>
        </form>
    </fieldset>
</div>
<div class="box_bottom"></div>
<script>document.forms[0].con_nome.focus()</script>