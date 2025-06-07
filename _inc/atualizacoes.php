<?php
$sqltexto="SELECT texto,hora FROM atualizacoes WHERE usuarioid=".$db['id'];
$sqltexto.=$sqlupdate;
$sqltexto.=" ORDER BY id DESC LIMIT 10";
$sqlu=mysql_query($sqltexto);
$dbu=mysql_fetch_assoc($sqlu);
//mysql_query("INSERT INTO atualizacoes (usuarioid,hora) VALUES (3,".time(date('Y-m-d H:i:s')).")");
require_once('formatar_tempo.php');
?>
<div class="box_top">Atualizações</div>
<div class="box_middle">Últimos 10 comandos realizados por você e seus amigos. Na página de configuração, você pode definir as permissões de suas atualizações (os ninjas que conseguirão visualizar estas informações).<div class="sep"></div>
	<table width="100%" cellpadding="0" cellspacing="0">
        <?php $cor='#323232'; if(mysql_num_rows($sqlu)==0) echo '<div class="aviso">Nenhuma atualização encontrada!</div><div class="sep"></div>'; else do{ ?>
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
        <?php if($cor=='#323232') $cor='#2C2C2C'; else $cor='#323232'; } while($dbu=mysql_fetch_assoc($sqlu)); ?>
        <tr>
        	<td colspan="3" align="center"><input type="button" class="botao" value="Mais Atualizações" onclick="location.href='?p=updates'" /></td>
        </tr>
    </table>
</div>
<div class="box_bottom"></div>
<?php
@mysql_free_result($sqlu);
?>