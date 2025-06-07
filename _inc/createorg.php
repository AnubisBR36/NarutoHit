<?php if($db['orgid']>0){ echo "<script>self.location='?p=myorg'</script>"; return; } ?>
<?php if($db['nivel']<1){ ?>
<div class="box_top">Criar Organização</div>
<div class="box_middle"><div class="aviso">Seu nível é muito baixo para ser líder de uma organização.<br />Volte quando estiver no nível 5.</div></div>
<div class="box_bottom"></div>
<?php } else { ?>
<?php
	if(isset($_POST['org'])){
		$nome=$_POST['org_nome'];
		$sigla=substr(strtoupper($_POST['org_sigla']),0,4);
		mysql_query("INSERT INTO organizacoes (vila,nome,sigla,data,liderid) VALUES (".$db['vila'].",'$nome','$sigla','".date('Y-m-d H:i:s')."',".$db['id'].")");
		$orgid=mysql_insert_id();
		mysql_query("INSERT INTO membros (orgid,usuarioid,posicao,rank,doado,status) VALUES ($orgid,".$db['id'].",1,'Líder',0,'sim')");
		mysql_query("UPDATE usuarios SET orgid=$orgid WHERE id=".$db['id']);
		echo "<script>self.location='?p=myorg'</script>";
	}
?>
<div class="box_top">Criar Organização</div>
<div class="box_middle">Para criar uma organização, primeiramente é necessário ter espírito de liderança. Um bom líder conseguirá administrar uma boa organização, e assim, conquistar os melhores ninjas! Além disso, também é preciso ter uma boa reserva de yens! Os custos para a criação são de 3.000,00 yens.<div class="sep"></div><div style="padding-left:5px;background:url(_img/gradient.jpg) repeat-y;font-weight:bold;color:#FFFFAA;"><img src="_img/yens.png" width="14" height="14" align="absmiddle" /> Meus yens: <?php echo number_format($db['yens'],2,',','.'); ?> yens</div><div class="sep"></div>
	<form method="post" action="?p=createorg" onsubmit="subm.value='Carregando...';subm.disabled=true;">
    <input type="hidden" id="org" name="org" value="1">
    <fieldset><legend>Criar Organização</legend>
    	<span class="destaque">Nome da Organização:</span><br />
        <input type="text" id="org_nome" name="org_nome"><br />
        <span class="sub2">Digite o nome da nova organização.</span><br /><br />
        <span class="destaque">Sigla:</span><br />
        <input type="text" id="org_sigla" name="org_sigla" maxlength="4" size="6"><br />
        <span class="sub2">Digite uma sigla de até 4 caracteres para a organização.</span>
        <div class="sep"></div>
        <div align="center"><input type="submit" id="subm" name="subm" class="botao" value="Criar Organização" /></div>
    </fieldset>
    </form>
</div>
<div class="box_bottom"></div>
<?php } ?>