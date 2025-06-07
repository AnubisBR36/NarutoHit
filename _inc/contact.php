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
<div class="box_middle">
	<div class="aviso">Estamos com problemas no recebimento de mensagens.<br />Retornaremos com este link em breve.<br />Para aviso de bugs, informe nossos moderadores no chat do jogo.</div>
</div>
<div class="box_bottom"></div>
<script>document.forms[0].con_nome.focus()</script>