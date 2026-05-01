<?php
if(!isset($_GET['pg'])) $pg=0; else $pg=(int)$_GET['pg'];
$min=($pg*10);

try {
    $stmt_count = $conexao->prepare("SELECT count(id) as conta FROM mensagens WHERE destino = ?");
    $stmt_count->execute([$db['id']]);
    $count_result = $stmt_count->fetch(PDO::FETCH_ASSOC);
    $qt = $count_result['conta'];
    
    $stmt_messages = $conexao->prepare("SELECT m.*,u.usuario,u.adm FROM mensagens m LEFT OUTER JOIN usuarios u ON m.origem=u.id WHERE destino=? ORDER BY m.status ASC, m.data DESC LIMIT $min,10");
    $stmt_messages->execute([$db['id']]);
    $messages = $stmt_messages->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar mensagens recebidas: " . $e->getMessage());
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
        <td align="center" width="30"><img id="msg<?php echo $i; ?>" src="_img/<?php if($dbmm['status']=='naolido') echo 'letter'; else echo 'unread'; ?>.png" /></td>
    <td align="center" width="70"><?php if($data[0]==date('Y-m-d')) echo '<b>Hoje</b>'; else echo date('d/m/Y',strtotime($data[0])); ?><br /><?php echo date('H:i:s',strtotime($data[1])); ?></td>
    <td style="padding-left:5px;">
        <?php if(isset($dbmm['adm']) && ($dbmm['adm']==1 || $dbmm['adm']==2)) { ?>
        <div style="display:inline-block;background:#3d2e1f;border:2px solid #ff6600;border-radius:4px;padding:2px 6px;margin-bottom:3px;">
            <img src="_img/kage/adm.jpg" style="width:20px;height:20px;vertical-align:middle;margin-right:4px;border-radius:2px;" />
            <span style="color:#ffaa00;font-weight:bold;font-size:10px;vertical-align:middle;">ADMINISTRAÇÃO</span>
        </div>
        <br />
        <?php } ?>
        <?php if($dbmm['origem']==0): ?>
            <span style="display:inline-flex;align-items:center;gap:5px;vertical-align:middle;">
                <img src="_img/kage/adm.jpg" alt="" style="height:18px;border:1px solid #444;vertical-align:middle;">
                <b style="color:#cc0000;text-shadow:-1px -1px 0 #000,1px -1px 0 #000,-1px 1px 0 #000,1px 1px 0 #000;letter-spacing:1px;">ADMINISTRAÇÃO</b>
            </span>
        <?php else: ?><a href="?p=view&amp;view=<?php echo strtolower($dbmm['usuario']); ?>"><?php echo $dbmm['usuario']; ?></a><?php endif; ?>
        <br /><span class="sub2"><b>Assunto:</b> <?php if($dbmm['origem']==0): ?><b style="color:#cc0000;text-shadow:-1px -1px 0 #000,1px -1px 0 #000,-1px 1px 0 #000,1px 1px 0 #000;"><?php echo htmlspecialchars($dbmm['assunto']); ?></b><?php else: echo htmlspecialchars($dbmm['assunto']); endif; ?></span>
    </td>
    <td align="center" width="100"><script type="text/javascript">$('a#msglink').modal();</script><a href="search_msg.php?id=<?php echo $dbmm['id']; ?>&amp;key=<?php echo $c->encode($db['id'],$chaveuniversal); ?>" class="modal" rel="modal" onclick="document.getElementById('msg<?php echo $i; ?>').setAttribute('src','_img/unread.png');">Ver</a> | <a href="?p=messages&amp;type=r&amp;pg=<?php echo $pg; ?>&amp;del=<?php echo $dbmm['id']; ?>">Apagar</a></td>
  </tr>
  <?php $i++; } } ?>
  <?php if($qt>10){ ?>
  <tr style="background:#323232;">
    <td colspan="6" align="center"><?php if($pg==0) echo 'Anterior'; else { ?><a href="?p=messages&amp;type=r&amp;pg=<?php echo $pg-1; ?>">Anterior</a><?php } ?> | <?php if((($pg+1)*10)>=$qt) echo 'Próximo'; else { ?><a href="?p=messages&amp;type=r&amp;pg=<?php echo $pg+1; ?>">Próximo</a><?php } ?></td>
  </tr>
  <?php } ?>
</table>
