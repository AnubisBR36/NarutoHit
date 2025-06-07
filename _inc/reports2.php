Abaixo estão listados os relatórios dos combates iniciados por outro ninja.<div class="sep"></div>
<?php
if(!isset($_GET['pg'])) $pg=0; else $pg=$_GET['pg'];
$min=($pg*10);
$sqlc=mysql_query("SELECT count(id) conta FROM relatorios WHERE inimigoid=".$db['id']);
if($sqlc && mysql_num_rows($sqlc) > 0) {
    $dbc=mysql_fetch_assoc($sqlc);
    $qt=$dbc['conta'];
} else {
    $qt = 0;
}
$sqlr=mysql_query("SELECT r.*, u.usuario usuario, u2.usuario inimigo FROM relatorios r LEFT OUTER JOIN usuarios u ON r.usuarioid=u.id LEFT OUTER JOIN usuarios u2 ON r.inimigoid=u2.id WHERE r.inimigoid=".$db['id']." ORDER BY status DESC, id DESC LIMIT $min,10");
if($sqlr) {
    $dbr=mysql_fetch_assoc($sqlr);
} else {
    $dbr = false;
}
?>
<table width="100%" cellpadding="0" cellspacing="1">
  <?php $i=1+($pg*1); if(!$sqlr || mysql_num_rows($sqlr)==0){ ?>
  <tr style="background:#323232">
    <td colspan="6"><div class="aviso">Nenhum relatório encontrado.</div></td>
  </tr>
  <?php } else do{ $data=explode(' ',$dbr['data']); ?>
  <tr style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
  	<td align="center" width="30"><img id="msg<?php echo $i; ?>" src="_img/<?php if($dbr['status']=='nao') echo 'letter'; else echo 'unread'; ?>.png" /></td>
    <td align="center" width="70"><?php if($data[0]==date('Y-m-d')) echo '<b>Hoje</b>'; else echo date('d/m/Y',strtotime($data[0])); ?><br /><?php echo date('H:i:s',strtotime($data[1])); ?></td>
    <td align="center"><a href="?p=view&amp;view=<?php echo strtolower($dbr['usuario']); ?>"><?php echo $dbr['usuario']; ?></a> x <?php echo $dbr['inimigo']; ?></td>
    <td align="center"><?php if($dbr['vencedor']==$dbr['usuarioid']) echo $dbr['usuario']; else echo $dbr['inimigo']; ?><br /><span class="sub2"><?php echo number_format($dbr['yens'],2,',','.'); ?> yens</span></td>
    <td align="center" width="70"><a href="?p=report&amp;id=<?php echo $dbr['id']; ?>">Ver</a></td>
  </tr>
  <?php $i++; } while($dbr=mysql_fetch_assoc($sqlr)); ?>
  <?php if(isset($dbc) && $dbc && $dbc['conta']>10){ ?>
  <tr style="background:#323232;">
    <td colspan="6" align="center"><?php if($pg==0) echo 'Anterior'; else { ?><a href="?p=reports&amp;type=d&amp;pg=<?php echo $pg-1; ?>">Anterior</a><?php } ?> | <?php if((($pg+1)*10)>=$qt) echo 'Próximo'; else { ?><a href="?p=reports&amp;type=d&amp;pg=<?php echo $pg+1; ?>">Próximo</a><?php } ?></td>
  </tr>
  <?php } ?>
</table>