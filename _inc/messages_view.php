<?php
require_once('verificar.php');

// Verificar se foi passado um ID válido
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>self.location='?p=messages'</script>";
    exit;
}

$message_id = (int)$_GET['id'];

try {
    // Buscar a mensagem (só pode ver se for remetente ou destinatário)
    $stmt = $conexao->prepare("SELECT m.*, u.usuario as remetente FROM mensagens m LEFT OUTER JOIN usuarios u ON m.origem=u.id WHERE m.id=? AND (m.destino=? OR m.origem=?)");
    $stmt->execute([$message_id, $db['id'], $db['id']]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$message) {
        echo "<div class='aviso'>Mensagem não encontrada.</div>";
        echo "<script>setTimeout(function(){ self.location='?p=messages'; }, 2000);</script>";
        exit;
    }
    
    // Marcar como lida se for o destinatário
    if($message['destino'] == $db['id'] && $message['status'] == 'naolido') {
        $update_stmt = $conexao->prepare("UPDATE mensagens SET status='lido' WHERE id=?");
        $update_stmt->execute([$message_id]);
    }
    
} catch (PDOException $e) {
    echo "<div class='aviso'>Erro ao carregar mensagem.</div>";
    echo "<script>setTimeout(function(){ self.location='?p=messages'; }, 2000);</script>";
    exit;
}

$data = explode(' ', $message['data']);
?>

<div class="box_top">Visualizar Mensagem</div>
<div class="box_middle">
    <div style="margin-bottom: 10px;">
        <a href="?p=messages">&laquo; Voltar às mensagens</a>
    </div>
    
    <fieldset>
        <legend>Detalhes da Mensagem</legend>
        <table width="100%" cellpadding="5" cellspacing="0">
            <tr>
                <td width="100"><strong>De:</strong></td>
                <td><?php if($message['origem'] == 0): ?>
                    <span style="display:inline-flex;align-items:center;gap:6px;">
                        <img src="_img/kage/adm.jpg" alt="" style="height:22px;vertical-align:middle;border:1px solid #444;">
                        <b style="color:#cc0000;text-shadow:-1px -1px 0 #000,1px -1px 0 #000,-1px 1px 0 #000,1px 1px 0 #000;letter-spacing:1px;">ADMINISTRAÇÃO</b>
                    </span>
                <?php else: echo htmlspecialchars($message['remetente']); endif; ?></td>
            </tr>
            <tr>
                <td><strong>Assunto:</strong></td>
                <td><?php if($message['origem'] == 0): ?>
                    <b style="color:#cc0000;text-shadow:-1px -1px 0 #000,1px -1px 0 #000,-1px 1px 0 #000,1px 1px 0 #000;font-size:14px;"><?php echo htmlspecialchars($message['assunto']); ?></b>
                <?php else: echo htmlspecialchars($message['assunto']); endif; ?></td>
            </tr>
            <tr>
                <td><strong>Data:</strong></td>
                <td><?php 
                    if($data[0] == date('Y-m-d')) echo '<b>Hoje</b>';
                    else echo date('d/m/Y', strtotime($data[0]));
                    echo ' às ' . date('H:i:s', strtotime($data[1]));
                ?></td>
            </tr>
        </table>
    </fieldset>
    
    <div class="sep"></div>
    
    <fieldset>
        <legend>Mensagem</legend>
        <div style="padding: 10px; background: #f5f5f5; border: 1px solid #ddd; min-height: 100px;">
            <?php 
            // Processar a mensagem para renderizar HTML corretamente (emoticons do TinyMCE)
            $processed_msg = $message['msg'];
            
            // Se a mensagem contém apenas tags <p>, remover as tags
            if (preg_match('/^<p>(.*?)<\/p>$/s', trim($processed_msg), $matches)) {
                $processed_msg = $matches[1];
            }
            
            // Renderizar a mensagem (permite HTML para emoticons)
            echo nl2br($processed_msg);
            ?>
        </div>
    </fieldset>
    
    <div class="sep"></div>
    
    <div align="center">
        <?php if($message['origem'] != $db['id'] && $message['origem'] != 0) { ?>
        <a href="?p=messages&type=form&reply_to=<?php echo urlencode($message['remetente']); ?>&reply_subject=Re: <?php echo urlencode($message['assunto']); ?>" class="botao">Responder</a>
        <?php } ?>
        <a href="?p=messages&type=delete&id=<?php echo $message['id']; ?>" onclick="return confirm('Tem certeza que deseja excluir esta mensagem?')" class="botao">Excluir</a>
        <a href="?p=messages" class="botao">Voltar</a>
    </div>
</div>
<div class="box_bottom"></div>
