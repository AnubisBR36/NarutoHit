<?php
if(isset($_GET['del'])){
	mysql_query("DELETE FROM amigos WHERE amigoid=".$_GET['del']." AND usuarioid=".$db['id']);
}
$sqla=mysql_query("SELECT a.amigoid,u.usuario,u.nivel,u.config_atualizacoes FROM amigos a LEFT OUTER JOIN usuarios u ON a.amigoid=u.id WHERE a.usuarioid=".$db['id']." AND a.status='sim' ORDER BY nivel DESC, yens_fat DESC, vitorias DESC, derrotas ASC");
$dba=mysql_fetch_assoc($sqla);
?>
<div class="box_top">Atualizações</div>
<div class="box_middle">Últimos comandos realizados por você e seus amigos. Na página de configuração, você pode definir as permissões de suas atualizações (os ninjas que conseguirão visualizar estas informações).<div class="sep"></div>
	<?php if(isset($_GET['msg'])){
	switch($_GET['msg']){
		case 1: $msg='Amigo adicionado com sucesso! Aguarde confirmação do usuário.'; break;
		case 2: $msg='Solicitação de amigo foi aceita!'; break;
		case 3: $msg='Solicitação de amigo rejeitada!'; break;
	}
	echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>';
	} ?>
	<table width="100%" cellpadding="0" cellspacing="0">
        <?php if(mysql_num_rows($sqla)==0) echo '<div class="aviso">Nenhum amigo encontrado.<br />Para adicionar um amigo, visite seu perfil e clique em <b>Adicionar Amigo</b>.</div>'; else do{ ?>
        <tr class="tabela_dados" style="background:url(_img/gradient.jpg) repeat-y;">
        	<td style="background:#282828;text-align:center;" valign="top" width="14"><img src="_img/buddy.png" /></td>
        	<td style="padding-left:4px;padding-right:5px;"><a href="?p=view&amp;view=<?php echo strtolower($dba['usuario']); ?>"><?php echo $dba['usuario']; ?></a> <a href="?p=messages&amp;destiny=<?php echo $dba['usuario']; ?>"><img src="_img/letter.png" border="0" align="absmiddle" /></a></td>
            <td style="text-align:center;" width="20%" valign="top">Nível <?php echo $dba['nivel']; ?></td>
            <td style="text-align:center;" width="25%" valign="top"><a href="?p=friends&amp;del=<?php echo $dba['amigoid']; ?>">Apagar</a></td>
        </tr>
        <tr>
        	<td colspan="4"><div class="sep"></div></td>
        </tr>
        <?php } while($dba=mysql_fetch_assoc($sqla)); ?>
    </table>
    <?php if(mysql_num_rows($sqla)==0) echo '<div class="sep"></div>'; ?><div align="center" style="font-size:10px;"><b>Número de Amigos:</b> <?php if(mysql_num_rows($sqla)==0) echo 'Nenhum'; else if(mysql_num_rows($sqla)>0) echo mysql_num_rows($sqla).' amigo'; if(mysql_num_rows($sqla)>1) echo 's'; ?>.</div>
</div>
</div>
<div class="box_bottom"></div>
<?php
@mysql_free_result($sqla);
?>