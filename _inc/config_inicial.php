<script>
function show(div,opt){
	if(opt=='brasil')
		document.getElementById(div).style.display='block';
	else
		document.getElementById(div).style.display='none';
}
</script>
<?php
if(isset($_POST['inicial'])){
	$msg=str_replace(array('np','narutoplayers','nP','NP','narutoPLAYERS','NARUTOPLAYERS','Np','NarutoPlayers','hentai','porn','p,layers','naruto p','n.P','n.p','N.p','N.P','3.0','naruto .p','server02','server03','server04'),'<b><i>[conteúdo removido]</i></b>',$_POST['inicial_msg']);
	$nome=$_POST['inicial_nome'];
	$sexo=$_POST['inicial_sexo'];
	if(($sexo<>'m')&&($sexo<>'f')&&($sexo<>'')){ echo "<script>self.location='?p=home'</script>"; break; }
	$idade=$_POST['inicial_idade'];
	$pais=$_POST['inicial_pais'];
	$uf=$_POST['inicial_uf'];
	if(isset($_POST['inicial_pergunta'])) $pergunta=$_POST['inicial_pergunta']; else $pergunta=$db['config_pergunta'];
	if(isset($_POST['inicial_resposta'])) $resposta=$_POST['inicial_resposta']; else $resposta=$db['config_resposta'];
	mysql_query("UPDATE usuarios SET pessoal_nome='".$nome."', pessoal_sexo='".$sexo."', pessoal_idade='".$idade."', pessoal_pais='".$pais."', pessoal_uf='".$uf."', config_apresentacao='".$msg."', config_pergunta=$pergunta, config_resposta='".$resposta."', config_recuperacao=1 WHERE id=".$db['id']);
	echo "<script>self.location='?p=config&msg=1'</script>";
}
if(isset($_GET['msg'])){
	switch($_GET['msg']){
		case 1: $msg='Configurações atualizadas com sucesso!'; break;
		case 2: $msg='A troca da vila foi feita com sucesso!'; break;
	}
	echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>';
}
?>
<form method="post" action="?p=config" onsubmit="subm.value='Carregando...';subm.disabled=true;">
<input type="hidden" id="inicial" name="inicial" value="1" />
<fieldset><legend>Configurações Iniciais</legend>
	<span class="destaque">Usuário:</span><br />
    <input type="text" readonly="readonly" value="<?php echo ucfirst($db['usuario']); ?>" /><br />
    <span class="sub2">Seu nome de usuário (impossível alterar).</span><br /><div class="sep"></div>
    <span class="destaque">Email Cadastrado:</span><br />
    <input type="text" readonly="readonly" value="<?php $em=strlen($db['email']); $i=1; do{ echo '*'; $i++; } while($i<$em); ?>" /><br />
    <span class="sub2">Email cadastrado (impossível alterar).</span><?php if($db['config_recuperacao']==0){ ?><br /><div class="sep"></div>
    
    <span class="destaque">Pergunta Secreta:</span><br />
    <select id="inicial_pergunta" name="inicial_pergunta">
    	<option value="0"<?php if($db['config_pergunta']==0) echo ' selected="selected"'; ?>>-- Selecionar --</option>
        <option value="1"<?php if($db['config_pergunta']==1) echo ' selected="selected"'; ?>>Qual o nome de sua mãe?</option>
        <option value="2"<?php if($db['config_pergunta']==2) echo ' selected="selected"'; ?>>Qual o nome de seu pai?</option>
        <option value="3"<?php if($db['config_pergunta']==3) echo ' selected="selected"'; ?>>Qual o nome de seu animal de estimação?</option>
        <option value="4"<?php if($db['config_pergunta']==4) echo ' selected="selected"'; ?>>Qual a data de nascimento da sua tia?</option>
        <option value="5"<?php if($db['config_pergunta']==5) echo ' selected="selected"'; ?>>Qual o nome de sua escola?</option>
        <option value="6"<?php if($db['config_pergunta']==6) echo ' selected="selected"'; ?>>Qual sua cor preferida?</option>
    </select><br />
    <span class="sub2">Selecione uma pergunta secreta, utilizada para recuperar sua senha.</span><br /><div class="sep"></div>
    <span class="destaque">Resposta da Pergunta Secreta:</span><br />
    <input type="text" id="inicial_resposta" name="inicial_resposta" value="<?php echo $db['config_resposta']; ?>" /><br />
    <span class="sub2">Digite uma resposta para a pergunta (nunca se esqueça desta resposta, nem da pergunta, pois não será possível recuperar a senha sem elas).</span><?php } ?>
</fieldset>
<fieldset><legend>Texto de Apresentação</legend>
    <textarea id="inicial_msg" name="inicial_msg" style="width:100%;height:200px;"><?php echo $db['config_apresentacao']; ?></textarea>
    <span class="sub2">Texto que aparecerá na página do seu perfil.</span>
</fieldset>
<fieldset><legend>Dados Pessoais</legend>Preencha os dados abaixo para ajudar a narutoHIT a montar um quadro estatístico. Nenhum dos campos abaixo serão publicados na página de seu perfil.<div class="sep"></div>
	<span class="destaque">Nome Completo:</span><br />
    <input type="text" id="inicial_nome" name="inicial_nome" value="<?php echo $db['pessoal_nome']; ?>" /><br />
    <span class="sub2">Digite seu nome completo (abreviar nomes do meio).</span><br /><div class="sep"></div>
    <span class="destaque">Sexo:</span><br />
    <select id="inicial_sexo" name="inicial_sexo">
    	<option value=""<?php if($db['pessoal_sexo']=='') echo ' selected="selected"'; ?>>-- Selecionar --</option>
        <option value="m"<?php if($db['pessoal_sexo']=='m') echo ' selected="selected"'; ?>>Masculino</option>
        <option value="f"<?php if($db['pessoal_sexo']=='f') echo ' selected="selected"'; ?>>Feminino</option>
    </select><br />
    <span class="sub2">Selecione seu sexo (apenas para estatística).</span><br /><div class="sep"></div>
    <span class="destaque">Idade:</span><br />
    <input type="text" id="inicial_idade" name="inicial_idade" size="5" maxlength="3" value="<?php echo $db['pessoal_idade']; ?>" /><br />
    <span class="sub2">Digite sua idade.</span><br /><div class="sep"></div>
    <span class="destaque">País:</span><br />
    <select id="inicial_pais" name="inicial_pais" onblur="event.onchange" onfocus="event.onchange" onchange="show('uf',this.value)">
    	<option value=""<?php if($db['pessoal_pais']=='') echo ' selected="selected"'; ?>>-- Selecionar --</option>
        <option value="alemanha"<?php if($db['pessoal_pais']=='alemanha') echo ' selected="selected"'; ?>>Alemanha</option>
        <option value="argentina"<?php if($db['pessoal_pais']=='argentina') echo ' selected="selected"'; ?>>Argentina</option>
        <option value="brasil"<?php if($db['pessoal_pais']=='brasil') echo ' selected="selected"'; ?>>Brasil</option>
        <option value="canada"<?php if($db['pessoal_pais']=='canada') echo ' selected="selected"'; ?>>Canadá</option>
        <option value="espanha"<?php if($db['pessoal_pais']=='espanha') echo ' selected="selected"'; ?>>Espanha</option>
        <option value="eua"<?php if($db['pessoal_pais']=='eua') echo ' selected="selected"'; ?>>EUA</option>
        <option value="franca"<?php if($db['pessoal_pais']=='franca') echo ' selected="selected"'; ?>>França</option>
        <option value="inglaterra"<?php if($db['pessoal_pais']=='inglaterra') echo ' selected="selected"'; ?>>Inglaterra</option>
        <option value="italia"<?php if($db['pessoal_pais']=='italia') echo ' selected="selected"'; ?>>Itáia</option>
        <option value="japao"<?php if($db['pessoal_pais']=='japao') echo ' selected="selected"'; ?>>Japão</option>
        <option value="mexico"<?php if($db['pessoal_pais']=='eua') echo ' selected="selected"'; ?>>México</option>
        <option value="portugal"<?php if($db['pessoal_pais']=='portugal') echo ' selected="selected"'; ?>>Portugal</option>
        <option value="outro"<?php if($db['pessoal_pais']=='outro') echo ' selected="selected"'; ?>>Outro</option>
    </select><br />
    <span class="sub2">Selecione o país em que reside (apenas para estatística).</span>
    <div id="uf" style="display:<?php if($db['pessoal_pais']=='brasil') echo 'block'; else echo 'none'; ?>">
    	<div class="sep"></div>
    	<span class="destaque">Estado (UF):</span><br />
    	<select id="inicial_uf" name="inicial_uf">
            <option value=""<?php if($db['pessoal_uf']=='') echo ' selected="selected"'; ?>>-- Selecionar --</option>
            <option value="ac"<?php if($db['pessoal_uf']=='ac') echo ' selected="selected"'; ?>>Acre</option>
            <option value="al"<?php if($db['pessoal_uf']=='al') echo ' selected="selected"'; ?>>Alagoas</option>
            <option value="am"<?php if($db['pessoal_uf']=='am') echo ' selected="selected"'; ?>>Amazonas</option>
            <option value="ap"<?php if($db['pessoal_uf']=='ap') echo ' selected="selected"'; ?>>Amapá</option>
            <option value="ba"<?php if($db['pessoal_uf']=='ba') echo ' selected="selected"'; ?>>Bahia</option>
            <option value="ce"<?php if($db['pessoal_uf']=='ce') echo ' selected="selected"'; ?>>Ceará</option>
            <option value="df"<?php if($db['pessoal_uf']=='df') echo ' selected="selected"'; ?>>Distrito Federal</option>
            <option value="es"<?php if($db['pessoal_uf']=='es') echo ' selected="selected"'; ?>>Espírito Santo</option>
            <option value="go"<?php if($db['pessoal_uf']=='go') echo ' selected="selected"'; ?>>Goiás</option>
            <option value="ma"<?php if($db['pessoal_uf']=='ma') echo ' selected="selected"'; ?>>Maranhão</option>
            <option value="mt"<?php if($db['pessoal_uf']=='mt') echo ' selected="selected"'; ?>>Mato Grosso</option>
            <option value="ms"<?php if($db['pessoal_uf']=='ms') echo ' selected="selected"'; ?>>Mato Grosso do Sul</option>
            <option value="mg"<?php if($db['pessoal_uf']=='mg') echo ' selected="selected"'; ?>>Minas Gerais</option>
            <option value="pa"<?php if($db['pessoal_uf']=='pa') echo ' selected="selected"'; ?>>Pará</option>
            <option value="pb"<?php if($db['pessoal_uf']=='pb') echo ' selected="selected"'; ?>>Paraíba</option>
            <option value="pr"<?php if($db['pessoal_uf']=='pr') echo ' selected="selected"'; ?>>Paraná</option>
            <option value="pe"<?php if($db['pessoal_uf']=='pe') echo ' selected="selected"'; ?>>Pernambuco</option>
            <option value="pi"<?php if($db['pessoal_uf']=='pi') echo ' selected="selected"'; ?>>Piauí</option>
            <option value="rj"<?php if($db['pessoal_uf']=='rj') echo ' selected="selected"'; ?>>Rio de Janeiro</option>
            <option value="rn"<?php if($db['pessoal_uf']=='rn') echo ' selected="selected"'; ?>>Rio Grande do Norte</option>
            <option value="ro"<?php if($db['pessoal_uf']=='ro') echo ' selected="selected"'; ?>>Rondônia</option>
            <option value="rs"<?php if($db['pessoal_uf']=='rs') echo ' selected="selected"'; ?>>Rio Grande do Sul</option>
            <option value="rr"<?php if($db['pessoal_uf']=='rr') echo ' selected="selected"'; ?>>Roraima</option>
            <option value="sc"<?php if($db['pessoal_uf']=='sc') echo ' selected="selected"'; ?>>Santa Catarina</option>
            <option value="se"<?php if($db['pessoal_uf']=='se') echo ' selected="selected"'; ?>>Sergipe</option>
            <option value="sp"<?php if($db['pessoal_uf']=='sp') echo ' selected="selected"'; ?>>São Paulo</option>
            <option value="to"<?php if($db['pessoal_uf']=='to') echo ' selected="selected"'; ?>>Tocantins</option>
        </select><br />
    	<span class="sub2">Selecione o estado em que reside (apenas para estatística).</span>
    </div>
	<div class="sep"></div>
    <div align="center"><input type="submit" id="subm" name="subm" class="botao" value="Salvar Alterações" /></div>
</fieldset>
</form>