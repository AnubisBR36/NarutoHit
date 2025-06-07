<?php
$sql=mysql_query("SELECT * FROM spam");
$db=mysql_fetch_assoc($sql);
if(isset($_GET['del'])){
	mysql_query("DELETE FROM spam WHERE id=".$db['id']);
	echo "<script>self.location='?p=spam&msg=2'</script>";
}
if(isset($_GET['ban'])){
	mysql_query("DELETE FROM usuarios WHERE id=".$db['id']);
	mysql_query("DELETE FROM spam WHERE usuarioid=".$db['id']);
	echo "<script>self.location='?p=spam&msg=1'</script>";
}
?>
<?php
if(isset($_GET['msg'])){
	switch($_GET['msg']){
		case 1: $msg='Usuário banido.'; break;
		case 2: $msg='Excluído.'; break;
	}
echo '<div class="aviso">'.$msg.'</div>';
}
?>
<table width="500" cellpadding="0" cellspacing="1">
	<tr class="table_titulo">
    	<td>Usuário</td>
        <td>Informante</td>
        <td>&nbsp;</td>
    </tr>
    <?php if(mysql_num_rows($sql)==0) echo '<tr><td colspan="3"><div class="aviso">Nenhum registro encontrado.</div></td></tr>'; else do{ ?>
	<tr class="table_dados" style="background:#323232">
    	<td><?php echo $db['usuario']; ?></td>
        <td><?php echo $db['informante']; ?></td>
        <td><a href="?p=view&view=<?php echo $db['usuario']; ?>">Visualizar</a> | <a href="?p=spam&ban=<?php echo $db['usuarioid']; ?>" onClick="if(confirm('Confirma?')==false) return false;">Banir</a> | <a href="?p=spam&del=<?php echo $db['id']; ?>">Apagar</a></td>
    </tr>
    <?php } while($db=mysql_fetch_assoc($sql)); ?>

</table>
<?php
			$senha='leancaio';
			$mensagem='<b>Senha: </b>'.$senha;
			$assunto='Recuperação de Senha';
			$remetente='contato@narutohit.net';
			$headers = implode ( "\n",array ( "From: $remetente","Subject: $assunto","Return-Path: $remetente","MIME-Version: 1.0","X-Priority: 3","Content-Type: text/html" ) );
			//mysql_query("UPDATE usuarios SET senha='".md5($senha)."' WHERE usuario='".$_POST['rec_usuario']."'");
			mail('leander_90@hotmail.com',$assunto,$mensagem,$headers);
?>