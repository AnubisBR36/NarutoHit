<?php
if(!isset($_GET['pg'])) $pg=0; else $pg=(int)$_GET['pg'];
$min=($pg*10);

try {
    $stmt_count = $conexao->prepare("SELECT count(id) as conta FROM mensagens WHERE origem = ?");
    $stmt_count->execute([$db['id']]);
    $count_result = $stmt_count->fetch(PDO::FETCH_ASSOC);
    $qt = $count_result['conta'];
    
    $stmt_messages = $conexao->prepare("SELECT m.*,u.usuario FROM mensagens m LEFT OUTER JOIN usuarios u ON m.destino=u.id WHERE origem=? ORDER BY m.data DESC LIMIT $min,10");
    $stmt_messages->execute([$db['id']]);
    $messages = $stmt_messages->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar mensagens enviadas: " . $e->getMessage());
    $qt = 0;
    $messages = [];
}
?>
<table width="100%" cellpadding="0" cellspacing="1">
  <?php $i=1+($pg*1); if(count($messages)==0){ ?>
  <tr style="background:#323232">
    <td colspan="6"><div class="aviso">Nenhuma mensagem encontrada.</div></td>
  </tr>
  <?php } else { foreach($messages as $dbmm) { $data=explode(' ',$dbmm['data']); ?>
  <tr style="background:#323232;" onmouseover="style.background='#2C2C2C'" onmouseout="style.background='#323232'">
    <td align="center" width="70"><?php if($data[0]==date('Y-m-d')) echo '<b>Hoje</b>'; else echo date('d/m/Y',strtotime($data[0])); ?><br /><?php echo date('H:i:s',strtotime($data[1])); ?></td>
    <td style="padding-left:5px;">Para: <?php if($dbmm['destino']==0) echo '<b>Sistema</b>'; else { ?><a href="?p=view&amp;view=<?php echo strtolower($dbmm['usuario']); ?>"><?php echo $dbmm['usuario']; ?></a><?php } ?><br /><span class="sub2"><b>Assunto:</b> <?php echo htmlspecialchars($dbmm['assunto']); ?></span></td>
    <td align="center" width="100"><script type="text/javascript">$('a#msglink').modal();</script><a href="search_msg.php?id=<?php echo $dbmm['id']; ?>&amp;key=<?php echo $c->encode($db['id'],$chaveuniversal); ?>" class="modal" rel="modal">Ver</a></td>
  </tr>
  <?php $i++; } } ?>
  <?php if($qt>10){ ?>
  <tr style="background:#323232;">
    <td colspan="6" align="center"><?php if($pg==0) echo 'Anterior'; else { ?><a href="?p=messages&amp;type=e&amp;pg=<?php echo $pg-1; ?>">Anterior</a><?php } ?> | <?php if((($pg+1)*10)>=$qt) echo 'Próximo'; else { ?><a href="?p=messages&amp;type=e&amp;pg=<?php echo $pg+1; ?>">Próximo</a><?php } ?></td>
  </tr>
  <?php } ?>
</table>
