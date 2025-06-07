<?php
$sqlf=mysql_query("SELECT a.amigoid,u.config_atualizacoes FROM amigos a LEFT OUTER JOIN usuarios u ON a.amigoid=u.id WHERE a.usuarioid=".$db['id']);
$i=0;
$sqlupdate=''; 
while($dbf=mysql_fetch_assoc($sqlf)){
	if($dbf['config_atualizacoes']=='sim') $sqlupdate.=' OR usuarioid='.$dbf['amigoid'];
}
$sqltexto="SELECT texto,hora FROM atualizacoes WHERE usuarioid=".$db['id'];
$sqltexto.=$sqlupdate;
$sqltexto.=" ORDER BY id DESC LIMIT 50";
$sqlu=mysql_query($sqltexto);
$dbu=mysql_fetch_assoc($sqlu);
require_once('formatar_tempo.php');
?>
<div class="box_top">Atualizações</div>
<div class="box_middle">Últimos comandos realizados por você e seus amigos. Na página de configuração, você pode definir as permissões de suas atualizações (os ninjas que conseguirão visualizar estas informações).<div class="sep"></div>
	<table width="100%" cellpadding="0" cellspacing="0">
        <?php if(mysql_num_rows($sqlu)==0) echo '<div class="aviso">Nenhuma atualização encontrada!</div>'; else do{ ?>
        <tr class="tabela_dados" style="background:url(_img/gradient.jpg) repeat-y;">
        	<td style="background:#282828;text-align:center;" valign="top"><img src="_img/refresh.png" /></td>
        	<td style="padding-left:4px;padding-right:5px;"><?php echo $dbu['texto']; ?></td>
            <td style="text-align:center;" width="25%" valign="top">
            	<?php
				$hora=formatar_tempo($dbu['hora']);
				echo $hora;
				?>
            </td>
        </tr>
        <tr>
        	<td colspan="3"><div class="sep"></div></td>
        </tr>
        <?php } while($dbu=mysql_fetch_assoc($sqlu)); ?>
    </table>
</div>
<div class="box_bottom"></div>
<?php
@mysql_free_result($sqlf);
@mysql_free_result($sqlu);
?>