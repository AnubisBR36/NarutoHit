<?php
if(isset($_GET['action'])){
	$sqlv=mysql_query("SELECT o.nivel, m.orgid, count(m.id) conta FROM membros m LEFT OUTER JOIN organizacoes o ON m.orgid=o.id WHERE m.status='sim' AND m.orgid=".$db['orgid']." GROUP BY m.orgid");
	$dbv=mysql_fetch_assoc($sqlv);
	if($dbv['conta']>=(5+($dbv['nivel']*5))){ echo "<script>self.location='?p=myorg&msg=1'</script>"; break; }
	$action=$c->decode($_GET['action'],$chaveuniversal);
	$ex=explode(',',$action);
	vn($ex[1]);
	switch($ex[0]){
		case 'aceitar': $sqlu=mysql_query("SELECT usuarioid FROM membros WHERE id=".$ex[1]); $dbu=mysql_fetch_assoc($sqlu); if(mysql_num_rows($sqlu)>0){ mysql_query("DELETE FROM membros WHERE usuarioid=".$dbu['usuarioid']." AND id<>".$ex[1]); mysql_query("UPDATE usuarios SET orgid=".$db['orgid']." WHERE id=".$dbu['usuarioid']); mysql_query("UPDATE membros SET status='sim' WHERE id=".$ex[1]); mysql_query("INSERT INTO mensagens (data, origem, destino, assunto, msg) VALUES ('".date('Y-m-d H:i:s')."', 0, ".$ex[2].", 'Solicitação do Clã foi Aceita!', 'Parabéns, o Administrador do clã aceitou sua solicitação de ingresso.<br />Visite a sede de seu clã agora mesmo, clicando <a href=?p=myorg style=color:#444444;>aqui</a>.')"); echo "<script>self.location='?p=addorg&msg=1'</script>"; } break;
		case 'rejeitar': mysql_query("DELETE FROM membros WHERE id=".$ex[1]); mysql_query("INSERT INTO mensagens (data, origem, destino, assunto, msg) VALUES ('".date('Y-m-d H:i:s')."', 0, ".$ex[2].", 'Solicitação do Clã Rejeitada!', 'Infelizmente o Administrador do clã ao qual você solicitou acesso rejeitou seu ingresso.<br />Tente acesso à outro clã, ou evolua um pouco mais e solicite um novo ingresso.')"); echo "<script>self.location='?p=addorg&msg=2'</script>"; break;
	}
}
$sqlv=mysql_query("SELECT posicao FROM membros WHERE usuarioid=".$db['id']." AND orgid=".$db['orgid']." AND status='sim'");
$dbv=mysql_fetch_assoc($sqlv);
if($dbv['posicao']>2){ echo "<script>self.location='?p=myorg'</script>"; break; }
$sqlm=mysql_query("SELECT m.*,u.usuario,u.nivel niveluser,u.timestamp FROM membros m LEFT OUTER JOIN usuarios u ON m.usuarioid=u.id WHERE m.status='nao' AND m.orgid=".$db['orgid']." ORDER BY id ASC");
$dbm=mysql_fetch_assoc($sqlm);
?>
<div class="box_top">Recrutar Membros</div>
<div class="box_middle"><div align="center"><a href="?p=myorg">Informações</a> | <a href="?p=configorg">Configurar</a> | <a href="?p=addorg">Recrutar</a><?php /* | <a href="?p=donateorg">Doar Yens</a>*/ ?></div><div class="sep"></div>Abaixo estão os ninjas que requisitaram acesso ao seu clã. Você pode aceitar ou rejeitar a entrada de cada um deles.<div class="sep"></div>
	<?php if(isset($_GET['msg'])){
		switch($_GET['msg']){
			case 1: $msg='Membro foi aceito!'; break;
			case 2: $msg='Membro foi rejeitado!'; break;
		}
	echo '<div class="aviso">'.$msg.'</div><div class="sep"></div>';
	}
	?>
	<table width="100%" cellpadding="0" cellspacing="1">
        <?php $i=1; if(mysql_num_rows($sqlm)==0) echo '<tr><td colspan="5"><div class="aviso">Nenhum membro neste clã.</div></td></tr>'; else do{ ?>
        <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
        	<td width="5%"><?php echo $i; ?></td>
            <td style="text-align:left"><a href="?p=view&amp;view=<?php echo strtolower($dbm['usuario']); ?>"><?php echo $dbm['usuario']; ?></a><br /><span class="sub2">Nível <?php echo $dbm['niveluser']; ?></span></td>
            <td><b>Status</b><br /><span class="sub2"><?php if($dbm['timestamp']>=(time()-900)) echo '<b>Online</b>'; else echo 'Offline'; ?></span></td>
            <td>Membro</td>
            <td><?php if($i==1) echo '<a href="?p=addorg&action='.$c->encode(('aceitar,'.$dbm['id'].','.$dbm['usuarioid']),$chaveuniversal).'">Aceitar</a> | <a href="?p=addorg&action='.$c->encode(('rejeitar,'.$dbm['id']).','.$dbm['usuarioid'],$chaveuniversal).'">Rejeitar</a>'; ?></td>
        </tr>
        <?php $i++; } while($dbm=mysql_fetch_assoc($sqlm)); ?>
        <tr>
        	<td colspan="5"><div class="sep"></div><div class="sub2">Total: <b><?php echo mysql_num_rows($sqlm); ?> requisiç<?php if(mysql_num_rows($sqlm)==1) echo 'ão'; else echo 'ões'; ?></b> para acesso ao clã</div></td>
        </tr>
    </table>
</div>
<div class="box_bottom"></div>