<?php
$sqla=mysql_query("SELECT a.amigoid,u.usuario,u.nivel,u.config_atualizacoes,u.timestamp FROM amigos a LEFT OUTER JOIN usuarios u ON a.amigoid=u.id WHERE a.usuarioid=".$db['id']." AND a.status='sim' ORDER BY nivel DESC, yens_fat DESC, vitorias DESC, derrotas ASC");
$dba=mysql_fetch_assoc($sqla);
$sqlupdate='';
$timeout=time()-900;
?>
<div class="box2_top">Lista de Amigos</div>
<div class="box2_middle"><?php if(mysql_num_rows($sqla)==0) echo '<div class="sub2">Nenhum amigo encontrado.<br />Para adicionar um amigo, visite seu perfil e clique em <b>Adicionar Amigo</b>.</div>'; else { ?>
	<table width="100%" cellspacing="0" cellpadding="0">
        <?php do{ if($dba['config_atualizacoes']=='sim') $sqlupdate.=' OR usuarioid='.$dba['amigoid']; ?>
        <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
        	<td><img src="_img/<?php if($dba['timestamp']>=$timeout) echo 'online'; else echo 'offline'; ?>.png" width="14" height="14" style="margin-right:2px;" /></td>
        	<td style="text-align:left;padding-left:2px;"><a href="?p=view&amp;view=<?php echo strtolower($dba['usuario']); ?>"><?php echo $dba['usuario']; ?></a></td>
            <td><b>[<?php echo $dba['nivel']; ?>]</b></td>
        </tr>
        <?php } while($dba=mysql_fetch_assoc($sqla)); ?>
    </table>
    <div class="sep"></div>
    <div class="sub2"><a href="?p=friends">Gerenciar Lista</a></div>
    <?php } ?>
</div>
<div class="box2_bottom"></div>
<?php
@mysql_free_result($sqla);
?>