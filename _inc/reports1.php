Abaixo estão listados os relatórios dos combates iniciados por você.<div class="sep"></div>
<?php
if(!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
if(!isset($_GET['pg'])) $pg=0; else $pg=$_GET['pg'];
$min=($pg*10);
try {
    $sqlc = $conexao->prepare("SELECT count(id) as conta FROM relatorios WHERE usuarioid=?");
    $sqlc->execute([$db['id']]);
    $dbc = $sqlc->fetch(PDO::FETCH_ASSOC);
    $qt = $dbc ? $dbc['conta'] : 0;
} catch (PDOException $e) {
    $qt = 0;
}
try {
    $sqlr = $conexao->prepare("SELECT r.*, u.usuario usuario, u2.usuario inimigo FROM relatorios r LEFT OUTER JOIN usuarios u ON r.usuarioid=u.id LEFT OUTER JOIN usuarios u2 ON r.inimigoid=u2.id WHERE r.usuarioid=? ORDER BY id DESC LIMIT ?,10");
    $sqlr->bindValue(1, $db['id'], PDO::PARAM_INT);
    $sqlr->bindValue(2, $min, PDO::PARAM_INT);
    $sqlr->execute();
    $dbr = $sqlr->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dbr = false;
}
?>
<table width="100%" cellpadding="0" cellspacing="1">
  <?php $i=1+($pg*1); if(!$dbr) { ?>
  <tr style="background:#323232">
    <td colspan="6"><div class="aviso">Nenhum relatório encontrado.</div></td>
  </tr>
  <?php } else do{ $data=explode(' ',$dbr['data']); ?>
  <tr style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
    <td align="center" width="70"><?php if($data[0]==date('Y-m-d')) echo '<b>Hoje</b>'; else echo date('d/m/Y',strtotime($data[0])); ?><br /><?php echo date('H:i:s',strtotime($data[1])); ?></td>
    <td align="center"><?php echo $dbr['usuario']; ?> x <a href="?p=view&amp;view=<?php echo strtolower($dbr['inimigo']); ?>"><?php echo $dbr['inimigo']; ?></a></td>
    <td align="center"><?php if($dbr['vencedor']==$dbr['usuarioid']) echo $dbr['usuario']; else echo $dbr['inimigo']; ?><br /><span class="sub2"><?php echo number_format($dbr['yens'],2,',','.'); ?> yens</span></td>
    <td align="center" width="120">
      <a href="?p=report&amp;id=<?php echo $dbr['id']; ?>">Ver</a> | 
      <form method="POST" action="?p=delete_report" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja apagar este relatório?');">
        <input type="hidden" name="id" value="<?php echo $dbr['id']; ?>" />
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
        <button type="submit" style="background:none; border:none; color:#FF9900; cursor:pointer; text-decoration:underline; padding:0; font-size:inherit;">Apagar</button>
      </form>
    </td>
  </tr>
  <?php $i++; } while($dbr=$sqlr->fetch(PDO::FETCH_ASSOC)); ?>
  <?php if(isset($dbc) && $dbc && $dbc['conta']>10){ ?>
  <tr style="background:#323232;">
    <td colspan="6" align="center"><?php if($pg==0) echo 'Anterior'; else { ?><a href="?p=reports&amp;type=a&amp;pg=<?php echo $pg-1; ?>">Anterior</a><?php } ?> | <?php if((($pg+1)*10)>=$qt) echo 'Próximo'; else { ?><a href="?p=reports&amp;type=a&amp;pg=<?php echo $pg+1; ?>">Próximo</a><?php } ?></td>
  </tr>
  <?php } ?>
</table>