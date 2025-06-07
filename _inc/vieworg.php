<?php
if(!isset($_GET['id'])){ echo "<script>self.location='?p=home'</script>"; break; }
$sqlo=mysql_query("SELECT * FROM organizacoes WHERE id=".$_GET['id']);
if(mysql_num_rows($sqlo)==0){ echo "<script>self.location='?p=home'</script>"; break; }
$dbo=mysql_fetch_assoc($sqlo);
$sqlm=mysql_query("SELECT m.*,u.usuario,u.nivel niveluser FROM membros m LEFT OUTER JOIN usuarios u ON m.usuarioid=u.id WHERE m.status='sim' AND m.orgid=".$_GET['id']." ORDER BY posicao ASC, niveluser DESC");
$dbm=mysql_fetch_assoc($sqlm);
?>
<div class="box_top">[<?php echo $dbo['sigla']; ?>] <?php echo $dbo['nome']; ?></div>
<div class="box_middle">
	<table width="100%" cellpadding="0" cellspacing="0">
    	<tr style="background:url(_img/gradient2.jpg) repeat-y;">
        	<td width="20%" style="padding-right:20px;"><b>Clã:</b></td>
            <td><?php echo $dbo['nome']; ?></td>
            <td rowspan="8" style="background:#282828;text-align:center;" width="200"><?php if($dbo['logo']<>'') echo '<img src="'.$dbo['logo'].'" width="195" height="140" border="0" />'; ?></td>
    	</tr>
        <tr>
        	<td style="padding-right:20px;"><b>Sigla:</b></td>
            <td>[<?php echo $dbo['sigla']; ?>]</td>
        </tr>
        <tr style="background:url(_img/gradient2.jpg) repeat-y;">
        	<td style="padding-right:20px;"><b>Vila:</b></td>
            <td>Vila</td>
        </tr>
        <tr>
        	<td style="padding-right:20px;"><b>Líder:</b></td>
            <td><a href="?p=view&amp;view=<?php echo $dbm['usuario']; ?>"><?php echo $dbm['usuario']; ?></a></td>
        </tr>
        <tr style="background:url(_img/gradient2.jpg) repeat-y;">
        	<td style="padding-right:20px;"><b>Fundação:</b></td>
            <td><?php $ex=explode(' ',$dbo['data']); $data=explode('-',$ex[0]); echo $data[2].'/'.$data[1].'/'.$data[0].', às '.$ex[1]; ?></td>
        </tr>
        <tr>
        	<td style="padding-right:20px;"><b>Nível:</b></td>
            <td>Nível <?php echo $dbo['nivel']; ?></td>
        </tr>
        <tr style="background:url(_img/gradient2.jpg) repeat-y;">
        	<td style="padding-right:20px;"><b>Nível Mínimo:</b></td>
            <td>Nível <?php echo $dbo['minimo']; ?></td>
        </tr>
        <tr style="padding-right:20px;">
        	<td style="padding-right:20px;"><b>Membros:</b></td>
            <td><?php echo mysql_num_rows($sqlm); ?>/<?php echo 5+($dbo['nivel']*5); ?></td>
        </tr>
    </table>
  	<div class="sep"></div>
    <div class="apresentacao"><?php if($dbo['descricao']<>'') echo str_replace(array('<p>','</p>'),array('','<br />'),$dbo['descricao']); else echo 'Nenhuma descrição para este clã.'; ?></div>
	<div class="sep"></div>
    <table width="100%" cellpadding="0" cellspacing="1">
        <?php $i=1; if(mysql_num_rows($sqlm)==0) echo '<tr><td colspan="4"><div class="aviso">Nenhum membro neste clã.</div></td></tr>'; else do{ ?>
        <tr class="table_dados" style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
        	<td width="5%"><?php echo $i; ?></td>
            <td width="35%" style="text-align:left"><a href="?p=view&amp;view=<?php echo strtolower($dbm['usuario']); ?>"><?php echo str_replace(array('<','>'),array('&lt;','&gt;'),$dbm['usuario']); ?></a><br /><span class="sub2">Nível <?php echo $dbm['niveluser']; ?></span></td>
            <td width="27%"><?php echo $dbm['rank']; ?></td>
            <td width="33%"><?php
			switch($dbm['posicao']){
				case 1: echo 'Administrador'; break;
				case 2: echo 'Moderador'; break;
				case 3: echo 'Membro'; break;
			} ?></td>
        </tr>
        <?php $i++; } while($dbm=mysql_fetch_assoc($sqlm)); ?>
    </table>
    <?php if(($db['orgid']==0)&&(mysql_num_rows($sqlm)<(5+($dbo['nivel']*5)))){ ?>
    <div class="sep"></div>
    <div align="center"><input type="button" class="botao" onclick="if(confirm('Deseja requisitar ingresso neste clã?')==true) location.href='?p=requestorg&id=<?php echo $c->encode($_GET['id'].','.$dbo['minimo'],$chaveuniversal); ?>';" value="Requisitar Ingresso" /></div>
    <?php } ?>
</div>
<div class="box_bottom"></div>
<?php
@mysql_free_result($sqlo);
@mysql_free_result($sqlm);
?>